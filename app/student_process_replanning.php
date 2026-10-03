<?php
declare(strict_types=1);

/**
 * Replanning keeps factual student_process_steps immutable and replaces only
 * the student's future intention. A currently running/paused route step is
 * first materialized as an interrupted factual step, so changing route never
 * erases something that already happened in the laboratory.
 */
function student_process_replanning_template_source(PDO $db,int $templateId,int $studentId): array {
    $template=student_process_template_for_student($db,$templateId,$studentId)??throw new RuntimeException('Processamento não encontrado.');
    $steps=student_process_template_steps($db,$templateId);if(!$steps)throw new RuntimeException('Adicione pelo menos uma etapa ao processamento.');
    return [
        'kind'=>'template',
        'name'=>(string)$template['name'],
        'source_template_id'=>$templateId,
        'source_global_version_id'=>null,
        'steps'=>array_map(static fn(array $step): array=>[
            'stage_key'=>(string)$step['stage_key'],
            'label'=>(string)$step['label'],
            'duration'=>(string)$step['duration'],
            'agitation_interval'=>(string)$step['agitation_interval'],
            'payload_json'=>(string)$step['payload_json'],
        ],$steps),
    ];
}

function student_process_replanning_standard_source(PDO $db,string $standardKey): array {
    $standard=student_global_process_for_key($db,$standardKey)??throw new RuntimeException('Processamento padrão não encontrado.');
    $steps=[];
    foreach((array)$standard['steps'] as $raw){
        $stageKey=(string)($raw['stage_key']??'');if($stageKey==='')continue;
        $data=student_process_stage_data($stageKey,$raw);
        $payload=student_process_template_payload_from_stage($data,$stageKey,$raw);
        $agitationInterval=(string)($raw['agitation_interval']??$payload['agitation_interval']??'');
        $steps[]=[
            'stage_key'=>$stageKey,
            'label'=>(string)$data['label'],
            'duration'=>(string)$data['duration'],
            'agitation_interval'=>$agitationInterval,
            'payload_json'=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        ];
    }
    if(!$steps)throw new RuntimeException('Este roteiro não possui etapas utilizáveis.');
    return [
        'kind'=>'standard',
        'name'=>(string)$standard['name'],
        'source_template_id'=>null,
        'source_global_version_id'=>(int)($standard['version_id']??0)?:null,
        'steps'=>$steps,
    ];
}

function student_process_replanning_fact_is_interrupted(array $fact): bool {
    $meta=student_process_json_array((string)($fact['metadata_json']??'{}'));
    return !empty($meta['interrupted']);
}

function student_process_replanning_prefix(array $facts,array $routeSteps): int {
    $limit=min(count($facts),count($routeSteps));$matched=0;
    for($i=0;$i<$limit;$i++){
        if(student_process_replanning_fact_is_interrupted($facts[$i]))break;
        if((string)($facts[$i]['stage_key']??'')!==(string)($routeSteps[$i]['stage_key']??''))break;
        $matched++;
    }
    return $matched;
}

function student_process_replanning_current_started_step(PDO $db,?array $plan): ?array {
    if(!$plan)return null;
    foreach(student_process_plan_steps($db,(int)$plan['id']) as $step){
        if((string)$step['status']==='completed')continue;
        return (string)$step['status']==='running'?$step:null;
    }
    return null;
}

function student_process_replanning_interrupted_duration(PDO $db,array $plan,array $step,int $studentId): array {
    $planned=student_process_time_seconds((string)$step['duration']);$elapsed=null;$state='started';
    $session=student_process_execution_session($db,(int)$plan['id'],$studentId);
    if($session&&(int)($session['plan_step_id']??0)===(int)$step['id']){
        $session=student_process_execution_normalize($db,$session);$state=(string)$session['state'];
        if($planned!==null&&$session['remaining_seconds']!==null&&in_array($state,['running','paused','elapsed'],true)){
            $elapsed=max(0,$planned-(int)$session['remaining_seconds']);
        }
    }
    return ['elapsed_seconds'=>$elapsed,'execution_state'=>$state];
}

function student_process_replanning_materialize_started(PDO $db,array $plan,array $step,int $studentId): ?array {
    $testId=(int)$plan['test_id'];$timing=student_process_replanning_interrupted_duration($db,$plan,$step,$studentId);
    $payload=student_process_json_array((string)$step['payload_json']);
    $payload['stage_key']=(string)$step['stage_key'];
    $payload['duration']=$timing['elapsed_seconds']===null?'':student_process_seconds_label((int)$timing['elapsed_seconds']);
    $existingNotes=trim((string)($payload['notes']??''));
    $interruptedNote='Etapa interrompida ao alterar as próximas etapas.';
    $payload['notes']=$existingNotes===''?$interruptedNote:$existingNotes.' '.$interruptedNote;
    $actual=student_process_recording_add_step($db,$testId,$studentId,$payload,'mixed');
    $actualId=(int)($actual['id']??0);if($actualId<1)return null;
    $meta=student_process_json_array((string)($actual['metadata_json']??'{}'));
    $meta['interrupted']=true;
    $meta['interrupted_reason']='route_change';
    $meta['planned_duration']=(string)$step['duration'];
    $meta['elapsed_seconds']=$timing['elapsed_seconds'];
    $meta['execution_state']=$timing['execution_state'];
    $now=utc_now();
    $db->prepare('UPDATE student_process_steps SET metadata_json=?,updated_at=? WHERE id=? AND test_id=?')
        ->execute([json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$actualId,$testId]);
    $recording=student_process_recording_meta($db,$testId,$studentId);
    student_process_recording_save_meta($db,$testId,$studentId,'mixed',$recording['performed_on']??null,(string)($recording['notes']??''));
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=? LIMIT 1');$q->execute([$actualId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:$actual;
}

