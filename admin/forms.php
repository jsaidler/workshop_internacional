<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();
require_admin();

function admin_forms_catalog_url(int $activityId,string $query='',string $locale='',int $page=1): string {
    $args=['activity'=>$activityId];
    if($query!=='')$args['q']=$query;
    if($locale!=='')$args['lang']=public_locale_query($locale);
    if($page>1)$args['page']=$page;
    return '/admin/forms.php?'.http_build_query($args);
}
function admin_form_purpose_label(array $form): string {
    return match(cms_form_purpose($form)){
        'interest'=>'Interesse',
        'registration'=>'Inscrição',
        'enrollment'=>'Inscrição que gera matrícula',
        default=>'Comum',
    };
}

$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];
if(!$activity){header('Location: /admin/activities.php');exit;}
$activityId=(int)$activity['id'];
$query=trim((string)($_GET['q']??$_POST['return_q']??''));
$rawLocale=trim((string)($_GET['lang']??$_POST['return_lang']??''));
$localeFilter=$rawLocale!==''?(normalize_public_locale($rawLocale)??''):'';
$page=max(1,(int)($_GET['page']??$_POST['return_page']??1));
$pageSize=25;
$selectedId=(int)($_GET['form']??$_POST['form_id']??0);

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('cms-forms',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $action=(string)($_POST['action']??'');
    $redirectForm=0;
    try{
        if($action==='create'){
            $form=cms_form_create($db,$activityId,(string)($_POST['locale']??PUBLIC_LOCALE_PT_BR),(string)($_POST['title']??'Novo formulário'),(string)($_POST['form_key']??'form'),(string)($_POST['kind']??'interest'));
            $form=cms_form_set_purpose($db,(int)$form['id'],(string)($_POST['purpose']??'common'));
            header('Location: /admin/forms.php?activity='.$activityId.'&form='.(int)$form['id']);exit;
        }
        if($action==='archive'){
            $redirectForm=(int)($_POST['form_id']??0);
            $form=cms_form_by_id($db,$redirectForm);
            if(!$form||(int)$form['activity_id']!==$activityId)throw new RuntimeException('form_not_found');
            cms_form_archive($db,$redirectForm);
            $_SESSION['admin_notice']='Formulário arquivado.';
            $redirectForm=0;
        }
        if($action==='save-purpose'){
            $redirectForm=(int)($_POST['form_id']??0);$form=cms_form_by_id($db,$redirectForm);
            if(!$form||(int)$form['activity_id']!==$activityId)throw new RuntimeException('form_not_found');
            cms_form_set_purpose($db,$redirectForm,(string)($_POST['purpose']??'common'));$_SESSION['admin_notice']='Finalidade salva.';
        }
        if($action==='save-workflow'){
            $redirectForm=(int)($_POST['form_id']??0);$form=cms_form_by_id($db,$redirectForm);
            if(!$form||(int)$form['activity_id']!==$activityId)throw new RuntimeException('form_not_found');
            $schema=cms_form_schema($form,false);$schema=cms_form_workflow_update($schema,$_POST);$form=cms_form_save($db,(int)$form['id'],$schema,(int)$form['draft_revision'],null);
            if(!empty($_POST['publish_workflow'])){$form=cms_form_publish($db,(int)$form['id']);$_SESSION['admin_notice']='Fluxo salvo e publicado.';}else $_SESSION['admin_notice']='Fluxo salvo no rascunho.';
        }
        if($action==='save-conditions'){
            $redirectForm=(int)($_POST['form_id']??0);$form=cms_form_by_id($db,$redirectForm);
            if(!$form||(int)$form['activity_id']!==$activityId)throw new RuntimeException('form_not_found');
            $schema=cms_form_schema($form,false);$schema=cms_form_conditions_update($schema,$_POST);$form=cms_form_save($db,(int)$form['id'],$schema,(int)$form['draft_revision'],null);
            if(!empty($_POST['publish_conditions'])){$form=cms_form_publish($db,(int)$form['id']);$_SESSION['admin_notice']='Condições salvas e publicadas.';}else $_SESSION['admin_notice']='Condições salvas no rascunho.';
        }
    }catch(RuntimeException $error){$_SESSION['admin_notice']=$error->getMessage();}
    if($redirectForm){
        $args=['activity'=>$activityId,'form'=>$redirectForm];
        if($query!=='')$args['q']=$query;if($localeFilter!=='')$args['lang']=public_locale_query($localeFilter);if($page>1)$args['page']=$page;
        header('Location: /admin/forms.php?'.http_build_query($args));exit;
    }
    header('Location: '.admin_forms_catalog_url($activityId,$query,$localeFilter,$page));exit;
}

