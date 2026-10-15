<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_process_runner_view.php';
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

student_shell_start('Roteiro',null,$student);
student_process_render_runner_view([
    'allSteps'=>$allSteps,
    'current'=>$current,
    'currentIndex'=>$currentIndex,
    'payload'=>$payload,
    'durationSeconds'=>$durationSeconds,
    'profile'=>$profile,
    'reuseSource'=>$reuseSource,
    'storageKey'=>$storageKey,
    'runnerState'=>$runnerState,
    'executionJson'=>$executionJson,
    'counts'=>$counts,
    'currentCompleted'=>$currentCompleted,
    'testId'=>$testId,
    'templateId'=>$templateId,
    'plan'=>$plan,
    'sourceName'=>$sourceName,
    'backUrl'=>$backUrl,
    'csrf'=>$csrf,
    'error'=>$error,
    'agitationSummary'=>$agitationSummary,
    'saved'=>isset($_GET['saved'])
]);
student_shell_end();
