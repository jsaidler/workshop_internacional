<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';require __DIR__.'/../app/admin_shell.php';security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity'];if(!$activity){header('Location: /admin/activities.php');exit;}$activityId=(int)$activity['id'];cms_forms_seed($db,$activityId);
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('cms-forms',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $action=(string)($_POST['action']??'');$redirectForm=0;
    try{
        if($action==='create'){$form=cms_form_create($db,$activityId,(string)($_POST['locale']??PUBLIC_LOCALE_PT_BR),(string)($_POST['title']??'Novo formulário'),(string)($_POST['form_key']??'form'),(string)($_POST['kind']??'interest'));header('Location: /admin/forms.php?activity='.$activityId.'&form='.(int)$form['id']);exit;}
        if($action==='archive'){cms_form_archive($db,(int)($_POST['form_id']??0));}
        if($action==='save-workflow'){
            $redirectForm=(int)($_POST['form_id']??0);$form=cms_form_by_id($db,$redirectForm);if(!$form||(int)$form['activity_id']!==$activityId)throw new RuntimeException('form_not_found');
            $schema=cms_form_schema($form,false);$schema=cms_form_workflow_update($schema,$_POST);$form=cms_form_save($db,(int)$form['id'],$schema,(int)$form['draft_revision'],null);
            if(!empty($_POST['publish_workflow'])){$form=cms_form_publish($db,(int)$form['id']);$_SESSION['admin_notice']='Fluxo salvo e publicado.';}else $_SESSION['admin_notice']='Fluxo salvo no rascunho.';
        }
    }catch(RuntimeException $error){$_SESSION['admin_notice']=$error->getMessage();}
    header('Location: /admin/forms.php?activity='.$activityId.($redirectForm?'&form='.$redirectForm:''));exit;
}
$forms=cms_forms($db,$activityId,null,false);$selectedId=(int)($_GET['form']??0);$selected=$selectedId?cms_form_by_id($db,$selectedId):null;$notice=$_SESSION['admin_notice']??null;unset($_SESSION['admin_notice']);$workflow=$selected?cms_form_workflow(cms_form_schema($selected,false)):null;
admin_shell_start('forms','Formulários',$state);?>
<?php if($notice):?><div class="admin-notice"><?=h((string)$notice)?></div><?php endif;?>
<section class="overview-hero"><div><p class="admin-kicker">Formulários próprios</p><h2>Inscrições e pesquisas dentro do site</h2><p>Campos, mensagens, destino após envio e notificações podem ser administrados aqui. As respostas continuam armazenadas no próprio sistema e podem ser exportadas.</p></div><div class="hero-actions"><button class="admin-button" type="button" data-dialog-open="new-form">Novo formulário</button></div></section>
<section class="overview-grid"><?php foreach($forms as $form):$unpublished=$form['published_revision']===null||(int)$form['draft_revision']!==(int)$form['published_revision'];?><article class="overview-card"><p class="admin-kicker"><?=h(public_language_label($form['locale']))?> · <?=h($form['form_key'])?></p><h2><?=h($form['title'])?></h2><p><?= $unpublished?'Há alterações não publicadas.':'Publicado e atualizado.' ?></p><a href="/admin/forms.php?activity=<?=$activityId?>&form=<?=(int)$form['id']?>">Editar formulário</a><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-forms'))?>"><input type="hidden" name="form_id" value="<?=(int)$form['id']?>"><button class="link-button danger" name="action" value="archive" data-confirm="Arquivar este formulário?">Arquivar</button></form></article><?php endforeach;?></section>
<?php if($selected&&$selected['status']!=='archived'):?>
<section class="admin-editor-card"><div class="admin-editor-header"><div><p class="admin-kicker">Editor de formulário</p><h2><?=h($selected['title'])?></h2></div><p>As alterações ficam em rascunho até serem publicadas.</p></div><div id="form-admin-root" data-form-id="<?=(int)$selected['id']?>"></div></section><script src="/assets/forms-admin.js"></script>
<section class="admin-editor-card"><div class="admin-editor-header"><div><p class="admin-kicker">Fluxo após envio</p><h2>Confirmação e notificação</h2></div><p>Configure o que acontece depois que uma resposta válida é gravada.</p></div>
<form method="post" class="cms-settings-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-forms'))?>"><input type="hidden" name="action" value="save-workflow"><input type="hidden" name="form_id" value="<?=(int)$selected['id']?>">
<div class="form-grid two-columns"><label>Depois do envio<select name="success_mode"><option value="inline"<?=$workflow['successMode']==='inline'?' selected':''?>>Mostrar confirmação no próprio formulário</option><option value="redirect"<?=$workflow['successMode']==='redirect'?' selected':''?>>Redirecionar para outra página</option></select></label><label>Caminho de redirecionamento<input name="redirect_path" value="<?=h($workflow['redirectPath'])?>" placeholder="/obrigado/?lang=pt-br"></label></div>
<p class="admin-muted">O redirecionamento aceita somente um caminho interno começando por <code>/</code>. Isso evita que o formulário seja usado como redirecionador externo.</p>
<div class="form-grid two-columns"><label>E-mail para notificação<input type="email" name="notification_email" value="<?=h($workflow['notificationEmail'])?>" placeholder="voce@exemplo.com"></label><label>Assunto da notificação<input name="notification_subject" value="<?=h($workflow['notificationSubject'])?>" placeholder="Nova inscrição pelo site"></label></div>
<p class="admin-muted">Se um e-mail for informado, o sistema tenta enviar um resumo usando o serviço de e-mail do servidor. A resposta é salva mesmo se o envio da notificação falhar.</p>
<div class="dialog-actions"><button class="admin-button" type="submit">Salvar fluxo</button><label class="check-row"><input type="checkbox" name="publish_workflow" value="1"> Salvar e publicar imediatamente</label></div></form></section>
<?php endif;?>
<dialog id="new-form" class="admin-dialog"><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-forms'))?>"><input type="hidden" name="action" value="create"><header><p class="admin-kicker">Novo formulário</p><h2>Criar formulário</h2></header><label>Título<input name="title" required></label><label>Chave<input name="form_key" placeholder="ex.: inscricao" required></label><label>Idioma<select name="locale"><option value="pt-BR">Português (Brasil)</option><option value="en">English</option></select></label><label>Modelo inicial<select name="kind"><option value="registration">Inscrição</option><option value="interest">Pesquisa de interesse</option></select></label><div class="dialog-actions"><button type="button" data-dialog-close>Cancelar</button><button class="admin-button" type="submit">Criar</button></div></form></dialog>
<?php admin_shell_end();
