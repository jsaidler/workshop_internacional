<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);$next='/aluno/processar.php';
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];student_tool_require($db,$studentId,'lab_timer');
$testId=(int)($_GET['test']??$_POST['test_id']??0);$templateId=(int)($_GET['template']??0);$error='';

function student_process_runner_json(array $payload,int $status=200): never {
    http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}
function student_process_runner_step_url(int $testId,int $stepId): string {return '/aluno/processar.php?test='.$testId.'&step='.$stepId;}

$test=null;$plan=null;$template=null;$allSteps=[];$sourceName='Processamento';$backUrl='/aluno/processamentos.php';$csrf='';
if($testId>0){
    $test=student_test_for_student($db,$testId,$studentId);
    if(!$test){http_response_code(404);student_shell_start('Registro não encontrado',null,$student);?><div class="student-empty">Registro não encontrado.</div><?php student_shell_end();exit;}
    $plan=student_process_plan_for_test($db,$testId,$studentId);
    $backUrl='/aluno/teste.php?id='.$testId.'#processamento';$csrf=csrf_token('student-process-runner-'.$testId);
    if($plan){$sourceName=(string)$plan['source_name'];$allSteps=student_process_plan_steps($db,(int)$plan['id']);}

    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        $action=(string)($_POST['action']??'');$ajax=(string)($_POST['ajax']??'')==='1'||str_starts_with($action,'timer_');
        if(!verify_csrf('student-process-runner-'.$testId,$_POST['_csrf']??null)){
            if($ajax)student_process_runner_json(['error'=>'Solicitação inválida.'],403);$error='Solicitação inválida.';
        }else try{
            if(!$plan)throw new RuntimeException('Associe um roteiro a este registro.');
            $planId=(int)$plan['id'];$planStepId=(int)($_POST['plan_step_id']??0);if($planStepId<1)throw new RuntimeException('Etapa não informada.');
            if(in_array($action,['timer_state','timer_start','timer_pause','timer_reset'],true)){
                $expectedRevision=array_key_exists('revision',$_POST)&&$_POST['revision']!==''?(int)$_POST['revision']:null;$clientToken=student_workspace_text($_POST['client_token']??'',120);
                if($action==='timer_state')$state=student_process_notebook_timer_state($db,$planId,$planStepId,$studentId);
                else $state=student_process_notebook_timer_transition($db,$planId,$planStepId,$studentId,substr($action,6),$clientToken,$expectedRevision);
                $fresh=student_process_notebook_step($db,$planId,$planStepId,$studentId);
                student_process_runner_json(['state'=>$state,'completed'=>(string)$fresh['status']==='completed']);
            }elseif($action==='toggle_complete'){
                $completed=(string)($_POST['completed']??'0')==='1';student_process_notebook_set_completed($db,$planId,$planStepId,$studentId,$completed,'manual');
                header('Location: '.student_process_runner_step_url($testId,$planStepId),true,303);exit;
            }elseif($action==='save_step'){
                student_process_notebook_update_step($db,$planId,$planStepId,$studentId,$_POST);
                header('Location: '.student_process_runner_step_url($testId,$planStepId).'&saved=1',true,303);exit;
            }else throw new RuntimeException('Ação inválida.');
        }catch(StudentProcessExecutionConflict $e){
            if($ajax)student_process_runner_json(['error'=>$e->getMessage(),'state'=>$e->executionState],409);$error=$e->getMessage();
        }catch(Throwable $e){$error=$e->getMessage();if($ajax)student_process_runner_json(['error'=>$error],422);}
        $plan=student_process_plan_for_test($db,$testId,$studentId);$allSteps=$plan?student_process_plan_steps($db,(int)$plan['id']):[];
    }
}else{
    $template=student_process_template_for_student($db,$templateId,$studentId);
    if(!$template){http_response_code(404);student_shell_start('Processamento não encontrado',null,$student);?><div class="student-empty">Processamento não encontrado.</div><?php student_shell_end();exit;}
    $allSteps=student_process_template_steps($db,$templateId);$sourceName=(string)$template['name'];$backUrl='/aluno/processamentos.php?id='.$templateId;
}

