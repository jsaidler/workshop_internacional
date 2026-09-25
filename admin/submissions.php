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

function submission_payload(array $row): array {return json_decode((string)($row['payload_json']??''),true)?:[];}
function submission_snapshot(array $row): array {return json_decode((string)($row['form_snapshot_json']??''),true)?:[];}
function submission_fields(array $row): array {$map=[];foreach(submission_snapshot($row)['fields']??[] as $field)if(is_array($field)&&isset($field['id']))$map[(string)$field['id']]=$field;return $map;}
function submission_name(array $row): string {$p=submission_payload($row);foreach(['name','full_name','email','phone'] as $key){$v=trim((string)($p[$key]??''));if($v!=='')return $v;}return 'Inscrição '.(string)$row['submission_uuid'];}
function submission_secondary(array $row): string {$p=submission_payload($row);foreach(['phone','email','city_state','city'] as $key){$v=trim((string)($p[$key]??''));if($v!=='')return $v;}return (string)$row['form_title'];}
function submission_value_label(array $field,mixed $value): string {
    if(is_array($value))return implode(', ',array_map(fn($item)=>submission_value_label($field,(string)$item),$value));
    $raw=(string)$value;foreach($field['options']??[] as $option)if(is_array($option)&&(string)($option['value']??'')===$raw)return (string)($option['label']??$raw);return $raw;
}
function submission_display_value(string $key,array $field,mixed $value): string {
    $display=submission_value_label($field,$value);
    if(is_array($value))return $display;
    $digits=preg_replace('/\D+/','',(string)$value)??'';
    if($key==='cpf'&&strlen($digits)===11)return substr($digits,0,3).'.'.substr($digits,3,3).'.'.substr($digits,6,3).'-'.substr($digits,9,2);
    if($key==='postal_code'&&strlen($digits)===8)return substr($digits,0,5).'-'.substr($digits,5,3);
    if($key==='phone'&&strlen($digits)===11)return '('.substr($digits,0,2).') '.substr($digits,2,5).'-'.substr($digits,7,4);
    if($key==='phone'&&strlen($digits)===10)return '('.substr($digits,0,2).') '.substr($digits,2,4).'-'.substr($digits,6,4);
    return $display;
}
function submission_url(int $activityId,int $id=0,string $status='',int $form=0): string {$url='/admin/submissions.php?activity='.$activityId;if($id)$url.='&submission='.$id;if($status!=='')$url.='&status='.rawurlencode($status);if($form)$url.='&form='.$form;return $url;}
function registration_status(array $row): string {if(($row['status']??'')==='archived')return 'Cancelada';if(($row['payment_status']??'pending')==='paid'||($row['status']??'')==='converted')return 'Confirmada';return 'Aguardando pagamento';}
function registration_payment_label(array $row): string {$p=submission_payload($row);$f=submission_fields($row);return submission_value_label($f['payment_method']??[],(string)($p['payment_method']??''));}

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('cms-submissions',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $id=(int)($_POST['submission_id']??0);$action=(string)($_POST['action']??'');
    $q=$db->prepare('SELECT s.*,f.form_key FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? AND s.activity_id=?');$q->execute([$id,$activityId]);$row=$q->fetch()?:null;
    if(!$row){http_response_code(404);exit('Submission not found');}
    if($action==='delete_registration'){
        try{admin_registration_delete($db,$id,$activityId);$_SESSION['admin_submissions_notice']='Inscrição excluída definitivamente.';}
        catch(Throwable $e){$_SESSION['admin_submissions_notice']='Não foi possível excluir: '.$e->getMessage();}
        header('Location: '.submission_url($activityId,0,(string)($_POST['filter_status']??''),(int)($_POST['filter_form']??0)),true,303);exit;
    }
    $now=utc_now();$paymentNote=array_key_exists('payment_note',$_POST)?trim((string)$_POST['payment_note']):(string)($row['payment_note']??'');
    if($row['form_key']==='registration'){
        if($action==='confirm')$db->prepare("UPDATE cms_form_submissions SET status='converted',payment_status='paid',payment_confirmed_at=?,payment_note=?,updated_at=? WHERE id=? AND activity_id=?")->execute([$now,$paymentNote,$now,$id,$activityId]);
        elseif($action==='pending')$db->prepare("UPDATE cms_form_submissions SET status='new',payment_status='pending',payment_confirmed_at=NULL,payment_note=?,updated_at=? WHERE id=? AND activity_id=?")->execute([$paymentNote,$now,$id,$activityId]);
        elseif($action==='archive')$db->prepare("UPDATE cms_form_submissions SET status='archived',payment_note=?,updated_at=? WHERE id=? AND activity_id=?")->execute([$paymentNote,$now,$id,$activityId]);
    }else{
        $allowed=['new','contacted','converted','archived'];$status=(string)($_POST['status']??'new');if(!in_array($status,$allowed,true))$status='new';$notes=trim((string)($_POST['notes']??''));$db->prepare('UPDATE cms_form_submissions SET status=?,notes=?,updated_at=? WHERE id=? AND activity_id=?')->execute([$status,$notes,$now,$id,$activityId]);
    }
    header('Location: '.submission_url($activityId,$id,(string)($_POST['filter_status']??''),(int)($_POST['filter_form']??0)));exit;
}

