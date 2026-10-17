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
    $notes=$db->query("SELECT a.id,a.student_id,a.page_id,a.body,a.anchor_type,q.id question_id,q.cohort_id question_cohort_id,q.visibility FROM student_material_annotations a LEFT JOIN student_questions q ON q.source_annotation_id=a.id ORDER BY a.id")->fetchAll(PDO::FETCH_ASSOC);
    $questions=$db->query('SELECT id,student_id,cohort_id,source_annotation_id,visibility,body FROM student_questions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['notes'=>$notes,'questions'=>$questions],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)."\n";
    exit;
}
if($mode==='revoke'){
    $db->prepare("UPDATE course_enrollments SET status='disabled' WHERE student_id=(SELECT id FROM student_users WHERE email=?) AND cohort_id=(SELECT id FROM course_cohorts WHERE cohort_uuid=?)")->execute(['journey-owner@example.invalid','journey-b']);
    echo "revoked\n";exit;
}
if($mode!=='seed'){fwrite(STDERR,"Unknown mode.\n");exit(1);}
$now=utc_now();
$db->prepare("INSERT INTO activities(admin_name,public_title,slug,status,is_root,created_at,updated_at) VALUES(?,?,?,'active',1,?,?)")
    ->execute(['Isolated QA','Isolated QA','qa-student',''.$now,$now]);
$activityId=(int)$db->lastInsertId();

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
