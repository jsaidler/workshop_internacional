<?php
declare(strict_types=1);

function student_process_recording_meta(PDO $db,int $testId,int $studentId): ?array {
    $q=$db->prepare('SELECT * FROM student_process_recording_meta WHERE test_id=? AND student_id=? LIMIT 1');
    $q->execute([$testId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_process_recording_save_meta(PDO $db,int $testId,int $studentId,string $mode,?string $performedOn,string $notes): array {
    student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    if(!in_array($mode,['live','retroactive','mixed'],true))throw new RuntimeException('Modo de registro inválido.');
    $performedOn=trim((string)$performedOn);$performedOn=$performedOn!==''?$performedOn:null;
    $notes=student_workspace_text($notes,3000);$now=utc_now();
    $existing=student_process_recording_meta($db,$testId,$studentId);
    if($existing)$db->prepare('UPDATE student_process_recording_meta SET entry_mode=?,performed_on=?,notes=?,updated_at=? WHERE test_id=? AND student_id=?')->execute([$mode,$performedOn,$notes,$now,$testId,$studentId]);
    else $db->prepare('INSERT INTO student_process_recording_meta(test_id,student_id,entry_mode,performed_on,notes,created_at,updated_at) VALUES(?,?,?,?,?,?,?)')->execute([$testId,$studentId,$mode,$performedOn,$notes,$now,$now]);
    return student_process_recording_meta($db,$testId,$studentId)??[];
}

function student_process_recording_add_step(PDO $db,int $testId,int $studentId,array $input,string $mode='retroactive'): array {
    unset($input['inventory_item_id'],$input['inventory_amount']);
    $step=student_process_add_flexible_step($db,$testId,$studentId,$input);
    $id=(int)($step['id']??0);if($id<1)return $step;
    $meta=student_process_json_array((string)($step['metadata_json']??'{}'));
    $meta['recording_mode']=$mode;
    $db->prepare('UPDATE student_process_steps SET inventory_item_id=NULL,metadata_json=?,updated_at=? WHERE id=? AND test_id=?')->execute([json_encode($meta,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),utc_now(),$id,$testId]);
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:$step;
}

function student_process_plan_apply_standard(PDO $db,string $standardKey,int $testId,int $studentId): array {
    $standard=student_global_process_for_key($db,$standardKey)??throw new RuntimeException('Processamento padrão não encontrado.');
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    if((string)$test['status']==='reviewed')throw new RuntimeException('Este registro já foi revisado.');
    if(student_process_steps($db,$testId))throw new RuntimeException('Este registro já possui etapas. Use a continuação do registro para documentar o restante.');
    $existing=student_process_plan_for_test($db,$testId,$studentId);
    if($existing){foreach(student_process_plan_steps($db,(int)$existing['id']) as $row)if((string)$row['status']!=='planned'||(int)($row['actual_step_id']??0)>0)throw new RuntimeException('Este processamento já foi iniciado.');}
    $now=utc_now();$db->beginTransaction();
    try{
        if($existing)$db->prepare('DELETE FROM student_process_plans WHERE id=?')->execute([(int)$existing['id']]);
        $q=$db->prepare('INSERT INTO student_process_plans(plan_uuid,student_id,test_id,source_template_id,source_global_version_id,source_name,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?, ?,?)');
        $q->execute([student_uuid(),$studentId,$testId,null,(int)($standard['version_id']??0)?:null,(string)$standard['name'],'planned',$now,$now]);$planId=(int)$db->lastInsertId();
        $insert=$db->prepare("INSERT INTO student_process_plan_steps(plan_id,position,stage_key,label,duration,agitation_interval,payload_json,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,'planned',?,?)");
        foreach((array)$standard['steps'] as $i=>$raw){
            $stageKey=(string)$raw['stage_key'];$data=student_process_stage_data($stageKey,$raw);$payload=student_process_template_payload_from_stage($data,$stageKey,$raw);
            $insert->execute([$planId,$i+1,$stageKey,(string)$data['label'],(string)$data['duration'],(string)($raw['agitation_interval']??''),json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$now]);
        }
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_process_plan_for_test($db,$testId,$studentId)??[];
}

function student_process_plan_complete_recorded(PDO $db,int $planId,int $studentId,?string $performedOn='',string $notes=''): array {
    $q=$db->prepare('SELECT * FROM student_process_plans WHERE id=? AND student_id=? LIMIT 1');$q->execute([$planId,$studentId]);$plan=$q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Processamento não encontrado.');
    $testId=(int)$plan['test_id'];$hadCompleted=false;
    foreach(student_process_plan_steps($db,$planId) as $planStep){
        if((string)$planStep['status']==='completed'){$hadCompleted=true;continue;}
        $payload=student_process_json_array((string)$planStep['payload_json']);$payload['stage_key']=(string)$planStep['stage_key'];$payload['duration']=(string)$planStep['duration'];
        $actual=student_process_recording_add_step($db,$testId,$studentId,$payload,$hadCompleted?'mixed':'retroactive');$actualId=(int)($actual['id']??0);if($actualId<1)throw new RuntimeException('Não foi possível registrar a etapa realizada.');
        $now=utc_now();$db->prepare("UPDATE student_process_plan_steps SET status='completed',actual_step_id=?,completed_at=?,updated_at=? WHERE id=? AND plan_id=?")->execute([$actualId,$now,$now,(int)$planStep['id'],$planId]);
    }
    $now=utc_now();$db->prepare("UPDATE student_process_plans SET status='completed',started_at=COALESCE(started_at,?),completed_at=?,updated_at=? WHERE id=? AND student_id=?")->execute([$now,$now,$now,$planId,$studentId]);
    $mode=$hadCompleted?'mixed':'retroactive';student_process_recording_save_meta($db,$testId,$studentId,$mode,$performedOn,$notes);
    return student_process_plan_for_test($db,$testId,$studentId)??$plan;
}

function student_process_recording_mode_for_test(PDO $db,int $testId,int $studentId): string {
    $meta=student_process_recording_meta($db,$testId,$studentId);return (string)($meta['entry_mode']??'live');
}
