<?php
declare(strict_types=1);
/** Render only an already-authorized laboratory projection. All writes and timer state remain server-owned. */
function student_process_render_runner_view(array $view): void {
    $allSteps=$view['allSteps'];
    $current=$view['current'];
    $currentIndex=$view['currentIndex'];
    $payload=$view['payload'];
    $durationSeconds=$view['durationSeconds'];
    $profile=$view['profile'];
    $reuseSource=$view['reuseSource'];
    $storageKey=$view['storageKey'];
    $runnerState=$view['runnerState'];
    $executionJson=$view['executionJson'];
    $counts=$view['counts'];
    $currentCompleted=$view['currentCompleted'];
    $testId=$view['testId'];
    $templateId=$view['templateId'];
    $plan=$view['plan'];
    $sourceName=$view['sourceName'];
    $backUrl=$view['backUrl'];
    $csrf=$view['csrf'];
    $error=$view['error'];
    $agitationSummary=$view['agitationSummary'];
    $saved=$view['saved'];
?>
<header class="student-process-runner-head">
  <a class="student-back" href="<?=h($backUrl)?>">← <?=$testId>0?'Registro':'Processamentos'?></a>
  <div><p class="student-kicker"><?=$testId>0?'Roteiro associado':'Roteiro'?></p><h1 class="student-title"><?=h($sourceName)?></h1><?php if($testId>0):?><p class="student-process-runner-details"><?=(int)$counts['completed']?> de <?=(int)$counts['total']?> etapas marcadas</p><?php endif;?></div>
</header>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<?php if($saved):?><p class="ui-alert ui-alert-success">Dados da etapa salvos.</p><?php endif;?>

<section class="student-process-runner" data-operational-runner-view data-process-runner data-duration-seconds="<?=$durationSeconds===null?'':$durationSeconds?>" data-agitation-mode="<?=h((string)$profile['mode'])?>" data-agitation-duration-seconds="<?=$profile['duration_seconds']===null?'':(int)$profile['duration_seconds']?>" data-agitation-interval-seconds="<?=$profile['interval_seconds']===null?'':(int)$profile['interval_seconds']?>" data-storage-key="<?=h($storageKey)?>"<?=$testId>0?' data-runner-endpoint="/aluno/processar.php?test='.$testId.'" data-runner-csrf="'.h($csrf).'" data-plan-id="'.(int)$plan['id'].'" data-plan-step-id="'.(int)$current['id'].'" data-execution-state="'.h($executionJson).'"':''?>>

<nav class="student-lab-stage-nav" aria-label="Etapas do roteiro">
<?php foreach($allSteps as $i=>$step):$done=$testId>0&&(string)$step['status']==='completed';$selected=(int)$step['id']===(int)$current['id'];$href=$testId>0?student_process_runner_step_url($testId,(int)$step['id']):'/aluno/processar.php?template='.$templateId.'&step='.(int)$step['id'];?>
<a href="<?=h($href)?>" class="<?=$done?'is-done ':''?><?=$selected?'is-selected':''?>"<?=$selected?' aria-current="step"':''?> aria-label="Etapa <?=($i+1)?>: <?=h((string)$step['label'])?>"><span><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><?php if($done):?><small>✓</small><?php endif;?></a>
<?php endforeach;?>
</nav>

<div class="student-process-runner-current">
  <p class="student-kicker">Etapa <?=($currentIndex+1)?> de <?=count($allSteps)?></p>
  <h2><?=h((string)$current['label'])?></h2>
  <?php $details=[];if((string)($payload['developer_name']??$payload['chemical_name']??'')!=='')$details[]=(string)($payload['developer_name']??$payload['chemical_name']);if((string)($payload['temperature']??'')!=='')$details[]=(string)$payload['temperature'];if($agitationSummary!=='')$details[]=$agitationSummary;elseif((string)($payload['agitation']??'')!=='')$details[]=(string)$payload['agitation'];if($details):?><p class="student-process-runner-details"><?=h(implode(' · ',$details))?></p><?php endif;?>
  <?php if($reuseSource==='first_development'):?><div class="student-process-runner-instruction"><strong>Reutilizar o banho da primeira revelação</strong><span>Esta é a referência registrada no roteiro.</span></div><?php endif;?>
</div>

<?php if($durationSeconds!==null):?>
<output class="student-process-runner-clock" data-runner-clock><?=h(student_process_seconds_label($runnerState['remaining_seconds']??$durationSeconds))?></output>
<div class="student-lab-agitation" data-runner-agitation aria-live="polite"><?php if($profile['mode']==='continuous'):?>Agitação contínua<?php elseif($profile['mode']==='periodic'&&$profile['interval_seconds']):?><?=$profile['duration_seconds']?'Agitar '.h(student_process_seconds_label((int)$profile['duration_seconds'])).' a cada '.h(student_process_seconds_label((int)$profile['interval_seconds'])):'Agitação a cada '.h(student_process_seconds_label((int)$profile['interval_seconds']))?><?php else:?>Sem aviso de agitação<?php endif;?></div>
<p class="student-process-runner-cue" data-runner-cue aria-live="polite"></p>
<div class="student-process-runner-status"><span data-runner-state-status><?=h(match((string)($runnerState['state']??'idle')){'running'=>'Timer em andamento','paused'=>'Timer pausado','elapsed'=>'Tempo concluído',default=>'Pronto para iniciar'})?></span><span data-runner-wake-status>Tela ativa ao iniciar</span></div>
<div class="student-actions student-process-runner-controls"><button class="button button-primary" type="button" data-runner-start>Iniciar</button><button class="button button-secondary" type="button" data-runner-pause disabled>Pausar</button><button class="button button-secondary" type="button" data-runner-reset>Reiniciar</button></div>
<?php else:?><div class="student-process-runner-untimed"><strong>Sem tempo definido</strong><p>Você pode acrescentar um tempo editando os dados desta etapa.</p></div><?php endif;?>

<?php if($testId>0):?>
<div class="student-lab-stage-state" data-step-completion><strong><?=$currentCompleted?'Etapa marcada como concluída':'Etapa não marcada'?></strong><span>O check é apenas um registro. Não altera nem bloqueia as outras etapas.</span></div>
<form method="post" class="student-actions student-notebook-step-check"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="toggle_complete"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="plan_step_id" value="<?=(int)$current['id']?>"><input type="hidden" name="completed" value="<?=$currentCompleted?'0':'1'?>"><button class="button <?=$currentCompleted?'button-secondary':'button-primary'?>" type="submit"><?=$currentCompleted?'Desmarcar etapa':'Marcar como concluída'?></button></form>

<details class="student-lab-timer-settings" open>
<summary>Editar dados da etapa</summary>
<form method="post" class="student-form-grid" data-agitation-settings><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="save_step"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="plan_step_id" value="<?=(int)$current['id']?>">
<label class="form-field student-span-2">Nome da etapa<input name="label" value="<?=h((string)$current['label'])?>"></label>
<label class="form-field">Tempo<input name="duration" value="<?=h((string)$current['duration'])?>" placeholder="07:00"></label>
<label class="form-field">Temperatura<input name="temperature" value="<?=h((string)($payload['temperature']??''))?>" placeholder="26 °C"></label>
<?php if(in_array((string)$current['stage_key'],['first_development','second_development'],true)):?>
<label class="form-field student-span-2">Revelador<input name="developer_name" value="<?=h((string)($payload['developer_name']??''))?>"></label>
<label class="form-field">Revelador / solução (ml)<input type="number" min="0" step="0.1" name="developer_amount" value="<?=h((string)($payload['developer_amount']??''))?>"></label>
<label class="form-field">Água (ml)<input type="number" min="0" step="0.1" name="water_amount" value="<?=h((string)($payload['water_amount']??''))?>"></label>
<?php elseif((string)($payload['chemical_name']??'')!==''):?><label class="form-field student-span-2">Químico / solução<input name="chemical_name" value="<?=h((string)$payload['chemical_name'])?>"></label><?php endif;?>
<label class="form-field student-span-2">Agitação<select name="agitation_mode" data-agitation-mode><option value="none"<?=$profile['mode']==='none'?' selected':''?>>Sem temporização</option><option value="periodic"<?=$profile['mode']==='periodic'?' selected':''?>>Periódica</option><option value="continuous"<?=$profile['mode']==='continuous'?' selected':''?>>Contínua</option></select></label>
<div class="student-span-2 student-form-grid" data-agitation-periodic<?=$profile['mode']==='periodic'?'':' hidden'?>> <label class="form-field">Duração de cada agitação<input name="agitation_duration" value="<?=h((string)$profile['duration'])?>" placeholder="00:10"></label><label class="form-field">Intervalo entre inícios<input name="agitation_interval" value="<?=h((string)$profile['interval'])?>" placeholder="01:00"></label></div>
<label class="form-field student-span-2">Observação de agitação<input name="agitation" value="<?=h((string)($payload['agitation']??''))?>"></label>
<label class="form-field student-span-2">Anotações<textarea name="notes" rows="3" maxlength="3000"><?=h((string)($payload['notes']??''))?></textarea></label>
<div class="student-actions student-span-2"><button class="button button-primary" type="submit">Salvar dados da etapa</button></div>
</form></details>

<div class="student-actions student-process-runner-secondary"><a class="button button-secondary" href="/aluno/inventario.php">Movimentar estoque</a><a class="button button-secondary" href="/aluno/registro-roteiro.php?test=<?=$testId?>">Alterar roteiro</a><a class="student-link" href="<?=h($backUrl)?>">Voltar ao registro</a></div>
<?php endif;?>
</section>
<?php
}
