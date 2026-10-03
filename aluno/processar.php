<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);$next='/aluno/processar.php';
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];student_tool_require($db,$studentId,'lab_timer');
$testId=(int)($_GET['test']??$_POST['test_id']??0);$templateId=(int)($_GET['template']??$_POST['template_id']??0);$intent=(string)($_GET['intent']??'');$error='';

function student_process_runner_json(array $payload,int $status=200): never {
    http_response_code($status);header('Content-Type: application/json; charset=utf-8');echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
}
function student_process_runner_step_url(int $testId,int $stepId): string {return '/aluno/processar.php?test='.$testId.'&intent=live&step='.$stepId;}

if($testId>0){
    $test=student_test_for_student($db,$testId,$studentId);if(!$test){http_response_code(404);student_shell_start('Registro não encontrado',null,$student);?><div class="student-empty">Registro não encontrado.</div><?php student_shell_end();exit;}
    $plan=student_process_plan_for_test($db,$testId,$studentId);
    if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
        $action=(string)($_POST['action']??'');$ajax=(string)($_POST['ajax']??'')==='1'||str_starts_with($action,'timer_');
        if(!verify_csrf('student-process-runner-'.$testId,$_POST['_csrf']??null)){
            if($ajax)student_process_runner_json(['error'=>'Solicitação inválida.'],403);$error='Solicitação inválida.';
        }else try{
            if(!$plan)throw new RuntimeException('Escolha um processamento para este registro.');
            $planId=(int)$plan['id'];$planStepId=(int)($_POST['plan_step_id']??0);
            if(in_array($action,['timer_state','timer_start','timer_pause','timer_reset','start'],true)){
                if($planStepId<1)throw new RuntimeException('Etapa do processamento não informada.');
                $expectedRevision=array_key_exists('revision',$_POST)&&$_POST['revision']!==''?(int)$_POST['revision']:null;$clientToken=student_workspace_text($_POST['client_token']??'',120);
                if($action==='timer_state')$state=student_process_execution_state($db,$planId,$planStepId,$studentId);
                else{$transition=$action==='start'?'start':substr($action,6);$state=student_process_execution_transition($db,$planId,$planStepId,$studentId,$transition,$clientToken,$expectedRevision);}
                student_process_runner_json(['state'=>$state]);
            }elseif($action==='advance_to_step'){
                if($planStepId<1)throw new RuntimeException('Etapa do processamento não informada.');
                student_process_lab_advance_to_step($db,$planId,$planStepId,$studentId);
                header('Location: '.student_process_runner_step_url($testId,$planStepId),true,303);exit;
            }elseif($action==='finish_process'){
                if($planStepId<1)throw new RuntimeException('Etapa do processamento não informada.');
                student_process_lab_finish_process($db,$planId,$planStepId,$studentId);
                header('Location: /aluno/teste.php?id='.$testId.'&view=process',true,303);exit;
            }elseif($action==='save_timer_settings'){
                if($planStepId<1)throw new RuntimeException('Etapa do processamento não informada.');
                student_process_lab_update_timer_settings($db,$planId,$planStepId,$studentId,$_POST);
                header('Location: '.student_process_runner_step_url($testId,$planStepId),true,303);exit;
            }else throw new RuntimeException('Ação inválida.');
        }catch(StudentProcessExecutionConflict $e){
            if($ajax)student_process_runner_json(['error'=>$e->getMessage(),'state'=>$e->executionState],409);$error=$e->getMessage();
        }catch(Throwable $e){$error=$e->getMessage();if($ajax)student_process_runner_json(['error'=>$error],422);}
    }
    $plan=student_process_plan_for_test($db,$testId,$studentId);$allSteps=$plan?student_process_plan_steps($db,(int)$plan['id']):[];
    $completedCount=0;foreach($allSteps as $row)if((string)($row['status']??'')==='completed')$completedCount++;
    $planStarted=$plan&&((string)($plan['status']??'planned')!=='planned'||$completedCount>0);$planComplete=$plan&&(string)($plan['status']??'')==='completed';
    $sourceName=(string)($plan['source_name']??'Processamento');$backUrl='/aluno/teste.php?id='.$testId.'&view=process';$managerUrl='/aluno/processamentos.php?test='.$testId;$showIntentChoice=$plan&&!$planStarted&&$intent!=='live';
}else{
    $test=null;$plan=null;$planStarted=false;$planComplete=false;$showIntentChoice=false;$template=student_process_template_for_student($db,$templateId,$studentId);if(!$template){http_response_code(404);student_shell_start('Processamento não encontrado',null,$student);?><div class="student-empty">Processamento não encontrado.</div><?php student_shell_end();exit;}
    $allSteps=student_process_template_steps($db,$templateId);if(!$allSteps){header('Location: /aluno/processamentos.php?id='.$templateId,true,303);exit;}$sourceName=(string)$template['name'];$backUrl='/aluno/processamentos.php?id='.$templateId;$managerUrl=$backUrl;
}

