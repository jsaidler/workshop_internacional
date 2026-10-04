<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    $columns=static function(string $table) use($db): array {
        $out=[];if(!(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($table))->fetchColumn())return $out;
        foreach($db->query('PRAGMA table_info('.$table.')')->fetchAll(PDO::FETCH_ASSOC) as $row)$out[(string)$row['name']]=true;
        return $out;
    };
    if(!$tableExists('cms_pages')||!$tableExists('cms_forms')||!$tableExists('course_cohorts')||!$tableExists('course_lessons'))return;

    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_uuid TEXT NOT NULL UNIQUE,
    activity_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    public_page_id INTEGER NULL,
    registration_form_id INTEGER NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    FOREIGN KEY(public_page_id) REFERENCES cms_pages(id) ON DELETE SET NULL,
    FOREIGN KEY(registration_form_id) REFERENCES cms_forms(id) ON DELETE SET NULL
);
CREATE UNIQUE INDEX IF NOT EXISTS idx_courses_activity_slug ON courses(activity_id,slug);
CREATE UNIQUE INDEX IF NOT EXISTS idx_courses_public_page ON courses(public_page_id) WHERE public_page_id IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_courses_registration_form ON courses(registration_form_id,status,id);

CREATE TABLE IF NOT EXISTS course_material_pages (
    course_id INTEGER NOT NULL,
    page_id INTEGER NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(course_id,page_id),
    FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY(page_id) REFERENCES cms_pages(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_course_material_pages_page ON course_material_pages(page_id,course_id);
CREATE INDEX IF NOT EXISTS idx_course_material_pages_order ON course_material_pages(course_id,sort_order,page_id);

CREATE TABLE IF NOT EXISTS course_material_sections (
    course_id INTEGER NOT NULL,
    page_id INTEGER NOT NULL,
    section_key TEXT NOT NULL,
    lesson_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(course_id,page_id,section_key),
    FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY(page_id) REFERENCES cms_pages(id) ON DELETE CASCADE,
    FOREIGN KEY(lesson_id) REFERENCES course_lessons(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_course_material_sections_lesson ON course_material_sections(course_id,lesson_id,page_id);
SQL);

    $cohortCols=$columns('course_cohorts');
    if(!isset($cohortCols['course_id']))$db->exec('ALTER TABLE course_cohorts ADD COLUMN course_id INTEGER NULL REFERENCES courses(id) ON DELETE SET NULL');
    $lessonCols=$columns('course_lessons');
    if(!isset($lessonCols['course_id']))$db->exec('ALTER TABLE course_lessons ADD COLUMN course_id INTEGER NULL REFERENCES courses(id) ON DELETE SET NULL');
    if($tableExists('cms_form_submissions')){
        $submissionCols=$columns('cms_form_submissions');
        if(!isset($submissionCols['course_id']))$db->exec('ALTER TABLE cms_form_submissions ADD COLUMN course_id INTEGER NULL REFERENCES courses(id) ON DELETE SET NULL');
    }

    $db->exec('CREATE INDEX IF NOT EXISTS idx_course_cohorts_course_status ON course_cohorts(course_id,status,id DESC)');
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_course_cohorts_course_slug ON course_cohorts(course_id,slug) WHERE course_id IS NOT NULL');
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_course_cohorts_course_default ON course_cohorts(course_id) WHERE course_id IS NOT NULL AND is_registration_default=1 AND status!='archived'");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_course_lessons_course_order ON course_lessons(course_id,sort_order,id)');
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_course_lessons_course_key ON course_lessons(course_id,lesson_key) WHERE course_id IS NOT NULL');
    if($tableExists('cms_form_submissions'))$db->exec('CREATE INDEX IF NOT EXISTS idx_cms_submissions_course_created ON cms_form_submissions(course_id,created_at DESC)');

    $pageById=$db->prepare('SELECT * FROM cms_pages WHERE id=? LIMIT 1');
    $rootOf=static function(int $pageId) use($db,$pageById): int {
        $seen=[];$current=$pageId;$root=0;$activityId=0;$locale='';
        while($current>0){
            if(isset($seen[$current]))return 0;$seen[$current]=true;$pageById->execute([$current]);$row=$pageById->fetch(PDO::FETCH_ASSOC);if(!$row||($row['status']??'')==='archived')return 0;
            if($root===0){$activityId=(int)$row['activity_id'];$locale=(string)$row['locale'];}
            elseif((int)$row['activity_id']!==$activityId||(string)$row['locale']!==$locale)return 0;
            $root=(int)$row['id'];$parent=(int)($row['parent_page_id']??0);if($parent<1)break;$current=$parent;
        }
        return $root;
    };
    $slugUnique=static function(int $activityId,string $slug) use($db): string {
        $base=trim($slug)!==''?$slug:'curso';$candidate=$base;$n=2;$q=$db->prepare('SELECT 1 FROM courses WHERE activity_id=? AND slug=?');
        while(true){$q->execute([$activityId,$candidate]);if(!$q->fetchColumn())return $candidate;$candidate=$base.'-'.$n++;}
    };

    $workshopIds=[];
    if(isset($columns('course_cohorts')['workshop_page_id']))foreach($db->query('SELECT DISTINCT workshop_page_id FROM course_cohorts WHERE workshop_page_id IS NOT NULL AND workshop_page_id>0')->fetchAll(PDO::FETCH_COLUMN) as $id)$workshopIds[(int)$id]=true;
    if(isset($columns('course_lessons')['workshop_page_id']))foreach($db->query('SELECT DISTINCT workshop_page_id FROM course_lessons WHERE workshop_page_id IS NOT NULL AND workshop_page_id>0')->fetchAll(PDO::FETCH_COLUMN) as $id)$workshopIds[(int)$id]=true;

    $insertCourse=$db->prepare('INSERT INTO courses(course_uuid,activity_id,title,slug,status,public_page_id,registration_form_id,created_at,updated_at) VALUES(?,?,?,?,\'active\',?,NULL,?,?)');
    $courseByPage=$db->prepare('SELECT id FROM courses WHERE public_page_id=? LIMIT 1');
    $now=gmdate('c');
    foreach(array_keys($workshopIds) as $pageId){
        $pageById->execute([$pageId]);$page=$pageById->fetch(PDO::FETCH_ASSOC);if(!$page||($page['status']??'')==='archived')continue;
        $courseByPage->execute([$pageId]);$courseId=(int)($courseByPage->fetchColumn()?:0);
        if($courseId<1){$slug=$slugUnique((int)$page['activity_id'],trim((string)$page['slug'])!==''?(string)$page['slug']:'curso-'.$pageId);$insertCourse->execute([bin2hex(random_bytes(16)),(int)$page['activity_id'],trim((string)$page['title'])?:'Curso',$slug,$pageId,$now,$now]);$courseId=(int)$db->lastInsertId();}
        $db->prepare('UPDATE course_cohorts SET course_id=? WHERE course_id IS NULL AND workshop_page_id=?')->execute([$courseId,$pageId]);
        $db->prepare('UPDATE course_lessons SET course_id=? WHERE course_id IS NULL AND workshop_page_id=?')->execute([$courseId,$pageId]);
    }

    // When a site had only one enrollment form, it is an unambiguous seed for courses in that site.
    $courseRows=$db->query("SELECT * FROM courses WHERE status!='archived' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $enrollmentForms=$db->prepare("SELECT f.id FROM cms_forms f WHERE f.activity_id=? AND f.status!='archived' AND f.purpose='enrollment' ORDER BY f.id");
    foreach($courseRows as $course){
        if((int)($course['registration_form_id']??0)>0)continue;$enrollmentForms->execute([(int)$course['activity_id']]);$formIds=array_map('intval',$enrollmentForms->fetchAll(PDO::FETCH_COLUMN));if(count($formIds)===1)$db->prepare('UPDATE courses SET registration_form_id=?,updated_at=? WHERE id=?')->execute([$formIds[0],$now,(int)$course['id']]);
    }

    if($tableExists('cms_form_submissions')){
        $rows=$db->query("SELECT s.id,s.page_id,s.activity_id,s.form_id,s.course_id FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE f.purpose='enrollment' ORDER BY s.id")->fetchAll(PDO::FETCH_ASSOC);
        $coursesByForm=$db->prepare("SELECT id,public_page_id FROM courses WHERE activity_id=? AND registration_form_id=? AND status!='archived' ORDER BY id");
        $setSubmission=$db->prepare('UPDATE cms_form_submissions SET course_id=?,updated_at=? WHERE id=? AND course_id IS NULL');
        foreach($rows as $row){if((int)($row['course_id']??0)>0)continue;$coursesByForm->execute([(int)$row['activity_id'],(int)$row['form_id']]);$candidates=$coursesByForm->fetchAll(PDO::FETCH_ASSOC);if(count($candidates)===1){$setSubmission->execute([(int)$candidates[0]['id'],$now,(int)$row['id']]);continue;}$pageId=(int)($row['page_id']??0);if($pageId<1)continue;$root=$rootOf($pageId);$matched=[];foreach($candidates as $candidate)if((int)$candidate['public_page_id']===$root)$matched[]=$candidate;if(count($matched)===1)$setSubmission->execute([(int)$matched[0]['id'],$now,(int)$row['id']]);}
    }

    // Existing page/section mappings are relations, not duplicated content. Copy them into the course-scoped relation.
    if($tableExists('course_page_sections')){
        $mapped=$db->query('SELECT s.page_id,s.section_key,s.lesson_id,l.course_id FROM course_page_sections s JOIN course_lessons l ON l.id=s.lesson_id WHERE l.course_id IS NOT NULL')->fetchAll(PDO::FETCH_ASSOC);
        $addPage=$db->prepare('INSERT OR IGNORE INTO course_material_pages(course_id,page_id,sort_order,created_at,updated_at) VALUES(?,?,0,?,?)');
        $addSection=$db->prepare('INSERT OR IGNORE INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?,?)');
        foreach($mapped as $row){$courseId=(int)$row['course_id'];$pageId=(int)$row['page_id'];if($courseId<1||$pageId<1)continue;$addPage->execute([$courseId,$pageId,$now,$now]);$addSection->execute([$courseId,$pageId,(string)$row['section_key'],(int)$row['lesson_id'],$now,$now]);}
    }

    // Protected pages inside the old workshop tree are safe material candidates and remain ordinary CMS pages.
    foreach($db->query("SELECT p.id,p.activity_id,p.access_level FROM cms_pages p WHERE p.status!='archived' AND p.access_level IN ('activity','enrolled','cohort') ORDER BY p.id")->fetchAll(PDO::FETCH_ASSOC) as $page){$root=$rootOf((int)$page['id']);if($root<1)continue;$courseByPage->execute([$root]);$courseId=(int)($courseByPage->fetchColumn()?:0);if($courseId<1)continue;$db->prepare('INSERT OR IGNORE INTO course_material_pages(course_id,page_id,sort_order,created_at,updated_at) VALUES(?,?,0,?,?)')->execute([$courseId,(int)$page['id'],$now,$now]);}

    // A course registration cannot silently become an enrollment before an administrator assigns a cohort.
    if($tableExists('course_enrollments')&&$tableExists('cms_form_submissions')){
        $db->exec('DROP TRIGGER IF EXISTS trg_course_enrollment_requires_assignment');
        $db->exec(<<<'SQL'
CREATE TRIGGER trg_course_enrollment_requires_assignment
BEFORE INSERT ON course_enrollments
WHEN NEW.source_submission_id IS NOT NULL
 AND EXISTS(
    SELECT 1 FROM cms_form_submissions s
    WHERE s.id=NEW.source_submission_id
      AND s.course_id IS NOT NULL
      AND s.cohort_id IS NULL
 )
BEGIN
    SELECT RAISE(ABORT,'course_registration_requires_explicit_cohort');
END;
SQL);
    }
};
