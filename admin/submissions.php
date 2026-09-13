<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();
require_admin();

$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];
if(!$activity){header('Location: /admin/activities.php');exit;}
$activityId=(int)$activity['id'];
cms_forms_seed($db,$activityId);

$statusLabels=['new'=>'Novo','contacted'=>'Em contato','converted'=>'Inscrito','archived'=>'Arquivado'];
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('cms-submissions',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $id=(int)($_POST['submission_id']??0);
    $status=(string)($_POST['status']??'new');
    if(!isset($statusLabels[$status]))$status='new';
    $notes=trim((string)($_POST['notes']??''));
    $q=$db->prepare('UPDATE cms_form_submissions SET status=?,notes=?,updated_at=? WHERE id=? AND activity_id=?');
    $q->execute([$status,$notes,utc_now(),$id,$activityId]);
    $returnStatus=(string)($_POST['filter_status']??'');
    $returnForm=(int)($_POST['filter_form']??0);
    $url='/admin/submissions.php?activity='.$activityId.'&submission='.$id;
    if(isset($statusLabels[$returnStatus]))$url.='&status='.rawurlencode($returnStatus);
    if($returnForm)$url.='&form='.$returnForm;
    header('Location: '.$url);exit;
}

$formFilter=(int)($_GET['form']??0);
$statusFilter=(string)($_GET['status']??'');
$sql='SELECT s.*,f.title form_title,f.form_key,f.locale form_locale,p.title page_title FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id LEFT JOIN cms_pages p ON p.id=s.page_id WHERE s.activity_id=?';
$args=[$activityId];
if($formFilter){$sql.=' AND s.form_id=?';$args[]=$formFilter;}
if(isset($statusLabels[$statusFilter])){$sql.=' AND s.status=?';$args[]=$statusFilter;}
$sql.=" ORDER BY CASE s.status WHEN 'new' THEN 0 WHEN 'contacted' THEN 1 WHEN 'converted' THEN 2 ELSE 3 END, s.created_at DESC LIMIT 250";
$q=$db->prepare($sql);$q->execute($args);$rows=$q->fetchAll();
$forms=cms_forms($db,$activityId);

$selectedId=(int)($_GET['submission']??0);
if(!$selectedId&&$rows)$selectedId=(int)$rows[0]['id'];
$selected=null;
if($selectedId){
    $q=$db->prepare('SELECT s.*,f.title form_title,f.form_key FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? AND s.activity_id=?');
    $q->execute([$selectedId,$activityId]);
    $selected=$q->fetch()?:null;
}
$legacyQ=$db->prepare('SELECT COUNT(*) FROM interest_submissions WHERE activity_id=?');$legacyQ->execute([$activityId]);$legacy=(int)$legacyQ->fetchColumn();
$countQ=$db->prepare('SELECT status,COUNT(*) n FROM cms_form_submissions WHERE activity_id=? GROUP BY status');$countQ->execute([$activityId]);$statusCounts=['new'=>0,'contacted'=>0,'converted'=>0,'archived'=>0];foreach($countQ->fetchAll() as $row)if(isset($statusCounts[$row['status']]))$statusCounts[$row['status']]=(int)$row['n'];

function cms_submission_payload(array $row): array {return json_decode((string)($row['payload_json']??''),true)?:[];}
function cms_submission_summary(array $row): string {
    $payload=cms_submission_payload($row);
    foreach(['full_name','name','email','whatsapp','phone'] as $key){$value=$payload[$key]??null;if(is_string($value)&&trim($value)!=='')return trim($value);}
    return 'Envio '.(string)$row['submission_uuid'];
}
function cms_submission_secondary(array $row): string {
    $payload=cms_submission_payload($row);
    foreach(['email','whatsapp','phone','city_state','city'] as $key){$value=$payload[$key]??null;if(is_string($value)&&trim($value)!=='')return trim($value);}
    return (string)$row['form_title'];
}
function cms_submission_status_label(string $status,array $labels): string {return $labels[$status]??$status;}
function cms_submission_filter_url(int $activityId,string $status,int $formFilter): string {$url='/admin/submissions.php?activity='.$activityId;if($status!=='')$url.='&status='.rawurlencode($status);if($formFilter)$url.='&form='.$formFilter;return $url;}
function cms_submission_row_url(int $activityId,int $id,string $statusFilter,int $formFilter): string {$url='/admin/submissions.php?activity='.$activityId.'&submission='.$id;if($statusFilter!=='')$url.='&status='.rawurlencode($statusFilter);if($formFilter)$url.='&form='.$formFilter;return $url;}

