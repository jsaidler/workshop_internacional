<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_enrollment_reconcile_confirmed($db);
$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fagenda.php',true,303);exit;}
$studentId=(int)$student['id'];
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-agenda',$_POST['_csrf']??null)){http_response_code(403);exit('Solicitação inválida.');}
    try{
        $cohortId=(int)($_POST['cohort_id']??0);
        $action=(string)($_POST['action']??'');
        if($cohortId<1)throw new RuntimeException('Turma inválida.');
        if($action==='request'){
            $reservation=course_agenda_request($db,$studentId,$cohortId);
            $_SESSION['student_agenda_notice']=$reservation['status']==='confirmed'?'Participação como ouvinte confirmada.':'Solicitação registrada na lista de espera.';
        }elseif($action==='cancel'){
            course_agenda_cancel($db,$studentId,$cohortId);
            $_SESSION['student_agenda_notice']='Solicitação de ouvinte cancelada.';
        }else throw new RuntimeException('Ação inválida.');
    }catch(Throwable $e){$_SESSION['student_agenda_error']=$e->getMessage();}
    header('Location: /aluno/agenda.php',true,303);exit;
}
$offers=course_agenda_student_offers($db,$studentId);
foreach($offers as $offer)course_agenda_reconcile_cohort($db,(int)$offer['cohort_id']);
$offers=course_agenda_student_offers($db,$studentId);
$meetings=course_agenda_student_meetings($db,$studentId);
$notice=(string)($_SESSION['student_agenda_notice']??'');$error=(string)($_SESSION['student_agenda_error']??'');
unset($_SESSION['student_agenda_notice'],$_SESSION['student_agenda_error']);
student_shell_start('Agenda de encontros',null,$student);
require __DIR__.'/../app/student_agenda_view.php';
student_shell_end();
