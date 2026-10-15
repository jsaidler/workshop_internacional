<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$first=($_GET['primeiro']??$_POST['primeiro']??'')==='1';$next=student_safe_next((string)($_GET['next']??$_POST['next']??'/aluno/'));
$student=$first?student_account_activation_user($db):student_account_current($db);if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-password',$_POST['_csrf']??null))$error='Solicitação inválida.';
    else{
        $password=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm']??'');
        if($password!==$confirm)$error='As senhas não conferem.';
        elseif(!student_password_valid($password))$error='Use uma senha com pelo menos 12 caracteres.';
        else{
            try{
                if($first)student_account_complete_activation($db,$password,!empty($_POST['privacy_ack']));
                else student_account_change_password($db,(int)$student['id'],$password);
                header('Location: '.$next,true,303);exit;
            }catch(Throwable $e){$error=$e->getMessage();}
        }
    }
}
require_once __DIR__.'/../app/student_auth_view.php';
student_shell_start(($first?'Ativar conta':'Alterar senha').' · Área do aluno',null,$first?null:$student);
student_auth_render_password($first,$error,$next);
student_shell_end();
