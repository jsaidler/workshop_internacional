<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $now=gmdate('c');
    $hasTable=static fn(string $name): bool => (bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    $columns=static function(string $table) use($db): array {
        $out=[];
        foreach($db->query('PRAGMA table_info('.$table.')')->fetchAll(PDO::FETCH_ASSOC) as $column)$out[(string)$column['name']]=true;
        return $out;
    };

    if($hasTable('student_users')){
        $c=$columns('student_users');
        if(!isset($c['activated_at']))$db->exec('ALTER TABLE student_users ADD COLUMN activated_at TEXT NULL');
        if(!isset($c['privacy_ack_at']))$db->exec('ALTER TABLE student_users ADD COLUMN privacy_ack_at TEXT NULL');
        if(!isset($c['privacy_notice_version']))$db->exec("ALTER TABLE student_users ADD COLUMN privacy_notice_version TEXT NOT NULL DEFAULT ''");
    }
    if($hasTable('cms_pages')){
        $c=$columns('cms_pages');
        if(!isset($c['access_level']))$db->exec("ALTER TABLE cms_pages ADD COLUMN access_level TEXT NOT NULL DEFAULT 'public'");
    }
    if($hasTable('cms_form_submissions')){
        $c=$columns('cms_form_submissions');
        if(!isset($c['student_id']))$db->exec('ALTER TABLE cms_form_submissions ADD COLUMN student_id INTEGER NULL');
        if(!isset($c['cohort_id']))$db->exec('ALTER TABLE cms_form_submissions ADD COLUMN cohort_id INTEGER NULL');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_cms_submissions_student ON cms_form_submissions(student_id,created_at DESC)');
        $db->exec('CREATE INDEX IF NOT EXISTS idx_cms_submissions_cohort ON cms_form_submissions(cohort_id,created_at DESC)');
    }

    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_profiles (
    student_id INTEGER PRIMARY KEY,
    cpf_lookup_hash TEXT NULL UNIQUE,
    cpf_ciphertext TEXT NULL,
    cpf_last4 TEXT NOT NULL DEFAULT '',
    phone TEXT NOT NULL DEFAULT '',
    instagram TEXT NOT NULL DEFAULT '',
    address TEXT NOT NULL DEFAULT '',
    city_state TEXT NOT NULL DEFAULT '',
    postal_code TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS course_cohorts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cohort_uuid TEXT NOT NULL UNIQUE,
    activity_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    is_registration_default INTEGER NOT NULL DEFAULT 0,
    starts_at TEXT NULL,
    ends_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_course_cohorts_activity_slug ON course_cohorts(activity_id,slug);
CREATE UNIQUE INDEX IF NOT EXISTS idx_course_cohorts_default ON course_cohorts(activity_id) WHERE is_registration_default=1 AND status!='archived';
CREATE INDEX IF NOT EXISTS idx_course_cohorts_activity_status ON course_cohorts(activity_id,status,id DESC);

CREATE TABLE IF NOT EXISTS course_lessons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_id INTEGER NOT NULL,
    lesson_key TEXT NOT NULL,
    title TEXT NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_course_lessons_activity_key ON course_lessons(activity_id,lesson_key);
CREATE INDEX IF NOT EXISTS idx_course_lessons_activity_order ON course_lessons(activity_id,sort_order,id);

CREATE TABLE IF NOT EXISTS course_enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    enrollment_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    cohort_id INTEGER NOT NULL,
    source_submission_id INTEGER NULL,
    status TEXT NOT NULL DEFAULT 'active',
    confirmed_at TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(cohort_id) REFERENCES course_cohorts(id) ON DELETE CASCADE,
    FOREIGN KEY(source_submission_id) REFERENCES cms_form_submissions(id) ON DELETE SET NULL,
    UNIQUE(student_id,cohort_id)
);
CREATE INDEX IF NOT EXISTS idx_course_enrollments_student ON course_enrollments(student_id,status,confirmed_at DESC);
CREATE INDEX IF NOT EXISTS idx_course_enrollments_cohort ON course_enrollments(cohort_id,status,student_id);

CREATE TABLE IF NOT EXISTS cohort_lesson_releases (
    cohort_id INTEGER NOT NULL,
    lesson_id INTEGER NOT NULL,
    released_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(cohort_id,lesson_id),
    FOREIGN KEY(cohort_id) REFERENCES course_cohorts(id) ON DELETE CASCADE,
    FOREIGN KEY(lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS course_page_sections (
    page_id INTEGER NOT NULL,
    section_key TEXT NOT NULL,
    lesson_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(page_id,section_key),
    FOREIGN KEY(page_id) REFERENCES cms_pages(id) ON DELETE CASCADE,
    FOREIGN KEY(lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_course_page_sections_lesson ON course_page_sections(lesson_id,page_id);

CREATE TABLE IF NOT EXISTS student_private_media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    asset_uuid TEXT NOT NULL UNIQUE,
    activity_id INTEGER NOT NULL,
    page_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    original_name TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    byte_size INTEGER NOT NULL,
    storage_path TEXT NOT NULL UNIQUE,
    checksum TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    FOREIGN KEY(page_id) REFERENCES cms_pages(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_private_media_page ON student_private_media(page_id,id DESC);
SQL);

    if($hasTable('activities')){
        $activities=$db->query("SELECT id FROM activities WHERE status!='archived' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        foreach($activities as $activityIdRaw){
            $activityId=(int)$activityIdRaw;
            $q=$db->prepare("SELECT id FROM course_cohorts WHERE activity_id=? AND status!='archived' ORDER BY is_registration_default DESC,id LIMIT 1");
            $q->execute([$activityId]);$cohortId=(int)($q->fetchColumn()?:0);
            if($cohortId===0){
                $db->prepare("INSERT INTO course_cohorts(cohort_uuid,activity_id,title,slug,status,is_registration_default,created_at,updated_at) VALUES(?,?,?,?, 'active',1,?,?)")
                    ->execute([bin2hex(random_bytes(16)),$activityId,'Turma atual','turma-atual',$now,$now]);
                $cohortId=(int)$db->lastInsertId();
            }else{
                $db->prepare('UPDATE course_cohorts SET is_registration_default=CASE WHEN id=? THEN 1 ELSE 0 END WHERE activity_id=?')->execute([$cohortId,$activityId]);
            }

            $lessonCount=$db->prepare('SELECT COUNT(*) FROM course_lessons WHERE activity_id=?');$lessonCount->execute([$activityId]);
            if((int)$lessonCount->fetchColumn()===0){
                $insertLesson=$db->prepare('INSERT INTO course_lessons(activity_id,lesson_key,title,sort_order,created_at,updated_at) VALUES(?,?,?,?,?,?)');
                foreach([[1,'aula-1','Aula 1'],[2,'aula-2','Aula 2'],[3,'aula-3','Aula 3']] as [$order,$key,$title])$insertLesson->execute([$activityId,$key,$title,$order,$now,$now]);
            }
            $lessonIds=$db->prepare('SELECT id FROM course_lessons WHERE activity_id=?');$lessonIds->execute([$activityId]);
            $release=$db->prepare('INSERT OR IGNORE INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at) VALUES(?,?,NULL,?,?)');
            foreach($lessonIds->fetchAll(PDO::FETCH_COLUMN) as $lessonId)$release->execute([$cohortId,(int)$lessonId,$now,$now]);

            if($hasTable('student_enrollments')){
                $legacy=$db->prepare('SELECT student_id,status,created_at,updated_at FROM student_enrollments WHERE activity_id=?');$legacy->execute([$activityId]);
                $insertEnrollment=$db->prepare("INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,source_submission_id,status,confirmed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?) ON CONFLICT(student_id,cohort_id) DO NOTHING");
                foreach($legacy->fetchAll(PDO::FETCH_ASSOC) as $row)$insertEnrollment->execute([bin2hex(random_bytes(16)),(int)$row['student_id'],$cohortId,null,$row['status']==='active'?'active':'disabled',(string)$row['created_at'],(string)$row['created_at'],(string)$row['updated_at']]);
            }
        }
    }

    if($hasTable('cms_pages')&&function_exists('cms_page_document')){
        $activityIds=$db->query("SELECT id FROM activities WHERE status!='archived' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        foreach($activityIds as $activityIdRaw){
            $activityId=(int)$activityIdRaw;
            $q=$db->prepare("SELECT id FROM cms_pages WHERE activity_id=? AND locale='pt-BR' AND slug='privacidade' AND status!='archived' LIMIT 1");$q->execute([$activityId]);
            if($q->fetchColumn())continue;
            $privacyHtml=<<<'HTML'
<section class="section" data-cms-section="privacy" data-cms-section-name="Privacidade">
  <p class="section-label">PRIVACIDADE E DADOS PESSOAIS</p>
  <div class="statement-grid"><h1>Aviso de Privacidade</h1><div class="statement-copy">
    <p>Os dados informados no site são usados para administrar inscrições, pagamentos, participação nos cursos, acesso aos materiais e, quando necessário, envio de itens relacionados ao curso.</p>
    <p>Quando uma matrícula é confirmada, os dados de identificação e contato passam a compor a conta do aluno. Essa conta permite acessar conteúdos protegidos e reaproveitar dados em inscrições futuras, sempre com possibilidade de revisão e correção pelo próprio aluno.</p>
    <p>Podem ser tratados nome, CPF, e-mail, telefone, endereço, dados fornecidos nos formulários, situação da matrícula e informações técnicas de segurança. Senhas não são armazenadas em texto legível. O CPF não é usado como senha permanente: no primeiro acesso ele serve apenas, junto com o e-mail, para confirmar a identidade e criar uma senha própria.</p>
    <p>Os dados necessários à inscrição e à realização do curso são tratados para viabilizar a relação contratual e as obrigações associadas. Comunicações promocionais que não sejam necessárias ao curso devem ser tratadas separadamente.</p>
    <p>O aluno pode consultar e corrigir seus dados pela Área do aluno. Solicitações sobre acesso, correção, eliminação quando aplicável ou outras questões de privacidade podem ser feitas pelo mesmo canal de contato utilizado nas comunicações oficiais do workshop.</p>
    <p>Os dados são mantidos somente enquanto necessários às finalidades informadas e às obrigações aplicáveis. Os prazos específicos de retenção administrativa e fiscal devem observar as obrigações efetivamente incidentes sobre cada registro.</p>
  </div></div>
</section>
HTML;
            $doc=cms_page_document(['theme'=>'auto','meta'=>['title'=>'Aviso de Privacidade','description'=>'Como os dados pessoais são utilizados nas inscrições, na área do aluno e nos cursos.'],'html'=>$privacyHtml]);
            $json=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $sortQ=$db->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM cms_pages WHERE activity_id=? AND locale=?');$sortQ->execute([$activityId,'pt-BR']);$sort=(int)$sortQ->fetchColumn();
            $db->prepare('INSERT INTO cms_pages(page_uuid,activity_id,locale,slug,title,nav_title,status,is_home,show_in_nav,sort_order,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at,access_level) VALUES(?,?,?,?,?,?,"active",0,0,?,?,?,?,?,?,?,?,?,"public")')
                ->execute([bin2hex(random_bytes(16)),$activityId,'pt-BR','privacidade','Aviso de Privacidade','Privacidade',$sort,$json,$json,1,1,$now,$now,$now,$now]);
        }
    }

    if($hasTable('cms_forms')){
        $forms=$db->query("SELECT id,draft_schema_json,published_schema_json FROM cms_forms WHERE form_key='registration' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
        $old='Os dados fornecidos neste formulário serão utilizados para organizar a inscrição, o pagamento, a participação no workshop e o envio do suporte.';
        $new='Os dados fornecidos neste formulário serão utilizados para organizar a inscrição, o pagamento, a participação no workshop, o envio do suporte e, após a confirmação da matrícula, a criação da conta do aluno para acesso aos materiais. Dados de identificação e contato poderão ser reaproveitados em inscrições futuras, com possibilidade de revisão e correção pelo próprio aluno. Consulte o Aviso de Privacidade do site.';
        foreach($forms as $form){
            $sets=[];$args=[];
            foreach(['draft_schema_json','published_schema_json'] as $column){
                $json=(string)($form[$column]??'');if($json==='')continue;$schema=json_decode($json,true);if(!is_array($schema))continue;$changed=false;
                foreach(($schema['settings']['contentBlocks']??[]) as &$block){if(!is_array($block)||($block['id']??'')!=='terms_copy')continue;$html=(string)($block['html']??'');$updated=str_replace($old,$new,$html,$count);if($count>0){$block['html']=$updated;$changed=true;}}unset($block);
                if($changed){$sets[]=$column.'=?';$args[]=json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
            }
            if($sets){$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$form['id'];$db->prepare('UPDATE cms_forms SET '.implode(',',$sets).' WHERE id=?')->execute($args);}
        }
    }
};
