<?php
declare(strict_types=1);

function fail_notebook_runtime(string $message): never {fwrite(STDERR,"student-process-notebook-runtime: $message\n");exit(1);}
function must_notebook_runtime(bool $ok,string $message): void {if(!$ok)fail_notebook_runtime($message);}
function utc_now(): string {return gmdate('c');}
function student_workspace_text(mixed $value,int $max): string {return mb_substr(trim((string)$value),0,$max);}
function student_workbench_float(mixed $value): ?float {$raw=trim((string)$value);if($raw==='')return null;return (float)str_replace(',','.',$raw);}
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
function student_process_managed_stage_catalog(PDO $db,bool $includeDisabled=false): array {
    return [
        'first_development'=>['label'=>'Primeira revelação','type'=>'development','chemical_name'=>''],
        'wash_after_bleach'=>['label'=>'Lavagem','type'=>'wash','chemical_name'=>''],
        'custom'=>['label'=>'Outra etapa','type'=>'custom','chemical_name'=>''],
    ];
}
$changeActions=[];
function student_process_change_log(PDO $db,string $actorRole,?int $adminId,?int $studentId,string $entityType,int $entityId,string $action,array $before=[],array $after=[],string $note=''): void {
    global $changeActions;$changeActions[]=$action;
}

require_once dirname(__DIR__).'/app/student_process_notebook.php';

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec("CREATE TABLE student_process_plans(id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,test_id INTEGER NOT NULL,updated_at TEXT NOT NULL);
CREATE TABLE student_process_plan_steps(id INTEGER PRIMARY KEY AUTOINCREMENT,plan_id INTEGER NOT NULL,position INTEGER NOT NULL,stage_key TEXT NOT NULL,label TEXT NOT NULL,duration TEXT NOT NULL DEFAULT '',agitation_interval TEXT NOT NULL DEFAULT '',payload_json TEXT NOT NULL DEFAULT '{}',status TEXT NOT NULL DEFAULT 'planned',actual_step_id INTEGER NULL,started_at TEXT NULL,completed_at TEXT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL);
CREATE TABLE student_process_step_timers(plan_step_id INTEGER PRIMARY KEY,plan_id INTEGER NOT NULL,student_id INTEGER NOT NULL,state TEXT NOT NULL DEFAULT 'idle',duration_seconds INTEGER NULL,remaining_seconds INTEGER NULL,timer_started_at TEXT NULL,timer_ends_at TEXT NULL,paused_at TEXT NULL,completion_applied_at TEXT NULL,revision INTEGER NOT NULL DEFAULT 0,last_client_token TEXT NOT NULL DEFAULT '',created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$now=utc_now();
$db->prepare('INSERT INTO student_process_plans(id,student_id,test_id,updated_at) VALUES(1,7,40,?)')->execute([$now]);
$insert=$db->prepare("INSERT INTO student_process_plan_steps(id,plan_id,position,stage_key,label,duration,agitation_interval,payload_json,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,'planned',?,?)");
$insert->execute([11,1,1,'first_development','Primeira revelação','07:00','',json_encode(['developer_name'=>'Parodinal','temperature'=>'26 °C','agitation_mode'=>'none']),$now,$now]);
$insert->execute([12,1,2,'wash_after_bleach','Lavagem','01:00','',json_encode([]),$now,$now]);

$marked=student_process_notebook_set_completed($db,1,11,7,true,'manual');
must_notebook_runtime((string)$marked['status']==='completed','manual check did not persist as completed');
$counts=student_process_notebook_counts($db,1,7);
must_notebook_runtime($counts['completed']===1&&$counts['total']===2,'completed count did not reflect the persisted check');

$edited=student_process_notebook_update_step($db,1,11,7,['label'=>'Primeira revelação ajustada','duration'=>'7:30','temperature'=>'25 °C','developer_name'=>'Parodinal 20 ml','agitation'=>'leve']);
$payload=student_process_json_array((string)$edited['payload_json']);
must_notebook_runtime((string)$edited['status']==='completed','editing a checked step cleared its check');
must_notebook_runtime((string)$edited['label']==='Primeira revelação ajustada'&&(string)$edited['duration']==='07:30','step edit did not persist label/time');
must_notebook_runtime(($payload['temperature']??'')==='25 °C'&&($payload['developer_name']??'')==='Parodinal 20 ml','step edit did not persist payload data');

$unmarked=student_process_notebook_set_completed($db,1,11,7,false,'manual');
must_notebook_runtime((string)$unmarked['status']==='planned'&&$unmarked['completed_at']===null,'manual uncheck did not persist');

student_process_notebook_move_step($db,1,12,7,-1);
$order=array_map(static fn(array $row): int=>(int)$row['id'],student_process_plan_steps($db,1));
must_notebook_runtime($order===[12,11],'moving a route step did not reorder the per-record snapshot');

$added=student_process_notebook_add_step($db,1,7,['stage_key'=>'wash_after_bleach','duration'=>'2:00','temperature'=>'24 °C','notes'=>'Lavagem extra']);
must_notebook_runtime((string)$added['label']==='Lavagem'&&(string)$added['duration']==='02:00','adding a route step did not persist its reference data');
must_notebook_runtime(count(student_process_plan_steps($db,1))===3,'adding a route step did not affect only the per-record route');

$blocked=false;try{student_process_notebook_set_completed($db,1,11,8,true,'manual');}catch(RuntimeException $e){$blocked=true;}
must_notebook_runtime($blocked,'route mutations are not enforcing plan ownership');

foreach(['mark_step_completed','edit_notebook_step','unmark_step_completed','move_notebook_step','add_notebook_step'] as $action)must_notebook_runtime(in_array($action,$changeActions,true),'change log did not receive '.$action);

echo "student-process-notebook-runtime: ok\n";