admin_shell_start('responses','Inscrições',$state);?>
<section class="inbox-toolbar">
    <div><h2>Inscrições</h2><p><?=$statusCounts['new']?> nova<?=$statusCounts['new']===1?'':'s'?> · <?=array_sum($statusCounts)?> no total</p></div>
    <div class="inbox-toolbar-actions"><a href="/admin/forms.php?activity=<?=$activityId?>">Editar formulário</a><a href="/admin/submissions-export.php?activity=<?=$activityId?><?=$formFilter?'&form='.$formFilter:''?>">Exportar CSV</a></div>
</section>
<nav class="inbox-status-tabs" aria-label="Filtrar inscrições por estado">
    <a href="<?=h(cms_submission_filter_url($activityId,'',$formFilter))?>"<?=$statusFilter===''?' aria-current="page"':''?>>Todas <span><?=array_sum($statusCounts)?></span></a>
    <?php foreach($statusLabels as $value=>$label):?><a href="<?=h(cms_submission_filter_url($activityId,$value,$formFilter))?>"<?=$statusFilter===$value?' aria-current="page"':''?>><?=h($label)?> <span><?=$statusCounts[$value]?></span></a><?php endforeach;?>
</nav>
<?php if(count($forms)>1):?><form class="inbox-form-filter" method="get"><input type="hidden" name="activity" value="<?=$activityId?>"><?php if($statusFilter!==''):?><input type="hidden" name="status" value="<?=h($statusFilter)?>"><?php endif;?><label>Formulário <select name="form" onchange="this.form.submit()"><option value="0">Todos</option><?php foreach($forms as $form):?><option value="<?=(int)$form['id']?>"<?=$formFilter===(int)$form['id']?' selected':''?>><?=h($form['title'].' · '.public_language_label($form['locale']))?></option><?php endforeach;?></select></label></form><?php endif;?>
<div class="submissions-layout inbox-layout">
    <section class="submission-list inbox-list" aria-label="Lista de inscrições">
        <?php if(!$rows):?><div class="admin-empty compact"><h2>Nenhuma inscrição aqui</h2><p>Escolha outro filtro ou aguarde novas respostas.</p></div><?php endif;?>
        <?php foreach($rows as $row):?><a class="submission-row<?=$selectedId===(int)$row['id']?' selected':''?><?=$row['status']==='new'?' is-new':''?>" href="<?=h(cms_submission_row_url($activityId,(int)$row['id'],$statusFilter,$formFilter))?>"><div class="submission-row-main"><strong><?=h(cms_submission_summary($row))?></strong><span><?=h(cms_submission_secondary($row))?></span></div><div class="submission-row-meta"><time><?=h(date('d/m · H:i',strtotime($row['created_at'])))?></time><small data-status="<?=h($row['status'])?>"><?=h(cms_submission_status_label((string)$row['status'],$statusLabels))?></small></div></a><?php endforeach;?>
    </section>
    <?php if($selected):$payload=cms_submission_payload($selected);$snapshot=json_decode((string)$selected['form_snapshot_json'],true)?:[];$labels=[];foreach($snapshot['fields']??[] as $field)$labels[$field['id']]=$field['label'];?>
    <aside class="submission-detail inbox-detail">
        <header class="inbox-detail-header"><div><p><?=h($selected['form_title'])?></p><h2><?=h(cms_submission_summary($selected))?></h2><span><?=h(date('d/m/Y · H:i',strtotime($selected['created_at'])))?></span></div></header>
        <dl class="inbox-fields"><?php foreach($payload as $key=>$value):?><div><dt><?=h($labels[$key]??$key)?></dt><dd><?=h(is_array($value)?implode(', ',$value):(string)$value)?></dd></div><?php endforeach;?></dl>
        <form method="post" class="inbox-followup"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-submissions'))?>"><input type="hidden" name="submission_id" value="<?=(int)$selected['id']?>"><input type="hidden" name="filter_status" value="<?=h($statusFilter)?>"><input type="hidden" name="filter_form" value="<?=$formFilter?>"><label>Notas<textarea name="notes" rows="4" placeholder="Anotações sobre contato, pagamento ou acompanhamento."><?=h($selected['notes'])?></textarea></label><div class="inbox-status-actions" aria-label="Alterar estado"><?php foreach($statusLabels as $value=>$label):?><button type="submit" name="status" value="<?=$value?>"<?=$selected['status']===$value?' aria-current="true"':''?>><?=h($label)?></button><?php endforeach;?></div></form>
    </aside><?php endif;?>
</div>
<?php if($legacy):?><p class="legacy-note">Existem <?= $legacy ?> respostas do formulário anterior preservadas. <a href="/admin/interests.php?activity=<?=$activityId?>">Abrir respostas legadas</a>.</p><?php endif;?>
<link rel="stylesheet" href="/assets/admin-inbox.css">
<?php admin_shell_end();
