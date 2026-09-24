<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
$db=database();
try{$activity=activity_for_request($db);}catch(Throwable $e){http_response_code(404);exit('Atividade não encontrada.');}
$student=require_student_for_activity($db,$activity,true);
$next=student_safe_next((string)($_GET['next']??$_POST['next']??student_url('/aluno/',$activity)));
$error='';$notice='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-password',$_POST['_csrf']??null))$error='Solicitação inválida.';
    else{
        $password=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm']??'');
        if($password!==$confirm)$error='As senhas não conferem.';
        elseif(!student_password_valid($password))$error='Use uma senha com pelo menos 12 caracteres.';
        else{student_change_password($db,(int)$student['id'],$password);header('Location: '.$next,true,303);exit;}
    }
}
student_shell_start('Alterar senha · Área do aluno',$activity,$student);?>
<section class="student-login-card">
  <p class="student-kicker">Segurança</p>
  <h1 class="student-title" style="font-size:50px">Sua senha</h1>
  <p class="student-lead" style="font-size:16px;margin-top:18px"><?=((int)$student['must_change_password']===1)?'Antes de acessar o material, escolha uma senha pessoal.':'Altere sua senha de acesso quando quiser.'?></p>
  <?php if($error):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-password'))?>">
    <input type="hidden" name="next" value="<?=h($next)?>">
    <label class="student-field">Nova senha<input name="password" type="password" autocomplete="new-password" minlength="12" required></label>
    <label class="student-field">Confirmar senha<input name="confirm" type="password" autocomplete="new-password" minlength="12" required></label>
    <button class="student-button" type="submit">Salvar senha</button>
  </form>
</section>
<?php student_shell_end();
