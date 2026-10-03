<?php
declare(strict_types=1);
function fail_execution_state(string $message): never {fwrite(STDERR,"student-process-execution-state: $message\n");exit(1);}
function must_execution_state(bool $ok,string $message): void {if(!$ok)fail_execution_state($message);}
function utc_now(): string {return gmdate('c');}
function student_workspace_text(mixed $value,int $max): string {return mb_substr(trim((string)$value),0,$max);}
function student_process_json_array(string $json): array {$v=json_decode($json,true);return is_array($v)?$v:[];}
function student_process_time_seconds(string $value): ?int {
    $value=trim(mb_strtolower($value));if($value==='')return null;
    if(preg_match('/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/',$value,$m))return isset($m[3])&&$m[3]!==''?(int)$m[1]*3600+(int)$m[2]*60+(int)$m[3]:(int)$m[1]*60+(int)$m[2];
    if(preg_match('/^(\d+(?:[.,]\d+)?)\s*(?:s|seg|segs|segundo|segundos)$/u',$value,$m))return (int)round((float)str_replace(',','.',$m[1]));
    if(preg_match('/^(\d+(?:[.,]\d+)?)\s*(?:m|min|mins|minuto|minutos)$/u',$value,$m))return (int)round((float)str_replace(',','.',$m[1])*60);
    return ctype_digit($value)?(int)$value:null;
}
function student_process_seconds_label(?int $seconds): string {
    if($seconds===null)return 'Sem tempo definido';$seconds=max(0,$seconds);$h=intdiv($seconds,3600);$m=intdiv($seconds%3600,60);$s=$seconds%60;
    return $h>0?sprintf('%02d:%02d:%02d',$h,$m,$s):sprintf('%02d:%02d',$m,$s);
}
function student_process_plan_steps(PDO $db,int $planId): array {$q=$db->prepare('SELECT * FROM student_process_plan_steps WHERE plan_id=? ORDER BY position,id');$q->execute([$planId]);return $q->fetchAll(PDO::FETCH_ASSOC);}
function student_process_plan_next_step(PDO $db,array $plan): ?array {foreach(student_process_plan_steps($db,(int)$plan['id']) as $step)if((string)$step['status']!=='completed')return $step;return null;}
function student_process_plan_for_test(PDO $db,int $testId,int $studentId): ?array {$q=$db->prepare('SELECT * FROM student_process_plans WHERE test_id=? AND student_id=? ORDER BY id DESC LIMIT 1');$q->execute([$testId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function student_process_plan_complete_step(PDO $db,int $planId,int $stepId,int $studentId): array {
    $q=$db->prepare("SELECT * FROM student_process_plan_steps WHERE id=? AND plan_id=? AND status!='completed'");$q->execute([$stepId,$planId]);$step=$q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Etapa inválida.');
    $now=utc_now();$db->prepare("UPDATE student_process_plan_steps SET status='completed',completed_at=?,updated_at=? WHERE id=?")->execute([$now,$now,$stepId]);return $step;
}
function student_process_recording_mode_for_test(PDO $db,int $testId,int $studentId): string {return 'live';}
function student_process_recording_meta(PDO $db,int $testId,int $studentId): ?array {return null;}
function student_process_recording_save_meta(PDO $db,int $testId,int $studentId,string $mode,?string $performedOn,string $notes): array {return ['entry_mode'=>$mode];}
function student_process_recording_add_step(PDO $db,int $testId,int $studentId,array $input,string $mode='retroactive'): array {
    $position=(int)$db->query('SELECT COALESCE(MAX(position),0)+1 FROM student_process_steps WHERE test_id='.(int)$testId)->fetchColumn();$now=utc_now();$metadata=json_encode(['recording_mode'=>$mode],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $q=$db->prepare('INSERT INTO student_process_steps(test_id,position,stage_key,label,duration,agitation,metadata_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');
    $q->execute([$testId,$position,(string)($input['stage_key']??''),(string)($input['custom_label']??$input['label']??$input['stage_key']??''),(string)($input['duration']??''),(string)($input['agitation']??''),$metadata,$now,$now]);$id=(int)$db->lastInsertId();
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:[];
}
function student_process_change_log(PDO $db,string $actorRole,?int $adminId,?int $studentId,string $entityType,int $entityId,string $action,array $before=[],array $after=[],string $note=''): void {}

require_once dirname(__DIR__).'/app/student_process_execution.php';
require_once dirname(__DIR__).'/app/student_process_lab_navigation.php';

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec("CREATE TABLE student_process_plans(id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,test_id INTEGER NOT NULL,status TEXT NOT NULL DEFAULT 'planned',started_at TEXT NULL,completed_at TEXT NULL,updated_at TEXT NOT NULL);
CREATE TABLE student_process_plan_steps(id INTEGER PRIMARY KEY,plan_id INTEGER NOT NULL,position INTEGER NOT NULL,stage_key TEXT NOT NULL,label TEXT NOT NULL,duration TEXT NOT NULL DEFAULT '',agitation_interval TEXT NOT NULL DEFAULT '',payload_json TEXT NOT NULL DEFAULT '{}',status TEXT NOT NULL DEFAULT 'planned',actual_step_id INTEGER NULL,started_at TEXT NULL,completed_at TEXT NULL,updated_at TEXT NOT NULL);
CREATE TABLE student_process_execution_sessions(plan_id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,plan_step_id INTEGER NULL,state TEXT NOT NULL DEFAULT 'idle',duration_seconds INTEGER NULL,remaining_seconds INTEGER NULL,timer_started_at TEXT NULL,timer_ends_at TEXT NULL,paused_at TEXT NULL,revision INTEGER NOT NULL DEFAULT 0,last_client_token TEXT NOT NULL DEFAULT '',created_at TEXT NOT NULL,updated_at TEXT NOT NULL);
CREATE TABLE student_process_steps(id INTEGER PRIMARY KEY AUTOINCREMENT,test_id INTEGER NOT NULL,position INTEGER NOT NULL,stage_key TEXT NOT NULL,label TEXT NOT NULL,duration TEXT NOT NULL DEFAULT '',agitation TEXT NOT NULL DEFAULT '',metadata_json TEXT NOT NULL DEFAULT '{}',created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$now=utc_now();$db->prepare("INSERT INTO student_process_plans(id,student_id,test_id,status,updated_at) VALUES(1,7,40,'planned',?)")->execute([$now]);
$insert=$db->prepare("INSERT INTO student_process_plan_steps(id,plan_id,position,stage_key,label,duration,agitation_interval,payload_json,status,updated_at) VALUES(?,?,?,?,?,?,?,?, 'planned',?)");
$insert->execute([11,1,1,'first_development','Primeira revelação','7:00','1:00',json_encode(['agitation'=>'10 s','temperature'=>'26 °C']),$now]);
$insert->execute([12,1,2,'wash_after_first','Lavagem','1:00','',json_encode([]),$now]);
$insert->execute([13,1,3,'second_development','Segunda revelação','7:00','',json_encode(['agitation'=>'contínua']),$now]);

$plan=student_process_execution_plan($db,1,7);$session=student_process_execution_ensure($db,$plan,7);
must_execution_state(is_array($session)&&$session['state']==='idle','new timer session must start idle');
must_execution_state((int)$session['plan_step_id']===11,'timer session must bind to the current process stage');
must_execution_state((int)$session['duration_seconds']===420&&(int)$session['remaining_seconds']===420,'timer must inherit the stage snapshot duration');

$started=student_process_execution_transition($db,1,11,7,'start','token-start',0);
must_execution_state($started['state']==='running','start must persist timer state');
$planRow=$db->query('SELECT * FROM student_process_plans WHERE id=1')->fetch();$stepRow=$db->query('SELECT * FROM student_process_plan_steps WHERE id=11')->fetch();
must_execution_state($planRow['status']==='planned'&&$planRow['started_at']===null,'starting the timer must not declare that the photographic process advanced');
must_execution_state($stepRow['status']==='planned'&&$stepRow['started_at']===null,'timer state must not become factual stage state');

$idempotent=student_process_execution_transition($db,1,11,7,'start','token-start',0);
must_execution_state($idempotent['state']==='running'&&(int)$idempotent['revision']===1,'replayed timer token must be idempotent');
$conflicted=false;try{student_process_execution_transition($db,1,11,7,'pause','token-stale',0);}catch(StudentProcessExecutionConflict $e){$conflicted=true;}
must_execution_state($conflicted,'stale timer revision must still be rejected');
$paused=student_process_execution_transition($db,1,11,7,'pause','token-pause',1);must_execution_state($paused['state']==='paused','pause must persist timer state');

$db->prepare("UPDATE student_process_execution_sessions SET duration_seconds=420,remaining_seconds=300,state='paused',revision=2 WHERE plan_id=1")->execute();
$retimed=student_process_execution_retime($db,1,11,7,480);
must_execution_state($retimed!==null&&$retimed['state']==='paused'&&(int)$retimed['remaining_seconds']===360,'changing timer duration must preserve elapsed time without creating a process fact');

$profile=student_process_lab_agitation_profile($db->query('SELECT * FROM student_process_plan_steps WHERE id=11')->fetch());
must_execution_state($profile['mode']==='periodic'&&(int)$profile['duration_seconds']===10&&(int)$profile['interval_seconds']===60,'legacy duration plus interval must resolve as periodic agitation');
student_process_lab_update_timer_settings($db,1,11,7,['duration'=>'8:00','agitation_mode'=>'periodic','agitation_duration'=>'10 s','agitation_interval'=>'60 s']);
$step11=$db->query('SELECT * FROM student_process_plan_steps WHERE id=11')->fetch();$payload11=student_process_json_array((string)$step11['payload_json']);
must_execution_state($step11['duration']==='08:00'&&$step11['agitation_interval']==='01:00','timer adjustment must update only this plan snapshot');
must_execution_state(($payload11['agitation_mode']??'')==='periodic'&&($payload11['agitation_duration']??'')==='00:10','periodic agitation must store mode and pulse duration');

student_process_lab_advance_to_step($db,1,13,7);
$rows=student_process_plan_steps($db,1);
must_execution_state($rows[0]['status']==='completed'&&$rows[1]['status']==='completed'&&$rows[2]['status']==='running','declaring a later current stage must confirm preceding route stages and make the target current');
must_execution_state((int)$db->query('SELECT COUNT(*) FROM student_process_steps WHERE test_id=40')->fetchColumn()===2,'reaching stage 3 must materialize stages 1 and 2 as facts');
$facts=$db->query('SELECT * FROM student_process_steps WHERE test_id=40 ORDER BY position')->fetchAll();
must_execution_state($facts[0]['duration']==='08:00'&&$facts[1]['duration']==='1:00','materialized facts must use the current route snapshot values');
must_execution_state(!str_contains((string)$facts[0]['metadata_json'],'interrupted'),'leaving the app timer behind must never invent an interrupted laboratory event');
$newSession=student_process_execution_session($db,1,7);must_execution_state($newSession&&(int)$newSession['plan_step_id']===13&&$newSession['state']==='idle','advancing process position must reset the timer for the new current stage');

student_process_lab_update_timer_settings($db,1,13,7,['duration'=>'7:30','agitation_mode'=>'continuous']);
$step13=$db->query('SELECT * FROM student_process_plan_steps WHERE id=13')->fetch();$profile13=student_process_lab_agitation_profile($step13);
must_execution_state($step13['duration']==='07:30'&&$profile13['mode']==='continuous','continuous agitation must be a first-class timer setting');
$session13=student_process_execution_session($db,1,7);must_execution_state($session13&&(int)$session13['duration_seconds']===450,'changing the current stage timer must retime its session');

student_process_lab_finish_process($db,1,13,7);
must_execution_state($db->query("SELECT status FROM student_process_plans WHERE id=1")->fetchColumn()==='completed','finishing the last stage must complete the process');
must_execution_state((int)$db->query('SELECT COUNT(*) FROM student_process_steps WHERE test_id=40')->fetchColumn()===3,'final stage must be materialized exactly once');
$finalFact=$db->query('SELECT * FROM student_process_steps WHERE test_id=40 ORDER BY position DESC LIMIT 1')->fetch();
must_execution_state($finalFact['duration']==='07:30'&&str_contains((string)$finalFact['metadata_json'],'"agitation_mode":"continuous"'),'final fact must retain adjusted duration and continuous agitation metadata');
must_execution_state((int)$db->query('SELECT COUNT(*) FROM student_process_execution_sessions WHERE plan_id=1')->fetchColumn()===0,'finishing the process must clear the timer session');

echo "student-process-execution-state: ok\n";
