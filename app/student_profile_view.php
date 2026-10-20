<?php
declare(strict_types=1);

/** Authenticated account view. Authorization and updates belong to aluno/perfil.php. */
function student_profile_render(array $profile,string $error='',string $notice='',bool $newsletterOptIn=false):void {
    $failed=$error!=='';
?>
<div class="student-profile-page">
  <header class="student-profile-header">
    <a class="student-back" href="/aluno/">← Início</a>
    <h1 class="student-title student-title-record">Conta</h1>
  </header>
  <?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert" tabindex="-1" data-profile-error><?=h($error)?></p><?php endif;?>
  <?php if($notice!==''):?><p class="ui-alert ui-alert-notice" role="status"><?=h($notice)?></p><?php endif;?>
  <section class="student-profile-identity" aria-labelledby="student-profile-heading">
    <div class="student-profile-section-header">
      <h2 id="student-profile-heading">Dados pessoais</h2>
    </div>
    <form method="post" class="student-form-grid student-profile-form" data-ui-validate data-student-profile-form>
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-profile'))?>">
      <div class="student-form-grid student-profile-essential student-span-2">
        <label class="form-field student-span-2">Nome completo<input name="name" autocomplete="name" value="<?=h((string)($profile['name']??''))?>" required></label>
        <label class="form-field">E-mail<input name="email" type="email" autocomplete="email" value="<?=h((string)($profile['email']??''))?>" required></label>
        <label class="form-field">CPF<input name="cpf" inputmode="numeric" autocomplete="off" pattern="[0-9. -]{11,14}" value="<?=h((string)($profile['cpf']??''))?>" required></label>
      </div>
      <details class="student-profile-disclosure student-span-2" data-profile-contact<?=$failed?' open':''?>>
        <summary><span><strong>Contato e redes sociais</strong><small>Telefone e Instagram · opcionais</small></span><span aria-hidden="true" class="student-profile-chevron"></span></summary>
        <div class="student-form-grid student-profile-disclosure-fields">
          <label class="form-field">Telefone para contato<input name="phone" autocomplete="tel" value="<?=h((string)($profile['phone']??''))?>"></label>
          <label class="form-field">Instagram<input name="instagram" value="<?=h((string)($profile['instagram']??''))?>"></label>
        </div>
      </details>
      <details class="student-profile-disclosure student-span-2" data-profile-address<?=$failed?' open':''?>>
        <summary><span><strong>Endereço</strong><small>Usado em inscrições, quando necessário · opcional</small></span><span aria-hidden="true" class="student-profile-chevron"></span></summary>
        <div class="student-form-grid student-profile-disclosure-fields">
          <label class="form-field student-span-2">Endereço<input name="address" autocomplete="street-address" value="<?=h((string)($profile['address']??''))?>"></label>
          <label class="form-field">Cidade e UF<input name="city_state" autocomplete="address-level2" value="<?=h((string)($profile['city_state']??''))?>"></label>
          <label class="form-field">CEP<input name="postal_code" autocomplete="postal-code" value="<?=h((string)($profile['postal_code']??''))?>"></label>
        </div>
      </details>
      <div class="student-actions student-span-2 student-profile-save"><button class="button button-primary" type="submit">Salvar dados</button></div>
    </form>
    <p class="student-profile-privacy"><a href="/?page=privacidade&lang=pt-br">Aviso de Privacidade</a></p>
  </section>
  <div class="student-profile-settings">
    <section class="student-profile-setting" aria-labelledby="student-newsletter-heading">
      <p class="student-kicker">Comunicações</p><h2 id="student-newsletter-heading">Novos cursos e atividades</h2>
      <p>Escolha se deseja receber novidades por e-mail. Avisos necessários sobre aulas de que participa são independentes dessa opção.</p>
      <form method="post">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-newsletter'))?>">
        <input type="hidden" name="action" value="newsletter">
        <label><input type="checkbox" name="newsletter_opt_in" value="yes"<?=$newsletterOptIn?' checked':''?>> Quero receber novidades sobre cursos, turmas e atividades.</label>
        <div class="student-actions"><button type="submit" class="button button-secondary">Salvar preferência</button></div>
      </form>
    </section>
    <section class="student-profile-setting" aria-labelledby="student-security-heading">
      <p class="student-kicker">Segurança</p><h2 id="student-security-heading">Senha</h2>
      <p>Altere sua senha sem modificar os dados da conta.</p>
      <a class="button button-secondary" href="/aluno/senha.php?next=%2Faluno%2Fperfil.php">Alterar senha</a>
    </section>
    <section class="student-profile-setting" aria-labelledby="student-session-heading">
      <p class="student-kicker">Acesso</p><h2 id="student-session-heading">Sessão</h2>
      <p>Encerre o acesso neste navegador. Seus dados continuam salvos.</p>
      <form method="post" action="/aluno/logout.php">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-logout'))?>">
        <button class="button button-secondary" type="submit">Sair da conta</button>
      </form>
    </section>
  </div>
</div>
<?php
}
