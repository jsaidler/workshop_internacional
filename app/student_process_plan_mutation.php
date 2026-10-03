<?php
declare(strict_types=1);

/**
 * Explicit mutations of the per-record process snapshot.
 *
 * These operations change only the future route of one photograph. They never
 * infer physical facts from interface state. Completed factual stages are
 * preserved; changing those facts belongs to explicit correction in Caderno.
 */
function student_process_plan_mutation_token(string $token): string {
    $token=trim($token);
    if($token==='')return '';
    if(strlen($token)>120||!preg_match('/^[A-Za-z0-9._:-]+$/',$token))throw new RuntimeException('Identificador da operação inválido.');
    return $token;
}

function student_process_plan_mutation_claim(PDO $db,string $token,int $studentId,int $planId,string $action): ?array {
    $token=student_process_plan_mutation_token($token);
    if($token==='')return null;
    $q=$db->prepare('INSERT OR IGNORE INTO student_process_plan_mutation_tokens(token,student_id,plan_id,action,result_json,created_at) VALUES(?,?,?,?,?,?)');
    $q->execute([$token,$studentId,$planId,$action,'{}',utc_now()]);
    if($q->rowCount()>0)return null;

    $q=$db->prepare('SELECT result_json FROM student_process_plan_mutation_tokens WHERE token=? AND student_id=? AND plan_id=? AND action=? LIMIT 1');
    $q->execute([$token,$studentId,$planId,$action]);$json=$q->fetchColumn();
    if($json===false)throw new RuntimeException('Este identificador de operação já foi usado em outra ação.');
    $result=student_process_json_array((string)$json);
    if(!$result)throw new RuntimeException('A operação já está em processamento. Atualize a página antes de tentar novamente.');
    return $result;
}

function student_process_plan_mutation_store_result(PDO $db,string $token,int $studentId,int $planId,string $action,array $result): void {
    $token=student_process_plan_mutation_token($token);if($token==='')return;
    $q=$db->prepare('UPDATE student_process_plan_mutation_tokens SET result_json=? WHERE token=? AND student_id=? AND plan_id=? AND action=?');
    $q->execute([json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$token,$studentId,$planId,$action]);
}

function student_process_plan_mutation_context(PDO $db,int $planId,int $studentId): array {
    $plan=student_process_execution_plan($db,$planId,$studentId);
    $test=student_test_for_student($db,(int)$plan['test_id'],$studentId)??throw new RuntimeException('Registro não encontrado.');
    if((string)$test['status']==='reviewed')throw new RuntimeException('Este registro já foi revisado.');
    if((string)$plan['status']==='completed')throw new RuntimeException('Este processamento já foi concluído. Corrija fatos no Caderno se necessário.');
    return [$plan,$test];
}

function student_process_plan_mutation_step(PDO $db,int $planId,int $planStepId): array {
    return student_process_lab_plan_step($db,$planId,$planStepId)??throw new RuntimeException('Etapa não encontrada.');
}

function student_process_plan_mutation_resequence(PDO $db,int $planId,string $now): void {
    $rows=student_process_plan_steps($db,$planId);
    $q=$db->prepare('UPDATE student_process_plan_steps SET position=?,updated_at=? WHERE id=? AND plan_id=?');
    foreach($rows as $i=>$row)$q->execute([$i+1,$now,(int)$row['id'],$planId]);
}

function student_process_plan_mutation_reset_session_if_current_changed(PDO $db,int $planId,int $studentId,?int $beforeCurrentId): void {
    if($beforeCurrentId===null)return;
    $plan=student_process_execution_plan($db,$planId,$studentId);
    $after=student_process_execution_current_step($db,$plan);
    if((int)($after['id']??0)!==$beforeCurrentId){
        $db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$planId,$studentId]);
    }
}

