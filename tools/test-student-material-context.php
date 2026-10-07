<?php
declare(strict_types=1);

function fail_student_material_context(string $message): never {fwrite(STDERR,"student-material-context: $message\n");exit(1);}
function must_student_material_context(bool $condition,string $message): void {if(!$condition)fail_student_material_context($message);}
// This test intentionally covers the legacy activity-scoped material fallback.
// Canonical course/material resolution has its own regression.
function cms_page_workshop_root_id(PDO $db,array $page): int {return 0;}
function workshop_course_scope_available(PDO $db): bool {return false;}

require __DIR__.'/../app/student_accounts.php';
require __DIR__.'/../app/student_enrollments.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,activity_id INTEGER,workshop_page_id INTEGER NULL,title TEXT,cohort_uuid TEXT,status TEXT);');
$db->exec('CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT,confirmed_at TEXT);');
$db->exec("INSERT INTO course_cohorts(id,activity_id,workshop_page_id,title,cohort_uuid,status) VALUES(7,1,NULL,'Turma A','cohort-a','active'),(8,1,NULL,'Turma B','cohort-b','active'),(9,2,NULL,'Turma C','cohort-c','active');");
$db->exec("INSERT INTO course_enrollments VALUES(1,10,7,'active','2026-09-01T00:00:00Z'),(2,10,8,'active','2026-09-02T00:00:00Z'),(3,10,9,'active','2026-09-03T00:00:00Z');");

$student=['id'=>10];
$activityPage=['id'=>101,'activity_id'=>1,'access_level'=>'activity','access_cohort_id'=>null];
$cohortPage=['id'=>102,'activity_id'=>1,'access_level'=>'cohort','access_cohort_id'=>7];
$publicPage=['id'=>103,'activity_id'=>1,'access_level'=>'public','access_cohort_id'=>null];
$authenticatedPage=['id'=>104,'activity_id'=>1,'access_level'=>'authenticated','access_cohort_id'=>null];

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
$css=(string)file_get_contents(__DIR__.'/../assets/cms-student-notes.css');
must_student_material_context(str_contains($renderer,'student_enrollment_material_context'),'public renderer does not derive context from the canonical enrollment helper');
must_student_material_context(str_contains($renderer,'cms_access_filter_html($db,$activity,$body,$currentStudent,$editor,null,$materialContext)'),'public renderer does not apply the canonical section access filter with enrollment context');
must_student_material_context(str_contains($renderer,'cms_student_material_context_html'),'material renderer does not expose the academic reading context');
must_student_material_context(str_contains($renderer,"'/aluno/cursos.php?cohort='")&&str_contains($renderer,"'/aluno/duvidas.php?cohort='"),'course/material navigation does not preserve the cohort uuid');
must_student_material_context(str_contains($renderer,'course_material_page_release_summary')&&str_contains($renderer,"['available','partial']"),'previous/next material navigation includes pages with no currently usable content');
must_student_material_context(str_contains($renderer,'<main id="main" data-cms-page-main><?=$studyContext?><?=$body?>'),'study context is not in the normal reading flow before editorial content');
must_student_material_context(str_contains($renderer,"if(\$materialContext)\$brandUrl='/aluno/'"),'authenticated material brand does not return to the student home');
must_student_material_context(str_contains($renderer,"student_shell_nav('courses','student-desktop-nav')")&&str_contains($renderer,"student_shell_nav('courses','student-mobile-nav')"),'authenticated material does not consume the shared student navigation');
must_student_material_context(str_contains($renderer,"cms_public_system_css_imports(\$assetVersion,\$design,(bool)\$materialContext)"),'authenticated material does not load the canonical student navigation styles');
must_student_material_context(str_contains($renderer,"\$materialContext?' cms-student-material':''"),'authenticated material lacks the shell context class');
must_student_material_context(str_contains($css,'.cms-student-study-context')&&str_contains($css,'.cms-student-study-pagination'),'material context has no canonical student CSS owner');
must_student_material_context(!str_contains($renderer,'/aluno/testes.php?cohort='),'protected material still exposes the obsolete Testes destination');
must_student_material_context(str_contains($renderer,'if(!$editor)'),'editor preview is not protected from student-session context UI');
echo "student-material-context: ok\n";
