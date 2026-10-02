<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);$testId=(int)($_GET['test']??$_POST['test']??0);$next='/aluno/processamento-realizado.php?test='.$testId;
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$record=student_test_for_student($db,$testId,$studentId);if(!$record){http_response_code(404);exit('Registro não encontrado.');}
$locked=(string)$record['status']==='reviewed';$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-recorded-'.$testId,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{
        if($locked)throw new RuntimeException('Este registro já foi revisado.');
        $action=(string)($_POST['action']??'');$performedOn=trim((string)($_POST['performed_on']??''));$processNotes=(string)($_POST['process_notes']??'');
        if($action==='record_template'){
            $plan=student_process_plan_apply_template($db,(int)($_POST['template_id']??0),$testId,$studentId);
            student_process_plan_complete_recorded($db,(int)$plan['id'],$studentId,$performedOn,$processNotes);
            header('Location: '.$next.'&saved=1',true,303);exit;
        }
        if($action==='record_standard'){
            $plan=student_process_plan_apply_standard($db,(string)($_POST['standard_key']??''),$testId,$studentId);
            student_process_plan_complete_recorded($db,(int)$plan['id'],$studentId,$performedOn,$processNotes);
            header('Location: '.$next.'&saved=1',true,303);exit;
        }
        if($action==='complete_plan'){
            $plan=student_process_plan_for_test($db,$testId,$studentId)??throw new RuntimeException('Nenhum roteiro aplicado.');
            student_process_plan_complete_recorded($db,(int)$plan['id'],$studentId,$performedOn,$processNotes);
            header('Location: '.$next.'&saved=1',true,303);exit;
        }
        if($action==='add_manual'){
            $hadSteps=(bool)student_process_steps($db,$testId);
            $input=$_POST;unset($input['inventory_item_id'],$input['inventory_amount']);
            student_process_recording_add_step($db,$testId,$studentId,$input,$hadSteps?'mixed':'retroactive');
            student_process_recording_save_meta($db,$testId,$studentId,$hadSteps?'mixed':'retroactive',$performedOn,$processNotes);
            header('Location: '.$next.'&manual=1',true,303);exit;
        }
        if($action==='save_meta'){
            $existing=student_process_recording_meta($db,$testId,$studentId);$mode=(string)($existing['entry_mode']??(student_process_steps($db,$testId)?'mixed':'retroactive'));
            student_process_recording_save_meta($db,$testId,$studentId,$mode,$performedOn,$processNotes);
            header('Location: '.$next.'&saved=1',true,303);exit;
        }
        throw new RuntimeException('Ação inválida.');
    }catch(Throwable $e){$error=$e->getMessage();}
}
$steps=student_process_steps($db,$testId);$complete=student_experience_process_complete($steps);$plan=student_process_plan_for_test($db,$testId,$studentId);$planSteps=$plan?student_process_plan_steps($db,(int)$plan['id']):[];$pending=array_values(array_filter($planSteps,fn($s)=>(string)$s['status']!=='completed'));$planCompleted=count($planSteps)-count($pending);
$templates=student_process_templates($db,$studentId);$standards=student_process_standard_catalog();$meta=student_process_recording_meta($db,$testId,$studentId);$performedOn=(string)($meta['performed_on']??$record['test_date']??'');$processNotes=(string)($meta['notes']??'');$stageCatalog=student_process_stage_catalog();$developers=student_process_developer_catalog();
student_shell_start('Registrar processamento realizado',null,$student);?>
<div class="student-appbar"><a class="student-back" href="/aluno/teste.php?id=<?=$testId?>&amp;view=process">← Processamento</a></div>
<header class="student-page-heading student-recording-heading"><div><p class="student-kicker">Caderno</p><h1 class="student-title">Registrar processamento</h1><p class="student-lead">Registre a sequência que você realmente realizou.</p></div></header>
<details class="student-recording-help"><summary>Como funciona este registro</summary><div><p>Não há baixa automática no inventário.</p><p>Um roteiro usado aqui vira uma cópia própria deste registro e pode ser ajustado sem alterar sua biblioteca.</p></div></details>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?><?php if(isset($_GET['saved'])):?><p class="ui-alert ui-alert-notice">Processamento registrado no Caderno.</p><?php endif;?>
<?php if($complete||($steps&&!$plan)):?><form method="post" class="student-recording-context student-form-grid"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-recorded-'.$testId))?>"><input type="hidden" name="test" value="<?=$testId?>"><input type="hidden" name="action" value="save_meta"><label class="form-field">Data do processamento<input type="date" name="performed_on" value="<?=h($performedOn)?>"></label><label class="form-field student-span-2">Observações gerais<textarea name="process_notes" rows="2" placeholder="Desvios ou condições importantes."><?=h($processNotes)?></textarea></label><div class="student-actions student-span-2"><button class="button button-secondary" type="submit">Salvar contexto</button></div></form><?php endif;?>

