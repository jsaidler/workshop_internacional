<?php
declare(strict_types=1);

function student_process_time_seconds(string $value): ?int {
    $raw=trim(mb_strtolower($value));
    if($raw==='')return null;
    $normalized=str_replace(',','.',$raw);
    if(preg_match('/^\d+(?:\.\d+)?$/',$normalized))return max(0,(int)round((float)$normalized));
    if(preg_match('/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/',$raw,$m)){
        if(isset($m[3])&&$m[3]!=='')return ((int)$m[1]*3600)+((int)$m[2]*60)+(int)$m[3];
        return ((int)$m[1]*60)+(int)$m[2];
    }
    if(preg_match('/^(\d+(?:[.,]\d+)?)\s*(?:h|hora|horas)$/u',$raw,$m))return max(0,(int)round((float)str_replace(',','.',$m[1])*3600));
    if(preg_match('/^(\d+(?:[.,]\d+)?)\s*(?:m|min|mins|minuto|minutos)$/u',$raw,$m))return max(0,(int)round((float)str_replace(',','.',$m[1])*60));
    if(preg_match('/^(\d+(?:[.,]\d+)?)\s*(?:s|seg|segs|segundo|segundos)$/u',$raw,$m))return max(0,(int)round((float)str_replace(',','.',$m[1])));
    return null;
}

function student_process_seconds_label(?int $seconds): string {
    if($seconds===null)return 'Sem tempo definido';
    $seconds=max(0,$seconds);$h=intdiv($seconds,3600);$m=intdiv($seconds%3600,60);$s=$seconds%60;
    if($h>0)return sprintf('%02d:%02d:%02d',$h,$m,$s);
    return sprintf('%02d:%02d',$m,$s);
}

function student_process_json_array(string $json): array {
    $decoded=json_decode($json,true);
    return is_array($decoded)?$decoded:[];
}