function student_process_plan_insert_step(PDO $db,int $planId,int $referenceStepId,int $studentId,array $input,string $operationToken=''): array {
    [$plan]=student_process_plan_mutation_context($db,$planId,$studentId);
    $db->beginTransaction();
    try{
        $duplicate=student_process_plan_mutation_claim($db,$operationToken,$studentId,$planId,'insert_plan_step');
        if($duplicate){
            $existing=student_process_lab_plan_step($db,$planId,(int)($duplicate['plan_step_id']??0));
            if(!$existing)throw new RuntimeException('A etapa adicionada anteriormente não foi encontrada.');
            $db->commit();return $existing;
        }

        $reference=student_process_plan_mutation_step($db,$planId,$referenceStepId);
        if((string)$reference['status']==='completed')throw new RuntimeException('Para inserir uma etapa no trecho já realizado, faça uma correção factual no Caderno.');
        $where=(string)($input['insert_where']??'after');if(!in_array($where,['before','after'],true))$where='after';
        $resolved=student_process_template_resolve_input($db,$studentId,$input);
        $stageKey=(string)($resolved['stage_key']??'');$data=student_process_stage_data($stageKey,$resolved);
        $payload=student_process_template_payload_from_stage($data,$stageKey,$resolved);
        $position=(int)$reference['position']+($where==='after'?1:0);
        $beforeCurrent=student_process_execution_current_step($db,$plan);$beforeCurrentId=$beforeCurrent?(int)$beforeCurrent['id']:null;
        $now=utc_now();

        $db->prepare('UPDATE student_process_plan_steps SET position=position+1,updated_at=? WHERE plan_id=? AND position>=?')->execute([$now,$planId,$position]);
        $q=$db->prepare("INSERT INTO student_process_plan_steps(plan_id,position,stage_key,label,duration,agitation_interval,payload_json,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,'planned',?,?)");
        $q->execute([$planId,$position,$stageKey,(string)$data['label'],(string)$data['duration'],(string)($payload['agitation_interval']??''),json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$now]);
        $insertedId=(int)$db->lastInsertId();
        $db->prepare('UPDATE student_process_plans SET completed_at=NULL,updated_at=? WHERE id=? AND student_id=?')->execute([$now,$planId,$studentId]);
        student_process_plan_mutation_resequence($db,$planId,$now);
        student_process_plan_mutation_reset_session_if_current_changed($db,$planId,$studentId,$beforeCurrentId);
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_process_plan',$planId,'insert_plan_step',[],[
            'plan_step_id'=>$insertedId,'reference_step_id'=>$referenceStepId,'insert_where'=>$where,'stage_key'=>$stageKey,'position'=>$position,
        ]);
        student_process_plan_mutation_store_result($db,$operationToken,$studentId,$planId,'insert_plan_step',['plan_step_id'=>$insertedId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}

    return student_process_lab_plan_step($db,$planId,$insertedId)??throw new RuntimeException('Não foi possível inserir a etapa.');
}

function student_process_plan_repeat_step(PDO $db,int $planId,int $sourceStepId,int $studentId,string $operationToken=''): array {
    [$plan]=student_process_plan_mutation_context($db,$planId,$studentId);
    $db->beginTransaction();
    try{
        $duplicate=student_process_plan_mutation_claim($db,$operationToken,$studentId,$planId,'repeat_plan_step');
        if($duplicate){
            $existing=student_process_lab_plan_step($db,$planId,(int)($duplicate['plan_step_id']??0));
            if(!$existing)throw new RuntimeException('A repetição adicionada anteriormente não foi encontrada.');
            $db->commit();return $existing;
        }

        $source=student_process_plan_mutation_step($db,$planId,$sourceStepId);
        if((string)$source['status']==='completed')throw new RuntimeException('Esta etapa já faz parte do histórico realizado. Repita uma etapa a partir do trecho ainda aberto do roteiro.');
        $position=(int)$source['position']+1;
        $beforeCurrent=student_process_execution_current_step($db,$plan);$beforeCurrentId=$beforeCurrent?(int)$beforeCurrent['id']:null;
        $payload=student_process_json_array((string)$source['payload_json']);$payload['instance_repeat_of_plan_step_id']=$sourceStepId;
        $now=utc_now();

        $db->prepare('UPDATE student_process_plan_steps SET position=position+1,updated_at=? WHERE plan_id=? AND position>=?')->execute([$now,$planId,$position]);
        $q=$db->prepare("INSERT INTO student_process_plan_steps(plan_id,position,stage_key,label,duration,agitation_interval,payload_json,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,'planned',?,?)");
        $q->execute([$planId,$position,(string)$source['stage_key'],(string)$source['label'],(string)$source['duration'],(string)$source['agitation_interval'],json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$now]);
        $insertedId=(int)$db->lastInsertId();
        $db->prepare('UPDATE student_process_plans SET completed_at=NULL,updated_at=? WHERE id=? AND student_id=?')->execute([$now,$planId,$studentId]);
        student_process_plan_mutation_resequence($db,$planId,$now);
        student_process_plan_mutation_reset_session_if_current_changed($db,$planId,$studentId,$beforeCurrentId);
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_process_plan',$planId,'repeat_plan_step',[],[
            'plan_step_id'=>$insertedId,'source_step_id'=>$sourceStepId,'stage_key'=>(string)$source['stage_key'],'position'=>$position,
        ]);
        student_process_plan_mutation_store_result($db,$operationToken,$studentId,$planId,'repeat_plan_step',['plan_step_id'=>$insertedId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}

    return student_process_lab_plan_step($db,$planId,$insertedId)??throw new RuntimeException('Não foi possível repetir a etapa.');
}

