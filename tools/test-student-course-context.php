<?php
declare(strict_types=1);
function fail_student_course_context(string $message): never {fwrite(STDERR,"student-course-context: $message\n");exit(1);}
function must_student_course_context(bool $condition,string $message): void {if(!$condition)fail_student_course_context($message);}
$root=dirname(__DIR__);require $root.'/app/student_enrollments.php';
$courseA=['id'=>1,'activity_id'=>10,'workshop_page_id'=>1001,'cohort_id'=>101,'cohort_uuid'=>'cohort-a','cohort_status'=>'active'];$courseB=['id'=>2,'activity_id'=>10,'workshop_page_id'=>1002,'cohort_id'=>202,'cohort_uuid'=>'cohort-b','cohort_status'=>'closed'];
must_student_course_context(student_enrollment_dashboard_context([$courseA],'')===$courseA,'a single enrollment must open directly');
must_student_course_context(student_enrollment_dashboard_context([$courseA,$courseB],'')===null,'multiple enrollments must require an explicit workspace choice');
must_student_course_context(student_enrollment_dashboard_context([$courseA,$courseB],'cohort-b')===$courseB,'cohort UUID did not select the matching enrollment');
must_student_course_context(student_enrollment_dashboard_context([$courseA,$courseB],'missing')===null,'unknown cohort UUID selected an unrelated enrollment');
must_student_course_context(student_enrollment_cohort_status_label('active')==='Turma ativa','active cohort label is incorrect');
must_student_course_context(student_enrollment_cohort_status_label('closed')==='Turma encerrada','closed cohort label is incorrect');
$courses=(string)file_get_contents($root.'/aluno/cursos.php');$home=(string)file_get_contents($root.'/aluno/index.php');
must_student_course_context(str_contains($courses,'student_enrollment_dashboard_context'),'courses surface bypasses canonical explicit selector for multiple enrollments');
must_student_course_context(str_contains($courses,'elseif(count($enrollments)===1)$selectedEnrollment=$enrollments[0]'),'single enrollment does not open its course directly');
must_student_course_context(str_contains($courses,'Trocar curso')&&str_contains($courses,'count($enrollments)>1'),'course switch is not limited to students with multiple enrollments');
must_student_course_context(str_contains($courses,'As aulas aparecerão conforme forem cadastradas.'),'empty lesson state is misleading');
must_student_course_context(str_contains($courses,"student_shell_start('Curso',\$selectedActivity,\$student)"),'selected course design context is not passed to student shell');
must_student_course_context(str_contains($courses,'student-academic-choice-list')&&str_contains($courses,'student-academic-material-list'),'course surface still uses dashboard-style card composition');
must_student_course_context(str_contains($home,'student_experience_dashboard_state')&&!str_contains($home,'student_course_context_header'),'student home must remain a next-action surface, not the course workspace');
echo "student-course-context: ok\n";
