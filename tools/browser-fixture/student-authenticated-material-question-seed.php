<?php
declare(strict_types=1);

/**
 * CLI ONLY. Builds and inspects a disposable, fully migrated installation.
 * Executed by student-authenticated-material-question.spec.cjs; never copied
 * into its document root and never shipped to production-dist.
 */
if(PHP_SAPI!=='cli')exit(1);
$root=realpath((string)($argv[1]??''));
$mode=(string)($argv[2]??'seed');
if(!$root||!is_file($root.'/storage/installed.lock')||!is_file($root.'/config/local.php')){
    fwrite(STDERR,"An isolated installed root is required.\n");exit(1);
}
require $root.'/app/bootstrap.php';
$db=database();

if($mode==='snapshot'){
    $notes=$db->query("SELECT a.id,a.student_id,a.page_id,a.body,a.anchor_type,a.section_key,a.quote_exact,a.block_key,a.source_block_hash,a.source_page_revision,q.id question_id,q.cohort_id question_cohort_id,q.visibility FROM student_material_annotations a LEFT JOIN student_questions q ON q.source_annotation_id=a.id ORDER BY a.id")->fetchAll(PDO::FETCH_ASSOC);
    $questions=$db->query('SELECT id,student_id,cohort_id,source_annotation_id,visibility,body FROM student_questions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['notes'=>$notes,'questions'=>$questions],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)."\n";
    exit;
}
if($mode==='revoke'){
    $db->prepare("UPDATE course_enrollments SET status='disabled' WHERE student_id=(SELECT id FROM student_users WHERE email=?) AND cohort_id=(SELECT id FROM course_cohorts WHERE cohort_uuid=?)")->execute(['journey-owner@example.invalid','journey-b']);
    echo "revoked\n";exit;
}
if($mode==='revise-formatted'){
    // An actual published CMS document with a paragraph spanning inline
    // emphasis and an anchor; the normal route sanitizes and instruments it.
    $activityId=(int)$db->query("SELECT id FROM activities WHERE is_root=1 ORDER BY id LIMIT 1")->fetchColumn();
    $page=cms_page_by_slug($db,$activityId,'pt-BR','material');
    if(!$page)throw new RuntimeException('Fixture material not found.');
    $document=cms_page_doc($page,false);
    $document['html']='<article class="study-material"><section data-cms-section="investigacao">'
        .'<h1>Material para regressão autenticada</h1>'
        .'<p>Antes <strong>exposição <em>e</em></strong> <a href="#destino-link">revelação e evidência</a> depois final. '
        .'<a href="#destino-normal">Consultar fonte</a>.</p>'
        .'<p id="destino-link">Destino da referência editorial.</p>'
        .'<p id="destino-normal">Destino da fonte sem destaque.</p>'
        .'</section></article>';
    $page=cms_page_save($db,(int)$page['id'],$document,(int)$page['draft_revision'],[
        'title'=>(string)$page['title'],'slug'=>(string)$page['slug'],'show_in_nav'=>true
    ]);
    $page=cms_page_publish($db,(int)$page['id']);
    echo json_encode(['revision'=>$page['published_revision'],'mode'=>$mode],JSON_THROW_ON_ERROR)."\n";
    exit;
}
if($mode==='revise'){
    // Use the same CMS draft/publish services as the application. The stable
    // page and section survive, but the old source excerpt no longer exists.
    $activityId=(int)$db->query("SELECT id FROM activities WHERE is_root=1 ORDER BY id LIMIT 1")->fetchColumn();
    $page=cms_page_by_slug($db,$activityId,'pt-BR','material');
    if(!$page)throw new RuntimeException('Fixture material not found.');
    $document=cms_page_doc($page,false);
    $document['html']='<article class="study-material"><section data-cms-section="investigacao"><h1>Material para regressão autenticada</h1><p>O estudo da exposição foi revisto no CMS. A revelação continua decisiva no positivo.</p></section></article>';
    $page=cms_page_save($db,(int)$page['id'],$document,(int)$page['draft_revision'],['title'=>(string)$page['title'],'slug'=>(string)$page['slug'],'show_in_nav'=>true]);
    $page=cms_page_publish($db,(int)$page['id']);
    echo json_encode(['published_revision'=>$page['published_revision']],JSON_THROW_ON_ERROR)."\n";
    exit;
}
if(in_array($mode,['revise-duplicates','revise-ambiguous','revise-ambiguous-preferred'],true)){
    // Real CMS publish, not a DOM patch. Ordinal p:1 keeps the short quote
    // but changes its surrounding paragraph; the original full paragraph
    // reappears later. Ambiguous additionally repeats that original exactly.
    $activityId=(int)$db->query("SELECT id FROM activities WHERE is_root=1 ORDER BY id LIMIT 1")->fetchColumn();
    $page=cms_page_by_slug($db,$activityId,'pt-BR','material');
    if(!$page)throw new RuntimeException('Fixture material not found.');
    $document=cms_page_doc($page,false);
    $old='<p>Exposição e revelação são decisões independentes.</p>';
    $changed='<p>Exposição e revelação foram reorganizadas na aula.</p>';
    $html='<article class="study-material"><section data-cms-section="investigacao">'
        .'<h1>Material para regressão autenticada</h1>'
        .($mode==='revise-ambiguous-preferred'?$old:$changed).$old.($mode==='revise-ambiguous'?$old:'')
        .'</section></article>';
    $document['html']=$html;
    $page=cms_page_save($db,(int)$page['id'],$document,(int)$page['draft_revision'],[
        'title'=>(string)$page['title'],'slug'=>(string)$page['slug'],'show_in_nav'=>true
    ]);
    $page=cms_page_publish($db,(int)$page['id']);
    echo json_encode(['revision'=>$page['published_revision'],'mode'=>$mode],JSON_THROW_ON_ERROR)."\n";
    exit;
}
if($mode!=='seed'){fwrite(STDERR,"Unknown mode.\n");exit(1);}
$now=utc_now();
// A fresh full migration already seeds the unique root activity. Reuse it;
// creating another root would make index.php resolve the wrong CMS course.
$activityId=(int)$db->query("SELECT id FROM activities WHERE is_root=1 ORDER BY id LIMIT 1")->fetchColumn();
if($activityId<1)throw new RuntimeException('No root activity after migrations.');
$db->prepare("UPDATE activities SET admin_name=?,public_title=?,updated_at=? WHERE id=?")
    ->execute(['Isolated QA','Isolated QA',$now,$activityId]);

$page=cms_page_create($db,$activityId,'pt-BR','Material para regressão autenticada','material');
$document=['version'=>2,'theme'=>'auto','meta'=>['title'=>'Material para regressão autenticada'],
    'html'=>'<article class="study-material"><section data-cms-section="investigacao"><h1>Material para regressão autenticada</h1><p data-student-anchor-block>Exposição e revelação são decisões independentes.</p></section></article>'];
$page=cms_page_save($db,(int)$page['id'],$document,(int)$page['draft_revision'],['title'=>'Material para regressão autenticada','slug'=>'material','show_in_nav'=>true]);
$page=cms_page_publish($db,(int)$page['id']);
$db->prepare("UPDATE cms_pages SET access_level='activity' WHERE id=?")->execute([(int)$page['id']]);

$db->prepare("INSERT INTO courses(course_uuid,activity_id,title,slug,status,created_at,updated_at) VALUES(?,?,?,?,'active',?,?)")
    ->execute([student_uuid(),$activityId,'Curso de verificação','curso-qa',$now,$now]);
$courseId=(int)$db->lastInsertId();
$db->prepare('INSERT INTO course_material_pages(course_id,page_id,sort_order,created_at,updated_at) VALUES(?,?,1,?,?)')
    ->execute([$courseId,(int)$page['id'],$now,$now]);
$db->prepare('INSERT INTO course_lessons(activity_id,course_id,lesson_key,title,sort_order,created_at,updated_at) VALUES(?,?,?,?,1,?,?)')
    ->execute([$activityId,$courseId,'investigacao','Investigação',$now,$now]);
$lessonId=(int)$db->lastInsertId();
$db->prepare('INSERT INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?,?)')
    ->execute([$courseId,(int)$page['id'],'investigacao',$lessonId,$now,$now]);
$cohortIds=[];
foreach(['journey-a'=>'Turma A','journey-b'=>'Turma B'] as $uuid=>$title){
    $db->prepare("INSERT INTO course_cohorts(cohort_uuid,activity_id,course_id,title,slug,status,is_registration_default,created_at,updated_at) VALUES(?,?,?,?,?,'active',0,?,?)")
        ->execute([$uuid,$activityId,$courseId,$title,$uuid,$now,$now]);
    $cohortIds[$uuid]=(int)$db->lastInsertId();
    $db->prepare('INSERT INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at) VALUES(?,?,?,?,?)')
        ->execute([$cohortIds[$uuid],$lessonId,$now,$now,$now]);
}
foreach(['journey-owner@example.invalid'=>'Autora QA','journey-peer@example.invalid'=>'Colega QA'] as $email=>$name){
    $db->prepare("INSERT INTO student_users(user_uuid,name,email,password_hash,status,must_change_password,activated_at,created_at,updated_at) VALUES(?,?,?,?,'active',0,?,?,?)")
        ->execute([student_uuid(),$name,$email,password_hash('fixture-password-123!',PASSWORD_DEFAULT),$now,$now,$now]);
    $studentId=(int)$db->lastInsertId();
    $db->prepare("INSERT INTO student_enrollments(student_id,activity_id,status,created_at,updated_at) VALUES(?,?,'active',?,?)")
        ->execute([$studentId,$activityId,$now,$now]);
    foreach(($email==='journey-owner@example.invalid'?['journey-a','journey-b']:['journey-a']) as $uuid){
        $db->prepare("INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,status,confirmed_at,created_at,updated_at) VALUES(?,?,?,'active',?,?,?)")
            ->execute([student_uuid(),$studentId,$cohortIds[$uuid],$now,$now,$now]);
    }
}
echo json_encode(['pageId'=>(int)$page['id'],'courseId'=>$courseId,'cohorts'=>$cohortIds],JSON_THROW_ON_ERROR)."\n";
