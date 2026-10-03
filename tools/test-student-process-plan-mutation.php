<?php
declare(strict_types=1);
function fail_mutation(string $message): never {fwrite(STDERR,"student-process-plan-mutation: $message\n");exit(1);}
function must_mutation(bool $ok,string $message): void {if(!$ok)fail_mutation($message);}
function utc_now(): string {return gmdate('c');}
function student_uuid(): string {return bin2hex(random_bytes(16));}
function student_workspace_text(mixed $value,int $max): string {return mb_substr(trim((string)$value),0,$max);}
function student_process_json_array(string $json): array {$decoded=json_decode($json,true);return is_array($decoded)?$decoded:[];}
function student_test_for_student(PDO $db,int $testId,int $studentId): ?array {$q=$db->prepare('SELECT * FROM student_tests WHERE id=? AND student_id=?');$q->execute([$testId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function student_process_execution_plan(PDO $db,int $planId,int $studentId): array {$q=$db->prepare('SELECT * FROM student_process_plans WHERE id=? AND student_id=?');$q->execute([$planId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Processamento não encontrado.');}
function student_process_plan_steps(PDO $db,int $planId): array {$q=$db->prepare('SELECT * FROM student_process_plan_steps WHERE plan_id=? ORDER BY position,id');$q->execute([$planId]);return $q->fetchAll(PDO::FETCH_ASSOC);}
function student_process_execution_current_step(PDO $db,array $plan): ?array {foreach(student_process_plan_steps($db,(int)$plan['id']) as $step)if((string)$step['status']!=='completed')return $step;return null;}
function student_process_lab_plan_step(PDO $db,int $planId,int $stepId): ?array {$q=$db->prepare('SELECT * FROM student_process_plan_steps WHERE id=? AND plan_id=?');$q->execute([$stepId,$planId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function student_process_template_resolve_input(PDO $db,int $studentId,array $input): array {return $input;}
function student_process_stage_data(string $stageKey,array $input): array {return ['label'=>$input['custom_label']??$stageKey,'duration'=>$input['duration']??'','stage_type'=>'wash','chemical_key'=>'','chemical_name'=>'','developer_amount'=>null,'water_amount'=>null,'temperature'=>$input['temperature']??'','agitation'=>$input['agitation']??'','notes'=>$input['notes']??'','reuse_source_stage_key'=>''];}
function student_process_template_payload_from_stage(array $data,string $stageKey,array $input): array {return ['stage_key'=>$stageKey,'duration'=>(string)$data['duration'],'agitation_interval'=>(string)($input['agitation_interval']??''),'agitation_mode'=>(string)($input['agitation_mode']??'none'),'agitation_duration'=>(string)($input['agitation_duration']??''),'temperature'=>(string)$data['temperature'],'notes'=>(string)$data['notes']];}
function student_process_change_log(PDO $db,string $actorRole,?int $adminId,?int $studentId,string $entityType,int $entityId,string $action,array $before=[],array $after=[],string $note=''): void {$db->prepare('INSERT INTO student_process_change_log(entity_type,entity_id,action,before_json,after_json) VALUES(?,?,?,?,?)')->execute([$entityType,$entityId,$action,json_encode($before),json_encode($after)]);}
$root=dirname(__DIR__);
$runnerPage=(string)file_get_contents($root.'/aluno/processar.php');$managerPage=(string)file_get_contents($root.'/aluno/processamentos.php');$adminPage=(string)file_get_contents($root.'/admin/processes.php');$templatesSource=(string)file_get_contents($root.'/app/student_process_templates.php');$runnerJs=(string)file_get_contents($root.'/assets/student-process-runner.js');
foreach(['insert_plan_step','repeat_plan_step','remove_plan_step','Ajustar roteiro desta fotografia','Adicionar outra etapa'] as $fragment)must_mutation(str_contains($runnerPage,$fragment),'laboratory route editor is missing '.$fragment);
foreach(['agitation_mode','agitation_duration','Intervalo entre inícios'] as $fragment){must_mutation(str_contains($managerPage,$fragment),'student process editor is missing structured agitation '.$fragment);must_mutation(str_contains($adminPage,$fragment),'admin process editor is missing structured agitation '.$fragment);}
must_mutation(str_contains($templatesSource,'student_process_agitation_input'),'template domain does not normalize structured agitation');
must_mutation(str_contains($runnerJs,'data-lab-insert-form')&&str_contains($runnerJs,'data-lab-agitation-mode'),'runner does not bind the per-photo inserted-step editor');
require_once $root.'/app/student_process_plan_mutation.php';

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec("CREATE TABLE student_tests(id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,status TEXT NOT NULL DEFAULT 'draft');
CREATE TABLE student_process_plans(id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,test_id INTEGER NOT NULL,status TEXT NOT NULL,completed_at TEXT NULL,updated_at TEXT NOT NULL);
CREATE TABLE student_process_plan_steps(id INTEGER PRIMARY KEY AUTOINCREMENT,plan_id INTEGER NOT NULL,position INTEGER NOT NULL,stage_key TEXT NOT NULL,label TEXT NOT NULL,duration TEXT NOT NULL DEFAULT '',agitation_interval TEXT NOT NULL DEFAULT '',payload_json TEXT NOT NULL DEFAULT '{}',status TEXT NOT NULL DEFAULT 'planned',actual_step_id INTEGER NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL);
CREATE TABLE student_process_execution_sessions(plan_id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,plan_step_id INTEGER NULL,state TEXT NOT NULL DEFAULT 'idle');
CREATE TABLE student_process_plan_mutation_tokens(token TEXT PRIMARY KEY,student_id INTEGER NOT NULL,plan_id INTEGER NOT NULL,action TEXT NOT NULL,result_json TEXT NOT NULL DEFAULT '{}',created_at TEXT NOT NULL);
CREATE TABLE student_process_change_log(id INTEGER PRIMARY KEY AUTOINCREMENT,entity_type TEXT,entity_id INTEGER,action TEXT,before_json TEXT,after_json TEXT);");
$now=utc_now();$db->exec("INSERT INTO student_tests(id,student_id,status) VALUES(40,7,'draft');INSERT INTO student_process_plans(id,student_id,test_id,status,updated_at) VALUES(9,7,40,'running','$now')");
$insert=$db->prepare("INSERT INTO student_process_plan_steps(plan_id,position,stage_key,label,duration,agitation_interval,payload_json,status,actual_step_id,created_at,updated_at) VALUES(9,?,?,?,?,?,'{}',?,?,?,?)");
$insert->execute([1,'first_development','Primeira revelação','07:00','', 'completed',101,$now,$now]);$completedId=(int)$db->lastInsertId();
$insert->execute([2,'wash_after_first','Lavagem','01:00','', 'running',null,$now,$now]);$currentId=(int)$db->lastInsertId();
$insert->execute([3,'ferric','Cloreto férrico','01:30','', 'planned',null,$now,$now]);$futureId=(int)$db->lastInsertId();
$db->prepare("INSERT INTO student_process_execution_sessions(plan_id,student_id,plan_step_id,state) VALUES(9,7,?,'running')")->execute([$currentId]);

$repeat=student_process_plan_repeat_step($db,9,$completedId,7,'repeat-completed');
must_mutation((string)$repeat['stage_key']==='first_development','repeating a completed stage must copy its stage');
must_mutation((string)$repeat['status']==='planned','repeated stage must be future work, not a duplicated fact');
$steps=student_process_plan_steps($db,9);must_mutation(count($steps)===4,'repeat must add exactly one route occurrence');
must_mutation((int)$steps[0]['id']===$completedId&&(int)$steps[0]['actual_step_id']===101,'original factual stage must remain untouched');
must_mutation((int)$steps[1]['id']===(int)$repeat['id'],'completed-source repeat must be inserted at the current point');
must_mutation((int)$db->query('SELECT COUNT(*) FROM student_process_execution_sessions')->fetchColumn()===0,'changing the current route position must discard only obsolete timer state');
$repeatAgain=student_process_plan_repeat_step($db,9,$completedId,7,'repeat-completed');must_mutation((int)$repeatAgain['id']===(int)$repeat['id']&&count(student_process_plan_steps($db,9))===4,'repeat operation token must be idempotent');

$inserted=student_process_plan_insert_step($db,9,(int)$repeat['id'],7,['insert_where'=>'after','stage_key'=>'wash_after_bleach','custom_label'=>'Lavagem extra','duration'=>'02:00','agitation_mode'=>'periodic','agitation_duration'=>'00:10','agitation_interval'=>'01:00'],'insert-extra');
must_mutation((string)$inserted['stage_key']==='wash_after_bleach','explicit inserted stage must preserve the chosen stage');
$steps=student_process_plan_steps($db,9);$ids=array_map(static fn(array $row): int=>(int)$row['id'],$steps);$repeatIndex=array_search((int)$repeat['id'],$ids,true);must_mutation($repeatIndex!==false&&($ids[$repeatIndex+1]??0)===(int)$inserted['id'],'insert-after must be placed directly after its reference in the snapshot');

$result=student_process_plan_remove_step($db,9,$futureId,7,'remove-future');must_mutation(student_process_lab_plan_step($db,9,$futureId)===null,'future stage removal must affect only the per-record route');must_mutation((int)($result['current_step']['id']??0)===(int)$repeat['id'],'removing a later step must not advance the declared process position');
$blocked=false;try{student_process_plan_remove_step($db,9,$completedId,7,'remove-fact');}catch(RuntimeException $e){$blocked=str_contains($e->getMessage(),'fato');}must_mutation($blocked,'completed factual stage must not be removable from the route editor');

$logActions=$db->query('SELECT action FROM student_process_change_log ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);foreach(['repeat_plan_step','insert_plan_step','remove_plan_step'] as $action)must_mutation(in_array($action,$logActions,true),'mutation audit missing '.$action);
echo "student-process-plan-mutation: ok\n";