if($showIntentChoice){
    student_shell_start('Processamento',null,$student);?>
    <header class="student-process-runner-head"><a class="student-back" href="<?=h($backUrl)?>">← Caderno</a><div><p class="student-kicker">Roteiro associado</p><h1 class="student-title"><?=h($sourceName)?></h1></div></header>
    <section class="student-process-section student-process-intent-gate" aria-labelledby="student-process-intent-title">
      <div class="student-process-editor-overview"><div><p class="student-kicker">Nenhuma execução iniciada</p><h2 id="student-process-intent-title">Como seguir?</h2></div></div>
      <div class="student-process-standard-grid student-process-intent-options">
        <article class="student-process-standard-card"><div class="student-process-standard-meta"><span>Agora</span></div><h3>Usar o modo laboratório</h3><div class="student-process-standard-footer"><span>Consulta e temporização</span><a class="button button-secondary button-compact" href="/aluno/processar.php?test=<?=$testId?>&amp;intent=live">Entrar no laboratório</a></div></article>
        <article class="student-process-standard-card"><div class="student-process-standard-meta"><span>Já realizado</span></div><h3>Registrar o processamento realizado</h3><div class="student-process-standard-footer"><span>Registro retroativo</span><a class="button button-secondary button-compact" href="/aluno/processamento-realizado.php?test=<?=$testId?>">Registrar</a></div></article>
      </div>
      <div class="student-process-editor-actions"><a class="student-link" href="<?=h($managerUrl)?>">Trocar roteiro</a></div>
    </section>
    <?php student_shell_end();exit;
}

