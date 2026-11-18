<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_home.php';
require_once __DIR__.'/../app/student_record_create_dialog.php';
security_headers();student_private_headers();
$db=database();
student_enrollment_reconcile_confirmed($db);
$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$studentId=(int)$student['id'];
$enrollments=student_enrollment_list($db,$studentId);
$createError='';$createDraft=[];
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    $createDraft=$_POST;
    if(!verify_csrf('student-process-notebook',$_POST['_csrf']??null))$createError='Solicitação inválida.';
    else{
        try{
            if((string)($_POST['action']??'')!=='create')throw new RuntimeException('Ação inválida.');
            $record=student_process_create_record($db,$studentId,$_POST);
            header('Location: /aluno/teste.php?id='.(int)$record['id'],true,303);
            exit;
        }catch(Throwable $e){$createError=$e->getMessage();}
    }
}

$home=[
    'meeting'=>student_home_next_meeting(course_agenda_student_meetings($db,$studentId)),
    'courses'=>student_home_course_items($db,$enrollments),
    'notices'=>student_home_live_notices($db,$studentId),
    'offers'=>student_home_public_offers($db,$studentId),
    'bibliography_url'=>student_home_shared_bibliography_url($db),
];
student_shell_start('Início',null,$student,count(array_filter($home['notices'],static fn(array $notice):bool=>!empty($notice['unread']))));
require __DIR__.'/../app/student_home_view.php';
// Existing open tabs may still POST a record to Home; recover their draft on error only.
if($createError!=='')student_record_create_dialog($enrollments,csrf_token('student-process-notebook'),$createDraft,$createError,'/aluno/');
student_shell_end();
