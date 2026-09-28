<?php
declare(strict_types=1);

function fail_course_admin_context(string $message): never {fwrite(STDERR,"course-admin-context: $message\n");exit(1);}
function must_course_admin_context(bool $ok,string $message): void {if(!$ok)fail_course_admin_context($message);}

$adminShell=file_get_contents(__DIR__.'/../app/admin_shell.php');
$courses=file_get_contents(__DIR__.'/../admin/courses.php');
$registrations=file_get_contents(__DIR__.'/../admin/registrations.php');
$cohorts=file_get_contents(__DIR__.'/../admin/cohorts.php');
$students=file_get_contents(__DIR__.'/../admin/students.php');
$lessons=file_get_contents(__DIR__.'/../admin/lessons.php');
$material=file_get_contents(__DIR__.'/../admin/material.php');
$studentRoute=file_get_contents(__DIR__.'/../admin/student-area.php');
$people=file_get_contents(__DIR__.'/../admin/people.php');
$legacy=file_get_contents(__DIR__.'/../admin/submissions.php');

must_course_admin_context(str_contains($courses,'Cursos'),'course administration entry point missing');
must_course_admin_context(str_contains($courses,'Abrir filtrado'),'course catalog does not expose related collections as filters');
must_course_admin_context(str_contains($adminShell,"'registrations'=>'/admin/registrations.php?")&&str_contains($adminShell,"'cohorts'=>'/admin/cohorts.php?")&&str_contains($adminShell,"'students'=>'/admin/students.php?")&&str_contains($adminShell,"'lessons'=>'/admin/lessons.php?")&&str_contains($adminShell,"'material'=>'/admin/material.php?"),'course links do not route into global collections');
must_course_admin_context(!str_contains($adminShell,'class="admin-course-nav"'),'course subtree navigation returned to the shell');
must_course_admin_context(!str_contains($registrations,'Escolha o curso para administrar'),'registrations still require course context before operating');
must_course_admin_context(str_contains($registrations,'Confirmadas sem turma'),'registrations do not preserve confirmed-without-cohort state');
must_course_admin_context(str_contains($registrations,'Disponibilidade agregada'),'registration availability is not visible when course filter is available');
must_course_admin_context(str_contains($registrations,'assign_cohort'),'cohort assignment is not explicit');
must_course_admin_context(str_contains($cohorts,'Todos os cursos'),'cohorts are not exposed as a global collection');
must_course_admin_context(str_contains($students,'Todos os cursos')&&str_contains($students,'Todas as turmas'),'students are not exposed as a filterable global collection');
must_course_admin_context(str_contains($lessons,'Todos os cursos'),'lessons are not exposed as a global collection');
must_course_admin_context(str_contains($material,'course_material_add_page'),'course material relation is not administered from material collection');
must_course_admin_context(str_contains($material,'/editor/?page='),'course material does not reuse canonical CMS editor');
must_course_admin_context(str_contains($studentRoute,'/admin/people.php'),'legacy student administration route does not enter global people authority');
must_course_admin_context(str_contains($people,'FROM student_users u'),'global people administration is not based on student identity authority');
must_course_admin_context(str_contains($people,'Sem curso associado'),'people administration hides orphaned historical enrollments');
must_course_admin_context(str_contains($legacy,'Outras respostas')||!str_contains($legacy,'Área do aluno'),'legacy submissions screen was promoted back to canonical registration management');

echo "course-admin-context: ok\n";