$currentProcessStep=null;$activeStepId=0;$executionState=null;
if($testId>0&&$plan&&!$planComplete){$currentProcessStep=student_process_execution_current_step($db,$plan);$activeStepId=(int)($currentProcessStep['id']??0);if($activeStepId>0)$executionState=student_process_execution_for_test($db,$testId,$studentId);}
$requested=(int)($_GET['step']??0);$selected=null;$selectedIndex=0;
if($testId>0){
    foreach($allSteps as $i=>$row)if((int)$row['id']===$requested){$selected=$row;$selectedIndex=$i;break;}
    if(!$selected&&$activeStepId>0)foreach($allSteps as $i=>$row)if((int)$row['id']===$activeStepId){$selected=$row;$selectedIndex=$i;break;}
    if(!$selected&&$requested>0)foreach($allSteps as $i=>$row)if((int)$row['id']===$requested){$selected=$row;$selectedIndex=$i;break;}
    if(!$selected&&!$planComplete&&$allSteps){$selected=$allSteps[0];$selectedIndex=0;}
}else{
    $requestedNumber=max(1,(int)($_GET['step']??1));$selectedIndex=min(count($allSteps)-1,$requestedNumber-1);$selected=$allSteps[$selectedIndex];$activeStepId=(int)$selected['id'];
}
$current=$selected;$currentIndex=$selectedIndex;
$payload=$current?student_process_json_array((string)$current['payload_json']):[];$durationSeconds=$current?student_process_time_seconds((string)$current['duration']):null;$profile=$current?student_process_lab_agitation_profile($current):['mode'=>'none','duration'=>'','duration_seconds'=>null,'interval'=>'','interval_seconds'=>null,'legacy'=>''];
$storageKey=$current?($testId>0?'plan-'.$plan['id'].'-step-'.$current['id']:'template-'.$templateId.'-step-'.$current['id']):'';$csrf=$testId>0?csrf_token('student-process-runner-'.$testId):'';$reuseSource=(string)($payload['reuse_source_stage_key']??'');
$selectedCompleted=$testId>0&&$current&&(string)($current['status']??'')==='completed';$selectedActive=$testId===0||($current&&(int)$current['id']===$activeStepId&&!$selectedCompleted);$runnerState=$selectedActive?$executionState:null;$executionJson=$runnerState?json_encode($runnerState,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):'';
$activeIndex=null;if($testId>0&&$activeStepId>0)foreach($allSteps as $i=>$row)if((int)$row['id']===$activeStepId){$activeIndex=$i;break;}
$nextPending=null;if($testId>0&&$activeIndex!==null)for($i=$activeIndex+1;$i<count($allSteps);$i++)if((string)$allSteps[$i]['status']!=='completed'){$nextPending=$allSteps[$i];break;}
$agitationSummary='';if($profile['mode']==='continuous')$agitationSummary='Agitação contínua';elseif($profile['mode']==='periodic'&&$profile['interval_seconds'])$agitationSummary=$profile['duration_seconds']?'Agitar '.student_process_seconds_label((int)$profile['duration_seconds']).' a cada '.student_process_seconds_label((int)$profile['interval_seconds']):'Agitação a cada '.student_process_seconds_label((int)$profile['interval_seconds']);

student_shell_start('Modo laboratório',null,$student);?>
<header class="student-process-runner-head"><a class="student-back" href="<?=h($backUrl)?>">← <?=$testId>0?'Caderno':'Processamentos'?></a><div><p class="student-kicker">Modo laboratório</p><h1 class="student-title"><?=h($sourceName)?></h1></div></header>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<?php if(!$plan&&$testId>0):?><section class="student-process-runner-empty"><p class="student-kicker">Nenhum roteiro selecionado</p><h2>Escolha um processamento para este registro.</h2><a class="button button-primary" href="<?=h($managerUrl)?>">Escolher processamento</a></section>
<?php elseif($planComplete&&!$current):?><section class="student-process-runner-complete"><p class="student-kicker">Processamento concluído</p><h2>Sequência finalizada.</h2><p>Os valores deste processamento ficaram registrados no Caderno.</p><a class="button button-primary" href="<?=h($backUrl)?>">Voltar ao Caderno</a></section>
<?php elseif(!$current):?><section class="student-process-runner-empty"><h2>Nenhuma etapa disponível.</h2><a class="button button-primary" href="<?=h($backUrl)?>">Voltar</a></section>
<?php else:?>
<section class="student-process-runner<?=$selectedActive?' is-active-stage':' is-browsing-stage'?>"<?=$selectedActive?' data-process-runner data-duration-seconds="'.($durationSeconds===null?'':$durationSeconds).'" data-agitation-mode="'.h((string)$profile['mode']).'" data-agitation-duration-seconds="'.($profile['duration_seconds']===null?'':(int)$profile['duration_seconds']).'" data-agitation-interval-seconds="'.($profile['interval_seconds']===null?'':(int)$profile['interval_seconds']).'" data-storage-key="'.h($storageKey).'"'.($testId>0?' data-runner-endpoint="/aluno/processar.php?test='.$testId.'" data-runner-csrf="'.h($csrf).'" data-plan-id="'.(int)$plan['id'].'" data-plan-step-id="'.(int)$current['id'].'" data-execution-state="'.h($executionJson).'"':''):''?>>
<nav class="student-lab-stage-nav" aria-label="Etapas do processamento">
<?php foreach($allSteps as $i=>$step):$done=$testId>0&&(string)($step['status']??'')==='completed';$isSelected=(int)$step['id']===(int)$current['id'];$isActive=$testId>0&&(int)$step['id']===$activeStepId;$stepHref=$testId>0?student_process_runner_step_url($testId,(int)$step['id']):'/aluno/processar.php?template='.$templateId.'&step='.($i+1);?>
<a href="<?=h($stepHref)?>" class="<?=$done?'is-done ':''?><?=$isActive?'is-active ':''?><?=$isSelected?'is-selected':''?>"<?=$isSelected?' aria-current="step"':''?> aria-label="Etapa <?=($i+1)?>: <?=h((string)$step['label'])?>"><span><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><?php if($done):?><small>✓</small><?php elseif($isActive):?><small>●</small><?php endif;?></a>
<?php endforeach;?>
</nav>

