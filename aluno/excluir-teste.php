<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
$id=(int)($_GET['id']??$_POST['id']??0);$next='/aluno/excluir-teste.php?id='.$id;
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$record=student_test_for_student($db,$id,$studentId);
if(!$record){http_response_code(404);student_shell_start('Registro não encontrado',null,$student);?><div class="student-empty">Registro não encontrado.</div><?php student_shell_end();exit;}
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-delete-test-'.$id,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else{
        try{
            student_test_delete_owned($db,$id,$studentId);
            $_SESSION['student_process_notice']='Registro excluído definitivamente, incluindo fotografias e mensagens associadas.';
            header('Location: /aluno/caderno.php',true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
require_once __DIR__.'/../app/student_record_auxiliary_views.php';
student_shell_start('Excluir registro',null,$student);
student_record_render_delete_confirmation($record,$id,$error);
student_shell_end();