function student_process_templates(PDO $db,int $studentId): array {
    $q=$db->prepare("SELECT t.*,COUNT(s.id) step_count
        FROM student_process_templates t
        LEFT JOIN student_process_template_steps s ON s.template_id=t.id
        WHERE t.student_id=?
        GROUP BY t.id
        ORDER BY t.updated_at DESC,t.id DESC");
    $q->execute([$studentId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

function student_process_template_for_student(PDO $db,int $templateId,int $studentId): ?array {
    if($templateId<1)return null;
    $q=$db->prepare('SELECT * FROM student_process_templates WHERE id=? AND student_id=? LIMIT 1');
    $q->execute([$templateId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_process_template_steps(PDO $db,int $templateId): array {
    $q=$db->prepare('SELECT * FROM student_process_template_steps WHERE template_id=? ORDER BY position,id');
    $q->execute([$templateId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

function student_process_template_step_for_student(PDO $db,int $templateId,int $stepId,int $studentId): ?array {
    if($templateId<1||$stepId<1)return null;
    student_process_template_for_student($db,$templateId,$studentId)??throw new RuntimeException('Processamento não encontrado.');
    $q=$db->prepare('SELECT * FROM student_process_template_steps WHERE id=? AND template_id=? LIMIT 1');
    $q->execute([$stepId,$templateId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_process_template_total_seconds(PDO $db,int $templateId): int {
    $total=0;foreach(student_process_template_steps($db,$templateId) as $step){$seconds=student_process_time_seconds((string)$step['duration']);if($seconds!==null)$total+=$seconds;}return $total;
}

function student_process_template_create(PDO $db,int $studentId,array $input): array {
    $name=student_workspace_text($input['name']??'',160);if($name==='')throw new RuntimeException('Dê um nome ao processamento.');
    $description=student_workspace_text($input['description']??'',1000);$now=utc_now();
    $q=$db->prepare('INSERT INTO student_process_templates(template_uuid,student_id,name,description,created_at,updated_at) VALUES(?,?,?,?,?,?)');
    $q->execute([student_uuid(),$studentId,$name,$description,$now,$now]);
    return student_process_template_for_student($db,(int)$db->lastInsertId(),$studentId)??[];
}

function student_process_template_update(PDO $db,int $templateId,int $studentId,array $input): array {
    $template=student_process_template_for_student($db,$templateId,$studentId)??throw new RuntimeException('Processamento não encontrado.');
    $name=student_workspace_text($input['name']??$template['name'],160);if($name==='')throw new RuntimeException('Dê um nome ao processamento.');
    $description=student_workspace_text($input['description']??$template['description'],1000);
    $db->prepare('UPDATE student_process_templates SET name=?,description=?,updated_at=? WHERE id=? AND student_id=?')->execute([$name,$description,utc_now(),$templateId,$studentId]);
    return student_process_template_for_student($db,$templateId,$studentId)??$template;
}

function student_process_template_duplicate(PDO $db,int $templateId,int $studentId): array {
    $template=student_process_template_for_student($db,$templateId,$studentId)??throw new RuntimeException('Processamento não encontrado.');
    $steps=student_process_template_steps($db,$templateId);$now=utc_now();
    $copyName=student_workspace_text((string)$template['name'].' — cópia',160);
    $db->beginTransaction();
    try{
        $q=$db->prepare('INSERT INTO student_process_templates(template_uuid,student_id,name,description,created_at,updated_at) VALUES(?,?,?,?,?,?)');
        $q->execute([student_uuid(),$studentId,$copyName,(string)$template['description'],$now,$now]);$copyId=(int)$db->lastInsertId();
        $insert=$db->prepare('INSERT INTO student_process_template_steps(template_id,position,stage_key,label,duration,agitation_interval,payload_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');
        foreach($steps as $step)$insert->execute([$copyId,(int)$step['position'],(string)$step['stage_key'],(string)$step['label'],(string)$step['duration'],(string)$step['agitation_interval'],(string)$step['payload_json'],$now,$now]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_process_template_for_student($db,$copyId,$studentId)??[];
}

function student_process_template_delete(PDO $db,int $templateId,int $studentId): void {
    student_process_template_for_student($db,$templateId,$studentId)??throw new RuntimeException('Processamento não encontrado.');
    $db->prepare('DELETE FROM student_process_templates WHERE id=? AND student_id=?')->execute([$templateId,$studentId]);
}

function student_process_template_resolve_input(PDO $db,int $studentId,array $input): array {
    $resolved=$input;$savedId=(int)($input['saved_preparation_id']??0);
    if($savedId>0){
        $q=$db->prepare('SELECT * FROM student_saved_preparations WHERE id=? AND student_id=? LIMIT 1');$q->execute([$savedId,$studentId]);$prep=$q->fetch(PDO::FETCH_ASSOC);
        if(!$prep)throw new RuntimeException('Predefinição de revelação não encontrada.');
        $resolved['developer_key']=(string)$prep['developer_key'];$resolved['developer_name']=(string)$prep['developer_name'];
        $resolved['developer_amount']=$prep['developer_amount'];$resolved['water_amount']=$prep['water_amount'];
        $resolved['fresh_volume']=$prep['developer_amount'];$resolved['temperature']=(string)$prep['temperature'];
        $resolved['duration']=(string)$prep['duration'];$resolved['agitation']=(string)$prep['agitation'];
    }
    return $resolved;
}

function student_process_template_payload_from_stage(array $data,string $stageKey,array $input): array {
    $reuseSource=$stageKey==='second_development'?student_workspace_text($input['reuse_source_stage_key']??'',80):'';
    return [
        'stage_key'=>$stageKey,
        'custom_label'=>$stageKey==='custom'?(string)$data['label']:'',
        'developer_key'=>(string)$data['chemical_key'],
        'developer_name'=>(string)$data['chemical_name'],
        'developer_amount'=>$data['developer_amount'],
        'water_amount'=>$data['water_amount'],
        'fresh_volume'=>$data['developer_amount'],
        'saved_preparation_id'=>(int)($input['saved_preparation_id']??0)?:null,
        'inventory_item_id'=>null,
        'inventory_amount'=>null,
        'temperature'=>(string)$data['temperature'],
        'duration'=>(string)$data['duration'],
        'agitation'=>(string)$data['agitation'],
        'notes'=>(string)$data['notes'],
        'reuse_source_stage_key'=>$reuseSource,
        'agitation_interval'=>student_workspace_text($input['agitation_interval']??'',80),
    ];
}

function student_process_template_add_step(PDO $db,int $templateId,int $studentId,array $input): array {
    student_process_template_for_student($db,$templateId,$studentId)??throw new RuntimeException('Processamento não encontrado.');
    $resolved=student_process_template_resolve_input($db,$studentId,$input);$stageKey=(string)($resolved['stage_key']??'');
    $data=student_process_stage_data($stageKey,$resolved);$payload=student_process_template_payload_from_stage($data,$stageKey,$resolved);
    $position=(int)$db->query('SELECT COALESCE(MAX(position),0)+1 FROM student_process_template_steps WHERE template_id='.(int)$templateId)->fetchColumn();
    $now=utc_now();$q=$db->prepare('INSERT INTO student_process_template_steps(template_id,position,stage_key,label,duration,agitation_interval,payload_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');
    $q->execute([$templateId,$position,$stageKey,$data['label'],$data['duration'],$payload['agitation_interval'],json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$now]);
    $db->prepare('UPDATE student_process_templates SET updated_at=? WHERE id=?')->execute([$now,$templateId]);
    $id=(int)$db->lastInsertId();$q=$db->prepare('SELECT * FROM student_process_template_steps WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:[];
}

function student_process_template_update_step(PDO $db,int $templateId,int $stepId,int $studentId,array $input): array {
    $step=student_process_template_step_for_student($db,$templateId,$stepId,$studentId)??throw new RuntimeException('Etapa não encontrada.');
    $resolved=student_process_template_resolve_input($db,$studentId,$input);$stageKey=(string)($resolved['stage_key']??$step['stage_key']);
    $data=student_process_stage_data($stageKey,$resolved);$payload=student_process_template_payload_from_stage($data,$stageKey,$resolved);$now=utc_now();
    $db->prepare('UPDATE student_process_template_steps SET stage_key=?,label=?,duration=?,agitation_interval=?,payload_json=?,updated_at=? WHERE id=? AND template_id=?')
        ->execute([$stageKey,$data['label'],$data['duration'],$payload['agitation_interval'],json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$stepId,$templateId]);
    $db->prepare('UPDATE student_process_templates SET updated_at=? WHERE id=?')->execute([$now,$templateId]);
    return student_process_template_step_for_student($db,$templateId,$stepId,$studentId)??$step;
}

function student_process_template_delete_step(PDO $db,int $templateId,int $stepId,int $studentId): void {
    student_process_template_for_student($db,$templateId,$studentId)??throw new RuntimeException('Processamento não encontrado.');
    $q=$db->prepare('SELECT id FROM student_process_template_steps WHERE id=? AND template_id=?');$q->execute([$stepId,$templateId]);if(!$q->fetchColumn())throw new RuntimeException('Etapa não encontrada.');
    $db->beginTransaction();try{
        $db->prepare('DELETE FROM student_process_template_steps WHERE id=? AND template_id=?')->execute([$stepId,$templateId]);
        $rows=student_process_template_steps($db,$templateId);$u=$db->prepare('UPDATE student_process_template_steps SET position=?,updated_at=? WHERE id=?');$now=utc_now();foreach($rows as $i=>$row)$u->execute([$i+1,$now,(int)$row['id']]);
        $db->prepare('UPDATE student_process_templates SET updated_at=? WHERE id=?')->execute([$now,$templateId]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}

function student_process_template_move_step(PDO $db,int $templateId,int $stepId,int $studentId,int $direction): void {
    student_process_template_for_student($db,$templateId,$studentId)??throw new RuntimeException('Processamento não encontrado.');
    $rows=student_process_template_steps($db,$templateId);$index=null;foreach($rows as $i=>$row)if((int)$row['id']===$stepId){$index=$i;break;}if($index===null)throw new RuntimeException('Etapa não encontrada.');
    $target=$index+($direction<0?-1:1);if($target<0||$target>=count($rows))return;
    [$rows[$index],$rows[$target]]=[$rows[$target],$rows[$index]];$now=utc_now();$db->beginTransaction();try{$u=$db->prepare('UPDATE student_process_template_steps SET position=?,updated_at=? WHERE id=?');foreach($rows as $i=>$row)$u->execute([$i+1,$now,(int)$row['id']]);$db->prepare('UPDATE student_process_templates SET updated_at=? WHERE id=?')->execute([$now,$templateId]);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}

function student_process_plan_for_test(PDO $db,int $testId,int $studentId): ?array {
    $q=$db->prepare('SELECT * FROM student_process_plans WHERE test_id=? AND student_id=? LIMIT 1');$q->execute([$testId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_process_plan_steps(PDO $db,int $planId): array {
    $q=$db->prepare('SELECT * FROM student_process_plan_steps WHERE plan_id=? ORDER BY position,id');$q->execute([$planId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

function student_process_plan_apply_template(PDO $db,int $templateId,int $testId,int $studentId): array {
    $template=student_process_template_for_student($db,$templateId,$studentId)??throw new RuntimeException('Processamento não encontrado.');
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    if((string)$test['status']==='reviewed')throw new RuntimeException('Este registro já foi revisado.');
    if(student_process_steps($db,$testId))throw new RuntimeException('Este registro já possui etapas executadas. Use o processamento livre para continuar.');
    $templateSteps=student_process_template_steps($db,$templateId);if(!$templateSteps)throw new RuntimeException('Adicione pelo menos uma etapa ao processamento.');
    $existing=student_process_plan_for_test($db,$testId,$studentId);
    if($existing){foreach(student_process_plan_steps($db,(int)$existing['id']) as $step)if((string)$step['status']!=='planned'||(int)($step['actual_step_id']??0)>0)throw new RuntimeException('Este processamento já foi iniciado.');}
    $now=utc_now();$db->beginTransaction();try{
        if($existing)$db->prepare('DELETE FROM student_process_plans WHERE id=?')->execute([(int)$existing['id']]);
        $q=$db->prepare('INSERT INTO student_process_plans(plan_uuid,student_id,test_id,source_template_id,source_name,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)');
        $q->execute([student_uuid(),$studentId,$testId,$templateId,(string)$template['name'],'planned',$now,$now]);$planId=(int)$db->lastInsertId();
        $insert=$db->prepare('INSERT INTO student_process_plan_steps(plan_id,position,stage_key,label,duration,agitation_interval,payload_json,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,\'planned\',?,?)');
        foreach($templateSteps as $step)$insert->execute([$planId,(int)$step['position'],(string)$step['stage_key'],(string)$step['label'],(string)$step['duration'],(string)$step['agitation_interval'],(string)$step['payload_json'],$now,$now]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_process_plan_for_test($db,$testId,$studentId)??[];
}

function student_process_plan_next_step(PDO $db,array $plan): ?array {
    foreach(student_process_plan_steps($db,(int)$plan['id']) as $step)if((string)$step['status']!=='completed')return $step;return null;
}

function student_process_plan_start(PDO $db,int $planId,int $studentId): array {
    $q=$db->prepare('SELECT * FROM student_process_plans WHERE id=? AND student_id=? LIMIT 1');$q->execute([$planId,$studentId]);$plan=$q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Processamento não encontrado.');
    if((string)$plan['status']==='planned'){$now=utc_now();$db->prepare("UPDATE student_process_plans SET status='running',started_at=COALESCE(started_at,?),updated_at=? WHERE id=? AND student_id=?")->execute([$now,$now,$planId,$studentId]);$plan['status']='running';$plan['started_at']=$plan['started_at']?:$now;}
    return $plan;
}

function student_process_plan_complete_step(PDO $db,int $planId,int $planStepId,int $studentId): array {
    $q=$db->prepare('SELECT * FROM student_process_plans WHERE id=? AND student_id=? LIMIT 1');$q->execute([$planId,$studentId]);$plan=$q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Processamento não encontrado.');
    if((string)$plan['status']==='completed')throw new RuntimeException('Este processamento já foi concluído.');
    $next=student_process_plan_next_step($db,$plan);if(!$next)throw new RuntimeException('Não há etapa pendente.');
    if((int)$next['id']!==$planStepId)throw new RuntimeException('Conclua a etapa atual antes de avançar.');
    if((string)$next['status']==='completed'&&(int)($next['actual_step_id']??0)>0)return $next;
    $payload=student_process_json_array((string)$next['payload_json']);$payload['stage_key']=(string)$next['stage_key'];$payload['duration']=(string)$next['duration'];
    $actual=student_process_add_flexible_step($db,(int)$plan['test_id'],$studentId,$payload);$actualId=(int)($actual['id']??0);if($actualId<1)throw new RuntimeException('Não foi possível registrar a etapa executada.');
    $now=utc_now();$db->prepare("UPDATE student_process_plan_steps SET status='completed',actual_step_id=?,completed_at=?,updated_at=? WHERE id=? AND plan_id=?")->execute([$actualId,$now,$now,$planStepId,$planId]);
    $pending=(int)$db->query("SELECT COUNT(*) FROM student_process_plan_steps WHERE plan_id=".(int)$planId." AND status!='completed'")->fetchColumn();
    if($pending===0)$db->prepare("UPDATE student_process_plans SET status='completed',completed_at=?,updated_at=? WHERE id=? AND student_id=?")->execute([$now,$now,$planId,$studentId]);
    else $db->prepare("UPDATE student_process_plans SET status='running',started_at=COALESCE(started_at,?),updated_at=? WHERE id=? AND student_id=?")->execute([$now,$now,$planId,$studentId]);
    $q=$db->prepare('SELECT * FROM student_process_plan_steps WHERE id=?');$q->execute([$planStepId]);return $q->fetch(PDO::FETCH_ASSOC)?:[];
}
