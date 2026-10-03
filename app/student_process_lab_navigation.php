<?php
declare(strict_types=1);

/**
 * Laboratory navigation follows the photographic process without turning the
 * interface into its controller. Browsing a stage is read-only. Only an
 * explicit declaration of the current stage advances the process, and reaching
 * a later stage confirms that the preceding stages in that route happened with
 * the snapshot values attached to this record.
 */
function student_process_lab_plan_step(PDO $db,int $planId,int $planStepId): ?array {
    if($planId<1||$planStepId<1)return null;
    $q=$db->prepare('SELECT * FROM student_process_plan_steps WHERE id=? AND plan_id=? LIMIT 1');
    $q->execute([$planStepId,$planId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_process_lab_agitation_profile(array $step): array {
    $payload=student_process_json_array((string)($step['payload_json']??'{}'));
    $mode=trim((string)($payload['agitation_mode']??''));
    $intervalRaw=trim((string)($payload['agitation_interval']??$step['agitation_interval']??''));
    $durationRaw=trim((string)($payload['agitation_duration']??''));
    $legacy=trim((string)($payload['agitation']??''));
    if(!in_array($mode,['none','periodic','continuous'],true)){
        $fold=mb_strtolower($legacy);
        if(str_contains($fold,'contín')||str_contains($fold,'contin'))$mode='continuous';
        elseif($intervalRaw!=='')$mode='periodic';
        else $mode='none';
    }
    if($mode==='periodic'&&$durationRaw===''&&$legacy!==''){
        if(preg_match('/(\d+(?:[.,]\d+)?)\s*(s|seg|segs|segundo|segundos|min|mins|minuto|minutos)\b/ui',$legacy,$m))$durationRaw=$m[1].' '.$m[2];
    }
    $durationSeconds=$durationRaw!==''?student_process_time_seconds($durationRaw):null;
    $intervalSeconds=$intervalRaw!==''?student_process_time_seconds($intervalRaw):null;
    if($mode!=='periodic'){$durationRaw='';$durationSeconds=null;$intervalRaw='';$intervalSeconds=null;}
    return [
        'mode'=>$mode,
        'duration'=>$durationRaw,
        'duration_seconds'=>$durationSeconds,
        'interval'=>$intervalRaw,
        'interval_seconds'=>$intervalSeconds,
        'legacy'=>$legacy,
    ];
}

function student_process_lab_recording_mode(PDO $db,int $testId,int $studentId): string {
    return student_process_recording_mode_for_test($db,$testId,$studentId)==='retroactive'?'mixed':'live';
}

function student_process_lab_materialize_step(PDO $db,array $plan,array $step,int $studentId,string $reason='process_progression'): array {
    if((string)$step['status']==='completed')return $step;
    $testId=(int)$plan['test_id'];$payload=student_process_json_array((string)$step['payload_json']);
    $payload['stage_key']=(string)$step['stage_key'];$payload['duration']=(string)$step['duration'];
    $payload['agitation_interval']=(string)($step['agitation_interval']??'');
    $actual=student_process_recording_add_step($db,$testId,$studentId,$payload,'live');$actualId=(int)($actual['id']??0);
    if($actualId<1)throw new RuntimeException('Não foi possível registrar a etapa realizada.');

    $profile=student_process_lab_agitation_profile($step);$meta=student_process_json_array((string)($actual['metadata_json']??'{}'));
    $meta['recording_mode']='live';$meta['confirmed_by']=$reason;$meta['route_plan_step_id']=(int)$step['id'];
    $meta['route_duration']=(string)$step['duration'];$meta['agitation_mode']=$profile['mode'];
    if($profile['mode']==='periodic'){
        $meta['agitation_duration']=$profile['duration'];$meta['agitation_interval']=$profile['interval'];
    }
    $now=utc_now();
    $db->prepare('UPDATE student_process_steps SET metadata_json=?,updated_at=? WHERE id=? AND test_id=?')
        ->execute([json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$actualId,$testId]);
    $db->prepare("UPDATE student_process_plan_steps SET status='completed',actual_step_id=?,completed_at=?,updated_at=? WHERE id=? AND plan_id=?")
        ->execute([$actualId,$now,$now,(int)$step['id'],(int)$plan['id']]);
    return student_process_lab_plan_step($db,(int)$plan['id'],(int)$step['id'])??$step;
}

function student_process_lab_advance_to_step(PDO $db,int $planId,int $planStepId,int $studentId): array {
    $plan=student_process_execution_plan($db,$planId,$studentId);
    if((string)$plan['status']==='completed')throw new RuntimeException('Este processamento já foi concluído.');
    $target=student_process_lab_plan_step($db,$planId,$planStepId)??throw new RuntimeException('Etapa não encontrada.');
    if((string)$target['status']==='completed')throw new RuntimeException('Esta etapa já foi realizada. Você pode consultá-la no roteiro ou corrigi-la no Caderno.');
    $steps=student_process_plan_steps($db,$planId);$current=student_process_execution_current_step($db,$plan)??throw new RuntimeException('O processamento já foi concluído.');
    if((int)$target['position']<(int)$current['position'])throw new RuntimeException('Esta etapa já ficou para trás no processo. Consulte-a sem alterar a posição atual.');

    $db->beginTransaction();
    try{
        foreach($steps as $step){
            if((int)$step['position']>=(int)$target['position'])break;
            if((string)$step['status']!=='completed')student_process_lab_materialize_step($db,$plan,$step,$studentId,'advanced_to_later_stage');
        }
        $now=utc_now();
        $db->prepare("UPDATE student_process_plan_steps SET status='running',started_at=COALESCE(started_at,?),updated_at=? WHERE id=? AND plan_id=? AND status!='completed'")
            ->execute([$now,$now,$planStepId,$planId]);
        $db->prepare("UPDATE student_process_plans SET status='running',started_at=COALESCE(started_at,?),completed_at=NULL,updated_at=? WHERE id=? AND student_id=?")
            ->execute([$now,$now,$planId,$studentId]);
        $db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$planId,$studentId]);
        $freshPlan=student_process_execution_plan($db,$planId,$studentId);student_process_execution_ensure($db,$freshPlan,$studentId);
        $mode=student_process_lab_recording_mode($db,(int)$plan['test_id'],$studentId);$recording=student_process_recording_meta($db,(int)$plan['test_id'],$studentId);
        student_process_recording_save_meta($db,(int)$plan['test_id'],$studentId,$mode,$recording['performed_on']??null,(string)($recording['notes']??''));
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_process_plan',$planId,'advance_process_position',[],['plan_step_id'=>$planStepId,'position'=>(int)$target['position']]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_process_lab_plan_step($db,$planId,$planStepId)??$target;
}

function student_process_lab_finish_process(PDO $db,int $planId,int $planStepId,int $studentId): array {
    $plan=student_process_execution_plan($db,$planId,$studentId);$current=student_process_execution_current_step($db,$plan)??throw new RuntimeException('Este processamento já foi concluído.');
    if((int)$current['id']!==$planStepId)throw new RuntimeException('Conclua o processamento a partir da etapa atual.');
    $pending=array_values(array_filter(student_process_plan_steps($db,$planId),static fn(array $step): bool=>(string)$step['status']!=='completed'));
    if(count($pending)!==1)throw new RuntimeException('Ainda existem etapas posteriores neste roteiro. Avance até a última etapa antes de concluir.');

    $db->beginTransaction();
    try{
        student_process_lab_materialize_step($db,$plan,$current,$studentId,'process_finished');$now=utc_now();
        $db->prepare("UPDATE student_process_plans SET status='completed',started_at=COALESCE(started_at,?),completed_at=?,updated_at=? WHERE id=? AND student_id=?")
            ->execute([$now,$now,$now,$planId,$studentId]);
        $db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$planId,$studentId]);
        $mode=student_process_lab_recording_mode($db,(int)$plan['test_id'],$studentId);$recording=student_process_recording_meta($db,(int)$plan['test_id'],$studentId);
        student_process_recording_save_meta($db,(int)$plan['test_id'],$studentId,$mode,$recording['performed_on']??null,(string)($recording['notes']??''));
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_process_plan',$planId,'finish_process',[],['plan_step_id'=>$planStepId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_process_execution_plan($db,$planId,$studentId);
}

function student_process_lab_update_timer_settings(PDO $db,int $planId,int $planStepId,int $studentId,array $input): array {
    $plan=student_process_execution_plan($db,$planId,$studentId);$step=student_process_lab_plan_step($db,$planId,$planStepId)??throw new RuntimeException('Etapa não encontrada.');
    if((string)$step['status']==='completed')throw new RuntimeException('Esta etapa já foi realizada. Corrija os dados no Caderno.');

    $durationRaw=student_workspace_text($input['duration']??'',40);$durationSeconds=$durationRaw===''?null:student_process_time_seconds($durationRaw);
    if($durationRaw!==''&&($durationSeconds===null||$durationSeconds<=0))throw new RuntimeException('Informe um tempo válido para a etapa.');
    $duration=$durationSeconds===null?'':student_process_seconds_label($durationSeconds);
    $mode=(string)($input['agitation_mode']??'none');if(!in_array($mode,['none','periodic','continuous'],true))$mode='none';
    $agitationDuration='';$agitationInterval='';
    if($mode==='periodic'){
        $rawDuration=student_workspace_text($input['agitation_duration']??'',40);$rawInterval=student_workspace_text($input['agitation_interval']??'',40);
        $pulseSeconds=student_process_time_seconds($rawDuration);$intervalSeconds=student_process_time_seconds($rawInterval);
        if($pulseSeconds===null||$pulseSeconds<=0)throw new RuntimeException('Informe quanto tempo dura cada agitação.');
        if($intervalSeconds===null||$intervalSeconds<=0)throw new RuntimeException('Informe o intervalo entre o início de cada agitação.');
        if($pulseSeconds>=$intervalSeconds)throw new RuntimeException('A duração da agitação deve ser menor que o intervalo. Para agitação sem pausa, escolha Contínua.');
        $agitationDuration=student_process_seconds_label($pulseSeconds);$agitationInterval=student_process_seconds_label($intervalSeconds);
    }

    $payload=student_process_json_array((string)$step['payload_json']);$before=['duration'=>(string)$step['duration'],'agitation_interval'=>(string)$step['agitation_interval'],'agitation_mode'=>$payload['agitation_mode']??null,'agitation_duration'=>$payload['agitation_duration']??null];
    $payload['duration']=$duration;$payload['agitation_mode']=$mode;$payload['agitation_duration']=$agitationDuration;$payload['agitation_interval']=$agitationInterval;
    $now=utc_now();$db->beginTransaction();
    try{
        $db->prepare('UPDATE student_process_plan_steps SET duration=?,agitation_interval=?,payload_json=?,updated_at=? WHERE id=? AND plan_id=?')
            ->execute([$duration,$agitationInterval,json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$planStepId,$planId]);
        $current=student_process_execution_current_step($db,$plan);
        $timerState=null;
        if($current&&(int)$current['id']===$planStepId)$timerState=student_process_execution_retime($db,$planId,$planStepId,$studentId,$durationSeconds);
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_process_plan_step',$planStepId,'update_timer_settings',$before,['duration'=>$duration,'agitation_interval'=>$agitationInterval,'agitation_mode'=>$mode,'agitation_duration'=>$agitationDuration]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return ['step'=>student_process_lab_plan_step($db,$planId,$planStepId)??$step,'state'=>$timerState];
}
