<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fperfil.php',true,303);exit;}
$error='';$notice='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-profile',$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{student_account_update_profile($db,(int)$student['id'],$_POST);$student=student_account_current($db)??$student;$notice='Dados atualizados.';}catch(Throwable $e){$error=$e->getMessage();}
}
$profile=student_account_profile($db,(int)$student['id']);
student_shell_start('Conta · Área do aluno',null,$student);?>
<p class="student-kicker">Área do aluno</p>
<h1 class="student-title student-title-record">Conta</h1>
<p class="student-lead student-lead-compact">Gerencie seus dados, sua senha e a sessão desta conta.</p>
<?php if($error):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<?php if($notice):?><p class="ui-alert ui-alert-notice"><?=h($notice)?></p><?php endif;?>

<section class="student-workflow-panel" aria-labelledby="student-profile-heading">
  <header class="student-workflow-heading"><div><p class="student-kicker">Dados pessoais</p><h2 class="student-subtitle" id="student-profile-heading">Perfil</h2></div><p>Estes dados podem preencher novas inscrições automaticamente. Você continua podendo revisá-los no formulário antes de enviar.</p></header>
  <form method="post" class="student-form-grid" data-ui-validate>
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-profile'))?>">
    <label class="form-field student-span-2">Nome completo<input name="name" autocomplete="name" value="<?=h((string)$profile['name'])?>" required></label>
    <label class="form-field">E-mail<input name="email" type="email" autocomplete="email" value="<?=h((string)$profile['email'])?>" required></label>
    <label class="form-field">CPF<input name="cpf" inputmode="numeric" autocomplete="off" pattern="[0-9. -]{11,14}" value="<?=h((string)$profile['cpf'])?>" required></label>
    <label class="form-field">WhatsApp / telefone<input name="phone" autocomplete="tel" value="<?=h((string)($profile['phone']??''))?>"></label>
    <label class="form-field">Instagram<input name="instagram" value="<?=h((string)($profile['instagram']??''))?>"></label>
    <label class="form-field student-span-2">Endereço<input name="address" autocomplete="street-address" value="<?=h((string)($profile['address']??''))?>"></label>
    <label class="form-field">Cidade/UF<input name="city_state" autocomplete="address-level2" value="<?=h((string)($profile['city_state']??''))?>"></label>
    <label class="form-field">CEP<input name="postal_code" autocomplete="postal-code" value="<?=h((string)($profile['postal_code']??''))?>"></label>
    <div class="student-actions student-span-2"><button class="button button-primary" type="submit">Salvar dados</button></div>
  </form>
  <p><a href="/?page=privacidade&lang=pt-br">Aviso de Privacidade</a></p>
</section>

<section class="student-workflow-panel" aria-labelledby="student-security-heading">
  <header class="student-workflow-heading"><div><p class="student-kicker">Segurança</p><h2 class="student-subtitle" id="student-security-heading">Senha</h2></div><p>O acesso é feito com o seu e-mail e a senha escolhida na ativação da conta.</p></header>
  <div class="student-actions"><a class="button button-secondary" href="/aluno/senha.php?next=%2Faluno%2Fperfil.php">Alterar senha</a></div>
</section>

<section class="student-workflow-panel" aria-labelledby="student-session-heading">
  <header class="student-workflow-heading"><div><p class="student-kicker">Sessão</p><h2 class="student-subtitle" id="student-session-heading">Sair da conta</h2></div><p>Encerre a sessão deste navegador. Seus dados e matrículas permanecem na conta.</p></header>
  <form method="post" action="/aluno/logout.php" class="student-actions">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-logout'))?>">
    <button class="button button-secondary" type="submit">Sair</button>
  </form>
</section>
<?php student_shell_end();
