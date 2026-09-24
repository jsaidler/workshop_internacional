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
student_shell_start('Perfil · Área do aluno',null,$student);?>
<section class="student-login-card" style="max-width:780px">
  <p class="student-kicker">Seus dados</p>
  <h1 class="student-title" style="font-size:50px">Perfil</h1>
  <p class="student-lead" style="font-size:16px;margin-top:18px">Estes dados podem preencher novas inscrições automaticamente. Você continua podendo revisá-los no formulário antes de enviar.</p>
  <?php if($error):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>
  <?php if($notice):?><p class="student-notice"><?=h($notice)?></p><?php endif;?>
  <form method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-profile'))?>">
    <label class="student-field" style="grid-column:1/-1">Nome completo<input name="name" autocomplete="name" value="<?=h((string)$profile['name'])?>" required></label>
    <label class="student-field">E-mail<input name="email" type="email" autocomplete="email" value="<?=h((string)$profile['email'])?>" required></label>
    <label class="student-field">CPF<input name="cpf" inputmode="numeric" autocomplete="off" value="<?=h((string)$profile['cpf'])?>" required></label>
    <label class="student-field">WhatsApp / telefone<input name="phone" autocomplete="tel" value="<?=h((string)($profile['phone']??''))?>"></label>
    <label class="student-field">Instagram<input name="instagram" value="<?=h((string)($profile['instagram']??''))?>"></label>
    <label class="student-field" style="grid-column:1/-1">Endereço<input name="address" autocomplete="street-address" value="<?=h((string)($profile['address']??''))?>"></label>
    <label class="student-field">Cidade/UF<input name="city_state" autocomplete="address-level2" value="<?=h((string)($profile['city_state']??''))?>"></label>
    <label class="student-field">CEP<input name="postal_code" autocomplete="postal-code" value="<?=h((string)($profile['postal_code']??''))?>"></label>
    <div style="grid-column:1/-1"><button class="student-button" type="submit">Salvar dados</button></div>
  </form>
  <p style="margin-top:22px;font-size:14px">Veja também o <a href="/?page=privacidade&lang=pt-br">Aviso de Privacidade</a>.</p>
</section>
<?php student_shell_end();
