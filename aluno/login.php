<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_auth.php';
student_security_headers();
if(current_student()){header('Location: /aluno/',true,303);exit;}
$error='';$return=student_safe_return_url($_GET['return']??$_POST['return']??'/aluno/');
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-login',$_POST['_csrf']??null))$error='Solicitação inválida. Atualize a página e tente novamente.';
    elseif(student_login((string)($_POST['username']??''),(string)($_POST['password']??''))){$student=current_student();header('Location: '.(($student&&(int)$student['must_change_password']===1)?'/aluno/senha.php':$return),true,303);exit;}
    else $error='Usuário ou senha inválidos. Se houver muitas tentativas, o acesso fica temporariamente bloqueado.';
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500;600&family=Saira+Extra+Condensed:wght@400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/student-area.css"><title>Área do aluno</title></head><body><main class="student-login"><section class="student-login-box"><p class="student-kicker">João Saidler · Área do aluno</p><h1>Entrar</h1><?php if($error):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?><form method="post" autocomplete="on"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-login'))?>"><input type="hidden" name="return" value="<?=h($return)?>"><label class="student-field">Usuário ou e-mail<input name="username" autocomplete="username" required autofocus></label><label class="student-field">Senha<input name="password" type="password" autocomplete="current-password" required></label><button class="student-button" type="submit">Entrar</button></form><p class="student-help">O acesso é individual. Se você recebeu uma senha provisória, será solicitado que a troque no primeiro acesso.</p></section></main></body></html>
