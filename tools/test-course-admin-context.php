<?php
declare(strict_types=1);

function fail_course_admin_context(string $message): never {fwrite(STDERR,"course-admin-context: $message\n");exit(1);}
function must_course_admin_context(bool $ok,string $message): void {if(!$ok)fail_course_admin_context($message);}

$courses=file_get_contents(__DIR__.'/../admin/courses.php');
$registrations=file_get_contents(__DIR__.'/../admin/registrations.php');
$studentRoute=file_get_contents(__DIR__.'/../admin/student-area.php');
$people=file_get_contents(__DIR__.'/../admin/people.php');
$legacy=file_get_contents(__DIR__.'/../admin/submissions.php');
$shell=file_get_contents(__DIR__.'/../app/admin_shell.php');

must_course_admin_context(str_contains($courses,'Administração dos cursos'),'course administration entry point missing');
must_course_admin_context(str_contains($shell,'admin_course_context_nav'),'canonical course context navigation missing');
foreach(['Visão geral','Inscrições','Turmas','Alunos','Aulas','Material'] as $label)must_course_admin_context(str_contains($shell,"'".$label."'"),'course context missing '.$label);
must_course_admin_context(str_contains($courses,'course_material_add_page'),'course material relation is not administered from course context');
must_course_admin_context(str_contains($courses,'/editor/?page='),'course material does not reuse canonical CMS editor');
must_course_admin_context(str_contains($courses,'Importar alunos históricos'),'historical students are not imported from cohort context');
must_course_admin_context(str_contains($registrations,'Escolha o curso'),'registrations do not start from explicit course context');
must_course_admin_context(str_contains($registrations,'Confirmadas sem turma'),'registrations do not preserve confirmed-without-cohort state');
must_course_admin_context(str_contains($registrations,'Disponibilidade declarada'),'registration availability is not operationally summarized');
must_course_admin_context(str_contains($registrations,'assign_cohort'),'cohort assignment is not explicit');
must_course_admin_context(str_contains($studentRoute,'/admin/people.php'),'legacy student administration route does not enter global people authority');
must_course_admin_context(str_contains($people,'FROM student_users u'),'global people administration is not based on student identity authority');
must_course_admin_context(str_contains($people,'Vínculo sem curso'),'people administration hides incomplete historical enrollments');
must_course_admin_context(str_contains($legacy,'Outras respostas')||!str_contains($legacy,'Área do aluno'),'legacy submissions screen was promoted back to canonical course registration management');

echo "course-admin-context: ok\n";