<div class="student-process-runner-current"><p class="student-kicker">Etapa <?=($currentIndex+1)?> de <?=count($allSteps)?></p><h2><?=h((string)$current['label'])?></h2><?php $details=[];if((string)($payload['developer_name']??'')!=='')$details[]=(string)$payload['developer_name'];if((string)($payload['temperature']??'')!=='')$details[]=(string)$payload['temperature'];if($agitationSummary!=='')$details[]=$agitationSummary;elseif((string)($payload['agitation']??'')!=='')$details[]=(string)$payload['agitation'];if($details):?><p class="student-process-runner-details"><?=h(implode(' · ',$details))?></p><?php endif;?>
<?php if($reuseSource==='first_development'):?><div class="student-process-runner-instruction"><strong>Reutilize o banho da primeira revelação</strong><span>Use a mesma solução de revelador já preparada. Não prepare outro banho e não registre novo consumo.</span></div><?php elseif((string)($payload['notes']??'')!==''):?><div class="student-process-runner-instruction"><strong>Orientação desta etapa</strong><span><?=h((string)$payload['notes'])?></span></div><?php endif;?></div>

<?php if($testId>0&&!$selectedCompleted):?>
<details class="student-lab-timer-settings"><summary>Ajustar tempo e agitação</summary><form method="post" class="student-form-grid" data-agitation-settings><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="save_timer_settings"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="plan_step_id" value="<?=(int)$current['id']?>"><label class="form-field">Tempo da etapa<input name="duration" value="<?=h((string)$current['duration'])?>" placeholder="07:00"></label><label class="form-field">Agitação<select name="agitation_mode" data-agitation-mode><option value="none"<?=$profile['mode']==='none'?' selected':''?>>Sem temporização</option><option value="periodic"<?=$profile['mode']==='periodic'?' selected':''?>>Periódica</option><option value="continuous"<?=$profile['mode']==='continuous'?' selected':''?>>Contínua</option></select></label><div class="student-span-2 student-form-grid" data-agitation-periodic<?=$profile['mode']==='periodic'?'':' hidden'?>> <label class="form-field">Duração de cada agitação<input name="agitation_duration" value="<?=h((string)$profile['duration'])?>" placeholder="00:10"></label><label class="form-field">Intervalo entre inícios<input name="agitation_interval" value="<?=h((string)$profile['interval'])?>" placeholder="01:00"></label></div><div class="student-actions student-span-2"><button class="button button-secondary button-compact" type="submit">Salvar nesta fotografia</button></div></form></details>
<?php endif;?>

