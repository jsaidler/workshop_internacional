<?php
declare(strict_types=1);
/* A read-only laboratory scenario projected over the authenticated runner's actual PHP view.
   Synthetic events and steps are not facts from any student; writes are disabled here. */
require_once __DIR__.'/../../app/student_workbench.php';
require_once __DIR__.'/../../app/student_process_templates.php';
require_once __DIR__.'/../../app/student_process_lab_navigation.php';
require_once __DIR__.'/../../app/student_process_runner_view.php';
require_once __DIR__.'/../../app/student_shell.php';
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function database():PDO{
 static $db=null;if($db instanceof PDO)return $db;
 $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $db->exec('CREATE TABLE student_tools(tool_key TEXT,enabled INTEGER,access_mode TEXT,sort_order INTEGER,label TEXT);
 CREATE TABLE student_tool_courses(tool_key TEXT,course_id INTEGER);
 CREATE TABLE course_cohorts(id INTEGER,course_id INTEGER,status TEXT);
 CREATE TABLE course_enrollments(id INTEGER,cohort_id INTEGER,student_id INTEGER,status TEXT)');
 return $db;
}
function student_account_current(PDO $db):?array{return ['id'=>1,'name'=>'Aluno de teste'];}
function csrf_token(string $scope):string{return 'fixture-runner-csrf';}
function cms_design_defaults():array{return ['colors'=>['bg'=>'#101010']];}
function cms_design_font_import_css(array $design):string{return '';}
function cms_design_css(array $design):string{return ':root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Arial,sans-serif;--mono:"Courier New",monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}';}
function student_process_runner_step_url(int $testId,int $stepId):string{return '/aluno/processar.php?test='.$testId.'&step='.$stepId;}
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit('Fixture does not process mutation requests');}
$state=(string)($_GET['state']??'idle');
$agitation=(string)($_GET['agitation']??'periodic');
if(!in_array($state,['idle','running','paused','elapsed'],true)||!in_array($agitation,['none','continuous','periodic'],true)){http_response_code(400);exit('Invalid scenario');}
$keys=['first_development','wash_after_first','ferric','wash_after_bleach','ammonia','wash_after_ammonia','second_development','final_wash','dry'];
$labels=['Primeira revelação','Lavagem','Branqueamento — cloreto férrico','Lavagem após branqueamento','Limpeza — amônia','Lavagem após amônia','Segunda revelação','Lavagem final','Secagem'];
$durations=['07:00','01:00','01:30','01:00','','01:00','07:00','01:00',''];
$steps=[];
foreach($keys as $i=>$key){
 $payload=[];
 if($i===0||$i===6)$payload=['developer_name'=>'Parodinal','developer_amount'=>'10','water_amount'=>'540','temperature'=>'26 °C'];
 if($i===6){
  $payload['reuse_source_stage_key']='first_development';$payload['agitation_mode']=$agitation;
  if($agitation==='periodic'){$payload['agitation_duration']='00:10';$payload['agitation_interval']='01:00';}
 }
 $steps[]=['id'=>301+$i,'position'=>$i+1,'stage_key'=>$key,'label'=>$labels[$i],
  'duration'=>$durations[$i],'agitation_interval'=>$i===6&&$agitation==='periodic'?'01:00':'',
  'payload_json'=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
  'status'=>in_array($i,[0,2,5],true)?'completed':'planned'];
}
$current=$steps[6];$payload=student_process_json_array((string)$current['payload_json']);
$durationSeconds=student_process_time_seconds((string)$current['duration']);
$profile=student_process_lab_agitation_profile($current);
$remaining=match($state){'running'=>300,'paused'=>233,'elapsed'=>0,default=>420};
$now=new DateTimeImmutable('now',new DateTimeZone('UTC'));
$runnerState=[
 'state'=>$state,'remaining_seconds'=>$remaining,'duration_seconds'=>420,'revision'=>4,
 'plan_id'=>91,'plan_step_id'=>307,
 'timer_started_at'=>$state==='running'?$now->modify('-120 seconds')->format(DATE_ATOM):null,
 'timer_ends_at'=>$state==='running'?$now->modify('+300 seconds')->format(DATE_ATOM):null,
 'paused_at'=>$state==='paused'?$now->format(DATE_ATOM):null,
 'server_now'=>$now->format(DATE_ATOM),
];
if((string)($_GET['done']??'')==='1')$current['status']='completed';
$view=[
 'allSteps'=>$steps,'current'=>$current,'currentIndex'=>6,
 'payload'=>$payload,'durationSeconds'=>$durationSeconds,'profile'=>$profile,
 'reuseSource'=>(string)($payload['reuse_source_stage_key']??''),'storageKey'=>'notebook-plan-91-step-307',
 'runnerState'=>$runnerState,'executionJson'=>json_encode($runnerState,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
 'counts'=>['completed'=>3+(($current['status']==='completed')?1:0),'total'=>count($steps)],
 'currentCompleted'=>$current['status']==='completed','testId'=>7,'templateId'=>0,
 'plan'=>['id'=>91,'test_id'=>7],'sourceName'=>'Positivo direto — Parodinal EI 200 — FeCl₃ + amônia',
 'backUrl'=>'/aluno/teste.php?id=7#processamento','csrf'=>csrf_token('student-process-runner-7'),
 'error'=>(string)($_GET['error']??''),'agitationSummary'=>match($agitation){
  'continuous'=>'Agitação contínua',
  'periodic'=>'Agitar 00:10 a cada 01:00',
  default=>'',
 },'saved'=>(string)($_GET['saved']??'')==='1',
];
$_GET['test']=7;$_SERVER['REQUEST_URI']='/aluno/processar.php?test=7';
student_shell_start('Roteiro',null,student_account_current(database()));
student_process_render_runner_view($view);
student_shell_end();
