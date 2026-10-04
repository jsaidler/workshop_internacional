<?php
declare(strict_types=1);

function fail_course_admin_context(string $message): never {fwrite(STDERR,"course-admin-context: $message\n");exit(1);}
function must_course_admin_context(bool $ok,string $message): void {if(!$ok)fail_course_admin_context($message);}

$adminShell=(string)file_get_contents(__DIR__.'/../app/admin_shell.php');
$courses=(string)file_get_contents(__DIR__.'/../admin/courses.php');
$registrations=(string)file_get_contents(__DIR__.'/../admin/registrations.php');
$cohorts=(string)file_get_contents(__DIR__.'/../admin/cohorts.php');
$students=(string)file_get_contents(__DIR__.'/../admin/students.php');
$lessons=(string)file_get_contents(__DIR__.'/../admin/lessons.php');
$material=(string)file_get_contents(__DIR__.'/../admin/material.php');
$tests=(string)file_get_contents(__DIR__.'/../admin/tests.php');
$questions=(string)file_get_contents(__DIR__.'/../admin/questions.php');
$preview=(string)file_get_contents(__DIR__.'/../admin/course-preview.php');
$studentRoute=(string)file_get_contents(__DIR__.'/../admin/student-area.php');
$people=(string)file_get_contents(__DIR__.'/../admin/people.php');
$submissions=(string)file_get_contents(__DIR__.'/../admin/submissions.php');

must_course_admin_context(str_contains($courses,'Abrir curso'),'course catalog must enter the course workspace');
must_course_admin_context(str_contains($adminShell,'function admin_course_workspace_items')&&str_contains($adminShell,'function admin_cohort_workspace_items'),'shell must own persistent course/cohort navigation helpers');
foreach(['registrations','cohorts','lessons','material','questions','tests'] as $area)must_course_admin_context(str_contains($adminShell,"'$area'=>"),'course/cohort route missing from contextual URL helpers: '.$area);
must_course_admin_context(str_contains($registrations,'admin_course_context($course'),'registrations must require and preserve course context');
must_course_admin_context(!str_contains($registrations,'Todos os cursos'),'registrations must not mix courses by default');
must_course_admin_context(str_contains($registrations,'Pagas sem turma')&&str_contains($registrations,'assign_cohort'),'confirmed-without-cohort state and explicit assignment must remain first-class');
must_course_admin_context(str_contains($cohorts,'admin_cohort_context($course,$cohort'),'cohort must be an operational workspace');
must_course_admin_context(str_contains($cohorts,'Abrir turma')&&str_contains($cohorts,'Editar turma'),'cohort collection must open an editable operational object');
must_course_admin_context(str_contains($students,'Busca global de pessoas com histórico de participação'),'global Students must replace the People/Student navigation decision');
must_course_admin_context(str_contains($students,'admin_cohort_context($course,$cohort'),'cohort students must retain cohort context');
must_course_admin_context(str_contains($lessons,'admin_course_context($course')&&str_contains($lessons,'admin_cohort_context($course,$cohort'),'lessons must separate course structure from cohort access');
must_course_admin_context(str_contains($material,'course_material_add_page')&&str_contains($material,'/editor/?page='),'material must retain canonical CMS-page reuse');
must_course_admin_context(str_contains($material,'Organizar por aula')&&!str_contains($material,'Gerenciar liberação'),'material must own structure, not claim to release cohort access');
must_course_admin_context(str_contains($tests,'student_test_set_review_status')&&str_contains($tests,'student_test_add_admin_message'),'test review must be present in current administration');
must_course_admin_context(str_contains($questions,'admin_course_context')&&str_contains($questions,'admin_cohort_context'),'questions must aggregate at course level and resolve at cohort level');
must_course_admin_context(str_contains($preview,'cms_access_filter_html'),'preview as cohort must use canonical server-side access filtering');
must_course_admin_context(str_contains($studentRoute,"'tests'=>'/admin/tests.php'")&&str_contains($studentRoute,"default=>'/admin/students.php'"),'legacy route must land on current workspaces');
must_course_admin_context(str_contains($people,'FROM student_users u'),'full identity history must remain available as secondary authority');
must_course_admin_context(str_contains($people,'Sem curso associado'),'historical orphan enrollments must remain visible');
must_course_admin_context(str_contains($submissions,'Outras respostas')||!str_contains($submissions,'Área do aluno'),'generic submissions must not become canonical course registration management');

echo "course-admin-context: ok\n";
