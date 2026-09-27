<?php
declare(strict_types=1);

function fail_course_admin_context(string $message): never {fwrite(STDERR,"course-admin-context: $message\n");exit(1);}
function must_course_admin_context(bool $ok,string $message): void {if(!$ok)fail_course_admin_context($message);}

$courses=file_get_contents(__DIR__.'/../admin/courses.php');
$registrations=file_get_contents(__DIR__.'/../admin/registrations.php');
$students=file_get_contents(__DIR__.'/../admin/student-area.php');
$legacy=file_get_contents(__DIR__.'/../admin/submissions.php');

must_course_admin_context(str_contains($courses,'Cursos'),'course administration entry point missing');
must_course_admin_context(str_contains($courses,'course_material_add_page'),'course material relation is not administered from course context');
must_course_admin_context(str_contains($courses,'/editor/?page='),'course material does not reuse canonical CMS editor');
must_course_admin_context(str_contains($registrations,'Escolha o curso'),'registrations do not start from explicit course context');
must_course_admin_context(str_contains($registrations,'Confirmadas sem turma'),'registrations do not preserve confirmed-without-cohort state');
must_course_admin_context(str_contains($registrations,'Disponibilidade'),'registration availability is not visible in course context');
must_course_admin_context(str_contains($registrations,'assign_cohort'),'cohort assignment is not explicit');
must_course_admin_context(str_contains($students,'Escolha o curso'),'student administration still starts from a mixed site-wide list');
must_course_admin_context(str_contains($legacy,'Outras respostas')||!str_contains($legacy,'Área do aluno'),'legacy submissions screen was promoted back to canonical course registration management');

echo "course-admin-context: ok\n";
