<?php
declare(strict_types=1);
/**
 * Versioned MJML visual editor used by the canonical campaign renderer.
 * Saving invalidates approval; only an approved frozen render can be queued.
 */
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
require_once __DIR__.'/../app/communications_builder_drafts.php';
security_headers();
require_admin();
header('Cache-Control: private, no-store, max-age=0');
$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];
if(!$activity){header('Location: /admin/activities.php',true,303);exit;}
$activityId=(int)$activity['id'];
$campaignId=max(0,(int)($_GET['id']??0));
$campaign=$campaignId?communication_builder_campaign($db,$activityId,$campaignId):null;
admin_shell_start('communications','Construtor visual de e-mails',$state,['/assets/communications-builder-lab.css']);
?>
<div class="communications-email-lab" data-communications-email-lab data-media-endpoint="/admin/communications-media.php"
  data-validation-endpoint="/admin/communications-builder-validate.php" data-validation-token="<?=h(csrf_token('communications-admin'))?>"
  data-draft-endpoint="/admin/communications-builder-draft.php" data-campaign-id="<?=$campaignId?>" data-activity-id="<?=$activityId?>"
  data-server-preview-endpoint="/admin/communications-builder-preview.php"
  data-visual-test-endpoint="/admin/communications-builder-test.php"
  data-admin-email="<?=h((string)(current_admin()['email']??''))?>">
  <div class="email-lab-heading">
    <div>
      <p class="admin-kicker">Composição profissional</p>
      <h1>Construtor visual de e-mails</h1>
      <p><?=($campaign?'Composição visual da mensagem #'.$campaignId.' — '.h((string)$campaign['subject']).'. ':'')?>O layout salvo será renderizado pelo servidor, congelado na aprovação e usado no envio da campanha.</p>
    </div>
    <a class="admin-button secondary" href="/admin/communications.php?<?=h(http_build_query(['activity'=>$activityId,'tab'=>'campaigns','id'=>$campaignId]))?>">Voltar às mensagens</a>
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
    <button type="button" data-lab-validate>Validar segurança</button>
    <button type="button" data-lab-server-preview>Prévia do servidor</button>
    <button type="button" data-lab-send-test title="Envia somente para seu e-mail administrativo, nunca aos alunos">Enviar teste visual para meu e-mail</button>
    <button type="button" data-lab-save <?=$campaignId<1?'disabled':''?> title="Salva o layout e exige uma nova aprovação antes do envio">Salvar composição</button>
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
      <p>Somente imagens públicas do site. As permissões são verificadas novamente ao salvar e renderizar.</p>
      <label>Imagem
        <select data-lab-media><option value="">Escolha na Biblioteca</option></select>
      </label>
      <label>Descrição da imagem
        <input type="text" data-lab-media-alt maxlength="250" placeholder="Texto alternativo">
      </label>
      <button type="button" data-lab-add-media>Inserir imagem selecionada</button>
      <p class="email-lab-media-status" data-lab-media-status role="status"></p>
      <details>
        <summary>Examinar MJML editável</summary>
        <textarea data-lab-source readonly spellcheck="false" aria-label="Código MJML gerado" rows="9"></textarea>
      </details>
    </aside>
  </div>
  <section class="email-lab-comparison" aria-label="Comparação das prévias de e-mail">
    <div class="email-lab-comparison-heading">
      <h2>Comparação de renderização</h2>
      <p>As duas saídas usam o mesmo MJML. Compare proporções, tipografia, blocos e comportamento responsivo antes de liberar o envio.</p>
    </div>
    <div class="email-lab-comparison-grid">
      <div class="email-lab-comparison-item">
        <h3>MJML — compilador do editor</h3>
        <p>HTML gerado pelo compilador original no navegador.</p>
        <iframe data-lab-preview title="Prévia compilada do e-mail" sandbox="" referrerpolicy="no-referrer"></iframe>
      </div>
      <div class="email-lab-comparison-item">
        <h3>PHP — renderer canônico do servidor</h3>
        <p>HTML tabular validado e congelado no momento da aprovação.</p>
        <iframe data-lab-server-frame title="Prévia HTML produzida pelo servidor" sandbox="" referrerpolicy="no-referrer"></iframe>
      </div>
    </div>
  </section>
</div>
<link rel="stylesheet" href="/assets/vendor/email-builder/grapes.min.css">
<script src="/assets/vendor/email-builder/lab.js" defer></script>
<?php admin_shell_end();
