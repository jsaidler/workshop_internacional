<?php
declare(strict_types=1);
/**
 * GrapesJS/MJML experimental editor. Read-only with respect to CMS data.
 * There is deliberately no save or send endpoint on this route.
 */
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();
require_admin();
header('Cache-Control: private, no-store, max-age=0');
$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];
if(!$activity){header('Location: /admin/activities.php',true,303);exit;}
$activityId=(int)$activity['id'];
admin_shell_start('communications','Construtor de e-mails — laboratório',$state,['/assets/communications-builder-lab.css']);
?>
<div class="communications-email-lab" data-communications-email-lab data-media-endpoint="/admin/communications-media.php">
  <div class="email-lab-heading">
    <div>
      <p class="admin-kicker">Laboratório de composição</p>
      <h1>Construtor visual de e-mails</h1>
      <p>Arraste blocos, reorganize colunas e edite diretamente no quadro. Este laboratório não salva nem envia mensagens.</p>
    </div>
    <a class="admin-button secondary" href="/admin/communications.php?<?=h(http_build_query(['activity'=>$activityId,'tab'=>'campaigns']))?>">Voltar às mensagens</a>
  </div>
  <div class="email-lab-tools" role="toolbar" aria-label="Ferramentas do construtor">
    <button type="button" data-lab-add-text>Inserir texto</button>
    <button type="button" data-lab-add-columns>Adicionar duas colunas</button>
    <button type="button" data-lab-undo>Desfazer</button>
    <button type="button" data-lab-redo>Refazer</button>
    <button type="button" data-lab-desktop>Computador</button>
    <button type="button" data-lab-mobile>Celular</button>
    <button type="button" data-lab-inspector-toggle aria-pressed="false" class="email-lab-inspector-toggle">Estilos</button>
    <button type="button" data-lab-reopen>Testar reabertura</button>
    <button type="button" data-lab-export class="email-lab-primary">Gerar prévia</button>
  </div>
  <p role="status" aria-live="polite" data-lab-status class="email-lab-status">Inicializando o construtor…</p>
  <div class="email-lab-workspace">
    <section aria-label="Editor do e-mail" class="email-lab-canvas-panel">
      <div data-lab-canvas id="communications-builder-stage"></div>
    </section>
    <aside class="email-lab-side" data-lab-side>
      <div class="email-lab-side-tabs" role="tablist" aria-label="Ferramentas de composição">
        <button type="button" role="tab" aria-selected="true" data-lab-tool="blocks">Blocos</button>
        <button type="button" role="tab" aria-selected="false" data-lab-tool="styles">Estilos</button>
      </div>
      <section data-lab-blocks-panel class="email-lab-manager" aria-label="Blocos de e-mail">
        <h2>Montar mensagem</h2>
        <p>Arraste um bloco para a posição desejada no e-mail.</p>
        <div data-lab-blocks></div>
      </section>
      <section data-lab-styles-panel class="email-lab-manager" aria-label="Estilos do bloco selecionado" hidden>
        <h2>Personalizar bloco</h2>
        <p>Selecione um elemento no quadro para ajustar suas propriedades.</p>
        <div data-lab-styles></div>
      </section>
      <hr>
      <h2>Imagem da Biblioteca</h2>
      <p>Somente imagens públicas do site. A versão final verificará novamente as permissões no servidor.</p>
      <label>Imagem
        <select data-lab-media><option value="">Escolha na Biblioteca</option></select>
      </label>
      <label>Descrição da imagem
        <input type="text" data-lab-media-alt maxlength="250" placeholder="Texto alternativo">
      </label>
      <button type="button" data-lab-add-media>Inserir imagem selecionada</button>
      <p class="email-lab-media-status" data-lab-media-status role="status"></p>
      <hr>
      <h2>Prévia compilada</h2>
      <p>HTML gerado pelo compilador MJML, não uma simulação da aparência final.</p>
      <iframe data-lab-preview title="Prévia compilada do e-mail" sandbox="" referrerpolicy="no-referrer"></iframe>
      <details>
        <summary>Examinar MJML editável</summary>
        <textarea data-lab-source readonly spellcheck="false" aria-label="Código MJML gerado" rows="9"></textarea>
      </details>
    </aside>
  </div>
</div>
<link rel="stylesheet" href="/assets/vendor/email-builder/grapes.min.css">
<script src="/assets/vendor/email-builder/lab.js" defer></script>
<?php admin_shell_end();