<?php if($selectedCompleted):?>
<div class="student-lab-stage-state is-complete"><strong>Etapa realizada</strong><span>Ela já faz parte deste processamento. Abrir outra etapa não altera o registro.</span></div>
<?php elseif($testId>0&&!$selectedActive):?>
<div class="student-lab-stage-state"><strong>Consulta</strong><?php if($currentProcessStep):?><span>O processo está em “<?=h((string)$currentProcessStep['label'])?>”. Você pode consultar qualquer etapa sem mudar a posição.</span><?php endif;?></div>
<form method="post" class="student-lab-select-form"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="advance_to_step"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="plan_step_id" value="<?=(int)$current['id']?>"><button class="button button-primary" type="submit">Estou nesta etapa</button><p>As etapas anteriores serão consideradas realizadas com os valores deste roteiro.</p></form>
<?php else:?>
<?php if($durationSeconds!==null):?><output class="student-process-runner-clock" data-runner-clock><?=h(student_process_seconds_label($runnerState['remaining_seconds']??$durationSeconds))?></output><div class="student-lab-agitation" data-runner-agitation aria-live="polite"><?php if($profile['mode']==='continuous'):?>Agitação contínua<?php elseif($profile['mode']==='periodic'&&$profile['interval_seconds']):?><?=$profile['duration_seconds']?'Agitar '.h(student_process_seconds_label((int)$profile['duration_seconds'])).' a cada '.h(student_process_seconds_label((int)$profile['interval_seconds'])):'Agitação a cada '.h(student_process_seconds_label((int)$profile['interval_seconds']))?><?php else:?>Sem aviso de agitação<?php endif;?></div><p class="student-process-runner-cue" data-runner-cue aria-live="polite"></p><div class="student-process-runner-status"><span data-runner-state-status><?=h(match((string)($runnerState['state']??'idle')){'running'=>'Cronômetro em andamento','paused'=>'Cronômetro pausado','elapsed'=>'Tempo concluído',default=>'Pronto para iniciar'})?></span><span data-runner-wake-status>Tela ativa ao iniciar</span></div><div class="student-actions student-process-runner-controls"><button class="button button-primary" type="button" data-runner-start>Iniciar</button><button class="button button-secondary" type="button" data-runner-pause disabled>Pausar</button><button class="button button-secondary" type="button" data-runner-reset>Reiniciar</button></div><?php else:?><div class="student-process-runner-untimed"><strong>Sem cronômetro</strong><p>O tempo pode ser acrescentado em “Ajustar tempo e agitação” se for necessário.</p></div><?php endif;?>
<div class="student-process-runner-advance">
<?php if($testId>0):?><?php if($nextPending):?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="advance_to_step"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="plan_step_id" value="<?=(int)$nextPending['id']?>"><button class="button button-primary" type="submit">Ir para próxima etapa</button></form><?php else:?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="finish_process"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="plan_step_id" value="<?=(int)$current['id']?>"><button class="button button-primary" type="submit">Concluir processamento</button></form><?php endif;?><?php else:$nextNumber=$currentIndex+2;$nextHref=isset($allSteps[$currentIndex+1])?'/aluno/processar.php?template='.$templateId.'&step='.$nextNumber:'/aluno/processamentos.php?id='.$templateId;?><a class="button button-primary" href="<?=h($nextHref)?>"><?=isset($allSteps[$currentIndex+1])?'Próxima etapa':'Sair do processamento'?></a><?php endif;?>
</div>
<?php endif;?>

<details class="student-lab-route-list"><summary>Ver roteiro completo</summary><ol><?php foreach($allSteps as $i=>$step):$done=$testId>0&&(string)($step['status']??'')==='completed';?><li class="<?=$done?'is-done':''?>"><span><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><strong><?=h((string)$step['label'])?></strong><small><?=h((string)$step['duration']!==''?student_process_seconds_label(student_process_time_seconds((string)$step['duration'])):'—')?></small></li><?php endforeach;?></ol></details>
</section>
<?php endif;?>
<?php student_shell_end();
