<?php
declare(strict_types=1);

/** Persisted laboratory helpers must be editable; otherwise they behave like disposable form submissions. */
function student_saved_preparation_for_student(PDO $db,int $studentId,int $id): ?array {
    if($id<1)return null;
    $q=$db->prepare('SELECT * FROM student_saved_preparations WHERE id=? AND student_id=? LIMIT 1');$q->execute([$id,$studentId]);
    return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_saved_preparation_input(array $input): array {
    $developer=student_process_developer((string)($input['developer_key']??''),(string)($input['developer_name']??''));
    $label=student_workspace_text($input['label']??'',120);if($label==='')throw new RuntimeException('Dê um nome à predefinição.');
    $mode=(string)$developer['mode'];
    $dev=$mode==='fresh'?student_workbench_float($input['fresh_volume']??null):student_workbench_float($input['developer_amount']??null);
    $water=$mode==='fresh'?null:student_workbench_float($input['water_amount']??null);
    $ingredients=is_array($input['ingredients']??null)?$input['ingredients']:[];$clean=[];
    foreach($ingredients as $row)if(is_array($row)&&trim((string)($row['name']??''))!=='')$clean[]=['name'=>student_workspace_text($row['name'],120),'amount'=>student_workbench_float($row['amount']??null),'unit'=>student_workspace_text($row['unit']??'',20)];
    return ['developer_key'=>$developer['key'],'developer_name'=>$developer['label'],'label'=>$label,'preparation_mode'=>$mode,'developer_amount'=>$dev,'water_amount'=>$water,'temperature'=>student_workspace_text($input['temperature']??'',80),'duration'=>student_workspace_text($input['duration']??'',120),'agitation'=>student_workspace_text($input['agitation']??'',600),'ingredients_json'=>json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)];
}

function student_saved_preparation_update_guided(PDO $db,int $studentId,int $id,array $input): array {
    student_saved_preparation_for_student($db,$studentId,$id)??throw new RuntimeException('Predefinição não encontrada.');
    if((string)($input['developer_key']??'')==='brewed-caffenol')$input['ingredients']=[['name'=>'Café torrado e moído extra-forte','amount'=>$input['coffee_amount']??'','unit'=>'g'],['name'=>'Carbonato de sódio','amount'=>$input['carbonate_amount']??'','unit'=>'g'],['name'=>'Ácido ascórbico','amount'=>$input['ascorbic_amount']??'','unit'=>'g'],['name'=>'Água','amount'=>$input['fresh_volume']??'','unit'=>'ml']];
    $v=student_saved_preparation_input($input);$now=utc_now();
    $db->prepare('UPDATE student_saved_preparations SET developer_key=?,developer_name=?,label=?,preparation_mode=?,developer_amount=?,water_amount=?,amount_unit=?,temperature=?,duration=?,agitation=?,ingredients_json=?,updated_at=? WHERE id=? AND student_id=?')
        ->execute([$v['developer_key'],$v['developer_name'],$v['label'],$v['preparation_mode'],$v['developer_amount'],$v['water_amount'],'ml',$v['temperature'],$v['duration'],$v['agitation'],$v['ingredients_json'],$now,$id,$studentId]);
    return student_saved_preparation_for_student($db,$studentId,$id)??throw new RuntimeException('Predefinição não encontrada.');
}

function student_calibration_for_student(PDO $db,int $studentId,int $id): ?array {
    if($id<1)return null;$q=$db->prepare('SELECT * FROM student_process_calibrations WHERE id=? AND student_id=? LIMIT 1');$q->execute([$id,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_inventory_update_metadata(PDO $db,int $studentId,int $itemId,array $input): array {
    $item=student_inventory_item($db,$studentId,$itemId)??throw new RuntimeException('Item de inventário não encontrado.');
    $name=student_workspace_text($input['name']??'',180);if($name==='')throw new RuntimeException('Informe o nome do item.');
    $minimum=student_workbench_float($input['minimum_quantity']??null);if($minimum!==null&&$minimum<0)throw new RuntimeException('A quantidade mínima não pode ser negativa.');
    $db->prepare('UPDATE student_inventory_items SET name=?,category=?,minimum_quantity=?,lot_code=?,acquired_or_prepared_at=?,expires_at=?,location=?,notes=?,updated_at=? WHERE id=? AND student_id=?')
        ->execute([$name,student_workspace_text($input['category']??'',100),$minimum,student_workspace_text($input['lot_code']??'',100),student_workspace_date($input['acquired_or_prepared_at']??null),student_workspace_date($input['expires_at']??null),student_workspace_text($input['location']??'',160),student_workspace_text($input['notes']??'',2000),utc_now(),$itemId,$studentId]);
    return student_inventory_item($db,$studentId,$itemId)??$item;
}
