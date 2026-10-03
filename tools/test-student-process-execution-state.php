<?php
declare(strict_types=1);
function fail_execution_state(string $message): never {fwrite(STDERR,"student-process-execution-state: $message\n");exit(1);}
function must_execution_state(bool $ok,string $message): void {if(!$ok)fail_execution_state($message);}
function utc_now(): string {return gmdate('c');}
function student_process_time_seconds(string $value): ?int {
    $value=trim($value);if($value==='')return null;
    if(preg_match('/^(\d+):(\d{1,2})$/',$value,$m))return (int)$m[1]*60+(int)$m[2];
    return ctype_digit($value)?(int)$value:null;
}
function student_process_plan_next_step(PDO $db,array $plan): ?array {
    $q=$db->prepare("SELECT * FROM student_process_plan_steps WHERE plan_id=? AND status!='completed' ORDER BY position,id LIMIT 1");$q->execute([(int)$plan['id']]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}
function student_process_plan_for_test(PDO $db,int $testId,int $studentId): ?array {
    $q=$db->prepare('SELECT * FROM student_process_plans WHERE test_id=? AND student_id=? ORDER BY id DESC LIMIT 1');$q->execute([$testId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}
function student_process_plan_complete_step(PDO $db,int $planId,int $stepId,int $studentId): array {
    $q=$db->prepare("SELECT * FROM student_process_plan_steps WHERE id=? AND plan_id=? AND status!='completed'");$q->execute([$stepId,$planId]);$step=$q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Etapa inválida.');
    $now=utc_now();$db->prepare("UPDATE student_process_plan_steps SET status='completed',completed_at=?,updated_at=? WHERE id=?")->execute([$now,$now,$stepId]);
    $pending=(int)$db->query("SELECT COUNT(*) FROM student_process_plan_steps WHERE plan_id=".$planId." AND status!='completed'")->fetchColumn();
    if($pending===0)$db->prepare("UPDATE student_process_plans SET status='completed',completed_at=?,updated_at=? WHERE id=? AND student_id=?")->execute([$now,$now,$planId,$studentId]);
    return $step;
}
function student_process_recording_mode_for_test(PDO $db,int $testId,int $studentId): string {return 'live';}
function student_process_recording_meta(PDO $db,int $testId,int $studentId): ?array {return null;}
function student_process_recording_save_meta(PDO $db,int $testId,int $studentId,string $mode,?string $performedOn,string $notes): array {return ['entry_mode'=>$mode];}

require_once dirname(__DIR__).'/app/student_process_execution.php';

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec("CREATE TABLE student_process_plans(id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,test_id INTEGER NOT NULL,status TEXT NOT NULL DEFAULT 'planned',started_at TEXT NULL,completed_at TEXT NULL,updated_at TEXT NOT NULL);
CREATE TABLE student_process_plan_steps(id INTEGER PRIMARY KEY,plan_id INTEGER NOT NULL,position INTEGER NOT NULL,stage_key TEXT NOT NULL,label TEXT NOT NULL,duration TEXT NOT NULL DEFAULT '',status TEXT NOT NULL DEFAULT 'planned',started_at TEXT NULL,completed_at TEXT NULL,updated_at TEXT NOT NULL);
CREATE TABLE student_process_execution_sessions(plan_id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,plan_step_id INTEGER NULL,state TEXT NOT NULL DEFAULT 'idle',duration_seconds INTEGER NULL,remaining_seconds INTEGER NULL,timer_started_at TEXT NULL,timer_ends_at TEXT NULL,paused_at TEXT NULL,revision INTEGER NOT NULL DEFAULT 0,last_client_token TEXT NOT NULL DEFAULT '',created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$now=utc_now();$db->prepare("INSERT INTO student_process_plans(id,student_id,test_id,status,updated_at) VALUES(1,7,40,'planned',?)")->execute([$now]);
$db->prepare("INSERT INTO student_process_plan_steps(id,plan_id,position,stage_key,label,duration,status,updated_at) VALUES(11,1,1,'first_development','Primeira revelação','7:00','planned',?)")->execute([$now]);
$db->prepare("INSERT INTO student_process_plan_steps(id,plan_id,position,stage_key,label,duration,status,updated_at) VALUES(12,1,2,'wash_after_first','Lavagem','1:00','planned',?)")->execute([$now]);

$plan=student_process_execution_plan($db,1,7);$session=student_process_execution_ensure($db,$plan,7);
must_execution_state(is_array($session)&&$session['state']==='idle','new execution session must start idle');
must_execution_state((int)$session['plan_step_id']===11,'session must bind to current plan step');
must_execution_state((int)$session['duration_seconds']===420&&(int)$session['remaining_seconds']===420,'server must derive timer duration from planned step');

$started=student_process_execution_transition($db,1,11,7,'start','token-start',0);
must_execution_state($started['state']==='running','start must persist running state');
must_execution_state((int)$started['revision']===1,'start must increment revision');
must_execution_state($started['timer_ends_at']!=='','running timer must persist absolute end timestamp');
$planRow=$db->query('SELECT * FROM student_process_plans WHERE id=1')->fetch();$stepRow=$db->query('SELECT * FROM student_process_plan_steps WHERE id=11')->fetch();
must_execution_state($planRow['status']==='running'&&$planRow['started_at']!=='','explicit timer start must mark plan started');
must_execution_state($stepRow['status']==='running'&&$stepRow['started_at']!=='','explicit timer start must mark current planned step started');

$idempotent=student_process_execution_transition($db,1,11,7,'start','token-start',0);
must_execution_state($idempotent['state']==='running'&&(int)$idempotent['revision']===1,'replayed client token must be idempotent');

$conflicted=false;try{student_process_execution_transition($db,1,11,7,'pause','token-stale',0);}catch(StudentProcessExecutionConflict $e){$conflicted=true;must_execution_state((int)$e->executionState['revision']===1,'conflict must return canonical revision');}
must_execution_state($conflicted,'stale tab/device revision must be rejected');

$paused=student_process_execution_transition($db,1,11,7,'pause','token-pause',1);
must_execution_state($paused['state']==='paused','pause must persist paused state');
must_execution_state((int)$paused['revision']===2,'pause must increment revision');
must_execution_state((int)$paused['remaining_seconds']>0&&$paused['timer_ends_at']==='','pause must persist remaining time without an active deadline');

$reset=student_process_execution_transition($db,1,11,7,'reset','token-reset',2);
must_execution_state($reset['state']==='idle'&&(int)$reset['remaining_seconds']===420,'reset must restore full duration');
must_execution_state((int)$reset['revision']===3,'reset must increment revision');

$db->prepare("UPDATE student_process_execution_sessions SET state='running',remaining_seconds=1,timer_started_at=?,timer_ends_at=?,revision=3,last_client_token='' WHERE plan_id=1")->execute([gmdate('c',time()-10),gmdate('c',time()-1)]);
$elapsed=student_process_execution_state($db,1,11,7);
must_execution_state($elapsed['state']==='elapsed'&&(int)$elapsed['remaining_seconds']===0,'expired absolute deadline must normalize to elapsed');
must_execution_state((int)$elapsed['revision']===4,'deadline normalization must advance revision so stale clients are detectable');
$stillRunning=$db->query("SELECT status FROM student_process_plan_steps WHERE id=11")->fetchColumn();
must_execution_state($stillRunning==='running','elapsed timer must not automatically complete the laboratory step');

student_process_execution_complete_step($db,1,11,7);
must_execution_state((int)$db->query("SELECT COUNT(*) FROM student_process_execution_sessions WHERE plan_id=1")->fetchColumn()===0,'completed step must clear its execution session');
must_execution_state($db->query("SELECT status FROM student_process_plan_steps WHERE id=11")->fetchColumn()==='completed','explicit completion must complete the factual planned step');

$nextState=student_process_execution_for_test($db,40,7);
must_execution_state(is_array($nextState)&&(int)$nextState['plan_step_id']===12&&$nextState['state']==='idle','next step must get a fresh independent session');
$staleStepRejected=false;try{student_process_execution_transition($db,1,11,7,'start','old-tab',0);}catch(RuntimeException){$staleStepRejected=true;}
must_execution_state($staleStepRejected,'old tab must not mutate a plan after the current step advanced');

$db->prepare("UPDATE student_process_plan_steps SET duration='' WHERE id=12")->execute();
$db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=1')->execute();
student_process_execution_complete_step($db,1,12,7);
must_execution_state($db->query("SELECT status FROM student_process_plans WHERE id=1")->fetchColumn()==='completed','untimed final stage must be explicitly completable without fake timer execution');

echo "student-process-execution-state: ok\n";