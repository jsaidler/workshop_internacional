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
student_shell_start(($first?'Ativar conta':'Alterar senha').' · Área do aluno',null,$first?null:$student);?>
<?php if(!$first):?><div class="student-appbar"><a class="student-back" href="/aluno/perfil.php">← Conta</a></div><?php endif;?>
<section class="student-login-card">
  <p class="student-kicker"><?=$first?'Primeiro acesso':'Segurança'?></p>
  <h1 class="student-title student-auth-title"><?=$first?'Crie sua senha':'Alterar senha'?></h1>
  <p class="student-lead student-auth-copy"><?=$first?'O CPF foi usado somente para confirmar a matrícula. A partir de agora o acesso será feito com seu e-mail e esta senha.':'Escolha uma nova senha de acesso com pelo menos 12 caracteres.'?></p>
  <?php if($error):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
  <form method="post" data-ui-validate>
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-password'))?>"><input type="hidden" name="primeiro" value="<?=$first?'1':'0'?>"><input type="hidden" name="next" value="<?=h($next)?>">
    <label class="form-field">Nova senha<input id="student-new-password" name="password" type="password" autocomplete="new-password" minlength="12" required><span class="form-field-help">Mínimo de 12 caracteres.</span></label>
    <label class="form-field">Confirmar senha<input name="confirm" type="password" autocomplete="new-password" minlength="12" required data-ui-match="#student-new-password"></label>
    <?php if($first):?><label class="check-field"><input name="privacy_ack" type="checkbox" value="1" required><span>Li o <a href="/?page=privacidade&lang=pt-br" target="_blank" rel="noopener">Aviso de Privacidade</a> e estou ciente de como os dados da conta são utilizados.</span></label><?php endif;?>
    <button class="button button-primary" type="submit"><?=$first?'Ativar conta':'Salvar senha'?></button>
  </form>
</section>
<?php student_shell_end();