$formFilter=(int)($_GET['form']??0);$statusFilter=(string)($_GET['status']??'');
$sql='SELECT s.*,f.title form_title,f.form_key,f.locale form_locale FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.activity_id=?';$args=[$activityId];
if($formFilter){$sql.=' AND s.form_id=?';$args[]=$formFilter;}if(in_array($statusFilter,['new','contacted','converted','archived'],true)){$sql.=' AND s.status=?';$args[]=$statusFilter;}$sql.=" ORDER BY CASE s.status WHEN 'new' THEN 0 WHEN 'contacted' THEN 1 WHEN 'converted' THEN 2 ELSE 3 END,s.created_at DESC LIMIT 250";$q=$db->prepare($sql);$q->execute($args);$rows=$q->fetchAll();
$forms=cms_forms($db,$activityId);$selectedId=(int)($_GET['submission']??0);if(!$selectedId&&$rows)$selectedId=(int)$rows[0]['id'];$selected=null;if($selectedId){$q=$db->prepare('SELECT s.*,f.title form_title,f.form_key,f.locale form_locale FROM cms_form_submissions s JOIN cms_forms f ON f.id=s.form_id WHERE s.id=? AND s.activity_id=?');$q->execute([$selectedId,$activityId]);$selected=$q->fetch()?:null;}
$countQ=$db->prepare('SELECT status,COUNT(*) n FROM cms_form_submissions WHERE activity_id=? GROUP BY status');$countQ->execute([$activityId]);$counts=['new'=>0,'contacted'=>0,'converted'=>0,'archived'=>0];foreach($countQ->fetchAll() as $r)if(isset($counts[$r['status']]))$counts[$r['status']]=(int)$r['n'];
$notice=(string)($_SESSION['admin_submissions_notice']??'');unset($_SESSION['admin_submissions_notice']);

