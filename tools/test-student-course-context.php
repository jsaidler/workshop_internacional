<?php
declare(strict_types=1);

function fail_student_course_context(string $message): never {fwrite(STDERR,"student-course-context: $message\n");exit(1);}
function must_student_course_context(bool $condition,string $message): void {if(!$condition)fail_student_course_context($message);}

$root=dirname(__DIR__);
require $root.'/app/student_enrollments.php';

$courseA=['id'=>1,'activity_id'=>10,'cohort_id'=>101,'cohort_uuid'=>'cohort-a','cohort_status'=>'active'];
$courseB=['id'=>2,'activity_id'=>20,'cohort_id'=>202,'cohort_uuid'=>'cohort-b','cohort_status'=>'closed'];

must_student_course_context(student_enrollment_dashboard_context([$courseA],'')===$courseA,'a single enrollment must open directly');
must_student_course_context(student_enrollment_dashboard_context([$courseA,$courseB],'')===null,'multiple enrollments must require an explicit workspace choice');
must_student_course_context(student_enrollment_dashboard_context([$courseA,$courseB],'cohort-b')===$courseB,'cohort UUID did not select the matching enrollment');
must_student_course_context(student_enrollment_dashboard_context([$courseA,$courseB],'missing')===null,'unknown cohort UUID selected an unrelated enrollment');
must_student_course_context(student_enrollment_cohort_status_label('active')==='Turma ativa','active cohort label is incorrect');
must_student_course_context(student_enrollment_cohort_status_label('closed')==='Turma encerrada','closed cohort label is incorrect');
must_student_course_context(student_enrollment_cohort_status_label('unexpected')==='Estado da turma indisponível','unknown cohort status must not be presented as active');

$dashboard=(string)file_get_contents($root.'/aluno/index.php');
must_student_course_context(str_contains($dashboard,'student_enrollment_dashboard_context'),'dashboard bypasses the canonical workspace selector');
must_student_course_context(str_contains($dashboard,'← Todos os cursos'),'selected workspace has no route back to the course selector');
must_student_course_context(str_contains($dashboard,'Sem aulas cadastradas'),'empty lesson state still renders a misleading 0/0 progress');
must_student_course_context(str_contains($dashboard,"student_shell_start('Área do aluno',\$selectedActivity,\$student)"),'selected course design is not passed to the student shell');

echo "student-course-context: ok\n";
