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
student_shell_start('Entrar · Área do aluno',null,null);?>
<div class="student-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;align-items:start">
<section class="student-login-card">
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title" style="font-size:58px">Entrar</h1>
  <p class="student-lead" style="font-size:16px;margin-top:18px">Para contas já ativadas.</p>
  <?php if($error&&$mode!=='activate'):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>
  <?php if($notice):?><p class="student-notice"><?=h($notice)?></p><?php endif;?>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-login'))?>"><input type="hidden" name="mode" value="login"><input type="hidden" name="next" value="<?=h($next)?>">
    <label class="student-field">E-mail<input name="email" type="email" autocomplete="username" required></label>
    <label class="student-field">Senha<input name="password" type="password" autocomplete="current-password" required></label>
    <button class="student-button" type="submit">Entrar</button>
  </form>
</section>
<section class="student-login-card">
  <p class="student-kicker">Primeiro acesso</p>
  <h2 class="student-title" style="font-size:44px">Ativar conta</h2>
  <p class="student-lead" style="font-size:16px;margin-top:18px">Depois que a matrícula for confirmada, use o mesmo e-mail da inscrição e o CPF somente com números. O CPF serve apenas para este primeiro reconhecimento; em seguida você cria sua própria senha.</p>
  <?php if($error&&$mode==='activate'):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-login'))?>"><input type="hidden" name="mode" value="activate"><input type="hidden" name="next" value="<?=h($next)?>">
    <label class="student-field">E-mail da inscrição<input name="email" type="email" autocomplete="email" required></label>
    <label class="student-field">CPF<input name="cpf" inputmode="numeric" autocomplete="off" pattern="[0-9. -]{11,14}" required></label>
    <button class="student-button" type="submit">Continuar</button>
  </form>
</section>
</div>
<?php student_shell_end();
