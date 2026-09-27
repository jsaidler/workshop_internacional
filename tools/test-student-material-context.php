<?php
declare(strict_types=1);

function fail_student_material_context(string $message): never {fwrite(STDERR,"student-material-context: $message\n");exit(1);}
function must_student_material_context(bool $condition,string $message): void {if(!$condition)fail_student_material_context($message);}

require __DIR__.'/../app/student_accounts.php';
require __DIR__.'/../app/student_enrollments.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,activity_id INTEGER,title TEXT,cohort_uuid TEXT,status TEXT);');
$db->exec('CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT,confirmed_at TEXT);');
$db->exec("INSERT INTO course_cohorts VALUES(7,1,'Turma A','cohort-a','active'),(8,1,'Turma B','cohort-b','active'),(9,2,'Turma C','cohort-c','active');");
$db->exec("INSERT INTO course_enrollments VALUES(1,10,7,'active','2026-09-01T00:00:00Z'),(2,10,8,'active','2026-09-02T00:00:00Z'),(3,10,9,'active','2026-09-03T00:00:00Z');");

$student=['id'=>10];
$activityPage=['activity_id'=>1,'access_level'=>'activity','access_cohort_id'=>null];
$cohortPage=['activity_id'=>1,'access_level'=>'cohort','access_cohort_id'=>7];
$publicPage=['activity_id'=>1,'access_level'=>'public','access_cohort_id'=>null];
$authenticatedPage=['activity_id'=>1,'access_level'=>'authenticated','access_cohort_id'=>null];

$context=student_enrollment_material_context($db,$student,$activityPage,'cohort-a');
must_student_material_context($context!==null&&(int)$context['cohort_id']===7,'activity material did not preserve the requested enrollment context');
$context=student_enrollment_material_context($db,$student,$activityPage,'cohort-b');
must_student_material_context($context!==null&&(int)$context['cohort_id']===8,'activity material did not switch to the requested enrollment context');
must_student_material_context(student_enrollment_material_context($db,$student,$activityPage,'missing')===null,'unknown cohort uuid fell back to another enrollment');

$context=student_enrollment_material_context($db,$student,$cohortPage,'cohort-b');
must_student_material_context($context!==null&&(int)$context['cohort_id']===7,'cohort-specific page did not resolve its own authorized cohort');
must_student_material_context(student_enrollment_material_context($db,['id'=>99],$cohortPage,'cohort-a')===null,'course context leaked to a student without enrollment');
must_student_material_context(student_enrollment_material_context($db,$student,$publicPage,'cohort-a')===null,'public page incorrectly received a course workspace context');
must_student_material_context(student_enrollment_material_context($db,$student,$authenticatedPage,'cohort-a')===null,'generic authenticated page incorrectly received a course workspace context');

$renderer=(string)file_get_contents(__DIR__.'/../app/cms_renderer.php');
$css=(string)file_get_contents(__DIR__.'/../assets/cms-header.css');
must_student_material_context(str_contains($renderer,'student_enrollment_material_context'),'public renderer does not derive context from the canonical enrollment helper');
must_student_material_context(str_contains($renderer,'data-cms-student-context'),'public renderer does not expose the protected material context bar');
must_student_material_context(str_contains($renderer,"'/aluno/?cohort='"),'course return link does not preserve the cohort uuid');
must_student_material_context(str_contains($renderer,'href="/aluno/testes.php"')&&str_contains($renderer,'href="/aluno/perfil.php"'),'material context does not expose tests and account');
must_student_material_context(str_contains($renderer,'if(!$editor)'),'editor preview is not protected from student-session context UI');
must_student_material_context(str_contains($css,'.cms-student-context')&&str_contains($css,'@media(max-width:620px)'),'material context is missing responsive styling');

echo "student-material-context: ok\n";
