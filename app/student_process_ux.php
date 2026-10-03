<?php
declare(strict_types=1);

/**
 * Operational process editing for the student notebook.
 *
 * A process is a laboratory record, not a one-way wizard. Existing stages can
 * be edited in place without destroying the stages that follow. Changing the
 * sequence remains an explicit destructive correction handled separately.
 */
function student_process_step_for_student(PDO $db,int $testId,int $position,int $studentId): ?array {
    if($testId<1||$position<1)return null;
    student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE test_id=? AND position=? ORDER BY id LIMIT 1');
    $q->execute([$testId,$position]);
    return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_process_step_inventory_amount(PDO $db,int $stepId,int $itemId): ?float {
    if($stepId<1||$itemId<1)return null;
    $q=$db->prepare('SELECT COALESCE(SUM(quantity_delta),0) FROM student_inventory_movements WHERE step_id=? AND item_id=?');
    $q->execute([$stepId,$itemId]);
    $delta=(float)$q->fetchColumn();
    return $delta<0?-$delta:null;
}

function student_process_stage_data(string $stageKey,array $input): array {
    $db=database();$catalog=student_process_managed_stage_catalog($db,false);
    if(!isset($catalog[$stageKey]))throw new RuntimeException('Etapa inválida.');
    $stage=$catalog[$stageKey];$stageType=(string)$stage['type'];
    $label=$stageKey==='custom'?student_workspace_text($input['custom_label']??'',120):(string)$stage['label'];
    if($label==='')throw new RuntimeException('Informe o nome da etapa.');

    $chemicalKey='';$chemicalName='';$developerAmount=null;$waterAmount=null;$unit='ml';$dilution='';$total=null;
    $savedPrepId=(int)($input['saved_preparation_id']??0)?:null;
    $reuseSource=$stageKey==='second_development'?student_workspace_text($input['reuse_source_stage_key']??'',80):'';
    if($reuseSource!==''&&$reuseSource!=='first_development')throw new RuntimeException('Origem de reutilização inválida.');

    if($stageType==='development'){
        $developer=student_process_managed_developer((string)($input['developer_key']??''),(string)($input['developer_name']??''),$db,true);
        $chemicalKey=(string)$developer['key'];
        $chemicalName=(string)$developer['label'];
        if((string)$developer['mode']==='fresh'){
            $developerAmount=student_workbench_float($input['fresh_volume']??$input['developer_amount']??null);
            $total=$developerAmount;
            $waterAmount=null;
        }else{
            $developerAmount=student_workbench_float($input['developer_amount']??null);
            $waterAmount=student_workbench_float($input['water_amount']??null);
            $calc=student_process_dilution($developerAmount,$waterAmount,(string)$developer['mode']);
            $dilution=$calc['label'];$total=$calc['total'];
        }
    }else{
        $catalogChemical=student_workspace_text($stage['chemical_name']??'',180);
        $freeChemical=student_workspace_text($input['chemical_name']??'',180);
        $chemicalName=$catalogChemical!==''?$catalogChemical:$freeChemical;
        $chemicalKey=$stageKey;
        $savedPrepId=null;
    }

    $inventoryAllowed=in_array($stageType,['development','chemical','custom'],true)&&$reuseSource==='';
    $inventoryItemId=$inventoryAllowed?((int)($input['inventory_item_id']??0)?:null):null;
    $usedAmount=$inventoryAllowed?student_workbench_float($input['inventory_amount']??null):null;
    if($inventoryItemId&&$usedAmount===null&&$developerAmount!==null&&$stageType==='development')$usedAmount=$developerAmount;
    if($usedAmount!==null&&$usedAmount<0)throw new RuntimeException('A quantidade utilizada não pode ser negativa.');

    $temperature=$stageType==='dry'?'':student_workspace_text($input['temperature']??'',80);
    $agitation=$stageType==='dry'?'':student_workspace_text($input['agitation']??'',600);
    return [
        'stage_type'=>$stageType,'stage_key'=>$stageKey,'label'=>$label,
        'chemical_key'=>$chemicalKey,'chemical_name'=>$chemicalName,'inventory_item_id'=>$inventoryItemId,
        'saved_preparation_id'=>$savedPrepId,'developer_amount'=>$developerAmount,'water_amount'=>$waterAmount,
        'amount_unit'=>$unit,'calculated_dilution'=>$dilution,'total_volume'=>$total,
        'temperature'=>$temperature,
        'duration'=>student_workspace_text($input['duration']??'',120),
        'agitation'=>$agitation,
        'notes'=>student_workspace_text($input['notes']??'',3000),
        'used_amount'=>$usedAmount,
        'reuse_source_stage_key'=>$reuseSource,
    ];
}

function student_process_sync_legacy_summary(PDO $db,int $testId,int $studentId,string $stageKey,array $data,string $now): void {
    if($stageKey==='first_development'){
        $db->prepare('UPDATE student_tests SET developer=?,dilution=?,temperature=?,development_time=?,agitation=?,updated_at=? WHERE id=? AND student_id=?')
            ->execute([$data['chemical_name'],$data['calculated_dilution'],$data['temperature'],$data['duration'],$data['agitation'],$now,$testId,$studentId]);
    }elseif(in_array($stageKey,['fixer','peracetic','ferric','dichromate','permanganate'],true)){
        $db->prepare('UPDATE student_tests SET bleach=?,updated_at=? WHERE id=? AND student_id=?')
            ->execute([$data['chemical_name'],$now,$testId,$studentId]);
    }else{
        $db->prepare('UPDATE student_tests SET updated_at=? WHERE id=? AND student_id=?')->execute([$now,$testId,$studentId]);
    }
}

function student_process_add_flexible_step(PDO $db,int $testId,int $studentId,array $input): array {
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    if((string)$test['status']==='reviewed')throw new RuntimeException('Este registro já foi revisado.');
    $steps=student_process_steps($db,$testId);
    if(student_experience_process_complete($steps))throw new RuntimeException('O processamento terminou na secagem. Registre o resultado.');
    $stageKey=(string)($input['stage_key']??'');
    $data=student_process_stage_data($stageKey,$input);
    $position=(int)$db->query('SELECT COALESCE(MAX(position),0)+1 FROM student_process_steps WHERE test_id='.(int)$testId)->fetchColumn();
    $now=utc_now();
    $metadata=['reuse_source_stage_key'=>$data['reuse_source_stage_key']];
    $db->beginTransaction();
    try{
        $q=$db->prepare('INSERT INTO student_process_steps(step_uuid,test_id,position,stage_type,stage_key,label,chemical_key,chemical_name,inventory_item_id,saved_preparation_id,developer_amount,water_amount,amount_unit,calculated_dilution,total_volume,temperature,duration,agitation,notes,metadata_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([student_uuid(),$testId,$position,$data['stage_type'],$stageKey,$data['label'],$data['chemical_key'],$data['chemical_name'],$data['inventory_item_id'],$data['saved_preparation_id'],$data['developer_amount'],$data['water_amount'],$data['amount_unit'],$data['calculated_dilution'],$data['total_volume'],$data['temperature'],$data['duration'],$data['agitation'],$data['notes'],json_encode($metadata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$now]);
        $stepId=(int)$db->lastInsertId();
        if($data['inventory_item_id']&&$data['used_amount']!==null&&$data['used_amount']>0){
            student_inventory_move($db,$studentId,(int)$data['inventory_item_id'],-(float)$data['used_amount'],'consume','Uso no Caderno',$testId,$stepId,false);
        }
        student_process_sync_legacy_summary($db,$testId,$studentId,$stageKey,$data,$now);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=?');$q->execute([$stepId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:[];
}

function student_process_update_step(PDO $db,int $testId,int $stepId,int $studentId,array $input): array {
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    if((string)$test['status']==='reviewed')throw new RuntimeException('Este registro já foi revisado.');
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=? AND test_id=? LIMIT 1');$q->execute([$stepId,$testId]);
    $step=$q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('Etapa não encontrada.');
    $stageKey=(string)$step['stage_key'];
    $data=student_process_stage_data($stageKey,$input);
    $now=utc_now();$metadata=['reuse_source_stage_key'=>$data['reuse_source_stage_key']];

    $db->beginTransaction();
    try{
        $movements=$db->prepare('SELECT item_id,COALESCE(SUM(quantity_delta),0) delta FROM student_inventory_movements WHERE step_id=? GROUP BY item_id');
        $movements->execute([$stepId]);
        foreach($movements->fetchAll(PDO::FETCH_ASSOC) as $movement){
            $delta=(float)$movement['delta'];
            if(abs($delta)<0.0000001)continue;
            student_inventory_move($db,$studentId,(int)$movement['item_id'],-$delta,'adjust','Ajuste por edição da etapa',$testId,$stepId,false);
        }

        $db->prepare('UPDATE student_process_steps SET stage_type=?,label=?,chemical_key=?,chemical_name=?,inventory_item_id=?,saved_preparation_id=?,developer_amount=?,water_amount=?,amount_unit=?,calculated_dilution=?,total_volume=?,temperature=?,duration=?,agitation=?,notes=?,metadata_json=?,updated_at=? WHERE id=? AND test_id=?')
            ->execute([$data['stage_type'],$data['label'],$data['chemical_key'],$data['chemical_name'],$data['inventory_item_id'],$data['saved_preparation_id'],$data['developer_amount'],$data['water_amount'],$data['amount_unit'],$data['calculated_dilution'],$data['total_volume'],$data['temperature'],$data['duration'],$data['agitation'],$data['notes'],json_encode($metadata,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$stepId,$testId]);

        if($data['inventory_item_id']&&$data['used_amount']!==null&&$data['used_amount']>0){
            student_inventory_move($db,$studentId,(int)$data['inventory_item_id'],-(float)$data['used_amount'],'consume','Uso no Caderno',$testId,$stepId,false);
        }
        student_process_sync_legacy_summary($db,$testId,$studentId,$stageKey,$data,$now);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}

    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=?');$q->execute([$stepId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:[];
}