$selected=$selectedId?cms_form_by_id($db,$selectedId):null;
if($selected&&((int)$selected['activity_id']!==$activityId||(string)$selected['status']==='archived'))$selected=null;
$notice=$_SESSION['admin_notice']??null;unset($_SESSION['admin_notice']);

$where=["f.activity_id=?","f.status!='archived'"];$args=[$activityId];
if($query!==''){$where[]='(LOWER(f.title) LIKE ? OR LOWER(f.form_key) LIKE ?)';$needle='%'.mb_strtolower($query).'%';$args[]=$needle;$args[]=$needle;}
if($localeFilter!==''){$where[]='f.locale=?';$args[]=$localeFilter;}
$whereSql=implode(' AND ',$where);
$countQ=$db->prepare('SELECT COUNT(*) FROM cms_forms f WHERE '.$whereSql);$countQ->execute($args);$total=(int)$countQ->fetchColumn();
$totalPages=max(1,(int)ceil($total/$pageSize));if($page>$totalPages)$page=$totalPages;$offset=($page-1)*$pageSize;
$sql="SELECT f.*,COUNT(s.id) response_count FROM cms_forms f LEFT JOIN cms_form_submissions s ON s.form_id=f.id WHERE {$whereSql} GROUP BY f.id ORDER BY f.locale,f.title,f.id LIMIT {$pageSize} OFFSET {$offset}";
$q=$db->prepare($sql);$q->execute($args);$forms=$q->fetchAll();

$draftSchema=$selected?cms_form_schema($selected,false):null;
$workflow=$draftSchema?cms_form_workflow($draftSchema):null;
$conditions=$draftSchema?cms_form_conditions($draftSchema):[];
$conditionFields=is_array($draftSchema['fields']??null)?$draftSchema['fields']:[];
$returnUrl=admin_forms_catalog_url($activityId,$query,$localeFilter,$page);
$clearUrl=admin_forms_catalog_url($activityId);

admin_shell_start('forms','Formulários',$state,['/assets/admin-site.css','/assets/admin-forms.css']);?>
<?php if($notice):?><div class="admin-notice"><?=h((string)$notice)?></div><?php endif;?>

