<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';require __DIR__.'/../app/admin_shell.php';security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity'];if(!$activity){header('Location: /admin/activities.php');exit;}$activityId=(int)$activity['id'];cms_forms_seed($db,$activityId);
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('cms-forms',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $action=(string)($_POST['action']??'');
    try{
        if($action==='create'){$form=cms_form_create($db,$activityId,(string)($_POST['locale']??PUBLIC_LOCALE_PT_BR),(string)($_POST['title']??'Novo formulário'),(string)($_POST['form_key']??'form'),(string)($_POST['kind']??'interest'));header('Location: /admin/forms.php?activity='.$activityId.'&form='.(int)$form['id']);exit;}
        if($action==='archive'){cms_form_archive($db,(int)($_POST['form_id']??0));}
    }catch(RuntimeException $error){$_SESSION['admin_notice']=$error->getMessage();}
    header('Location: /admin/forms.php?activity='.$activityId);exit;
}
$forms=cms_forms($db,$activityId,null,false);$selectedId=(int)($_GET['form']??0);$selected=$selectedId?cms_form_by_id($db,$selectedId):null;$notice=$_SESSION['admin_notice']??null;unset($_SESSION['admin_notice']);
admin_shell_start('forms','Formulários',$state);?>
<?php if($notice):?><div class="admin-notice"><?=h((string)$notice)?></div><?php endif;?>
<section class="overview-hero"><div><p class="admin-kicker">Formulários próprios</p><h2>Inscrições e pesquisas dentro do site</h2><p>Os formulários têm campos, mensagens e publicação independentes. As respostas ficam armazenadas no próprio sistema e podem ser exportadas.</p></div><div class="hero-actions"><button class="admin-button" type="button" data-dialog-open="new-form">Novo formulário</button></div></section>
<section class="overview-grid"><?php foreach($forms as $form):$unpublished=$form['published_revision']===null||(int)$form['draft_revision']!==(int)$form['published_revision'];?><article class="overview-card"><p class="admin-kicker"><?=h(public_language_label($form['locale']))?> · <?=h($form['form_key'])?></p><h2><?=h($form['title'])?></h2><p><?= $unpublished?'Há alterações não publicadas.':'Publicado e atualizado.' ?></p><a href="/admin/forms.php?activity=<?=$activityId?>&form=<?=(int)$form['id']?>">Editar campos</a><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-forms'))?>"><input type="hidden" name="form_id" value="<?=(int)$form['id']?>"><button class="link-button danger" name="action" value="archive" data-confirm="Arquivar este formulário?">Arquivar</button></form></article><?php endforeach;?></section>
<?php if($selected&&$selected['status']!=='archived'):?><section class="admin-editor-card"><div class="admin-editor-header"><div><p class="admin-kicker">Editor de formulário</p><h2><?=h($selected['title'])?></h2></div><p>As alterações ficam em rascunho até serem publicadas.</p></div><div id="form-admin-root" data-form-id="<?=(int)$selected['id']?>"></div></section><script src="/assets/forms-admin.js"></script><?php endif;?>
<dialog id="new-form" class="admin-dialog"><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-forms'))?>"><input type="hidden" name="action" value="create"><header><p class="admin-kicker">Novo formulário</p><h2>Criar formulário</h2></header><label>Título<input name="title" required></label><label>Chave<input name="form_key" placeholder="ex.: inscricao" required></label><label>Idioma<select name="locale"><option value="pt-BR">Português (Brasil)</option><option value="en">English</option></select></label><label>Modelo inicial<select name="kind"><option value="registration">Inscrição</option><option value="interest">Pesquisa de interesse</option></select></label><div class="dialog-actions"><button type="button" data-dialog-close>Cancelar</button><button class="admin-button" type="submit">Criar</button></div></form></dialog>
<?php admin_shell_end();
