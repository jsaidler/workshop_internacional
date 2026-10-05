<?php
declare(strict_types=1);

/** Etapas livres pertencem ao registro, sem validação de sequência e sem estoque implícito. */
function student_process_notebook_add_free_step(PDO $db,int $testId,int $studentId,array $input): array {
    student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    $catalog=student_process_stage_catalog();$stageKey=trim((string)($input['stage_key']??'custom'));
    if(!isset($catalog[$stageKey]))$stageKey='custom';$stage=$catalog[$stageKey];
    $label=$stageKey==='custom'?student_workspace_text($input['label']??$input['custom_label']??'',120):(string)$stage['label'];
    if($label==='')throw new RuntimeException('Informe o nome da etapa.');
    $duration=student_workspace_text($input['duration']??'',40);if($duration!==''&&student_process_time_seconds($duration)===null)throw new RuntimeException('Informe um tempo válido ou deixe o campo vazio.');
    if($duration!=='')$duration=student_process_seconds_label(student_process_time_seconds($duration));
    $chemicalName=student_workspace_text($input['chemical_name']??'',180);$chemicalKey=$stageKey==='custom'?'':$stageKey;
    $temperature=student_workspace_text($input['temperature']??'',80);$agitation=student_workspace_text($input['agitation']??'',600);$notes=student_workspace_text($input['notes']??'',3000);
    $position=(int)$db->query('SELECT COALESCE(MAX(position),0)+1 FROM student_process_steps WHERE test_id='.(int)$testId)->fetchColumn();$now=utc_now();
    $q=$db->prepare('INSERT INTO student_process_steps(step_uuid,test_id,position,stage_type,stage_key,label,chemical_key,chemical_name,inventory_item_id,saved_preparation_id,developer_amount,water_amount,amount_unit,calculated_dilution,total_volume,temperature,duration,agitation,notes,metadata_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,NULL,NULL,NULL,NULL,?,\'\',NULL,?,?,?,?,\'{}\',?,?)');
    $q->execute([student_uuid(),$testId,$position,(string)$stage['type'],$stageKey,$label,$chemicalKey,$chemicalName,'ml',$temperature,$duration,$agitation,$notes,$now,$now]);
    $id=(int)$db->lastInsertId();$db->prepare('UPDATE student_tests SET updated_at=? WHERE id=? AND student_id=?')->execute([$now,$testId,$studentId]);
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:[];
}

function student_process_notebook_update_free_step(PDO $db,int $testId,int $stepId,int $studentId,array $input): array {
    student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=? AND test_id=?');$q->execute([$stepId,$testId]);$step=$q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Etapa não encontrada.');
    $label=student_workspace_text($input['label']??$step['label'],120);if($label==='')throw new RuntimeException('Informe o nome da etapa.');
    $chemicalName=student_workspace_text($input['chemical_name']??$step['chemical_name'],180);$temperature=student_workspace_text($input['temperature']??$step['temperature'],80);$agitation=student_workspace_text($input['agitation']??$step['agitation'],600);$notes=student_workspace_text($input['notes']??$step['notes'],3000);
    $duration=student_workspace_text($input['duration']??$step['duration'],40);if($duration!==''&&student_process_time_seconds($duration)===null)throw new RuntimeException('Informe um tempo válido ou deixe o campo vazio.');if($duration!=='')$duration=student_process_seconds_label(student_process_time_seconds($duration));
    $now=utc_now();$db->prepare('UPDATE student_process_steps SET label=?,chemical_name=?,temperature=?,duration=?,agitation=?,notes=?,updated_at=? WHERE id=? AND test_id=?')->execute([$label,$chemicalName,$temperature,$duration,$agitation,$notes,$now,$stepId,$testId]);$db->prepare('UPDATE student_tests SET updated_at=? WHERE id=? AND student_id=?')->execute([$now,$testId,$studentId]);
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=?');$q->execute([$stepId]);return $q->fetch(PDO::FETCH_ASSOC)?:[];
}

function student_process_notebook_delete_free_step(PDO $db,int $testId,int $stepId,int $studentId): void {
    student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    $q=$db->prepare('SELECT id FROM student_process_steps WHERE id=? AND test_id=?');$q->execute([$stepId,$testId]);if(!$q->fetchColumn())throw new RuntimeException('Etapa não encontrada.');
    $db->beginTransaction();try{
        $db->prepare('DELETE FROM student_process_steps WHERE id=? AND test_id=?')->execute([$stepId,$testId]);
        $rows=student_process_steps($db,$testId);$u=$db->prepare('UPDATE student_process_steps SET position=?,updated_at=? WHERE id=?');$now=utc_now();foreach($rows as $i=>$row)$u->execute([$i+1,$now,(int)$row['id']);
        $db->prepare('UPDATE student_tests SET updated_at=? WHERE id=? AND student_id=?')->execute([$now,$testId,$studentId]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}