<?php if(!$selected):?>
<section class="site-intro"><p class="site-intro-copy">Crie e mantenha formulários do site. A finalidade define o papel operacional; campos, lógica condicional e comportamento após o envio ficam no detalhe de cada formulário.</p><div class="site-intro-actions"><button class="admin-button" type="button" data-dialog-open="new-form">Novo formulário</button></div></section>
<form class="admin-data-toolbar" method="get"><input type="hidden" name="activity" value="<?=$activityId?>"><label class="grow">Buscar formulário<input type="search" name="q" value="<?=h($query)?>" placeholder="Título ou chave"></label><label>Idioma<select name="lang"><option value="">Todos</option><option value="pt-br"<?=$localeFilter===PUBLIC_LOCALE_PT_BR?' selected':''?>>Português</option><option value="en"<?=$localeFilter===PUBLIC_LOCALE_EN?' selected':''?>>English</option></select></label><button class="admin-button secondary" type="submit">Filtrar</button><?php if($query!==''||$localeFilter!==''):?><a class="link-button" href="<?=h($clearUrl)?>">Limpar filtros</a><?php endif;?></form>
<div class="site-summary"><span><strong><?=admin_quantity_label($total,'formulário','formulários')?></strong></span><span>Página <?=$page?> de <?=$totalPages?></span></div>
<?php if(!$forms):?><section class="admin-empty"><h2>Nenhum formulário encontrado</h2><p>Ajuste os filtros ou crie um novo formulário.</p></section><?php else:?>
<section class="site-catalog" aria-label="Formulários"><div class="site-catalog-head"><div>Formulário</div><div>Idioma</div><div>Finalidade</div><div>Respostas</div><div>Ações</div></div>
<?php foreach($forms as $form):$unpublished=$form['published_revision']===null||(int)$form['draft_revision']!==(int)$form['published_revision'];$openArgs=['activity'=>$activityId,'form'=>(int)$form['id']];if($query!=='')$openArgs['q']=$query;if($localeFilter!=='')$openArgs['lang']=public_locale_query($localeFilter);if($page>1)$openArgs['page']=$page;?>
<article class="site-catalog-row"><div class="site-catalog-primary"><strong><?=h((string)$form['title'])?></strong><span><?=h((string)$form['form_key'])?></span></div><div class="site-catalog-cell"><?=h(public_language_label((string)$form['locale']))?></div><div class="site-catalog-cell"><?=h(admin_form_purpose_label($form))?></div><div class="site-catalog-cell"><?=(int)$form['response_count']?> · <?=admin_badge($unpublished?'Alterações':'Publicado',$unpublished?'attention':'good')?></div><div class="site-catalog-actions"><a class="admin-button secondary admin-table-action" href="/admin/forms.php?<?=h(http_build_query($openArgs))?>">Abrir</a></div></article>
<?php endforeach;?></section>
<?php if($totalPages>1):?><div class="admin-pagination"><span>Exibindo <?=min($total,$offset+1)?>–<?=min($total,$offset+$pageSize)?> de <?=$total?></span><nav aria-label="Paginação dos formulários"><?php for($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++):?><a href="<?=h(admin_forms_catalog_url($activityId,$query,$localeFilter,$p))?>"<?=$p===$page?' aria-current="page"':''?>><?=$p?></a><?php endfor;?></nav></div><?php endif;?>
<?php endif;?>
<?php else:
$unpublished=$selected['published_revision']===null||(int)$selected['draft_revision']!==(int)$selected['published_revision'];?>
<section class="site-context-bar"><div><span>Formulário</span><strong><?=h((string)$selected['title'])?></strong></div><div class="site-context-actions"><?=admin_badge($unpublished?'Alterações não publicadas':'Publicado',$unpublished?'attention':'good')?><a class="link-button" href="<?=h($returnUrl)?>">← Todos os formulários</a></div></section>
<div class="site-detail-layout"><nav class="site-detail-index" aria-label="Seções do formulário"><a href="#form-purpose">Finalidade</a><a href="#form-fields">Campos</a><a href="#form-conditions">Condições</a><a href="#form-workflow">Após o envio</a></nav><div class="site-detail-main site-settings-stack">
<section class="site-settings-section" id="form-purpose"><header><div><h2>Finalidade</h2><p>A chave identifica o formulário; a finalidade determina em qual fluxo administrativo suas respostas entram.</p></div></header><form method="post" class="cms-settings-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-forms'))?>"><input type="hidden" name="action" value="save-purpose"><input type="hidden" name="form_id" value="<?=(int)$selected['id']?>"><input type="hidden" name="return_q" value="<?=h($query)?>"><input type="hidden" name="return_lang" value="<?=h($localeFilter)?>"><input type="hidden" name="return_page" value="<?=$page?>"><div class="form-grid two-columns"><label>Finalidade<select name="purpose"><?php foreach(['common'=>'Comum','interest'=>'Interesse','registration'=>'Inscrição','enrollment'=>'Inscrição que gera matrícula'] as $value=>$label):?><option value="<?=h($value)?>"<?=cms_form_purpose($selected)===$value?' selected':''?>><?=h($label)?></option><?php endforeach;?></select></label><p class="admin-muted"><strong>Inscrição que gera matrícula</strong> é a única finalidade que participa do lifecycle de conta, turma e acesso do aluno.</p></div><div class="dialog-actions"><button class="admin-button" type="submit">Salvar finalidade</button></div></form></section>
<section class="site-settings-section" id="form-fields"><header><div><h2>Campos e mensagens</h2><p>Edite a estrutura apresentada ao visitante. As alterações permanecem em rascunho até a publicação.</p></div></header><div class="site-form-editor" id="form-admin-root" data-form-id="<?=(int)$selected['id']?>"></div></section>
<section class="site-settings-section" id="form-conditions"><header><div><h2>Lógica condicional</h2><p>Mostre campos apenas quando uma resposta anterior tornar aquele dado relevante.</p></div></header><form method="post" class="cms-settings-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-forms'))?>"><input type="hidden" name="action" value="save-conditions"><input type="hidden" name="form_id" value="<?=(int)$selected['id']?>"><input type="hidden" name="return_q" value="<?=h($query)?>"><input type="hidden" name="return_lang" value="<?=h($localeFilter)?>"><input type="hidden" name="return_page" value="<?=$page?>"><div class="conditions-builder"><div class="conditions-head"><strong>Campo</strong><strong>Mostrar quando</strong><strong>Operador</strong><strong>Valor</strong></div><?php foreach($conditionFields as $target):$targetId=(string)($target['id']??'');if($targetId==='')continue;$rule=$conditions[$targetId]??['source'=>'','operator'=>'equals','value'=>''];?><div class="condition-row"><div><strong><?=h((string)($target['label']??$targetId))?></strong><small><?=h($targetId)?></small></div><label><span class="sr-only">Campo de origem</span><select name="condition_source[<?=h($targetId)?>]"><option value="">Sempre mostrar</option><?php foreach($conditionFields as $source):$sourceId=(string)($source['id']??'');if($sourceId===''||$sourceId===$targetId)continue;?><option value="<?=h($sourceId)?>"<?=$rule['source']===$sourceId?' selected':''?>><?=h((string)($source['label']??$sourceId))?></option><?php endforeach;?></select></label><label><span class="sr-only">Operador</span><select name="condition_operator[<?=h($targetId)?>]"><?php foreach(['equals'=>'é igual a','not_equals'=>'é diferente de','contains'=>'contém','checked'=>'está marcado / preenchido','not_checked'=>'não está marcado / está vazio'] as $op=>$label):?><option value="<?=$op?>"<?=$rule['operator']===$op?' selected':''?>><?=h($label)?></option><?php endforeach;?></select></label><label><span class="sr-only">Valor esperado</span><input name="condition_value[<?=h($targetId)?>]" value="<?=h((string)$rule['value'])?>" placeholder="valor/opção"></label></div><?php endforeach;?></div><p class="admin-muted">Para listas, rádio e múltipla escolha, use o valor interno da opção. “Marcado / preenchido” funciona para checkbox, aceite e campos em que basta existir uma resposta.</p><div class="dialog-actions"><button class="admin-button" type="submit">Salvar condições</button><label class="check-row"><input type="checkbox" name="publish_conditions" value="1"> Salvar e publicar imediatamente</label></div></form></section>
<section class="site-settings-section" id="form-workflow"><header><div><h2>Após o envio</h2><p>Defina confirmação, eventual redirecionamento e notificação por e-mail.</p></div></header><form method="post" class="cms-settings-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-forms'))?>"><input type="hidden" name="action" value="save-workflow"><input type="hidden" name="form_id" value="<?=(int)$selected['id']?>"><input type="hidden" name="return_q" value="<?=h($query)?>"><input type="hidden" name="return_lang" value="<?=h($localeFilter)?>"><input type="hidden" name="return_page" value="<?=$page?>"><div class="form-grid two-columns"><label>Depois do envio<select name="success_mode"><option value="inline"<?=$workflow['successMode']==='inline'?' selected':''?>>Mostrar confirmação no próprio formulário</option><option value="redirect"<?=$workflow['successMode']==='redirect'?' selected':''?>>Redirecionar para outra página</option></select></label><label>Caminho de redirecionamento<input name="redirect_path" value="<?=h($workflow['redirectPath'])?>" placeholder="/obrigado/"></label></div><p class="admin-muted">O redirecionamento aceita somente um caminho interno começando por <code>/</code>.</p><div class="form-grid two-columns"><label>E-mail para notificação<input type="email" name="notification_email" value="<?=h($workflow['notificationEmail'])?>" placeholder="voce@exemplo.com"></label><label>Assunto da notificação<input name="notification_subject" value="<?=h($workflow['notificationSubject'])?>" placeholder="Nova resposta pelo site"></label></div><p class="admin-muted">A resposta é salva mesmo se o envio da notificação falhar.</p><div class="dialog-actions"><button class="admin-button" type="submit">Salvar fluxo</button><label class="check-row"><input type="checkbox" name="publish_workflow" value="1"> Salvar e publicar imediatamente</label></div></form></section>
<section class="site-settings-section"><header><div><h2>Arquivamento</h2><p>Arquivar remove o formulário das superfícies ativas sem apagar respostas já armazenadas.</p></div></header><form method="post" data-confirm="Arquivar este formulário?"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-forms'))?>"><input type="hidden" name="form_id" value="<?=(int)$selected['id']?>"><input type="hidden" name="return_q" value="<?=h($query)?>"><input type="hidden" name="return_lang" value="<?=h($localeFilter)?>"><input type="hidden" name="return_page" value="<?=$page?>"><button class="admin-button danger" name="action" value="archive">Arquivar formulário</button></form></section>
</div></div>
<script src="<?=h(admin_asset_url('/assets/forms-admin.js'))?>"></script>
<?php endif;?>

<dialog id="new-form" class="admin-dialog"><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-forms'))?>"><input type="hidden" name="action" value="create"><header><h2>Criar formulário</h2></header><label>Título<input name="title" required></label><label>Chave<input name="form_key" placeholder="ex.: inscricao" required></label><label>Idioma<select name="locale"><option value="pt-BR">Português (Brasil)</option><option value="en">English</option></select></label><label>Modelo inicial<select name="kind"><option value="registration">Inscrição</option><option value="interest">Pesquisa de interesse</option></select></label><label>Finalidade<select name="purpose"><option value="common">Comum</option><option value="interest">Interesse</option><option value="registration">Inscrição</option><option value="enrollment">Inscrição que gera matrícula</option></select></label><div class="dialog-actions"><button type="button" data-dialog-close>Cancelar</button><button class="admin-button" type="submit">Criar</button></div></form></dialog>
<?php admin_shell_end();
