<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);
$next=student_safe_next((string)($_GET['next']??$_POST['next']??'/aluno/'));
$current=student_account_current($db);if($current){header('Location: '.$next,true,303);exit;}
$error='';$notice='';$mode=(string)($_POST['mode']??'login');
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-login',$_POST['_csrf']??null))$error='Solicitação inválida.';
    elseif($mode==='activate'){
        $result=student_account_begin_activation($db,(string)($_POST['email']??''),(string)($_POST['cpf']??''));
        if($result==='ok'){header('Location: /aluno/senha.php?primeiro=1&next='.rawurlencode($next),true,303);exit;}
        if($result==='already_active')$notice='Esta conta já foi ativada. Entre com o e-mail e a senha escolhida.';
        else $error='Não encontrei uma matrícula confirmada com este e-mail e CPF. Confira os dados ou aguarde a confirmação da matrícula.';
    }elseif(student_account_login($db,(string)($_POST['email']??''),(string)($_POST['password']??''))){header('Location: '.$next,true,303);exit;}
    else $error='E-mail ou senha inválidos. Se houve muitas tentativas, aguarde alguns minutos antes de tentar novamente.';
}
require_once __DIR__.'/../app/student_auth_view.php';
student_shell_start('Entrar · Área do aluno',null,null);
student_auth_render_login($error,$notice,$mode,$next);
student_shell_end();