function student_process_plan_remove_step(PDO $db,int $planId,int $planStepId,int $studentId,string $operationToken=''): array {
    [$plan]=student_process_plan_mutation_context($db,$planId,$studentId);
    $db->beginTransaction();
    try{
        $duplicate=student_process_plan_mutation_claim($db,$operationToken,$studentId,$planId,'remove_plan_step');
        if($duplicate){
            $freshPlan=student_process_execution_plan($db,$planId,$studentId);
            $currentId=(int)($duplicate['current_step_id']??0);
            $current=$currentId>0?student_process_lab_plan_step($db,$planId,$currentId):student_process_execution_current_step($db,$freshPlan);
            $db->commit();return ['plan'=>$freshPlan,'current_step'=>$current];
        }

        $step=student_process_plan_mutation_step($db,$planId,$planStepId);
        if((string)$step['status']==='completed'||(int)($step['actual_step_id']??0)>0){
            throw new RuntimeException('Esta etapa já é um fato do processamento. Corrija-a no Caderno em vez de removê-la do roteiro.');
        }
        $rows=student_process_plan_steps($db,$planId);
        if(count($rows)<=1)throw new RuntimeException('Não é possível deixar o roteiro desta fotografia sem nenhuma etapa. Troque o roteiro se necessário.');

        $beforeCurrent=student_process_execution_current_step($db,$plan);$beforeCurrentId=$beforeCurrent?(int)$beforeCurrent['id']:null;
        $now=utc_now();
        $db->prepare('DELETE FROM student_process_plan_steps WHERE id=? AND plan_id=?')->execute([$planStepId,$planId]);
        student_process_plan_mutation_resequence($db,$planId,$now);
        $pending=(int)$db->query("SELECT COUNT(*) FROM student_process_plan_steps WHERE plan_id=".(int)$planId." AND status!='completed'")->fetchColumn();
        if($pending===0){
            $db->prepare("UPDATE student_process_plans SET status='completed',completed_at=?,updated_at=? WHERE id=? AND student_id=?")->execute([$now,$now,$planId,$studentId]);
            $db->prepare('DELETE FROM student_process_execution_sessions WHERE plan_id=? AND student_id=?')->execute([$planId,$studentId]);
        }else{
            $db->prepare('UPDATE student_process_plans SET completed_at=NULL,updated_at=? WHERE id=? AND student_id=?')->execute([$now,$planId,$studentId]);
            student_process_plan_mutation_reset_session_if_current_changed($db,$planId,$studentId,$beforeCurrentId);
        }
        if(function_exists('student_process_change_log'))student_process_change_log($db,'student',null,$studentId,'student_process_plan',$planId,'remove_plan_step',[
            'plan_step_id'=>$planStepId,'position'=>(int)$step['position'],'stage_key'=>(string)$step['stage_key'],'label'=>(string)$step['label'],
        ],['pending_steps'=>$pending]);
        $freshPlanForResult=student_process_execution_plan($db,$planId,$studentId);$currentForResult=student_process_execution_current_step($db,$freshPlanForResult);
        student_process_plan_mutation_store_result($db,$operationToken,$studentId,$planId,'remove_plan_step',[
            'removed_step_id'=>$planStepId,'plan_status'=>(string)$freshPlanForResult['status'],'current_step_id'=>(int)($currentForResult['id']??0),
        ]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}

    $freshPlan=student_process_execution_plan($db,$planId,$studentId);$current=student_process_execution_current_step($db,$freshPlan);
    return ['plan'=>$freshPlan,'current_step'=>$current];
}
