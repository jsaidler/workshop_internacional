<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/student_workbench.php';
require_once dirname(__DIR__).'/app/student_process_templates.php';
require_once dirname(__DIR__).'/app/student_process_lab_navigation.php';
require_once dirname(__DIR__).'/app/student_process_runner_view.php';
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function student_process_runner_step_url(int $testId,int $stepId):string {return '/aluno/processar.php?test='.$testId.'&step='.$stepId;}
function ensure_runner_view(bool $ok,string $description):void {
 if(!$ok){fwrite(STDERR,"student-process-runner-view: ".$description."\n");exit(1);}
}
function render_runner(string $state='idle',bool $done=false,string $agitation='periodic',string $name='Roteiro sintético'):string {
 $labels=['Primeira revelação','Lavagem','Cloreto férrico','Lavagem','Amônia','Lavagem','Segunda revelação','Lavagem final','Secagem'];
 $keys=['first_development','wash_after_first','ferric','wash_after_bleach','ammonia','wash_after_ammonia','second_development','final_wash','dry'];$steps=[];
 foreach($keys as $i=>$key)$steps[]=['id'=>301+$i,'stage_key'=>$key,'label'=>$labels[$i],'duration'=>$i===6?'07:00':'',
  'status'=>in_array($i,[0,2,5],true)?'completed':'planned','payload_json'=>'{}'];
 $steps[6]['status']=$done?'completed':'planned';$current=$steps[6];
 $current['payload_json']=json_encode(['developer_name'=>'Parodinal','temperature'=>'26 °C','reuse_source_stage_key'=>'first_development','agitation_mode'=>$agitation,'agitation_interval'=>$agitation==='periodic'?'01:00':'','agitation_duration'=>$agitation==='periodic'?'00:10':''],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 $payload=student_process_json_array((string)$current['payload_json']);$profile=student_process_lab_agitation_profile($current);
 $time=['state'=>$state,'remaining_seconds'=>$state==='elapsed'?0:420,'revision'=>4,'plan_step_id'=>307,'server_now'=>gmdate('c')];
 $input=['allSteps'=>$steps,'current'=>$current,'currentIndex'=>6,'payload'=>$payload,'durationSeconds'=>420,
  'profile'=>$profile,'reuseSource'=>'first_development','storageKey'=>'fixture','runnerState'=>$time,
  'executionJson'=>json_encode($time,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'counts'=>['completed'=>3+(int)$done,'total'=>9],
  'currentCompleted'=>$done,'testId'=>7,'templateId'=>0,'plan'=>['id'=>91],'sourceName'=>$name,
  'backUrl'=>'/aluno/teste.php?id=7#processamento','csrf'=>'synthetic-csrf',
  'error'=>'','agitationSummary'=>'Agitar 00:10 a cada 01:00','saved'=>false];
 ob_start();student_process_render_runner_view($input);return (string)ob_get_clean();
}
$html=render_runner();
ensure_runner_view(str_contains($html,'data-operational-runner-view')&&str_contains($html,'data-process-runner'),'shared runner root missing');
ensure_runner_view(substr_count($html,'aria-label="Etapa ')===9,'must provide all 9 stage links');
ensure_runner_view(str_contains($html,'href="/aluno/processar.php?test=7&amp;step=307"')&&str_contains($html,'aria-current="step"'),'selected stage is a URL, not progress');
ensure_runner_view(str_contains($html,'name="action" value="toggle_complete"')&&str_contains($html,'name="completed" value="1"'),'reversible mark must remain explicit POST');
ensure_runner_view(str_contains($html,'name="action" value="save_step"')&&str_contains($html,'name="_csrf" value="synthetic-csrf"'),'step editor must retain CSRF');
ensure_runner_view(str_contains($html,'data-runner-endpoint="/aluno/processar.php?test=7"')&&str_contains($html,'data-runner-clock'),'timer stays bound to server and visible');
ensure_runner_view(str_contains($html,'Reutilizar o banho da primeira revelação'),'reuse instruction missing');
ensure_runner_view(str_contains($html,'Movimentar estoque')&&str_contains($html,'href="/aluno/inventario.php"'),'inventory must be independent');
ensure_runner_view(!str_contains($html,'Ir para próxima etapa')&&!str_contains($html,'Concluir processamento'),'no forced workflow allowed');
ensure_runner_view(!preg_match('~</(?:div|label|details|form)(?!>)~i',$html),'malformed tag');
$done=render_runner('elapsed',true);
ensure_runner_view(str_contains($done,'Etapa marcada como concluída')&&str_contains($done,'name="completed" value="0"')&&str_contains($done,'Desmarcar etapa'),'completed check must be reversible');
ensure_runner_view(str_contains(render_runner('elapsed',false),'Etapa não marcada'),'timer elapsed must not imply completion');
ensure_runner_view(str_contains(render_runner('running',false,'continuous'),'Agitação contínua'),'continuous agitation must have independent presentation');
$unsafe=render_runner('idle',false,'none','<img src=x onerror=alert(1)>');
ensure_runner_view(!str_contains($unsafe,'<img src=x onerror=alert(1)>')&&str_contains($unsafe,'&lt;img'),'renderer must escape user-defined process title');
$root=dirname(__DIR__);
$route=(string)file_get_contents($root.'/aluno/processar.php');
$fixture=(string)file_get_contents($root.'/tools/browser-fixture/student-process-runner-operational.php');
ensure_runner_view(str_contains($route,"require_once __DIR__.'/../app/student_process_runner_view.php'")&&str_contains($route,'student_process_render_runner_view(['),'real authenticated route must compose shared view');
foreach(['student_tool_require($db,$studentId','verify_csrf(','student_process_notebook_timer_transition(','student_process_notebook_set_completed(','student_process_notebook_update_step('] as $authority)
 ensure_runner_view(str_contains($route,$authority),'controller lost security or server-owned mutation: '.$authority);
ensure_runner_view(str_contains($fixture,"require_once __DIR__.'/../../app/student_process_runner_view.php'")&&str_contains($fixture,'student_process_render_runner_view($view)'),'fixture must share renderer');
ensure_runner_view(str_contains($fixture,'405')&&str_contains($fixture,'REQUEST_METHOD'),'fixture cannot mutate laboratory facts');
echo "student-process-runner-view: ok\n";
