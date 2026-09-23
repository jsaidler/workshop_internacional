<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_auth.php';
student_security_headers();
$student=require_student(true);$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    $password=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm']??'');
    if(!verify_csrf('student-password',$_POST['_csrf']??null))$error='Solicitação inválida. Atualize a página e tente novamente.';
    elseif($password!==$confirm)$error='As duas senhas precisam ser iguais.';
    elseif(!student_password_is_valid($password))$error='Use uma senha com pelo menos '.STUDENT_PASSWORD_MIN_LENGTH.' caracteres.';
    else{student_change_own_password(database(),$student,$password);header('Location: /aluno/',true,303);exit;}
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive"><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500;600&family=Saira+Extra+Condensed:wght@400;500;600&display=swap" rel="stylesheet"><link rel="stylesheet" href="/assets/student-area.css"><title>Definir senha · Área do aluno</title></head><body><main class="student-login"><section class="student-login-box"><p class="student-kicker">Área do aluno</p><h1><?=((int)$student['must_change_password']===1)?'Crie sua senha':'Alterar senha'?></h1><?php if($error):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-password'))?>"><label class="student-field">Nova senha<input name="password" type="password" autocomplete="new-password" minlength="<?=STUDENT_PASSWORD_MIN_LENGTH?>" required></label><label class="student-field">Repita a nova senha<input name="confirm" type="password" autocomplete="new-password" minlength="<?=STUDENT_PASSWORD_MIN_LENGTH?>" required></label><button class="student-button" type="submit">Salvar senha</button></form><p class="student-help">Use pelo menos <?=STUDENT_PASSWORD_MIN_LENGTH?> caracteres e não reutilize uma senha importante de outro serviço.</p></section></main></body></html>
