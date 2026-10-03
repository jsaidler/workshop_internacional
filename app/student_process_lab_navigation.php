<?php
declare(strict_types=1);

/**
 * The laboratory runner is a workbench, not a wizard.
 *
 * A student may inspect any stage, move the active execution focus to another
 * unfinished stage, interrupt an attempt, skip a stage or finish a stage before
 * its planned timer expires. Facts already recorded are never rewritten.
 */
function student_process_lab_plan_step(PDO $db,int $planId,int $planStepId): ?array {
    if($planId<1||$planStepId<1)return null;
    $q=$db->prepare('SELECT * FROM student_process_plan_steps WHERE id=? AND plan_id=? LIMIT 1');
    $q->execute([$planStepId,$planId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_process_lab_terminal_status(string $status): bool {
    return in_array($status,['completed','skipped'],true);
}

function student_process_lab_elapsed_seconds(array $step,?array $session): ?int {
    $planned=student_process_time_seconds((string)($step['duration']??''));
    if($planned===null||!$session||(int)($session['plan_step_id']??0)!==(int)$step['id'])return null;
    $session=student_process_execution_normalize(database(),$session);
    $state=(string)($session['state']??'idle');
    if($state==='idle'&&(string)($step['status']??'planned')!=='running')return null;
    $remaining=$session['remaining_seconds'];
    if($remaining===null)return null;
    return max(0,$planned-max(0,(int)$remaining));
}

function student_process_lab_interrupt_attempt(PDO $db,array $plan,array $step,int $studentId,?array $session,string $reason): ?array {
    $session=$session?student_process_execution_normalize($db,$session):null;
    $state=(string)($session['state']??'idle');
    $started=(string)($step['status']??'planned')==='running'||in_array($state,['running','paused','elapsed'],true);
    if(!$started)return null;

    $plannedSeconds=student_process_time_seconds((string)$step['duration']);
    $elapsed=null;
    if($plannedSeconds!==null&&$session&&(int)($session['plan_step_id']??0)===(int)$step['id']&&$session['remaining_seconds']!==null){
        $elapsed=max(0,$plannedSeconds-max(0,(int)$session['remaining_seconds']));
    }
    $payload=student_process_json_array((string)$step['payload_json']);
    $payload['stage_key']=(string)$step['stage_key'];
    $payload['duration']=$elapsed===null?'':student_process_seconds_label($elapsed);
    $existingNotes=trim((string)($payload['notes']??''));
    $note=$reason==='stage_switch'?'Etapa interrompida ao mudar para outra etapa do roteiro.':'Etapa interrompida antes de ser marcada como não realizada.';
    $payload['notes']=$existingNotes===''?$note:$existingNotes.' '.$note;
    $actual=student_process_recording_add_step($db,(int)$plan['test_id'],$studentId,$payload,'mixed');
    $actualId=(int)($actual['id']??0);if($actualId<1)throw new RuntimeException('Não foi possível preservar a etapa interrompida.');
    $meta=student_process_json_array((string)($actual['metadata_json']??'{}'));
    $meta['interrupted']=true;$meta['interrupted_reason']=$reason;$meta['planned_duration']=(string)$step['duration'];$meta['elapsed_seconds']=$elapsed;$meta['execution_state']=$state;
    $now=utc_now();
    $db->prepare('UPDATE student_process_steps SET metadata_json=?,updated_at=? WHERE id=? AND test_id=?')->execute([json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$actualId,(int)$plan['test_id']]);
    $db->prepare("UPDATE student_process_plan_steps SET status='planned',actual_step_id=NULL,started_at=NULL,completed_at=NULL,updated_at=? WHERE id=? AND plan_id=?")
        ->execute([$now,(int)$step['id'],(int)$plan['id']]);
    $recording=student_process_recording_meta($db,(int)$plan['test_id'],$studentId);
    student_process_recording_save_meta($db,(int)$plan['test_id'],$studentId,'mixed',$recording['performed_on']??null,(string)($recording['notes']??''));
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=?');$q->execute([$actualId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:$actual;
}

function student_process_lab_select_step(PDO $db,int $planId,int $planStepId,int $studentId): array {
    $plan=student_process_execution_plan($db,$planId,$studentId);
    if((string)$plan['status']==='completed'){
        $target=student_process_lab_plan_step($db,$planId,$planStepId);
        if(!$target||(string)$target['status']!=='skipped')throw new RuntimeException('Este processamento já foi concluído.');
    }
    $target=student_process_lab_plan_step($db,$planId,$planStepId)??throw new RuntimeException('Etapa não encontrada.');
    if((string)$target['status']==='completed')throw new RuntimeException('Esta etapa já foi registrada. Você pode consultá-la, mas não substituir o fato já realizado.');

    $db->beginTransaction();
    try{
        $existing=student_process_execution_session($db,$planId,$studentId);
        if($existing&&(int)($existing['plan_step_id']??0)!==$planStepId){
            $existing=student_process_execution_normalize($db,$existing);
            $old=student_process_lab_plan_step($db,$planId,(int)($existing['plan_step_id']??0));
            if($old&&!student_process_lab_terminal_status((string)$old['status']))student_process_lab_interrupt_attempt($db,$plan,$old,$studentId,$existing,'stage_switch');
        }
        $now=utc_now();$duration=student_process_time_seconds((string)$target['duration']);
        if((string)$target['status']==='skipped'){
            $db->prepare("UPDATE student_process_plan_steps SET status='planned',completed_at=NULL,updated_at=? WHERE id=? AND plan_id=?")->execute([$now,$planStepId,$planId]);
            if((string)$plan['status']==='completed')$db->prepare("UPDATE student_process_plans SET status='running',completed_at=NULL,updated_at=? WHERE id=? AND student_id=?")->execute([$now,$planId,$studentId]);
        }
        if($existing){
            $db->prepare("UPDATE student_process_execution_sessions SET plan_step_id=?,state='idle',duration_seconds=?,remaining_seconds=?,timer_started_at=NULL,timer_ends_at=NULL,paused_at=NULL,revision=revision+1,last_client_token='',updated_at=? WHERE plan_id=? AND student_id=?")
                ->execute([$planStepId,$duration,$duration,$now,$planId,$studentId]);
        }else{
            $db->prepare("INSERT INTO student_process_execution_sessions(plan_id,student_id,plan_step_id,state,duration_seconds,remaining_seconds,revision,last_client_token,created_at,updated_at) VALUES(?,?,?,'idle',?,?,0,'',?,?)")
                ->execute([$planId,$studentId,$planStepId,$duration,$duration,$now,$now]);
        }
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_process_plan',$planId,'select_process_stage',[],['plan_step_id'=>$planStepId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    $row=student_process_execution_session($db,$planId,$studentId)??throw new RuntimeException('Não foi possível selecionar a etapa.');
    return student_process_execution_payload($row);
}

function student_process_lab_skip_step(PDO $db,int $planId,int $planStepId,int $studentId): array {
    $plan=student_process_execution_plan($db,$planId,$studentId);$step=student_process_lab_plan_step($db,$planId,$planStepId)??throw new RuntimeException('Etapa não encontrada.');
    if(student_process_lab_terminal_status((string)$step['status']))return $step;
    $db->beginTransaction();
    try{
        $session=student_process_execution_session($db,$planId,$studentId);
        if($session&&(int)($session['plan_step_id']??0)===$planStepId)student_process_lab_interrupt_attempt($db,$plan,$step,$studentId,$session,'stage_skip');
        $now=utc_now();
        $db->prepare("UPDATE student_process_plan_steps SET status='skipped',actual_step_id=NULL,completed_at=?,updated_at=? WHERE id=? AND plan_id=?")
            ->execute([$now,$now,$planStepId,$planId]);
        $db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$planId,$studentId]);
        $pending=(int)$db->query("SELECT COUNT(*) FROM student_process_plan_steps WHERE plan_id=".$planId." AND status NOT IN ('completed','skipped')")->fetchColumn();
        if($pending===0)$db->prepare("UPDATE student_process_plans SET status='completed',completed_at=?,updated_at=? WHERE id=? AND student_id=?")->execute([$now,$now,$planId,$studentId]);
        elseif((string)$plan['status']==='completed')$db->prepare("UPDATE student_process_plans SET status='running',completed_at=NULL,updated_at=? WHERE id=? AND student_id=?")->execute([$now,$planId,$studentId]);
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_process_plan',$planId,'skip_process_stage',[],['plan_step_id'=>$planStepId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_process_lab_plan_step($db,$planId,$planStepId)??$step;
}

function student_process_lab_complete_step(PDO $db,int $planId,int $planStepId,int $studentId): array {
    $plan=student_process_execution_plan($db,$planId,$studentId);$step=student_process_lab_plan_step($db,$planId,$planStepId)??throw new RuntimeException('Etapa não encontrada.');
    if((string)$step['status']==='completed')return $step;
    if((string)$step['status']==='skipped')throw new RuntimeException('Selecione esta etapa novamente antes de executá-la.');
    $session=student_process_execution_session($db,$planId,$studentId);
    if($session&&(int)($session['plan_step_id']??0)!==$planStepId)throw new RuntimeException('Selecione esta etapa antes de concluí-la.');
    $session=$session?student_process_execution_normalize($db,$session):null;
    $plannedSeconds=student_process_time_seconds((string)$step['duration']);$actualSeconds=null;$timerState=(string)($session['state']??'idle');
    if($plannedSeconds!==null){
        if($session&&$session['remaining_seconds']!==null&&in_array($timerState,['running','paused','elapsed'],true))$actualSeconds=max(0,$plannedSeconds-max(0,(int)$session['remaining_seconds']));
        else $actualSeconds=$plannedSeconds;
    }
    $payload=student_process_json_array((string)$step['payload_json']);$payload['stage_key']=(string)$step['stage_key'];$payload['duration']=$actualSeconds===null?(string)$step['duration']:student_process_seconds_label($actualSeconds);
    $mode=student_process_recording_mode_for_test($db,(int)$plan['test_id'],$studentId);$mode=$mode==='retroactive'?'mixed':'live';

    $db->beginTransaction();
    try{
        student_process_execution_mark_started($db,$plan,$step,$studentId);
        $actual=student_process_recording_add_step($db,(int)$plan['test_id'],$studentId,$payload,$mode);$actualId=(int)($actual['id']??0);if($actualId<1)throw new RuntimeException('Não foi possível registrar a etapa executada.');
        $meta=student_process_json_array((string)($actual['metadata_json']??'{}'));
        $meta['planned_duration']=(string)$step['duration'];$meta['timer_state']=$timerState;
        if($plannedSeconds!==null&&$actualSeconds!==null&&$actualSeconds<$plannedSeconds)$meta['completed_before_planned_time']=true;
        $now=utc_now();
        $db->prepare('UPDATE student_process_steps SET metadata_json=?,updated_at=? WHERE id=? AND test_id=?')->execute([json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$actualId,(int)$plan['test_id']]);
        $db->prepare("UPDATE student_process_plan_steps SET status='completed',actual_step_id=?,completed_at=?,updated_at=? WHERE id=? AND plan_id=?")
            ->execute([$actualId,$now,$now,$planStepId,$planId]);
        $db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$planId,$studentId]);
        $pending=(int)$db->query("SELECT COUNT(*) FROM student_process_plan_steps WHERE plan_id=".$planId." AND status NOT IN ('completed','skipped')")->fetchColumn();
        if($pending===0)$db->prepare("UPDATE student_process_plans SET status='completed',completed_at=?,updated_at=? WHERE id=? AND student_id=?")->execute([$now,$now,$planId,$studentId]);
        else $db->prepare("UPDATE student_process_plans SET status='running',started_at=COALESCE(started_at,?),completed_at=NULL,updated_at=? WHERE id=? AND student_id=?")->execute([$now,$now,$planId,$studentId]);
        $recording=student_process_recording_meta($db,(int)$plan['test_id'],$studentId);student_process_recording_save_meta($db,(int)$plan['test_id'],$studentId,$mode,$recording['performed_on']??null,(string)($recording['notes']??''));
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_process_plan',$planId,'complete_process_stage',[],['plan_step_id'=>$planStepId,'actual_step_id'=>$actualId,'actual_seconds'=>$actualSeconds]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_process_lab_plan_step($db,$planId,$planStepId)??$step;
}