admin_shell_start('responses','Inscrições',$state);?>
<?php if($notice!==''):?><p class="admin-success"><?=h($notice)?></p><?php endif;?>
<section class="inbox-toolbar">
<div class="inbox-toolbar-summary"><strong><?=$counts['new']?> aguardando</strong><span>· <?=$counts['converted']?> confirmada<?=$counts['converted']===1?'':'s'?></span></div>
<div class="inbox-toolbar-controls">
<?php if(count($forms)>1):?><form class="inbox-form-filter" method="get"><input type="hidden" name="activity" value="<?=$activityId?>"><label><span>Formulário</span><select name="form" onchange="this.form.submit()"><option value="0">Todos</option><?php foreach($forms as $form):?><option value="<?=(int)$form['id']?>"<?=$formFilter===(int)$form['id']?' selected':''?>><?=h($form['title'].' · '.public_language_label($form['locale']))?></option><?php endforeach;?></select></label></form><?php endif;?>
<div class="inbox-toolbar-actions"><a href="/admin/forms.php?activity=<?=$activityId?>">Editar formulário</a><a href="/admin/submissions-export.php?activity=<?=$activityId?><?=$formFilter?'&form='.$formFilter:''?>">Exportar CSV</a></div>
</div></section>
<div class="submissions-layout inbox-layout">
<section class="submission-list inbox-list" aria-label="Lista de inscrições">
<?php if(!$rows):?><div class="admin-empty compact"><h2>Nenhuma inscrição</h2></div><?php endif;?>
<?php foreach($rows as $row):$isRegistration=$row['form_key']==='registration';$status=$isRegistration?registration_status($row):($row['status']==='converted'?'Concluída':($row['status']==='contacted'?'Em contato':($row['status']==='archived'?'Arquivada':'Nova')));?>
<a class="submission-row<?=$selectedId===(int)$row['id']?' selected':''?><?=$row['status']==='new'?' is-new':''?>" href="<?=h(submission_url($activityId,(int)$row['id'],$statusFilter,$formFilter))?>"><div class="submission-row-main"><strong><?=h(submission_name($row))?></strong><span><?=h(submission_secondary($row))?></span></div><div class="submission-row-meta"><time><?=h(date('d/m · H:i',strtotime($row['created_at'])))?></time><small data-status="<?=h($row['status'])?>"><?=h($status)?></small></div></a>
<?php endforeach;?></section>
<?php if($selected):$payload=submission_payload($selected);$fields=submission_fields($selected);$isRegistration=$selected['form_key']==='registration';?>
<aside class="submission-detail inbox-detail">
<header class="inbox-detail-header"><div><p><?=h($selected['form_title'])?></p><h2><?=h(submission_name($selected))?></h2><span><?=h(date('d/m/Y · H:i',strtotime($selected['created_at'])))?></span></div></header>
<?php if($isRegistration):?>
<section class="registration-admin-status <?=$selected['status']==='archived'?'is-cancelled':($selected['payment_status']==='paid'?'is-paid':'is-pending')?>"><div><span>Status</span><strong><?=h(registration_status($selected))?></strong></div><?php if($selected['status']!=='archived'):?><form method="post" class="registration-status-action"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-submissions'))?>"><input type="hidden" name="submission_id" value="<?=(int)$selected['id']?>"><input type="hidden" name="filter_status" value="<?=h($statusFilter)?>"><input type="hidden" name="filter_form" value="<?=$formFilter?>"><?php if(($selected['payment_status']??'pending')!=='paid'):?><button type="submit" name="action" value="confirm">Confirmar pagamento</button><?php else:?><button type="submit" name="action" value="pending">Marcar como aguardando</button><?php endif;?></form><?php endif;?></section>
<section class="registration-admin-group"><h3>Contato</h3><dl class="inbox-fields"><?php foreach(['cpf'=>'CPF','phone'=>'WhatsApp','email'=>'E-mail','instagram'=>'Instagram'] as $key=>$label):$value=$payload[$key]??'';if(trim((string)$value)==='')continue;?><div data-field="<?=h($key)?>"><dt><?=h($label)?></dt><dd><?=h(submission_display_value($key,$fields[$key]??[],$value))?></dd></div><?php endforeach;?></dl></section>
<section class="registration-admin-group"><h3>Presente e envio</h3><dl class="inbox-fields"><?php foreach(['support_size'=>'Tamanho do suporte','address'=>'Endereço','city_state'=>'Cidade/UF','postal_code'=>'CEP'] as $key=>$label):$value=$payload[$key]??'';if($value===''||$value===[])continue;?><div data-field="<?=h($key)?>"><dt><?=h($label)?></dt><dd><?=h(submission_display_value($key,$fields[$key]??[],$value))?></dd></div><?php endforeach;?></dl></section>
<section class="registration-admin-group"><h3>Disponibilidade</h3><p><?=h(submission_value_label($fields['availability']??[],$payload['availability']??[]))?></p></section>
<section class="registration-admin-payment"><p class="admin-kicker">Pagamento</p><h3><?=h(registration_payment_label($selected))?></h3><p><strong><?=$selected['payment_status']==='paid'?'Confirmado':'Aguardando confirmação'?></strong><?php if(!empty($selected['payment_confirmed_at'])):?> · <?=h(date('d/m/Y · H:i',strtotime($selected['payment_confirmed_at'])))?><?php endif;?></p>
<form method="post" class="registration-payment-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-submissions'))?>"><input type="hidden" name="submission_id" value="<?=(int)$selected['id']?>"><input type="hidden" name="filter_status" value="<?=h($statusFilter)?>"><input type="hidden" name="filter_form" value="<?=$formFilter?>"><label>Informações do pagamento<textarea name="payment_note" rows="3" placeholder="Ex.: comprovante conferido, identificação da transação…"><?=h((string)($selected['payment_note']??''))?></textarea></label><div class="registration-payment-actions"><?php if(($selected['payment_status']??'pending')!=='paid'):?><button class="admin-button" type="submit" name="action" value="confirm">Confirmar inscrição e pagamento</button><?php else:?><button class="admin-button secondary" type="submit" name="action" value="pending">Marcar como aguardando pagamento</button><?php endif;?><button class="admin-button secondary" type="submit" name="action" value="archive">Cancelar inscrição</button></div></form></section>
<section class="registration-admin-delete"><h3>Excluir inscrição</h3><p>Remove permanentemente esta inscrição. Se ela originou uma matrícula, esse acesso também é removido. A conta do aluno e os testes já registrados são preservados.</p><form method="post" data-confirm="Excluir definitivamente esta inscrição?"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-submissions'))?>"><input type="hidden" name="submission_id" value="<?=(int)$selected['id']?>"><input type="hidden" name="filter_status" value="<?=h($statusFilter)?>"><input type="hidden" name="filter_form" value="<?=$formFilter?>"><button class="admin-button danger" type="submit" name="action" value="delete_registration">Excluir inscrição definitivamente</button></form></section>
<?php else:?><dl class="inbox-fields"><?php foreach($payload as $key=>$value):?><div><dt><?=h((string)($fields[$key]['label']??$key))?></dt><dd><?=h(submission_value_label($fields[$key]??[],$value))?></dd></div><?php endforeach;?></dl><form method="post" class="inbox-followup"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-submissions'))?>"><input type="hidden" name="submission_id" value="<?=(int)$selected['id']?>"><input type="hidden" name="filter_status" value="<?=h($statusFilter)?>"><input type="hidden" name="filter_form" value="<?=$formFilter?>"><label>Notas<textarea name="notes" rows="4"><?=h((string)$selected['notes'])?></textarea></label><div class="inbox-status-actions"><?php foreach(['new'=>'Novo','contacted'=>'Em contato','converted'=>'Concluído','archived'=>'Arquivado'] as $value=>$label):?><button type="submit" name="status" value="<?=$value?>" formaction="/admin/submissions.php" onclick="this.form.action.value='generic'"<?=$selected['status']===$value?' aria-current="true"':''?>><?=h($label)?></button><?php endforeach;?><input type="hidden" name="action" value="generic"></div></form><?php endif;?>
</aside><?php endif;?></div>
<link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-inbox.css'))?>"><link rel="stylesheet" href="<?=h(admin_asset_url('/assets/admin-registration.css'))?>">
<?php admin_shell_end();