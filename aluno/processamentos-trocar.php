<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_process_replanning.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);$testId=(int)($_GET['test']??$_POST['test']??0);$from=(string)($_GET['from']??$_POST['from']??'record');if(!in_array($from,['record','recorded'],true))$from='record';
$next='/aluno/processamentos-trocar.php?test='.$testId.'&from='.rawurlencode($from);
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];student_tool_require($db,$studentId,'lab_timer');$record=student_test_for_student($db,$testId,$studentId);
if(!$record){http_response_code(404);student_shell_start('Registro não encontrado',null,$student);?><div class="student-empty">Registro não encontrado.</div><?php student_shell_end();exit;}
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-replanning-'.$testId,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{
        $source=(string)($_POST['source']??'');
        if($source==='template')$plan=student_process_replan_template($db,(int)($_POST['template_id']??0),$testId,$studentId);
        elseif($source==='standard')$plan=student_process_replan_standard($db,(string)($_POST['standard_key']??''),$testId,$studentId);
        else throw new RuntimeException('Escolha um roteiro.');
        $_SESSION['student_process_notice']=student_process_replanning_notice($plan);
        $target=$from==='recorded'?'/aluno/processamento-realizado.php?test='.$testId:'/aluno/teste.php?id='.$testId.'&view=process';
        header('Location: '.$target,true,303);exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}
$plan=student_process_plan_for_test($db,$testId,$studentId);$facts=student_process_steps($db,$testId);$templates=student_process_templates($db,$studentId);$standards=student_process_standard_catalog();
$currentStarted=$plan?student_process_replanning_current_started_step($db,$plan):null;$csrf=csrf_token('student-process-replanning-'.$testId);$back=$from==='recorded'?'/aluno/processamento-realizado.php?test='.$testId:'/aluno/teste.php?id='.$testId.'&view=process';
$currentTemplateId=(int)($plan['source_template_id']??0);$currentGlobalVersionId=(int)($plan['source_global_version_id']??0);
student_shell_start('Alterar próximas etapas',null,$student);?>
<div class="student-appbar"><a class="student-back" href="<?=h($back)?>">← Processamento</a></div>
<header class="student-page-heading student-replanning-heading"><div><p class="student-kicker">Caderno</p><h1 class="student-title">Alterar próximas etapas</h1><p class="student-process-context"><strong><?=count($facts)?> etapa<?=count($facts)===1?'':'s'?> registrada<?=count($facts)===1?'':'s'?></strong> permanece<?=count($facts)===1?'':'m'?> no histórico. A escolha abaixo muda somente o que vem depois.</p></div></header>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<?php if($currentStarted):?><p class="ui-alert ui-alert-notice">A etapa atual já foi iniciada. Se você trocar o roteiro agora, ela será preservada como <strong>interrompida</strong>, com o tempo decorrido quando ele estiver disponível.</p><?php endif;?>
<?php if($plan):?><section class="student-replanning-current"><span>Roteiro atual</span><strong><?=h((string)$plan['source_name'])?></strong></section><?php endif;?>

<section class="student-process-section" aria-labelledby="replanning-personal-title">
  <div class="student-process-section-heading"><div><p class="student-kicker">Sua biblioteca</p><h2 class="student-subtitle" id="replanning-personal-title">Meus processamentos</h2></div></div>
  <?php if(!$templates):?><div class="student-process-empty"><p>Você ainda não tem um roteiro pessoal salvo.</p></div><?php else:?><div class="student-process-library">
  <?php foreach($templates as $template):$isCurrent=$currentTemplateId===(int)$template['id'];$hasSteps=(int)$template['step_count']>0;?>
    <article class="student-process-template-card<?=$isCurrent?' is-current':''?>"><div class="student-process-template-main"><span class="student-process-step-number"><?=str_pad((string)(int)$template['step_count'],2,'0',STR_PAD_LEFT)?></span><div><div class="student-replanning-title-row"><h3><?=h((string)$template['name'])?></h3><?php if($isCurrent):?><span class="student-status">Atual</span><?php endif;?></div><div class="student-process-summary"><span><?=(int)$template['step_count']?> <?=((int)$template['step_count']===1?'etapa':'etapas')?></span><span><?=h(student_process_template_duration_summary($db,(int)$template['id']))?></span></div></div></div><div class="student-process-template-actions"><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="test" value="<?=$testId?>"><input type="hidden" name="from" value="<?=h($from)?>"><input type="hidden" name="source" value="template"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><button class="button <?=$isCurrent?'button-secondary':'button-primary'?> button-compact" type="submit"<?=$hasSteps?'':' disabled'?>><?=$isCurrent?'Reaplicar daqui em diante':'Usar nas próximas etapas'?></button></form></div></article>
  <?php endforeach;?></div><?php endif;?>
</section>

<section class="student-process-section student-process-standards" aria-labelledby="replanning-standards-title">
  <div class="student-process-section-heading"><div><p class="student-kicker">Padrões do workshop</p><h2 class="student-subtitle" id="replanning-standards-title">Roteiros conhecidos</h2></div></div>
  <div class="student-process-standard-grid">
  <?php foreach($standards as $standardKey=>$standard):$ei=str_contains((string)$standardKey,'ei400')?'EI 400':'EI 200';$route=str_contains((string)$standardKey,'ferric-ammonia')?'FeCl₃ + amônia':'Peracética';$isCurrent=$currentGlobalVersionId>0&&$currentGlobalVersionId===(int)($standard['version_id']??0);?>
    <article class="student-process-standard-card<?=$isCurrent?' is-current':''?>"><div class="student-process-standard-meta"><span><?=h($ei)?></span><span><?=str_contains((string)$standardKey,'caffenol')?'Caffenol':'Parodinal'?></span><span><?=h($route)?></span><?php if($isCurrent):?><span>Atual</span><?php endif;?></div><h3><?=h((string)$standard['name'])?></h3><div class="student-process-standard-footer"><span><?=count((array)$standard['steps'])?> etapas · <?=h(student_process_standard_duration_summary((array)$standard['steps']))?></span><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="test" value="<?=$testId?>"><input type="hidden" name="from" value="<?=h($from)?>"><input type="hidden" name="source" value="standard"><input type="hidden" name="standard_key" value="<?=h((string)$standardKey)?>"><button class="button <?=$isCurrent?'button-secondary':'button-primary'?> button-compact" type="submit"><?=$isCurrent?'Reaplicar daqui em diante':'Usar nas próximas etapas'?></button></form></div></article>
  <?php endforeach;?></div>
</section>
<?php student_shell_end();
