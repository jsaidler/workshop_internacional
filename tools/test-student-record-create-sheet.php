<?php
declare(strict_types=1);
require_once __DIR__.'/../app/student_record_create_dialog.php';
function record_create_assert(bool $ok,string $message): void {
    if(!$ok){fwrite(STDERR,"student-record-create-sheet: $message\n");exit(1);}
}
$enrollments=[['cohort_id'=>12,'course_title'=>'Curso Alfa','cohort_title'=>'Turma Um'],['cohort_id'=>17,'course_title'=>'Curso Beta','cohort_title'=>'Turma Dois']];
ob_start();
student_record_create_dialog($enrollments,'token',[], '');
$normal=(string)ob_get_clean();
record_create_assert(str_contains($normal,'data-record-create-dialog')&&str_contains($normal,'student-record-create-dialog'),'real shared dialog missing');
record_create_assert(str_contains($normal,'name="context_scope" value="personal" checked'),'new registration should default to personal');
record_create_assert(str_contains($normal,'name="title" maxlength="180" value=""'),'new registration must begin blank');
record_create_assert(!str_contains($normal,'data-record-create-error'),'clean state must not be flagged failed');
ob_start();
student_record_create_dialog($enrollments,'token',['title'=>'Teste & <script>','test_date'=>'2026-10-09','context_scope'=>'course','context_cohort_id'=>'17'],'Falha <script>');
$error=(string)ob_get_clean();
record_create_assert(str_contains($error,'Teste &amp; &lt;script&gt;'),'failed title is not escaped or preserved');
record_create_assert(str_contains($error,'Falha &lt;script&gt;'),'server error was not escaped');
record_create_assert(str_contains($error,'data-record-create-error')&&str_contains($error,'data-record-create-alert'),'failed form must reopen contextually');
record_create_assert(str_contains($error,'value="17" selected'),'failed draft must retain selected cohort');
record_create_assert(str_contains($error,'name="context_scope" value="course" checked'),'failed draft must retain chosen scope');
record_create_assert(!str_contains($error,'data-process-course-context hidden'),'selected cohort must be visible before JS');
ob_start();
student_record_create_dialog([], 'token',['context_scope'=>'course'],'');
$personal=(string)ob_get_clean();
record_create_assert(!str_contains($personal,'value="course"'),'non-enrolled student cannot select course');
record_create_assert(str_contains($personal,'value="personal" checked'),'non-enrolled student remains personal');
echo "student-record-create-sheet: ok\n";
