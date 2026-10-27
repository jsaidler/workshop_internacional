<?php
declare(strict_types=1);

/**
 * Associar ou trocar um roteiro substitui somente a folha de referência do
 * registro. Fatos livres, checks já feitos e estoque não são reinterpretados.
 */
function student_process_replanning_template_source(PDO $db,int $templateId,int $studentId): array {
    $template=student_process_template_for_student($db,$templateId,$studentId)??throw new RuntimeException('Processamento não encontrado.');
    $steps=student_process_template_steps($db,$templateId);if(!$steps)throw new RuntimeException('Adicione pelo menos uma etapa ao processamento.');
    return ['kind'=>'template','name'=>(string)$template['name'],'source_template_id'=>$templateId,'source_global_version_id'=>null,'steps'=>array_map(static fn(array $step): array=>[
        'stage_key'=>(string)$step['stage_key'],'label'=>(string)$step['label'],'duration'=>(string)$step['duration'],'agitation_interval'=>(string)$step['agitation_interval'],'payload_json'=>(string)$step['payload_json'],
    ],$steps)];
}

function student_process_replanning_standard_source(PDO $db,string $standardKey): array {
    $standard=student_global_process_for_key($db,$standardKey)??throw new RuntimeException('Processamento padrão não encontrado.');$steps=[];
    foreach((array)$standard['steps'] as $raw){
        $stageKey=(string)($raw['stage_key']??'');if($stageKey==='')continue;$data=student_process_stage_data($stageKey,$raw);$payload=student_process_template_payload_from_stage($data,$stageKey,$raw);
        $steps[]=['stage_key'=>$stageKey,'label'=>(string)$data['label'],'duration'=>(string)$data['duration'],'agitation_interval'=>(string)($raw['agitation_interval']??$payload['agitation_interval']??''),'payload_json'=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
    }
    if(!$steps)throw new RuntimeException('Este roteiro não possui etapas utilizáveis.');
    return ['kind'=>'standard','name'=>(string)$standard['name'],'source_template_id'=>null,'source_global_version_id'=>(int)($standard['version_id']??0)?:null,'steps'=>$steps];
}

/**
 * Checks pertencem à etapa do roteiro do registro. Ao trocar a referência,
 * preservamos checks somente quando encontramos a mesma stage_key na mesma
 * ocorrência relativa. Não inferimos conclusão a partir de fatos antigos.
 */
function student_process_replanning_check_buckets(array $steps): array {
    $buckets=[];foreach($steps as $step)$buckets[(string)$step['stage_key']][]=$step;return $buckets;
}

function student_process_replan(PDO $db,int $testId,int $studentId,array $source): array {
    student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    $routeSteps=array_values((array)($source['steps']??[]));if(!$routeSteps)throw new RuntimeException('Este roteiro não possui etapas.');
    $newName=trim((string)($source['name']??''));if($newName==='')throw new RuntimeException('Roteiro inválido.');

    $existing=student_process_plan_for_test($db,$testId,$studentId);$previousSource=(string)($existing['source_name']??'');
    $oldPlanId=(int)($existing['id']??0);$oldPlanUuid=(string)($existing['plan_uuid']??'');$oldCreatedAt=(string)($existing['created_at']??'');
    $oldSteps=$existing?student_process_plan_steps($db,$oldPlanId):[];$buckets=student_process_replanning_check_buckets($oldSteps);$preservedChecks=0;
    $facts=student_process_steps($db,$testId);$ownsTransaction=!$db->inTransaction();if($ownsTransaction)$db->beginTransaction();
    try{
        $now=utc_now();
        if($existing){
            // Timers são ferramentas; trocar a folha de referência não produz fato laboratorial.
            $db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$oldPlanId,$studentId]);
            $db->prepare('DELETE FROM student_process_step_timers WHERE plan_id=? AND student_id=?')->execute([$oldPlanId,$studentId]);
            $db->prepare('DELETE FROM student_process_plans WHERE id=? AND student_id=?')->execute([$oldPlanId,$studentId]);
        }
        $q=$db->prepare('INSERT INTO student_process_plans(plan_uuid,student_id,test_id,source_template_id,source_global_version_id,source_name,status,started_at,completed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,\'planned\',NULL,NULL,?,?)');
        $q->execute([$oldPlanUuid!==''?$oldPlanUuid:student_uuid(),$studentId,$testId,$source['source_template_id']??null,$source['source_global_version_id']??null,$newName,$oldCreatedAt!==''?$oldCreatedAt:$now,$now]);$planId=(int)$db->lastInsertId();
        $insert=$db->prepare('INSERT INTO student_process_plan_steps(plan_id,position,stage_key,label,duration,agitation_interval,payload_json,status,actual_step_id,started_at,completed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,NULL,?,?,?)');
        foreach($routeSteps as $i=>$routeStep){
            $key=(string)$routeStep['stage_key'];$previous=!empty($buckets[$key])?array_shift($buckets[$key]):null;$completed=$previous&&(string)$previous['status']==='completed';if($completed)$preservedChecks++;
            $insert->execute([$planId,$i+1,$key,(string)$routeStep['label'],(string)$routeStep['duration'],(string)($routeStep['agitation_interval']??''),(string)$routeStep['payload_json'],$completed?'completed':'planned',$previous?(((int)($previous['actual_step_id']??0))?:null):null,$completed?(string)($previous['completed_at']??$now):null,$now,$now]);
        }
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_test',$testId,'change_process_route',[
            'plan_id'=>$oldPlanId?:null,'source_name'=>$previousSource,'fact_count'=>count($facts),'checked_step_count'=>count(array_filter($oldSteps,static fn(array $s): bool=>(string)$s['status']==='completed'))
        ],['plan_id'=>$planId,'source_name'=>$newName,'fact_count'=>count($facts),'preserved_checked_step_count'=>$preservedChecks]);
        if($ownsTransaction)$db->commit();
    }catch(Throwable $e){if($ownsTransaction&&$db->inTransaction())$db->rollBack();throw $e;}
    $plan=student_process_plan_for_test($db,$testId,$studentId)??[];$plan['_change']=['previous_source'=>$previousSource,'source_name'=>$newName,'fact_count'=>count($facts),'preserved_checks'=>$preservedChecks];return $plan;
}

function student_process_replan_template(PDO $db,int $templateId,int $testId,int $studentId): array {return student_process_replan($db,$testId,$studentId,student_process_replanning_template_source($db,$templateId,$studentId));}
function student_process_replan_standard(PDO $db,string $standardKey,int $testId,int $studentId): array {return student_process_replan($db,$testId,$studentId,student_process_replanning_standard_source($db,$standardKey));}

function student_process_replanning_notice(array $plan): string {
    $change=(array)($plan['_change']??[]);$previous=(string)($change['previous_source']??'');$preserved=(int)($change['preserved_checks']??0);
    if($previous!=='')return 'Roteiro alterado.'.($preserved>0?' '.$preserved.' marcação'.($preserved===1?' compatível foi preservada.':' compatíveis foram preservadas.'):' Os registros já existentes foram preservados.');
    return 'Roteiro associado ao registro.';
}