<?php
declare(strict_types=1);

final class StudentProcessExecutionConflict extends RuntimeException {
    public function __construct(public readonly array $executionState,string $message='O estado do laboratório mudou em outra aba ou dispositivo. Atualize antes de continuar.'){
        parent::__construct($message);
    }
}

function student_process_execution_session(PDO $db,int $planId,int $studentId): ?array {
    $q=$db->prepare('SELECT * FROM student_process_execution_sessions WHERE plan_id=? AND student_id=? LIMIT 1');
    $q->execute([$planId,$studentId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_process_execution_plan(PDO $db,int $planId,int $studentId): array {
    $q=$db->prepare('SELECT * FROM student_process_plans WHERE id=? AND student_id=? LIMIT 1');
    $q->execute([$planId,$studentId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Processamento não encontrado.');
}

function student_process_execution_current_step(PDO $db,array $plan): ?array {
    foreach(student_process_plan_steps($db,(int)$plan['id']) as $step){
        if((string)$step['status']!=='completed')return $step;
    }
    return null;
}

function student_process_execution_timestamp(?string $value): ?int {
    $value=trim((string)$value);if($value==='')return null;
    $timestamp=strtotime($value);
    return $timestamp===false?null:$timestamp;
}

function student_process_execution_payload(array $row): array {
    return [
        'plan_id'=>(int)$row['plan_id'],
        'plan_step_id'=>(int)($row['plan_step_id']??0),
        'state'=>(string)$row['state'],
        'duration_seconds'=>$row['duration_seconds']===null?null:(int)$row['duration_seconds'],
        'remaining_seconds'=>$row['remaining_seconds']===null?null:max(0,(int)$row['remaining_seconds']),
        'timer_started_at'=>(string)($row['timer_started_at']??''),
        'timer_ends_at'=>(string)($row['timer_ends_at']??''),
        'paused_at'=>(string)($row['paused_at']??''),
        'revision'=>(int)$row['revision'],
        'server_now'=>gmdate('c'),
    ];
}

function student_process_execution_normalize(PDO $db,array $row): array {
    if((string)$row['state']!=='running')return $row;
    $endsAt=student_process_execution_timestamp((string)($row['timer_ends_at']??''));
    if($endsAt===null)return $row;
    $remaining=max(0,$endsAt-time());
    $row['remaining_seconds']=$remaining;
    if($remaining>0)return $row;

    $now=utc_now();$revision=(int)$row['revision'];
    $q=$db->prepare("UPDATE student_process_execution_sessions SET state='elapsed',remaining_seconds=0,paused_at=NULL,revision=revision+1,updated_at=? WHERE plan_id=? AND student_id=? AND revision=? AND state='running'");
    $q->execute([$now,(int)$row['plan_id'],(int)$row['student_id'],$revision]);
    $fresh=student_process_execution_session($db,(int)$row['plan_id'],(int)$row['student_id']);
    return $fresh?:array_merge($row,['state'=>'elapsed','remaining_seconds'=>0,'revision'=>$revision+1,'updated_at'=>$now]);
}

function student_process_execution_ensure(PDO $db,array $plan,int $studentId): ?array {
    $planId=(int)$plan['id'];$current=student_process_execution_current_step($db,$plan);
    if(!$current){$db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$planId,$studentId]);return null;}
    $stepId=(int)$current['id'];$duration=student_process_time_seconds((string)$current['duration']);$existing=student_process_execution_session($db,$planId,$studentId);
    if($existing&&(int)($existing['plan_step_id']??0)===$stepId){
        return student_process_execution_normalize($db,$existing);
    }
    $now=utc_now();
    if($existing){
        $q=$db->prepare("UPDATE student_process_execution_sessions SET plan_step_id=?,state='idle',duration_seconds=?,remaining_seconds=?,timer_started_at=NULL,timer_ends_at=NULL,paused_at=NULL,revision=revision+1,last_client_token='',updated_at=? WHERE plan_id=? AND student_id=?");
        $q->execute([$stepId,$duration,$duration,$now,$planId,$studentId]);
    }else{
        $q=$db->prepare("INSERT INTO student_process_execution_sessions(plan_id,student_id,plan_step_id,state,duration_seconds,remaining_seconds,revision,last_client_token,created_at,updated_at) VALUES(?,?,?,'idle',?,?,0,'',?,?)");
        $q->execute([$planId,$studentId,$stepId,$duration,$duration,$now,$now]);
    }
    return student_process_execution_session($db,$planId,$studentId);
}

function student_process_execution_for_test(PDO $db,int $testId,int $studentId): ?array {
    $plan=student_process_plan_for_test($db,$testId,$studentId);if(!$plan)return null;
    $row=student_process_execution_ensure($db,$plan,$studentId);return $row?student_process_execution_payload($row):null;
}

function student_process_execution_assert_current(PDO $db,array $plan,int $planStepId): array {
    $current=student_process_execution_current_step($db,$plan)??throw new RuntimeException('O processamento já foi concluído.');
    if((int)$current['id']!==$planStepId)throw new RuntimeException('O cronômetro pertence a outra etapa do processamento. Atualize a página.');
    return $current;
}

function student_process_execution_mark_started(PDO $db,array $plan,array $step,int $studentId): void {
    $now=utc_now();$planId=(int)$plan['id'];$stepId=(int)$step['id'];
    $db->prepare("UPDATE student_process_plans SET status=CASE WHEN status='planned' THEN 'running' ELSE status END,started_at=COALESCE(started_at,?),updated_at=? WHERE id=? AND student_id=? AND status!='completed'")
        ->execute([$now,$now,$planId,$studentId]);
    $db->prepare("UPDATE student_process_plan_steps SET status=CASE WHEN status='planned' THEN 'running' ELSE status END,started_at=COALESCE(started_at,?),updated_at=? WHERE id=? AND plan_id=? AND status!='completed'")
        ->execute([$now,$now,$stepId,$planId]);
}

function student_process_execution_check_revision(array $row,?int $expectedRevision): void {
    if($expectedRevision===null)return;
    if((int)$row['revision']!==$expectedRevision)throw new StudentProcessExecutionConflict(student_process_execution_payload($row));
}

function student_process_execution_transition(PDO $db,int $planId,int $planStepId,int $studentId,string $action,string $clientToken='',?int $expectedRevision=null): array {
    $plan=student_process_execution_plan($db,$planId,$studentId);student_process_execution_assert_current($db,$plan,$planStepId);
    $row=student_process_execution_ensure($db,$plan,$studentId)??throw new RuntimeException('Não há uma etapa disponível para o cronômetro.');
    $row=student_process_execution_normalize($db,$row);
    if((int)$row['plan_step_id']!==$planStepId)throw new RuntimeException('O cronômetro pertence a outra etapa do processamento. Atualize a página.');
    if($clientToken!==''&&hash_equals((string)$row['last_client_token'],$clientToken))return student_process_execution_payload($row);
    student_process_execution_check_revision($row,$expectedRevision);

    $duration=$row['duration_seconds']===null?null:(int)$row['duration_seconds'];
    if($duration===null)throw new RuntimeException('Esta etapa não possui cronômetro.');
    $state=(string)$row['state'];$remaining=max(0,(int)($row['remaining_seconds']??$duration));$now=utc_now();$revision=(int)$row['revision']+1;

    if($action==='start'){
        if($state==='elapsed')throw new RuntimeException('O tempo terminou. Reinicie ou ajuste o cronômetro para continuar contando.');
        if($state==='running')return student_process_execution_payload($row);
        if($remaining<=0)$remaining=$duration;
        $endsAt=gmdate('c',time()+$remaining);
        $q=$db->prepare("UPDATE student_process_execution_sessions SET state='running',remaining_seconds=?,timer_started_at=COALESCE(timer_started_at,?),timer_ends_at=?,paused_at=NULL,revision=?,last_client_token=?,updated_at=? WHERE plan_id=? AND student_id=?");
        $q->execute([$remaining,$now,$endsAt,$revision,$clientToken,$now,$planId,$studentId]);
    }elseif($action==='pause'){
        if($state==='elapsed')return student_process_execution_payload($row);
        if($state!=='running')return student_process_execution_payload($row);
        $endsAt=student_process_execution_timestamp((string)($row['timer_ends_at']??''));
        $remaining=$endsAt===null?$remaining:max(0,$endsAt-time());
        if($remaining<=0){
            $q=$db->prepare("UPDATE student_process_execution_sessions SET state='elapsed',remaining_seconds=0,paused_at=NULL,revision=?,last_client_token=?,updated_at=? WHERE plan_id=? AND student_id=?");
            $q->execute([$revision,$clientToken,$now,$planId,$studentId]);
        }else{
            $q=$db->prepare("UPDATE student_process_execution_sessions SET state='paused',remaining_seconds=?,timer_ends_at=NULL,paused_at=?,revision=?,last_client_token=?,updated_at=? WHERE plan_id=? AND student_id=?");
            $q->execute([$remaining,$now,$revision,$clientToken,$now,$planId,$studentId]);
        }
    }elseif($action==='reset'){
        $q=$db->prepare("UPDATE student_process_execution_sessions SET state='idle',remaining_seconds=?,timer_started_at=NULL,timer_ends_at=NULL,paused_at=NULL,revision=?,last_client_token=?,updated_at=? WHERE plan_id=? AND student_id=?");
        $q->execute([$duration,$revision,$clientToken,$now,$planId,$studentId]);
    }else throw new RuntimeException('Ação de cronômetro inválida.');

    $fresh=student_process_execution_session($db,$planId,$studentId)??throw new RuntimeException('Não foi possível atualizar o cronômetro.');
    return student_process_execution_payload(student_process_execution_normalize($db,$fresh));
}

function student_process_execution_retime(PDO $db,int $planId,int $planStepId,int $studentId,?int $newDuration): ?array {
    $plan=student_process_execution_plan($db,$planId,$studentId);student_process_execution_assert_current($db,$plan,$planStepId);
    $row=student_process_execution_ensure($db,$plan,$studentId);if(!$row)return null;
    $row=student_process_execution_normalize($db,$row);
    $oldDuration=$row['duration_seconds']===null?null:(int)$row['duration_seconds'];$state=(string)$row['state'];$elapsed=0;
    if($oldDuration!==null&&$row['remaining_seconds']!==null&&in_array($state,['running','paused','elapsed'],true)){
        $elapsed=max(0,$oldDuration-max(0,(int)$row['remaining_seconds']));
    }
    $now=utc_now();$revision=(int)$row['revision']+1;$startedAt=(string)($row['timer_started_at']??'');
    if($newDuration===null){
        $q=$db->prepare("UPDATE student_process_execution_sessions SET state='idle',duration_seconds=NULL,remaining_seconds=NULL,timer_started_at=NULL,timer_ends_at=NULL,paused_at=NULL,revision=?,last_client_token='',updated_at=? WHERE plan_id=? AND student_id=?");
        $q->execute([$revision,$now,$planId,$studentId]);
    }else{
        $newDuration=max(0,$newDuration);$remaining=max(0,$newDuration-$elapsed);$nextState='idle';$endsAt=null;$pausedAt=null;
        if($state==='running'){
            if($remaining>0){$nextState='running';$endsAt=gmdate('c',time()+$remaining);}else{$nextState='elapsed';}
        }elseif(in_array($state,['paused','elapsed'],true)){
            if($remaining>0){$nextState='paused';$pausedAt=$now;}else{$nextState='elapsed';}
        }else{$remaining=$newDuration;$startedAt='';}
        $q=$db->prepare('UPDATE student_process_execution_sessions SET state=?,duration_seconds=?,remaining_seconds=?,timer_started_at=?,timer_ends_at=?,paused_at=?,revision=?,last_client_token=\'\',updated_at=? WHERE plan_id=? AND student_id=?');
        $q->execute([$nextState,$newDuration,$remaining,$startedAt!==''?$startedAt:null,$endsAt,$pausedAt,$revision,$now,$planId,$studentId]);
    }
    $fresh=student_process_execution_session($db,$planId,$studentId);
    return $fresh?student_process_execution_payload(student_process_execution_normalize($db,$fresh)):null;
}

function student_process_execution_state(PDO $db,int $planId,int $planStepId,int $studentId): array {
    $plan=student_process_execution_plan($db,$planId,$studentId);student_process_execution_assert_current($db,$plan,$planStepId);
    $row=student_process_execution_ensure($db,$plan,$studentId)??throw new RuntimeException('Não há uma etapa disponível para o cronômetro.');
    return student_process_execution_payload(student_process_execution_normalize($db,$row));
}

function student_process_execution_complete_step(PDO $db,int $planId,int $planStepId,int $studentId): array {
    $plan=student_process_execution_plan($db,$planId,$studentId);$step=student_process_execution_assert_current($db,$plan,$planStepId);
    student_process_execution_mark_started($db,$plan,$step,$studentId);
    $completed=student_process_plan_complete_step($db,$planId,$planStepId,$studentId);
    $db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$planId,$studentId]);
    $mode=student_process_recording_mode_for_test($db,(int)$plan['test_id'],$studentId);$mode=$mode==='retroactive'?'mixed':'live';
    $meta=student_process_recording_meta($db,(int)$plan['test_id'],$studentId);student_process_recording_save_meta($db,(int)$plan['test_id'],$studentId,$mode,$meta['performed_on']??null,(string)($meta['notes']??''));
    return $completed;
}