if($testId>0&&!$plan){
    student_shell_start('Roteiro do registro',null,$student);?>
    <header class="student-process-runner-head"><a class="student-back" href="<?=h($backUrl)?>">← Registro</a><div><p class="student-kicker">Processamento</p><h1 class="student-title">Roteiro do registro</h1></div></header>
    <section class="student-process-runner-empty"><h2>Nenhum roteiro associado.</h2><p>Associe um roteiro para consultar as etapas e usar seus timers. Isso não inicia nem controla o processamento.</p><a class="button button-primary" href="/aluno/registro-roteiro.php?test=<?=$testId?>">Associar roteiro</a></section>
    <?php student_shell_end();exit;
}
if(!$allSteps){
    student_shell_start('Roteiro',null,$student);?>
    <header class="student-process-runner-head"><a class="student-back" href="<?=h($backUrl)?>">← Voltar</a><div><p class="student-kicker">Roteiro</p><h1 class="student-title"><?=h($sourceName)?></h1></div></header>
    <section class="student-process-runner-empty"><h2>Este roteiro não possui etapas.</h2></section>
    <?php student_shell_end();exit;
}

$requested=(int)($_GET['step']??0);$current=null;$currentIndex=0;
foreach($allSteps as $i=>$row){if((int)$row['id']===$requested){$current=$row;$currentIndex=$i;break;}}
if(!$current){$current=$allSteps[0];$currentIndex=0;}
$payload=student_process_json_array((string)$current['payload_json']);$durationSeconds=student_process_time_seconds((string)$current['duration']);
$profile=student_process_lab_agitation_profile($current);$reuseSource=(string)($payload['reuse_source_stage_key']??'');
$storageKey=$testId>0?'notebook-plan-'.$plan['id'].'-step-'.$current['id']:'template-'.$templateId.'-step-'.$current['id'];
$runnerState=$testId>0?student_process_notebook_timer_state($db,(int)$plan['id'],(int)$current['id'],$studentId):null;
$executionJson=$runnerState?json_encode($runnerState,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):'';
$counts=$testId>0?student_process_notebook_counts($db,(int)$plan['id'],$studentId):['completed'=>0,'total'=>count($allSteps)];
$currentCompleted=$testId>0&&(string)$current['status']==='completed';
$agitationSummary='';if($profile['mode']==='continuous')$agitationSummary='Agitação contínua';elseif($profile['mode']==='periodic'&&$profile['interval_seconds'])$agitationSummary=$profile['duration_seconds']?'Agitar '.student_process_seconds_label((int)$profile['duration_seconds']).' a cada '.student_process_seconds_label((int)$profile['interval_seconds']):'Agitação a cada '.student_process_seconds_label((int)$profile['interval_seconds']);

student_shell_start('Roteiro',null,$student);?>
<header class="student-process-runner-head">
  <a class="student-back" href="<?=h($backUrl)?>">← <?=$testId>0?'Registro':'Processamentos'?></a>
  <div><p class="student-kicker"><?=$testId>0?'Roteiro associado':'Roteiro'?></p><h1 class="student-title"><?=h($sourceName)?></h1><?php if($testId>0):?><p class="student-process-runner-details"><?=(int)$counts['completed']?> de <?=(int)$counts['total']?> etapas marcadas</p><?php endif;?></div>
</header>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<?php if(isset($_GET['saved'])):?><p class="ui-alert ui-alert-success">Dados da etapa salvos.</p><?php endif;?>

<section class="student-process-runner" data-process-runner data-duration-seconds="<?=$durationSeconds===null?'':$durationSeconds?>" data-agitation-mode="<?=h((string)$profile['mode'])?>" data-agitation-duration-seconds="<?=$profile['duration_seconds']===null?'':(int)$profile['duration_seconds']?>" data-agitation-interval-seconds="<?=$profile['interval_seconds']===null?'':(int)$profile['interval_seconds']?>" data-storage-key="<?=h($storageKey)?>"<?=$testId>0?' data-runner-endpoint="/aluno/processar.php?test='.$testId.'" data-runner-csrf="'.h($csrf).'" data-plan-id="'.(int)$plan['id'].'" data-plan-step-id="'.(int)$current['id'].'" data-execution-state="'.h($executionJson).'"':''?>>

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
<?php student_shell_end();