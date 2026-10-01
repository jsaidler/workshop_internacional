<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);
$testId=(int)($_GET['registro']??$_POST['registro']??0);$position=(int)($_GET['etapa']??$_POST['etapa']??0);
$next='/aluno/teste-etapa.php?registro='.$testId.'&etapa='.$position;
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$record=student_test_for_student($db,$testId,$studentId);$step=$record?student_process_step_for_student($db,$testId,$position,$studentId):null;
if(!$record||!$step){http_response_code(404);student_shell_start('Etapa não encontrada',null,$student);?><div class="student-empty">Etapa não encontrada.</div><?php student_shell_end();exit;}
$locked=(string)$record['status']==='reviewed';$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-'.$testId,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{
        if($locked)throw new RuntimeException('Este registro já foi revisado.');
        $action=(string)($_POST['action']??'save');
        if($action==='truncate'){
            student_process_delete_from_step($db,$testId,(int)$step['id'],$studentId);
            $_SESSION['student_process_notice']='Etapa e etapas seguintes removidas.';
            header('Location: /aluno/teste.php?id='.$testId.'&view=process',true,303);exit;
        }
        student_process_update_step($db,$testId,(int)$step['id'],$studentId,$_POST);
        $_SESSION['student_process_notice']='Etapa atualizada.';
        header('Location: /aluno/teste.php?id='.$testId.'&view=process',true,303);exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}
