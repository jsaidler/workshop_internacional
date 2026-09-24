<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();
try{$activity=activity_for_request($db);}catch(Throwable $e){http_response_code(404);exit('Atividade não encontrada.');}
$next=student_safe_next((string)($_GET['next']??$_POST['next']??student_url('/aluno/',$activity)));
$current=current_student($db);if($current&&student_has_activity($db,(int)$current['id'],(int)$activity['id'])){header('Location: '.((int)$current['must_change_password']===1?student_url('/aluno/senha.php',$activity,['next'=>$next]):$next),true,303);exit;}
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-login',$_POST['_csrf']??null))$error='Solicitação inválida.';
    elseif(student_login($db,$activity,(string)($_POST['email']??''),(string)($_POST['password']??''))){$student=current_student($db);$target=$student&&(int)$student['must_change_password']===1?student_url('/aluno/senha.php',$activity,['next'=>$next]):$next;header('Location: '.$target,true,303);exit;}
    else $error='E-mail ou senha inválidos. Se houve muitas tentativas, aguarde alguns minutos antes de tentar novamente.';
}
student_shell_start('Entrar · Área do aluno',$activity,null);?>
<section class="student-login-card">
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title" style="font-size:58px">Entrar</h1>
  <p class="student-lead" style="font-size:16px;margin-top:18px">Use o acesso individual fornecido para este workshop.</p>
  <?php if($error):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-login'))?>">
    <input type="hidden" name="next" value="<?=h($next)?>">
    <label class="student-field">E-mail<input name="email" type="email" autocomplete="username" required></label>
    <label class="student-field">Senha<input name="password" type="password" autocomplete="current-password" required></label>
    <button class="student-button" type="submit">Entrar</button>
  </form>
</section>
<?php student_shell_end();
