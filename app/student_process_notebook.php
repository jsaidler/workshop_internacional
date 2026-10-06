<?php
declare(strict_types=1);

/**
 * Caderno de Processos: o roteiro associado é uma folha de referência editável.
 * Nenhuma operação deste serviço infere sequência física, "etapa atual" ou
 * conclusão do processamento. Checks, timers e estoque são conceitos separados.
 */
function student_process_notebook_plan(PDO $db,int $planId,int $studentId): array {
    $q=$db->prepare('SELECT * FROM student_process_plans WHERE id=? AND student_id=? LIMIT 1');
    $q->execute([$planId,$studentId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Roteiro não encontrado.');
}

function student_process_notebook_step(PDO $db,int $planId,int $planStepId,int $studentId): array {
    student_process_notebook_plan($db,$planId,$studentId);
    $q=$db->prepare('SELECT * FROM student_process_plan_steps WHERE id=? AND plan_id=? LIMIT 1');
    $q->execute([$planStepId,$planId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Etapa não encontrada.');
}

function student_process_notebook_counts(PDO $db,int $planId,int $studentId): array {
    student_process_notebook_plan($db,$planId,$studentId);
    $q=$db->prepare("SELECT COUNT(*) total,SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) completed FROM student_process_plan_steps WHERE plan_id=?");
    $q->execute([$planId]);$row=$q->fetch(PDO::FETCH_ASSOC)?:[];
    return ['total'=>(int)($row['total']??0),'completed'=>(int)($row['completed']??0)];
}

function student_process_notebook_set_completed(PDO $db,int $planId,int $planStepId,int $studentId,bool $completed,string $source='manual'): array {
    $step=student_process_notebook_step($db,$planId,$planStepId,$studentId);
    $was=(string)$step['status']==='completed';
    if($was===$completed)return $step;
    $now=utc_now();
    $db->prepare("UPDATE student_process_plan_steps SET status=?,completed_at=?,updated_at=? WHERE id=? AND plan_id=?")
        ->execute([$completed?'completed':'planned',$completed?$now:null,$now,$planStepId,$planId]);
    // O status agregado do plano não é autoridade do Caderno. Mantemos apenas
    // updated_at para que listas possam ordenar o registro sem criar um workflow.
    $db->prepare('UPDATE student_process_plans SET updated_at=? WHERE id=? AND student_id=?')->execute([$now,$planId,$studentId]);
    if(function_exists('student_process_change_log'))student_process_change_log(
        $db,'student',null,$studentId,'student_process_plan_step',$planStepId,$completed?'mark_step_completed':'unmark_step_completed',
        ['completed'=>$was],['completed'=>$completed,'source'=>$source]
    );
    return student_process_notebook_step($db,$planId,$planStepId,$studentId);
}

function student_process_notebook_update_step(PDO $db,int $planId,int $planStepId,int $studentId,array $input): array {
    $step=student_process_notebook_step($db,$planId,$planStepId,$studentId);
    $payload=student_process_json_array((string)$step['payload_json']);
    $before=['label'=>(string)$step['label'],'duration'=>(string)$step['duration'],'payload'=>$payload];

    $label=student_workspace_text($input['label']??$step['label'],120);if($label==='')$label=(string)$step['label'];
    $duration=student_workspace_text($input['duration']??$step['duration'],40);
    if($duration!==''&&student_process_time_seconds($duration)===null)throw new RuntimeException('Informe um tempo válido ou deixe o campo vazio.');
    if($duration!=='')$duration=student_process_seconds_label(student_process_time_seconds($duration));

    foreach(['temperature'=>80,'agitation'=>600,'notes'=>3000,'developer_name'=>180,'chemical_name'=>180] as $key=>$limit){
        if(array_key_exists($key,$input))$payload[$key]=student_workspace_text($input[$key],$limit);
    }
    foreach(['developer_amount','water_amount','fresh_volume'] as $key){
        if(array_key_exists($key,$input))$payload[$key]=student_workbench_float($input[$key]);
    }
    $payload['duration']=$duration;

    $agitationMode=(string)($input['agitation_mode']??($payload['agitation_mode']??'none'));
    if(!in_array($agitationMode,['none','periodic','continuous'],true))$agitationMode='none';
    $agitationDuration='';$agitationInterval='';
    if($agitationMode==='periodic'){
        $agitationDuration=student_workspace_text($input['agitation_duration']??($payload['agitation_duration']??''),40);
        $agitationInterval=student_workspace_text($input['agitation_interval']??($payload['agitation_interval']??$step['agitation_interval']??''),40);
        $pulse=student_process_time_seconds($agitationDuration);$interval=student_process_time_seconds($agitationInterval);
        if($pulse===null||$pulse<=0||$interval===null||$interval<=0||$pulse>=$interval)throw new RuntimeException('Revise a duração e o intervalo da agitação periódica.');
        $agitationDuration=student_process_seconds_label($pulse);$agitationInterval=student_process_seconds_label($interval);
    }
    $payload['agitation_mode']=$agitationMode;$payload['agitation_duration']=$agitationDuration;$payload['agitation_interval']=$agitationInterval;

    $now=utc_now();
    $db->prepare('UPDATE student_process_plan_steps SET label=?,duration=?,agitation_interval=?,payload_json=?,updated_at=? WHERE id=? AND plan_id=?')
        ->execute([$label,$duration,$agitationInterval,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$planStepId,$planId]);
    $db->prepare('UPDATE student_process_plans SET updated_at=? WHERE id=? AND student_id=?')->execute([$now,$planId,$studentId]);
    student_process_notebook_timer_retime($db,$planId,$planStepId,$studentId,student_process_time_seconds($duration));
    if(function_exists('student_process_change_log'))student_process_change_log(
        $db,'student',null,$studentId,'student_process_plan_step',$planStepId,'edit_notebook_step',$before,
        ['label'=>$label,'duration'=>$duration,'payload'=>$payload]
    );
    return student_process_notebook_step($db,$planId,$planStepId,$studentId);
}

function student_process_notebook_timer_row(PDO $db,int $planStepId,int $studentId): ?array {
    $q=$db->prepare('SELECT * FROM student_process_step_timers WHERE plan_step_id=? AND student_id=? LIMIT 1');
    $q->execute([$planStepId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_process_notebook_timer_payload(array $row): array {
    $remaining=$row['remaining_seconds']===null?null:max(0,(int)$row['remaining_seconds']);
    if((string)$row['state']==='running'&&($row['timer_ends_at']??'')!==''){
        $end=strtotime((string)$row['timer_ends_at']);if($end!==false)$remaining=max(0,$end-time());
    }
    return [
        'plan_id'=>(int)$row['plan_id'],'plan_step_id'=>(int)$row['plan_step_id'],'state'=>(string)$row['state'],
        'duration_seconds'=>$row['duration_seconds']===null?null:(int)$row['duration_seconds'],'remaining_seconds'=>$remaining,
        'timer_started_at'=>(string)($row['timer_started_at']??''),'timer_ends_at'=>(string)($row['timer_ends_at']??''),
        'paused_at'=>(string)($row['paused_at']??''),'revision'=>(int)$row['revision'],'server_now'=>gmdate('c'),
    ];
}

function student_process_notebook_timer_ensure(PDO $db,int $planId,int $planStepId,int $studentId): array {
    $step=student_process_notebook_step($db,$planId,$planStepId,$studentId);$duration=student_process_time_seconds((string)$step['duration']);
    $row=student_process_notebook_timer_row($db,$planStepId,$studentId);$now=utc_now();
    if(!$row){
        $q=$db->prepare("INSERT INTO student_process_step_timers(plan_step_id,plan_id,student_id,state,duration_seconds,remaining_seconds,revision,last_client_token,created_at,updated_at) VALUES(?,?,?,'idle',?,?,0,'',?,?)");
        $q->execute([$planStepId,$planId,$studentId,$duration,$duration,$now,$now]);
        $row=student_process_notebook_timer_row($db,$planStepId,$studentId)??throw new RuntimeException('Não foi possível preparar o timer.');
    }
    return student_process_notebook_timer_normalize($db,$row);
}

function student_process_notebook_timer_normalize(PDO $db,array $row): array {
    if((string)$row['state']!=='running')return $row;
    $end=strtotime((string)($row['timer_ends_at']??''));if($end===false||$end>time())return $row;
    $now=utc_now();$first=trim((string)($row['completion_applied_at']??''))==='';
    $q=$db->prepare("UPDATE student_process_step_timers SET state='elapsed',remaining_seconds=0,timer_ends_at=NULL,paused_at=NULL,completion_applied_at=COALESCE(completion_applied_at,?),revision=revision+1,updated_at=? WHERE plan_step_id=? AND student_id=? AND state='running'");
    $q->execute([$now,$now,(int)$row['plan_step_id'],(int)$row['student_id']]);
    if($first&&$q->rowCount()>0)student_process_notebook_set_completed($db,(int)$row['plan_id'],(int)$row['plan_step_id'],(int)$row['student_id'],true,'timer');
    return student_process_notebook_timer_row($db,(int)$row['plan_step_id'],(int)$row['student_id'])?:array_merge($row,['state'=>'elapsed','remaining_seconds'=>0,'completion_applied_at'=>$now]);
}

function student_process_notebook_timer_state(PDO $db,int $planId,int $planStepId,int $studentId): array {
    return student_process_notebook_timer_payload(student_process_notebook_timer_ensure($db,$planId,$planStepId,$studentId));
}

function student_process_notebook_timer_transition(PDO $db,int $planId,int $planStepId,int $studentId,string $action,string $clientToken='',?int $expectedRevision=null): array {
    $row=student_process_notebook_timer_ensure($db,$planId,$planStepId,$studentId);$row=student_process_notebook_timer_normalize($db,$row);
    if($clientToken!==''&&hash_equals((string)$row['last_client_token'],$clientToken))return student_process_notebook_timer_payload($row);
    if($expectedRevision!==null&&(int)$row['revision']!==$expectedRevision)throw new StudentProcessExecutionConflict(student_process_notebook_timer_payload($row));
    $duration=$row['duration_seconds']===null?null:(int)$row['duration_seconds'];if($duration===null||$duration<=0)throw new RuntimeException('Esta etapa não possui tempo definido.');
    $state=(string)$row['state'];$remaining=max(0,(int)($row['remaining_seconds']??$duration));$now=utc_now();$revision=(int)$row['revision']+1;

    if($action==='start'){
        if($state==='running')return student_process_notebook_timer_payload($row);
        if($state==='elapsed'||$remaining<=0)$remaining=$duration;
        $endsAt=gmdate('c',time()+$remaining);
        $db->prepare("UPDATE student_process_step_timers SET state='running',remaining_seconds=?,timer_started_at=?,timer_ends_at=?,paused_at=NULL,completion_applied_at=NULL,revision=?,last_client_token=?,updated_at=? WHERE plan_step_id=? AND student_id=?")
            ->execute([$remaining,$now,$endsAt,$revision,$clientToken,$now,$planStepId,$studentId]);
    }elseif($action==='pause'){
        if($state!=='running')return student_process_notebook_timer_payload($row);
        $end=strtotime((string)($row['timer_ends_at']??''));$remaining=$end===false?$remaining:max(0,$end-time());
        if($remaining<=0){
            $db->prepare("UPDATE student_process_step_timers SET state='elapsed',remaining_seconds=0,timer_ends_at=NULL,paused_at=NULL,completion_applied_at=COALESCE(completion_applied_at,?),revision=?,last_client_token=?,updated_at=? WHERE plan_step_id=? AND student_id=?")
                ->execute([$now,$revision,$clientToken,$now,$planStepId,$studentId]);
            if(trim((string)($row['completion_applied_at']??''))==='')student_process_notebook_set_completed($db,$planId,$planStepId,$studentId,true,'timer');
        }else{
            $db->prepare("UPDATE student_process_step_timers SET state='paused',remaining_seconds=?,timer_ends_at=NULL,paused_at=?,revision=?,last_client_token=?,updated_at=? WHERE plan_step_id=? AND student_id=?")
                ->execute([$remaining,$now,$revision,$clientToken,$now,$planStepId,$studentId]);
        }
    }elseif($action==='reset'){
        $db->prepare("UPDATE student_process_step_timers SET state='idle',remaining_seconds=?,timer_started_at=NULL,timer_ends_at=NULL,paused_at=NULL,completion_applied_at=NULL,revision=?,last_client_token=?,updated_at=? WHERE plan_step_id=? AND student_id=?")
            ->execute([$duration,$revision,$clientToken,$now,$planStepId,$studentId]);
    }else throw new RuntimeException('Ação de timer inválida.');
    $fresh=student_process_notebook_timer_row($db,$planStepId,$studentId)??throw new RuntimeException('Não foi possível atualizar o timer.');
    return student_process_notebook_timer_payload(student_process_notebook_timer_normalize($db,$fresh));
}

function student_process_notebook_timer_retime(PDO $db,int $planId,int $planStepId,int $studentId,?int $duration): void {
    $row=student_process_notebook_timer_row($db,$planStepId,$studentId);if(!$row)return;
    $row=student_process_notebook_timer_normalize($db,$row);$state=(string)$row['state'];$now=utc_now();
    if($state==='running'){
        $old=$row['duration_seconds']===null?0:(int)$row['duration_seconds'];$end=strtotime((string)($row['timer_ends_at']??''));$remaining=$end===false?max(0,(int)($row['remaining_seconds']??0)):max(0,$end-time());$elapsed=max(0,$old-$remaining);
        $next=$duration===null?null:max(0,$duration-$elapsed);$nextState=$duration===null?'idle':($next>0?'running':'elapsed');$nextEnd=$nextState==='running'?gmdate('c',time()+(int)$next):null;
        $db->prepare('UPDATE student_process_step_timers SET state=?,duration_seconds=?,remaining_seconds=?,timer_ends_at=?,revision=revision+1,updated_at=? WHERE plan_step_id=? AND student_id=?')
            ->execute([$nextState,$duration,$next,$nextEnd,$now,$planStepId,$studentId]);
        if($nextState==='elapsed'&&trim((string)($row['completion_applied_at']??''))===''){
            $db->prepare('UPDATE student_process_step_timers SET completion_applied_at=? WHERE plan_step_id=? AND student_id=?')->execute([$now,$planStepId,$studentId]);
            student_process_notebook_set_completed($db,$planId,$planStepId,$studentId,true,'timer');
        }
        return;
    }
    $remaining=$duration;$nextState=$state==='paused'&&$duration!==null?'paused':'idle';
    $db->prepare('UPDATE student_process_step_timers SET state=?,duration_seconds=?,remaining_seconds=?,timer_ends_at=NULL,revision=revision+1,updated_at=? WHERE plan_step_id=? AND student_id=?')
        ->execute([$nextState,$duration,$remaining,$now,$planStepId,$studentId]);
}