$step=student_process_step_for_student($db,$testId,$position,$studentId)??$step;$developers=student_process_developer_catalog();$preparations=student_saved_preparations($db,$studentId);$inventory=student_inventory_items($db,$studentId);$stageKey=(string)$step['stage_key'];$isDevelopment=in_array($stageKey,['first_development','second_development'],true);$isCustom=$stageKey==='custom';$stepName=trim((string)$step['chemical_name'])!==''?(string)$step['chemical_name']:(string)$step['label'];$developerKey=$isDevelopment?(string)($step['chemical_key']?:'other'):'';$inventoryId=(int)($step['inventory_item_id']??0);$inventoryAmount=$inventoryId?student_process_step_inventory_amount($db,(int)$step['id'],$inventoryId):null;
student_shell_start('Editar etapa · '.(string)$record['title'],null,$student);?>
<header class="student-record-header student-step-editor-header"><div class="student-record-context"><a class="student-back" href="/aluno/teste.php?id=<?=$testId?>&amp;view=process">← Processamento</a><span class="student-status">Etapa <?=str_pad((string)$position,2,'0',STR_PAD_LEFT)?></span></div><p class="student-kicker">Editar registro</p><h1 class="student-title student-title-record"><?=h($stepName)?></h1><p class="student-lead">Altere os dados desta etapa sem apagar o que foi registrado depois dela.</p></header>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="student-workflow-panel student-step-editor">
<form method="post" class="student-process-edit-form<?=$isDevelopment?' student-developer-edit-form':''?>"<?=$isDevelopment?' data-saved-preparation-form':''?> data-ui-validate>
<input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$testId))?>"><input type="hidden" name="registro" value="<?=$testId?>"><input type="hidden" name="etapa" value="<?=$position?>"><input type="hidden" name="action" value="save">
<?php if($isDevelopment):?>
<div class="student-form-grid"><label class="form-field student-span-2">Revelador<select name="developer_key" data-developer-select><?php foreach($developers as $key=>$developer):?><option value="<?=h($key)?>" data-preparation-mode="<?=h((string)$developer['mode'])?>"<?=$developerKey===$key?' selected':''?>><?=h((string)$developer['label'])?></option><?php endforeach;?></select></label><label class="form-field student-span-2" data-custom-developer<?=$developerKey==='other'?'':' hidden'?>>Outro revelador<input name="developer_name" value="<?=$developerKey==='other'?h((string)$step['chemical_name']):''?>"></label><label class="form-field student-span-2">Predefinição de revelação<select name="saved_preparation_id" data-saved-preparation><option value="">Nenhum</option><?php foreach($preparations as $prep):?><option value="<?=(int)$prep['id']?>" data-developer-key="<?=h((string)$prep['developer_key'])?>" data-mode="<?=h((string)$prep['preparation_mode'])?>" data-developer-amount="<?=h((string)$prep['developer_amount'])?>" data-water-amount="<?=h((string)$prep['water_amount'])?>" data-temperature="<?=h((string)$prep['temperature'])?>" data-duration="<?=h((string)$prep['duration'])?>" data-agitation="<?=h((string)$prep['agitation'])?>"<?=((int)$step['saved_preparation_id']===(int)$prep['id'])?' selected':''?>><?=h((string)$prep['label'])?></option><?php endforeach;?></select></label><div class="student-span-2 student-developer-mix" data-developer-mix><label class="form-field">Revelador ou solução estoque (ml)<input type="number" min="0" step="0.1" name="developer_amount" data-developer-amount value="<?=h((string)$step['developer_amount'])?>"></label><label class="form-field">Água (ml)<input type="number" min="0" step="0.1" name="water_amount" data-water-amount value="<?=h((string)$step['water_amount'])?>"></label><output class="student-dilution-output student-span-2" data-dilution-output><?=h((string)$step['calculated_dilution'])?></output></div><label class="form-field student-span-2" data-fresh-volume hidden>Volume preparado (ml)<input type="number" min="0" step="0.1" name="fresh_volume" data-fresh-volume-input value="<?=h((string)$step['developer_amount'])?>"></label>
<?php else:?>
<div class="student-form-grid"><?php if($isCustom):?><label class="form-field student-span-2">Nome da etapa<input name="custom_label" value="<?=h((string)$step['label'])?>" required></label><?php else:?><div class="student-step-fixed student-span-2"><span>Tipo da etapa</span><strong><?=h((string)$step['label'])?></strong><small>Para mudar a sequência, use a correção de processo abaixo.</small></div><?php endif;?>
<?php endif;?>
<label class="form-field">Tempo<input name="duration" value="<?=h((string)$step['duration'])?>"></label><label class="form-field">Temperatura<input name="temperature" value="<?=h((string)$step['temperature'])?>"></label><label class="form-field student-span-2">Agitação<input name="agitation" value="<?=h((string)$step['agitation'])?>"></label><label class="form-field student-span-2">Item do inventário<select name="inventory_item_id"><option value="">Não vincular ao inventário</option><?php foreach($inventory as $item):?><option value="<?=(int)$item['id']?>"<?=$inventoryId===(int)$item['id']?' selected':''?>><?=h((string)$item['name'])?> · <?=h(student_workbench_number((float)$item['quantity']).' '.$item['unit'])?></option><?php endforeach;?></select></label><label class="form-field">Quantidade utilizada<input type="number" min="0" step="0.1" name="inventory_amount" value="<?=$inventoryAmount!==null?h(student_workbench_number($inventoryAmount)):''?>"></label><label class="form-field student-span-2">Anotações da etapa<textarea name="notes" rows="4" maxlength="3000"><?=h((string)$step['notes'])?></textarea></label></div>
<div class="student-sticky-action"><button class="button button-primary" type="submit">Salvar alterações</button><a class="button button-secondary" href="/aluno/teste.php?id=<?=$testId?>&amp;view=process">Cancelar</a></div></form>
<?php if(!$locked):?><details class="student-step-danger"><summary>Corrigir a sequência do processo</summary><div><p>Se esta etapa estiver no lugar errado, remova-a daqui para frente e registre novamente a sequência correta. Esta ação apaga também todas as etapas posteriores.</p><form method="post" onsubmit="return confirm('Remover esta etapa e todas as etapas seguintes?')"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$testId))?>"><input type="hidden" name="registro" value="<?=$testId?>"><input type="hidden" name="etapa" value="<?=$position?>"><input type="hidden" name="action" value="truncate"><button class="button button-danger" type="submit">Remover esta etapa e as seguintes</button></form></div></details><?php endif;?>
</section>
<?php student_shell_end();
