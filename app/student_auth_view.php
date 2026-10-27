<?php
declare(strict_types=1);
/** Operational form markup shared with browser fixtures; auth and CSRF validation remain in routes. */
function student_auth_render_login(string $error,string $notice,string $mode,string $next): void {
?>
<div class="student-auth-page">
  <header class="student-auth-intro">
    <p class="student-kicker">Área do aluno</p>
    <h1 class="student-title">Acesso</h1>
    <p class="student-lead student-lead-compact">Entre com sua conta. Se este é o primeiro acesso, ative-a com os mesmos dados usados na inscrição.</p>
  </header>

  <div class="student-auth-grid">
    <section class="student-auth-section student-auth-primary" aria-labelledby="student-login-title">
      <p class="student-kicker">Conta ativada</p>
      <h2 id="student-login-title" class="student-subtitle">Entrar</h2>
      <?php if($error&&$mode!=='activate'):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
      <?php if($notice):?><p class="ui-alert ui-alert-notice"><?=h($notice)?></p><?php endif;?>
      <form method="post" class="student-auth-form" data-ui-validate>
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-login'))?>"><input type="hidden" name="mode" value="login"><input type="hidden" name="next" value="<?=h($next)?>">
        <label class="form-field">E-mail<input name="email" type="email" autocomplete="username" required></label>
        <label class="form-field">Senha<input name="password" type="password" autocomplete="current-password" required></label>
        <div class="student-auth-actions"><button class="button button-primary" type="submit">Entrar</button></div>
      </form>
    </section>

    <section class="student-auth-section student-auth-secondary" aria-labelledby="student-activation-title">
      <p class="student-kicker">Primeiro acesso</p>
      <h2 id="student-activation-title" class="student-subtitle">Ativar conta</h2>
      <p class="student-auth-copy">Depois que a matrícula for confirmada, use o mesmo e-mail da inscrição e o CPF somente com números. O CPF é usado apenas neste reconhecimento inicial; em seguida você cria sua própria senha.</p>
      <?php if($error&&$mode==='activate'):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
      <form method="post" class="student-auth-form" data-ui-validate>
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-login'))?>"><input type="hidden" name="mode" value="activate"><input type="hidden" name="next" value="<?=h($next)?>">
        <label class="form-field">E-mail da inscrição<input name="email" type="email" autocomplete="email" required></label>
        <label class="form-field">CPF<input name="cpf" inputmode="numeric" autocomplete="off" pattern="[0-9. -]{11,14}" required></label>
        <div class="student-auth-actions"><button class="button button-secondary" type="submit">Ativar conta</button></div>
      </form>
    </section>
  </div>
</div>
<?php
}
function student_auth_render_password(bool $first,string $error,string $next): void {
?>
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
<?php
}