<?php if($complete):?>
<section class="student-workflow-panel"><div class="student-process-complete"><p class="student-process-now-label">Registro completo</p><h2>Processamento registrado</h2><p>Agora registre o resultado.</p><div class="student-actions"><a class="button button-primary" href="/aluno/teste.php?id=<?=$testId?>&amp;view=review">Registrar resultado →</a><a class="button button-secondary" href="/aluno/teste.php?id=<?=$testId?>&amp;view=process">Voltar</a></div></div></section>
<?php elseif($plan&&$pending):?>
<section class="student-workflow-panel student-recording-plan"><p class="student-kicker"><?=$planCompleted>0?'Registro parcial':'Roteiro associado'?></p><h2 class="student-subtitle"><?=h((string)$plan['source_name'])?></h2><p class="student-recording-plan-status"><?=$planCompleted?> de <?=count($planSteps)?> etapas registradas.</p><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-recorded-'.$testId))?>"><input type="hidden" name="test" value="<?=$testId?>"><input type="hidden" name="action" value="complete_plan"><input type="hidden" name="performed_on" value="<?=h($performedOn)?>"><input type="hidden" name="process_notes" value="<?=h($processNotes)?>"><button class="button button-primary" type="submit"><?=$planCompleted>0?'Registrar etapas restantes':'Registrar processo como realizado'?></button></form></section>
<?php elseif(!$steps&&!$plan):?>
<section class="student-workflow-panel student-recording-source"><div class="student-section-heading"><div><p class="student-kicker">Processo usado</p><h2 class="student-subtitle">Qual roteiro você usou?</h2></div></div>
<?php if($templates):?><h3 class="student-recording-group-title">Meus processamentos</h3><div class="student-recording-grid"><?php foreach($templates as $template):?><article class="student-recording-option"><h4><?=h((string)$template['name'])?></h4><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-recorded-'.$testId))?>"><input type="hidden" name="test" value="<?=$testId?>"><input type="hidden" name="action" value="record_template"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="performed_on" value="<?=h($performedOn)?>"><input type="hidden" name="process_notes" value="<?=h($processNotes)?>"><button class="button button-primary button-compact" type="submit">Usar</button></form></article><?php endforeach;?></div><?php endif;?>
<h3 class="student-recording-group-title">Padrões do workshop</h3><div class="student-recording-grid"><?php foreach($standards as $key=>$standard):?><article class="student-recording-option"><h4><?=h((string)$standard['name'])?></h4><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-recorded-'.$testId))?>"><input type="hidden" name="test" value="<?=$testId?>"><input type="hidden" name="action" value="record_standard"><input type="hidden" name="standard_key" value="<?=h($key)?>"><input type="hidden" name="performed_on" value="<?=h($performedOn)?>"><input type="hidden" name="process_notes" value="<?=h($processNotes)?>"><button class="button button-secondary button-compact" type="submit">Usar</button></form></article><?php endforeach;?></div></section>
<?php endif;?>

<?php if(!$complete):?><details class="student-manual-process student-recording-manual"<?=isset($_GET['manual'])||$steps?' open':''?>><summary><span><strong>Registrar etapa por etapa</strong><small>Para um processo diferente dos roteiros acima.</small></span><span>＋</span></summary><form method="post" class="student-form-grid" data-ui-validate data-recorded-process-form><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-recorded-'.$testId))?>"><input type="hidden" name="test" value="<?=$testId?>"><input type="hidden" name="action" value="add_manual"><input type="hidden" name="performed_on" value="<?=h($performedOn)?>"><input type="hidden" name="process_notes" value="<?=h($processNotes)?>"><label class="form-field student-span-2">Etapa<select name="stage_key" required data-recorded-stage><?php foreach($stageCatalog as $key=>$stage):?><option value="<?=h($key)?>" data-stage-type="<?=h((string)$stage['type'])?>"><?=h((string)$stage['label'])?></option><?php endforeach;?></select></label><label class="form-field student-span-2" data-recorded-custom hidden>Nome da etapa personalizada<input name="custom_label"></label><div class="student-span-2 student-form-grid" data-recorded-development hidden><label class="form-field student-span-2">Revelador<select name="developer_key" data-recorded-developer><?php foreach($developers as $key=>$developer):?><option value="<?=h($key)?>" data-preparation-mode="<?=h((string)$developer['mode'])?>"><?=h((string)$developer['label'])?></option><?php endforeach;?></select></label><label class="form-field" data-recorded-stock-volume>Revelador ou solução estoque (ml)<input type="number" min="0" step="0.1" name="developer_amount"></label><label class="form-field" data-recorded-water>Água (ml)<input type="number" min="0" step="0.1" name="water_amount"></label><label class="form-field student-span-2" data-recorded-fresh-volume hidden>Volume da solução preparada fresca (ml)<input type="number" min="0" step="0.1" name="fresh_volume"></label></div><label class="form-field">Tempo<input name="duration" placeholder="5:00"></label><label class="form-field">Temperatura<input name="temperature" placeholder="35,7 °C"></label><label class="form-field student-span-2">Agitação<input name="agitation"></label><label class="form-field student-span-2">Anotações da etapa<textarea name="notes" rows="3"></textarea></label><div class="student-actions student-span-2"><button class="button button-primary" type="submit">Adicionar etapa realizada</button></div></form></details><?php endif;?>

<?php if($steps):?><section class="student-section"><div class="student-section-heading"><div><p class="student-kicker">Sequência registrada</p><h2 class="student-subtitle"><?=count($steps)?> etapa<?=count($steps)===1?'':'s'?></h2></div></div><div class="student-process-history"><?php foreach($steps as $step):?><article><div><span><?=str_pad((string)$step['position'],2,'0',STR_PAD_LEFT)?></span><div><strong><?=h((string)$step['label'])?></strong><p><?=h(implode(' · ',array_filter([(string)$step['chemical_name'],(string)$step['duration'],(string)$step['temperature']])))?></p></div></div><?php if(!$locked):?><a class="button button-secondary button-compact" href="/aluno/teste-etapa.php?registro=<?=$testId?>&amp;etapa=<?=(int)$step['position']?>">Editar etapa</a><?php endif;?></article><?php endforeach;?></div></section><?php endif;?>
<?php student_shell_end();