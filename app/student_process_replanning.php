<?php
declare(strict_types=1);

/**
 * Replanning preserves factual student_process_steps and replaces only the
 * route snapshot that still governs the record. Timer/session state is
 * operational UI state and is never materialized as a laboratory fact.
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

function student_process_replanning_fact_is_legacy_interrupted(array $fact): bool {
    $meta=student_process_json_array((string)($fact['metadata_json']??'{}'));
    return !empty($meta['interrupted']);
}

function student_process_replanning_prefix(array $facts,array $routeSteps): int {
    $limit=min(count($facts),count($routeSteps));$matched=0;
    for($i=0;$i<$limit;$i++){
        // Legacy interrupted facts may exist from older builds. They remain
        // historical records but must not be treated as completed route stages.
        if(student_process_replanning_fact_is_legacy_interrupted($facts[$i]))break;
        if((string)($facts[$i]['stage_key']??'')!==(string)($routeSteps[$i]['stage_key']??''))break;
        $matched++;
    }
    return $matched;
}

function student_process_replan(PDO $db,int $testId,int $studentId,array $source): array {
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    if((string)$test['status']==='reviewed')throw new RuntimeException('Este registro já foi revisado.');
    $routeSteps=array_values((array)($source['steps']??[]));if(!$routeSteps)throw new RuntimeException('Este roteiro não possui etapas.');
    $newName=trim((string)($source['name']??''));if($newName==='')throw new RuntimeException('Roteiro inválido.');

    $existing=student_process_plan_for_test($db,$testId,$studentId);
    $previousSource=(string)($existing['source_name']??'');
    $oldPlanId=(int)($existing['id']??0);$oldPlanUuid=(string)($existing['plan_uuid']??'');$oldCreatedAt=(string)($existing['created_at']??'');$oldStartedAt=(string)($existing['started_at']??'');
    $facts=student_process_steps($db,$testId);$matched=student_process_replanning_prefix($facts,$routeSteps);
    $preserved=count($facts);$divergent=max(0,$preserved-$matched);$pending=max(0,count($routeSteps)-$matched);
    $ownsTransaction=!$db->inTransaction();if($ownsTransaction)$db->beginTransaction();
    try{
        $now=utc_now();
        if($existing){
            // Timer/session state belongs to the UI, not to laboratory history.
            $db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$oldPlanId,$studentId]);
            $db->prepare('DELETE FROM student_process_plans WHERE id=? AND student_id=?')->execute([$oldPlanId,$studentId]);
        }
        $status=$pending===0?'completed':($preserved>0?'running':'planned');
        $startedAt=$oldStartedAt!==''?$oldStartedAt:($preserved>0?$now:null);$completedAt=$status==='completed'?$now:null;
        $q=$db->prepare('INSERT INTO student_process_plans(plan_uuid,student_id,test_id,source_template_id,source_global_version_id,source_name,status,started_at,completed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([$oldPlanUuid!==''?$oldPlanUuid:student_uuid(),$studentId,$testId,$source['source_template_id']??null,$source['source_global_version_id']??null,$newName,$status,$startedAt,$completedAt,$oldCreatedAt!==''?$oldCreatedAt:$now,$now]);
        $planId=(int)$db->lastInsertId();
        $insert=$db->prepare('INSERT INTO student_process_plan_steps(plan_id,position,stage_key,label,duration,agitation_interval,payload_json,status,actual_step_id,started_at,completed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach($routeSteps as $i=>$routeStep){
            $isMatched=$i<$matched;$fact=$isMatched?($facts[$i]??null):null;$actualStepId=$isMatched?(((int)($fact['id']??0))?:null):null;
            $insert->execute([$planId,$i+1,(string)$routeStep['stage_key'],(string)$routeStep['label'],(string)$routeStep['duration'],(string)($routeStep['agitation_interval']??''),(string)$routeStep['payload_json'],$isMatched?'completed':'planned',$actualStepId,null,$isMatched?(string)($fact['updated_at']??$now):null,$now,$now]);
        }
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_test',$testId,'change_process_route',[
            'plan_id'=>$oldPlanId?:null,'source_name'=>$previousSource,'fact_count'=>$preserved
        ],[
            'plan_id'=>$planId,'source_name'=>$newName,'preserved_fact_count'=>$preserved,'matched_prefix'=>$matched,'divergent_fact_count'=>$divergent,'pending_step_count'=>$pending
        ]);
        if($ownsTransaction)$db->commit();
    }catch(Throwable $e){if($ownsTransaction&&$db->inTransaction())$db->rollBack();throw $e;}
    $plan=student_process_plan_for_test($db,$testId,$studentId)??[];
    $plan['_change']=['previous_source'=>$previousSource,'source_name'=>$newName,'preserved_steps'=>$preserved,'matched_prefix'=>$matched,'divergent_steps'=>$divergent,'pending_steps'=>$pending];
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
    if($preserved>0&&$pending>0)return 'Roteiro alterado. '.$preserved.' etapa'.($preserved===1?' realizada permanece':'s realizadas permanecem').' no histórico; as próximas '.$pending.' seguem “'.$name.'”.';
    if($preserved>0)return 'Roteiro alterado. '.$preserved.' etapa'.($preserved===1?' realizada permanece':'s realizadas permanecem').' no histórico; não há etapas pendentes neste roteiro.';
    return 'Roteiro associado ao registro. Nenhuma etapa foi consolidada pelo Caderno.';
}