function student_process_replan(PDO $db,int $testId,int $studentId,array $source): array {
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    if((string)$test['status']==='reviewed')throw new RuntimeException('Este registro já foi revisado.');
    $routeSteps=array_values((array)($source['steps']??[]));if(!$routeSteps)throw new RuntimeException('Este roteiro não possui etapas.');
    $newName=trim((string)($source['name']??''));if($newName==='')throw new RuntimeException('Roteiro inválido.');

    $existing=student_process_plan_for_test($db,$testId,$studentId);
    $previousSource=(string)($existing['source_name']??'');$interrupted=null;
    if($existing){
        $started=student_process_replanning_current_started_step($db,$existing);
        if($started)$interrupted=student_process_replanning_materialize_started($db,$existing,$started,$studentId);
    }

    $facts=student_process_steps($db,$testId);$matched=student_process_replanning_prefix($facts,$routeSteps);
    $preserved=count($facts);$divergent=max(0,$preserved-$matched);$pending=max(0,count($routeSteps)-$matched);
    $oldPlanId=(int)($existing['id']??0);$oldPlanUuid=(string)($existing['plan_uuid']??'');$oldCreatedAt=(string)($existing['created_at']??'');$oldStartedAt=(string)($existing['started_at']??'');
    $now=utc_now();$db->beginTransaction();
    try{
        if($existing){
            $db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$oldPlanId,$studentId]);
            $db->prepare('DELETE FROM student_process_plans WHERE id=? AND student_id=?')->execute([$oldPlanId,$studentId]);
        }
        $status=$pending===0?'completed':($preserved>0?'running':'planned');
        $startedAt=$oldStartedAt!==''?$oldStartedAt:($preserved>0?$now:null);
        $completedAt=$status==='completed'?$now:null;
        $q=$db->prepare('INSERT INTO student_process_plans(plan_uuid,student_id,test_id,source_template_id,source_global_version_id,source_name,status,started_at,completed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([$oldPlanUuid!==''?$oldPlanUuid:student_uuid(),$studentId,$testId,$source['source_template_id']??null,$source['source_global_version_id']??null,$newName,$status,$startedAt,$completedAt,$oldCreatedAt!==''?$oldCreatedAt:$now,$now]);
        $planId=(int)$db->lastInsertId();
        $insert=$db->prepare('INSERT INTO student_process_plan_steps(plan_id,position,stage_key,label,duration,agitation_interval,payload_json,status,actual_step_id,started_at,completed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach($routeSteps as $i=>$routeStep){
            $isMatched=$i<$matched;$fact=$isMatched?($facts[$i]??null):null;
            $insert->execute([$planId,$i+1,(string)$routeStep['stage_key'],(string)$routeStep['label'],(string)$routeStep['duration'],(string)($routeStep['agitation_interval']??''),(string)$routeStep['payload_json'],$isMatched?'completed':'planned',$isMatched?(int)($fact['id']??0)?:null,null,null,$now,$now]);
        }
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_test',$testId,'change_process_route',[
            'plan_id'=>$oldPlanId?:null,'source_name'=>$previousSource,'fact_count'=>$preserved-($interrupted?1:0)
        ],[
            'plan_id'=>$planId,'source_name'=>$newName,'preserved_fact_count'=>$preserved,'matched_prefix'=>$matched,'divergent_fact_count'=>$divergent,'pending_step_count'=>$pending,'interrupted_step_id'=>(int)($interrupted['id']??0)?:null
        ]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    $plan=student_process_plan_for_test($db,$testId,$studentId)??[];
    $plan['_change']=[
        'previous_source'=>$previousSource,
        'source_name'=>$newName,
        'preserved_steps'=>$preserved,
        'matched_prefix'=>$matched,
        'divergent_steps'=>$divergent,
        'pending_steps'=>$pending,
        'interrupted_step_added'=>$interrupted!==null,
    ];
    return $plan;
}

function student_process_replan_template(PDO $db,int $templateId,int $testId,int $studentId): array {
    return student_process_replan($db,$testId,$studentId,student_process_replanning_template_source($db,$templateId,$studentId));
}

function student_process_replan_standard(PDO $db,string $standardKey,int $testId,int $studentId): array {
    return student_process_replan($db,$testId,$studentId,student_process_replanning_standard_source($db,$standardKey));
}

function student_process_replanning_notice(array $plan): string {
    $change=(array)($plan['_change']??[]);$preserved=(int)($change['preserved_steps']??0);$pending=(int)($change['pending_steps']??0);$name=(string)($change['source_name']??$plan['source_name']??'novo roteiro');
    if(!empty($change['interrupted_step_added']))return 'A etapa em andamento foi preservada como interrompida. As próximas '.$pending.' etapa'.($pending===1?'':'s').' seguem “'.$name.'”.';
    if($preserved>0)return 'Roteiro alterado. '.$preserved.' etapa'.($preserved===1?' registrada foi preservada':'s registradas foram preservadas').'; as próximas '.$pending.' seguem “'.$name.'”.';
    return 'Roteiro associado ao registro. Nenhuma execução foi iniciada.';
}
