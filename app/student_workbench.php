<?php
declare(strict_types=1);

function student_workbench_float(mixed $value): ?float {
    if($value===null||$value==='')return null;
    $normalized=str_replace(',','.',trim((string)$value));
    return is_numeric($normalized)?(float)$normalized:null;
}
function student_workbench_number(float $value): string {
    $rounded=round($value,3);
    $text=rtrim(rtrim(number_format($rounded,3,'.',''),'0'),'.');
    return $text===''?'0':$text;
}

function student_process_developer_catalog(): array {
    return [
        'parodinal'=>['label'=>'Parodinal','mode'=>'concentrate','storable'=>true,'priority'=>10],
        'brewed-caffenol'=>['label'=>'Brewed Caffenol','mode'=>'fresh','storable'=>false,'priority'=>20],
        'rodinal'=>['label'=>'Rodinal / Adonal','mode'=>'concentrate','storable'=>true,'priority'=>30],
        'd76'=>['label'=>'Kodak D-76','mode'=>'stock','storable'=>true,'priority'=>40],
        'id11'=>['label'=>'Ilford ID-11','mode'=>'stock','storable'=>true,'priority'=>50],
        'xtol'=>['label'=>'Kodak XTOL','mode'=>'stock','storable'=>true,'priority'=>60],
        'hc110'=>['label'=>'Kodak HC-110','mode'=>'concentrate','storable'=>true,'priority'=>70],
        'tmax'=>['label'=>'Kodak T-MAX Developer','mode'=>'concentrate','storable'=>true,'priority'=>80],
        'ilfosol3'=>['label'=>'Ilford ILFOSOL 3','mode'=>'concentrate','storable'=>true,'priority'=>90],
        'ddx'=>['label'=>'Ilford ILFOTEC DD-X','mode'=>'concentrate','storable'=>true,'priority'=>100],
        'microphen'=>['label'=>'Ilford MICROPHEN','mode'=>'stock','storable'=>true,'priority'=>110],
        'perceptol'=>['label'=>'Ilford PERCEPTOL','mode'=>'stock','storable'=>true,'priority'=>120],
        'other'=>['label'=>'Outro','mode'=>'custom','storable'=>true,'priority'=>999],
    ];
}
function student_process_developer(string $key,string $customName=''): array {
    $catalog=student_process_developer_catalog();$key=trim($key);
    $entry=$catalog[$key]??$catalog['other'];
    if($key===''||!isset($catalog[$key]))$key='other';
    if($key==='other'&&trim($customName)!=='')$entry['label']=student_workspace_text($customName,180);
    return ['key'=>$key]+$entry;
}
function student_process_dilution(?float $developerAmount,?float $waterAmount,string $mode): array {
    if($mode==='fresh')return ['label'=>'','total'=>$developerAmount];
    if($developerAmount===null||$developerAmount<=0)return ['label'=>'','total'=>null];
    $water=max(0.0,(float)($waterAmount??0));$total=$developerAmount+$water;
    if($water<=0)return ['label'=>$mode==='stock'?'Stock':'1+0','total'=>$total];
    return ['label'=>'1+'.student_workbench_number($water/$developerAmount),'total'=>$total];
}

function student_tool_release_column_available(PDO $db): bool {
    try{foreach($db->query('PRAGMA table_info(student_tool_courses)')->fetchAll(PDO::FETCH_ASSOC) as $column)if((string)($column['name']??'')==='release_lesson_id')return true;}catch(Throwable){}
    return false;
}
function student_tool_release_state(?string $releasedAt): string {
    if(function_exists('cms_access_lesson_release_state'))return cms_access_lesson_release_state($releasedAt);
    if(!$releasedAt)return 'blocked';$ts=strtotime($releasedAt);if($ts===false)return 'blocked';return $ts<=time()?'released':'scheduled';
}
function student_tools_for_student(PDO $db,int $studentId): array {
    $hasRelease=student_tool_release_column_available($db);
    $releaseSelect=$hasRelease?',tc.release_lesson_id,r.released_at':',NULL release_lesson_id,NULL released_at';
    $releaseJoin=$hasRelease?' LEFT JOIN cohort_lesson_releases r ON r.cohort_id=c.id AND r.lesson_id=tc.release_lesson_id ':'';
    $q=$db->prepare("SELECT t.*,tc.course_id mapped_course_id,c.id mapped_cohort_id,e.id enrollment_id$releaseSelect
        FROM student_tools t
        LEFT JOIN student_tool_courses tc ON tc.tool_key=t.tool_key
        LEFT JOIN course_cohorts c ON c.course_id=tc.course_id AND c.status!='archived'
        LEFT JOIN course_enrollments e ON e.cohort_id=c.id AND e.student_id=? AND e.status='active'
        $releaseJoin
        WHERE t.enabled=1 AND (t.access_mode='all' OR e.id IS NOT NULL)
        ORDER BY t.sort_order,t.label,tc.course_id,c.id");
    $q->execute([$studentId]);$out=[];
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row){
        $allowed=(string)$row['access_mode']==='all';
        if(!$allowed&&(int)($row['enrollment_id']??0)>0){
            $lessonId=(int)($row['release_lesson_id']??0);
            $state=$lessonId<1?'released':student_tool_release_state(isset($row['released_at'])?(string)$row['released_at']:null);
            $allowed=$state==='released';
        }
        if(!$allowed)continue;
        $key=(string)$row['tool_key'];if(isset($out[$key]))continue;
        unset($row['mapped_course_id'],$row['mapped_cohort_id'],$row['enrollment_id'],$row['release_lesson_id'],$row['released_at']);$out[$key]=$row;
    }
    return array_values($out);
}
function student_tool_course_access_rows(PDO $db,int $courseId): array {
    if(student_tool_release_column_available($db))$q=$db->prepare("SELECT t.*,tc.release_lesson_id,l.title release_lesson_title FROM student_tool_courses tc JOIN student_tools t ON t.tool_key=tc.tool_key LEFT JOIN course_lessons l ON l.id=tc.release_lesson_id AND l.course_id=tc.course_id WHERE tc.course_id=? AND t.enabled=1 ORDER BY t.sort_order,t.label");
    else $q=$db->prepare("SELECT t.*,NULL release_lesson_id,NULL release_lesson_title FROM student_tool_courses tc JOIN student_tools t ON t.tool_key=tc.tool_key WHERE tc.course_id=? AND t.enabled=1 ORDER BY t.sort_order,t.label");
    $q->execute([$courseId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function student_tool_course_set_release_lesson(PDO $db,int $courseId,string $toolKey,?int $lessonId): void {
    if(!student_tool_release_column_available($db))throw new RuntimeException('Atualize a instalação antes de configurar liberações de ferramentas.');
    $q=$db->prepare('SELECT 1 FROM student_tool_courses WHERE course_id=? AND tool_key=?');$q->execute([$courseId,$toolKey]);if(!$q->fetchColumn())throw new RuntimeException('Ferramenta não vinculada a este curso.');
    if($lessonId!==null&&$lessonId>0){$q=$db->prepare('SELECT 1 FROM course_lessons WHERE id=? AND course_id=?');$q->execute([$lessonId,$courseId]);if(!$q->fetchColumn())throw new RuntimeException('A aula não pertence a este curso.');}else $lessonId=null;
    $db->prepare('UPDATE student_tool_courses SET release_lesson_id=? WHERE course_id=? AND tool_key=?')->execute([$lessonId,$courseId,$toolKey]);
}
function student_tools_released_by_lesson(PDO $db,int $courseId,int $lessonId): array {
    if(!student_tool_release_column_available($db))return [];
    $q=$db->prepare('SELECT t.tool_key,t.label FROM student_tool_courses tc JOIN student_tools t ON t.tool_key=tc.tool_key WHERE tc.course_id=? AND tc.release_lesson_id=? AND t.enabled=1 ORDER BY t.sort_order,t.label');$q->execute([$courseId,$lessonId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function student_tool_allowed(PDO $db,int $studentId,string $toolKey): bool {
    foreach(student_tools_for_student($db,$studentId) as $tool)if((string)$tool['tool_key']===$toolKey)return true;
    return false;
}
function student_tool_require(PDO $db,int $studentId,string $toolKey): void {
    if(!student_tool_allowed($db,$studentId,$toolKey))throw new RuntimeException('Esta área ainda não está disponível para sua matrícula.');
}

function student_process_create_record(PDO $db,int $studentId,array $input): array {
    $enrollments=student_enrollment_list($db,$studentId);
    if(!$enrollments)throw new RuntimeException('É necessária uma matrícula ativa para criar um registro.');
    $scope=(string)($input['context_scope']??'personal');if(!in_array($scope,['personal','course'],true))$scope='personal';
    $requestedCohort=(int)($input['context_cohort_id']??0);$selected=null;
    if($scope==='course'&&$requestedCohort>0){foreach($enrollments as $enrollment)if((int)$enrollment['cohort_id']===$requestedCohort){$selected=$enrollment;break;}}
    if($scope==='course'&&!$selected)throw new RuntimeException('Escolha uma turma válida ou use um registro pessoal.');
    $legacy=$selected?:$enrollments[0];
    $payload=$input;$title=student_workspace_text($input['title']??'',180);if($title==='')$title='Registro '.date('d/m/Y');$payload['title']=$title;
    $test=student_test_create($db,$studentId,(int)$legacy['cohort_id'],$payload);
    $contextId=$scope==='course'?(int)$selected['cohort_id']:null;
    $db->prepare('UPDATE student_tests SET context_scope=?,context_cohort_id=?,updated_at=? WHERE id=? AND student_id=?')->execute([$scope,$contextId,utc_now(),(int)$test['id'],$studentId]);
    return student_test_for_student($db,(int)$test['id'],$studentId)??$test;
}
function student_process_records_for_student(PDO $db,int $studentId): array {
    $q=$db->prepare("SELECT t.*,c.title cohort_title,a.public_title,
        cc.title context_cohort_title,ca.public_title context_course_title
        FROM student_tests t
        JOIN course_cohorts c ON c.id=t.cohort_id
        JOIN activities a ON a.id=c.activity_id
        LEFT JOIN course_cohorts cc ON cc.id=t.context_cohort_id
        LEFT JOIN activities ca ON ca.id=cc.activity_id
        WHERE t.student_id=? ORDER BY t.updated_at DESC,t.id DESC");
    $q->execute([$studentId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function student_process_duplicate(PDO $db,int $testId,int $studentId): array {
    $source=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');
    $input=$source;$input['title']='Cópia de '.(string)$source['title'];$input['context_scope']=(string)($source['context_scope']??'course');$input['context_cohort_id']=(int)($source['context_cohort_id']??0);
    $copy=student_process_create_record($db,$studentId,$input);$copyId=(int)$copy['id'];
    $db->prepare('UPDATE student_tests SET test_date=?,film=?,lot=?,iso_reference=?,aperture=?,calculated_time=?,reciprocity_time=?,light_condition=?,tonal_range=?,notes=?,updated_at=? WHERE id=? AND student_id=?')
        ->execute([$source['test_date'],$source['film'],$source['lot'],$source['iso_reference'],$source['aperture'],$source['calculated_time'],$source['reciprocity_time'],$source['light_condition'],$source['tonal_range'],'',utc_now(),$copyId,$studentId]);
    $steps=student_process_steps($db,$testId);$insert=$db->prepare('INSERT INTO student_process_steps(step_uuid,test_id,position,stage_type,stage_key,label,chemical_key,chemical_name,inventory_item_id,saved_preparation_id,developer_amount,water_amount,amount_unit,calculated_dilution,total_volume,temperature,duration,agitation,notes,metadata_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,NULL,?,?,?,?,?,?,?,?,?,?,?, ?,?)');
    $now=utc_now();foreach($steps as $step)$insert->execute([student_uuid(),$copyId,(int)$step['position'],$step['stage_type'],$step['stage_key'],$step['label'],$step['chemical_key'],$step['chemical_name'],$step['saved_preparation_id']?:null,$step['developer_amount'],$step['water_amount'],$step['amount_unit'],$step['calculated_dilution'],$step['total_volume'],$step['temperature'],$step['duration'],$step['agitation'],$step['notes'],$step['metadata_json'],$now,$now]);
    return student_test_for_student($db,$copyId,$studentId)??$copy;
}

function student_process_stage_catalog(): array {
    return [
        'first_development'=>['label'=>'Primeira revelação','type'=>'development'],
        'wash_after_first'=>['label'=>'Lavagem com água','type'=>'wash'],
        'stop_after_first'=>['label'=>'Banho interruptor','type'=>'chemical'],
        'fixer'=>['label'=>'Fixação / hipossulfito','type'=>'chemical'],
        'peracetic'=>['label'=>'Solução peroxiacética','type'=>'chemical'],
        'ferric'=>['label'=>'Cloreto férrico','type'=>'chemical'],
        'dichromate'=>['label'=>'Dicromato','type'=>'chemical'],
        'permanganate'=>['label'=>'Permanganato','type'=>'chemical'],
        'wash_after_bleach'=>['label'=>'Lavagem','type'=>'wash'],
        'ammonia'=>['label'=>'Banho de amônia','type'=>'chemical'],
        'wash_after_ammonia'=>['label'=>'Lavagem','type'=>'wash'],
        'clearing'=>['label'=>'Banho de limpeza','type'=>'chemical'],
        'wash_after_clearing'=>['label'=>'Lavagem','type'=>'wash'],
        'second_development'=>['label'=>'Segunda revelação','type'=>'development'],
        'final_wash'=>['label'=>'Lavagem final','type'=>'wash'],
        'dry'=>['label'=>'Secagem','type'=>'dry'],
        'custom'=>['label'=>'Outra etapa','type'=>'custom'],
    ];
}
function student_process_steps(PDO $db,int $testId): array {$q=$db->prepare('SELECT * FROM student_process_steps WHERE test_id=? ORDER BY position,id');$q->execute([$testId]);return $q->fetchAll(PDO::FETCH_ASSOC);}
function student_process_last_bleach(array $steps): string {
    $branch='';foreach($steps as $step)if(in_array((string)$step['stage_key'],['fixer','peracetic','ferric','dichromate','permanganate'],true))$branch=(string)$step['stage_key'];return $branch;
}
function student_process_next_choices(array $steps): array {
    $catalog=student_process_stage_catalog();
    if(!$steps)$keys=['first_development'];
    else{
        $last=(string)$steps[array_key_last($steps)]['stage_key'];$branch=student_process_last_bleach($steps);
        $keys=match($last){
            'first_development'=>['wash_after_first','stop_after_first','custom'],
            'wash_after_first','stop_after_first'=>['fixer','peracetic','ferric','dichromate','permanganate','custom'],
            'fixer'=>['final_wash','custom'],
            'peracetic','ferric','dichromate','permanganate'=>['wash_after_bleach','custom'],
            'wash_after_bleach'=>$branch==='ferric'?['ammonia','custom']:(in_array($branch,['dichromate','permanganate'],true)?['clearing','custom']:['second_development','custom']),
            'ammonia'=>['wash_after_ammonia','custom'],
            'wash_after_ammonia'=>['second_development','custom'],
            'clearing'=>['wash_after_clearing','custom'],
            'wash_after_clearing'=>['second_development','custom'],
            'second_development'=>['final_wash','custom'],
            'final_wash'=>['dry','custom'],
            'dry'=>['custom'],
            default=>['wash_after_first','fixer','peracetic','ferric','dichromate','permanganate','second_development','final_wash','dry','custom'],
        };
    }
    $out=[];foreach($keys as $key)if(isset($catalog[$key]))$out[$key]=$catalog[$key];return $out;
}
function student_process_add_step(PDO $db,int $testId,int $studentId,array $input): array {
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');if($test['status']==='reviewed')throw new RuntimeException('Este registro já foi revisado.');
    $catalog=student_process_stage_catalog();$stageKey=(string)($input['stage_key']??'');if(!isset($catalog[$stageKey]))throw new RuntimeException('Etapa inválida.');
    $allowed=student_process_next_choices(student_process_steps($db,$testId));if($stageKey!=='custom'&&!isset($allowed[$stageKey]))throw new RuntimeException('Esta etapa não corresponde ao próximo passo do processo.');
    $stage=$catalog[$stageKey];$label=$stageKey==='custom'?student_workspace_text($input['custom_label']??'',120):(string)$stage['label'];if($label==='')throw new RuntimeException('Informe o nome da etapa.');
    $chemicalKey='';$chemicalName='';$developerAmount=null;$waterAmount=null;$unit='ml';$dilution='';$total=null;$savedPrepId=(int)($input['saved_preparation_id']??0)?:null;
    if(in_array($stageKey,['first_development','second_development'],true)){
        $developer=student_process_developer((string)($input['developer_key']??''),(string)($input['developer_name']??''));$chemicalKey=(string)$developer['key'];$chemicalName=(string)$developer['label'];
        if($developer['mode']==='fresh'){$developerAmount=student_workbench_float($input['fresh_volume']??null);$total=$developerAmount;}
        else{$developerAmount=student_workbench_float($input['developer_amount']??null);$waterAmount=student_workbench_float($input['water_amount']??null);$calc=student_process_dilution($developerAmount,$waterAmount,(string)$developer['mode']);$dilution=$calc['label'];$total=$calc['total'];}
    }else{
        $chemicalNames=['stop_after_first'=>'Banho interruptor','fixer'=>'Hipossulfito / fixador','peracetic'=>'Solução peroxiacética','ferric'=>'Cloreto férrico','dichromate'=>'Dicromato','permanganate'=>'Permanganato','ammonia'=>'Amônia','clearing'=>'Banho de limpeza'];
        $chemicalName=$chemicalNames[$stageKey]??student_workspace_text($input['chemical_name']??'',180);$chemicalKey=$stageKey;
    }
    $inventoryItemId=(int)($input['inventory_item_id']??0)?:null;$usedAmount=student_workbench_float($input['inventory_amount']??null);
    if($inventoryItemId&&$usedAmount===null&&$developerAmount!==null&&in_array($stageKey,['first_development','second_development'],true))$usedAmount=$developerAmount;
    $position=(int)$db->query('SELECT COALESCE(MAX(position),0)+1 FROM student_process_steps WHERE test_id='.(int)$testId)->fetchColumn();$now=utc_now();
    $db->beginTransaction();try{
        $q=$db->prepare('INSERT INTO student_process_steps(step_uuid,test_id,position,stage_type,stage_key,label,chemical_key,chemical_name,inventory_item_id,saved_preparation_id,developer_amount,water_amount,amount_unit,calculated_dilution,total_volume,temperature,duration,agitation,notes,metadata_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([student_uuid(),$testId,$position,$stage['type'],$stageKey,$label,$chemicalKey,$chemicalName,$inventoryItemId,$savedPrepId,$developerAmount,$waterAmount,$unit,$dilution,$total,student_workspace_text($input['temperature']??'',80),student_workspace_text($input['duration']??'',120),student_workspace_text($input['agitation']??'',600),student_workspace_text($input['notes']??'',3000),'{}',$now,$now]);$stepId=(int)$db->lastInsertId();
        if($inventoryItemId&&$usedAmount!==null&&$usedAmount>0)student_inventory_move($db,$studentId,$inventoryItemId,-$usedAmount,'consume','Uso no Caderno',$testId,$stepId,false);
        $db->prepare('UPDATE student_tests SET developer=CASE WHEN ?="first_development" THEN ? ELSE developer END,dilution=CASE WHEN ?="first_development" THEN ? ELSE dilution END,temperature=CASE WHEN ?="first_development" THEN ? ELSE temperature END,development_time=CASE WHEN ?="first_development" THEN ? ELSE development_time END,agitation=CASE WHEN ?="first_development" THEN ? ELSE agitation END,updated_at=? WHERE id=? AND student_id=?')->execute([$stageKey,$chemicalName,$stageKey,$dilution,$stageKey,student_workspace_text($input['temperature']??'',80),$stageKey,student_workspace_text($input['duration']??'',120),$stageKey,student_workspace_text($input['agitation']??'',600),$now,$testId,$studentId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    $q=$db->prepare('SELECT * FROM student_process_steps WHERE id=?');$q->execute([$stepId]);return $q->fetch(PDO::FETCH_ASSOC)?:[];
}
function student_process_delete_step(PDO $db,int $testId,int $stepId,int $studentId): void {
    student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Registro não encontrado.');$q=$db->prepare('SELECT * FROM student_process_steps WHERE id=? AND test_id=?');$q->execute([$stepId,$testId]);$step=$q->fetch(PDO::FETCH_ASSOC);if(!$step)throw new RuntimeException('Etapa inválida.');
    $sum=$db->prepare('SELECT COALESCE(SUM(quantity_delta),0) FROM student_inventory_movements WHERE step_id=?');$sum->execute([$stepId]);$delta=(float)$sum->fetchColumn();
    $db->beginTransaction();try{if($delta<0&&$step['inventory_item_id'])student_inventory_move($db,$studentId,(int)$step['inventory_item_id'],-$delta,'adjust','Estorno por remoção de etapa',$testId,null,false);$db->prepare('DELETE FROM student_process_steps WHERE id=? AND test_id=?')->execute([$stepId,$testId]);$db->prepare('UPDATE student_tests SET updated_at=? WHERE id=?')->execute([utc_now(),$testId]);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function student_process_path_label(array $steps): string {
    $labels=[];foreach($steps as $step){$label=(string)$step['label'];if($step['chemical_name']!=='')$label=(string)$step['chemical_name'];$labels[]=$label;}return implode(' → ',$labels);
}

function student_saved_preparations(PDO $db,int $studentId,string $developerKey=''): array {$sql='SELECT * FROM student_saved_preparations WHERE student_id=?';$args=[$studentId];if($developerKey!==''){$sql.=' AND developer_key=?';$args[]=$developerKey;}$sql.=' ORDER BY developer_key,label,id';$q=$db->prepare($sql);$q->execute($args);return $q->fetchAll(PDO::FETCH_ASSOC);}
function student_saved_preparation_create(PDO $db,int $studentId,array $input): array {
    $developer=student_process_developer((string)($input['developer_key']??''),(string)($input['developer_name']??''));$label=student_workspace_text($input['label']??'',120);if($label==='')throw new RuntimeException('Dê um nome ao preparo.');
    $dev=student_workbench_float($input['developer_amount']??null);$water=student_workbench_float($input['water_amount']??null);$ingredients=is_array($input['ingredients']??null)?$input['ingredients']:[];$cleanIngredients=[];foreach($ingredients as $row)if(is_array($row)&&trim((string)($row['name']??''))!=='')$cleanIngredients[]=['name'=>student_workspace_text($row['name'],120),'amount'=>student_workbench_float($row['amount']??null),'unit'=>student_workspace_text($row['unit']??'',20)];
    $now=utc_now();$db->prepare('INSERT INTO student_saved_preparations(preparation_uuid,student_id,developer_key,developer_name,label,preparation_mode,developer_amount,water_amount,amount_unit,temperature,duration,agitation,ingredients_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([student_uuid(),$studentId,$developer['key'],$developer['label'],$label,$developer['mode'],$dev,$water,'ml',student_workspace_text($input['temperature']??'',80),student_workspace_text($input['duration']??'',120),student_workspace_text($input['agitation']??'',600),json_encode($cleanIngredients,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$now]);$q=$db->prepare('SELECT * FROM student_saved_preparations WHERE id=?');$q->execute([(int)$db->lastInsertId()]);return $q->fetch(PDO::FETCH_ASSOC)?:[];
}
function student_saved_preparation_delete(PDO $db,int $studentId,int $id): void {$q=$db->prepare('DELETE FROM student_saved_preparations WHERE id=? AND student_id=?');$q->execute([$id,$studentId]);}

function student_inventory_items(PDO $db,int $studentId,bool $includeArchived=false): array {$sql='SELECT * FROM student_inventory_items WHERE student_id=?'.($includeArchived?'':' AND archived_at IS NULL').' ORDER BY item_kind,name,id';$q=$db->prepare($sql);$q->execute([$studentId]);return $q->fetchAll(PDO::FETCH_ASSOC);}
function student_inventory_item(PDO $db,int $studentId,int $itemId): ?array {$q=$db->prepare('SELECT * FROM student_inventory_items WHERE id=? AND student_id=?');$q->execute([$itemId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function student_inventory_create(PDO $db,int $studentId,array $input): array {
    $kind=in_array((string)($input['item_kind']??'raw'),['raw','solution'],true)?(string)$input['item_kind']:'raw';$name=student_workspace_text($input['name']??'',180);if($name==='')throw new RuntimeException('Informe o insumo ou solução.');if($kind==='solution'&&preg_match('/caffenol/i',$name))throw new RuntimeException('Caffenol é preparado para uso imediato e não deve ser cadastrado como solução armazenada. Cadastre seus ingredientes como insumos.');
    $quantity=student_workbench_float($input['quantity']??0)??0;if($quantity<0)throw new RuntimeException('A quantidade inicial não pode ser negativa.');$unit=student_workspace_text($input['unit']??'ml',20);if($unit==='')$unit='ml';$now=utc_now();
    $db->beginTransaction();try{$db->prepare('INSERT INTO student_inventory_items(item_uuid,student_id,item_kind,name,category,quantity,unit,minimum_quantity,lot_code,acquired_or_prepared_at,expires_at,location,storable,notes,created_at,updated_at) VALUES(?,?,?,?,?,0,?,?,?,?,?,?,1,?,?,?)')->execute([student_uuid(),$studentId,$kind,$name,student_workspace_text($input['category']??'',100),$unit,student_workbench_float($input['minimum_quantity']??null),student_workspace_text($input['lot_code']??'',100),student_workspace_date($input['acquired_or_prepared_at']??null),student_workspace_date($input['expires_at']??null),student_workspace_text($input['location']??'',160),student_workspace_text($input['notes']??'',2000),$now,$now]);$id=(int)$db->lastInsertId();if($quantity>0)student_inventory_move($db,$studentId,$id,$quantity,'in','Quantidade inicial',null,null,false);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}return student_inventory_item($db,$studentId,$id)??[];
}
function student_inventory_move(PDO $db,int $studentId,int $itemId,float $delta,string $type,string $note='',?int $testId=null,?int $stepId=null,bool $manageTransaction=true): void {
    if(abs($delta)<0.0000001)throw new RuntimeException('Informe uma quantidade diferente de zero.');$item=student_inventory_item($db,$studentId,$itemId)??throw new RuntimeException('Item de inventário inválido.');$next=(float)$item['quantity']+$delta;if($next<-0.000001)throw new RuntimeException('O estoque não é suficiente para esta baixa.');
    $started=$manageTransaction&&!$db->inTransaction();if($started)$db->beginTransaction();try{$now=utc_now();$db->prepare('UPDATE student_inventory_items SET quantity=?,updated_at=? WHERE id=? AND student_id=?')->execute([max(0,$next),$now,$itemId,$studentId]);$db->prepare('INSERT INTO student_inventory_movements(movement_uuid,student_id,item_id,test_id,step_id,movement_type,quantity_delta,note,created_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([student_uuid(),$studentId,$itemId,$testId,$stepId,$type,$delta,student_workspace_text($note,500),$now]);if($started)$db->commit();}catch(Throwable $e){if($started&&$db->inTransaction())$db->rollBack();throw $e;}
}
function student_inventory_movements(PDO $db,int $studentId,int $itemId=0,int $limit=100): array {$sql='SELECT m.*,i.name item_name,i.unit FROM student_inventory_movements m JOIN student_inventory_items i ON i.id=m.item_id WHERE m.student_id=?';$args=[$studentId];if($itemId>0){$sql.=' AND m.item_id=?';$args[]=$itemId;}$sql.=' ORDER BY m.id DESC LIMIT '.max(1,min(300,$limit));$q=$db->prepare($sql);$q->execute($args);return $q->fetchAll(PDO::FETCH_ASSOC);}
function student_inventory_archive(PDO $db,int $studentId,int $itemId): void {$db->prepare('UPDATE student_inventory_items SET archived_at=?,updated_at=? WHERE id=? AND student_id=?')->execute([utc_now(),utc_now(),$itemId,$studentId]);}

function student_questions_for_cohort(PDO $db,int $studentId,int $cohortId): array {$q=$db->prepare("SELECT q.*,u.name student_name FROM student_questions q JOIN student_users u ON u.id=q.student_id WHERE q.cohort_id=? AND (q.student_id=? OR q.visibility='cohort') ORDER BY CASE q.status WHEN 'open' THEN 0 ELSE 1 END,q.updated_at DESC");$q->execute([$cohortId,$studentId]);return $q->fetchAll(PDO::FETCH_ASSOC);}
function student_question_create(PDO $db,int $studentId,int $cohortId,array $input): array {student_test_assert_enrollment($db,$studentId,$cohortId);$title=student_workspace_text($input['title']??'',180);$body=student_workspace_text($input['body']??'',5000);if($title===''||$body==='')throw new RuntimeException('Informe o título e a dúvida.');$visibility=(string)($input['visibility']??'private');if(!in_array($visibility,['private','cohort'],true))$visibility='private';$testId=(int)($input['test_id']??0)?:null;if($testId&& !student_test_for_student($db,$testId,$studentId))throw new RuntimeException('Registro vinculado inválido.');$now=utc_now();$db->prepare("INSERT INTO student_questions(question_uuid,student_id,cohort_id,test_id,topic,title,body,visibility,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,'open',?,?)")->execute([student_uuid(),$studentId,$cohortId,$testId,student_workspace_text($input['topic']??'',80),$title,$body,$visibility,$now,$now]);$q=$db->prepare('SELECT * FROM student_questions WHERE id=?');$q->execute([(int)$db->lastInsertId()]);return $q->fetch(PDO::FETCH_ASSOC)?:[];}
function student_question_for_student(PDO $db,int $studentId,int $questionId): ?array {$q=$db->prepare("SELECT q.*,u.name student_name FROM student_questions q JOIN student_users u ON u.id=q.student_id WHERE q.id=? AND (q.student_id=? OR q.visibility='cohort') LIMIT 1");$q->execute([$questionId,$studentId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function student_question_messages(PDO $db,int $questionId): array {$q=$db->prepare('SELECT m.*,u.name student_name FROM student_question_messages m LEFT JOIN student_users u ON u.id=m.student_id WHERE m.question_id=? ORDER BY m.id');$q->execute([$questionId]);return $q->fetchAll(PDO::FETCH_ASSOC);}
function student_question_reply(PDO $db,int $studentId,int $questionId,string $body): void {$question=student_question_for_student($db,$studentId,$questionId)??throw new RuntimeException('Dúvida não encontrada.');if((string)$question['visibility']!=='cohort'&&(int)$question['student_id']!==$studentId)throw new RuntimeException('Esta dúvida é privada.');$body=student_workspace_text($body,4000);if($body==='')throw new RuntimeException('Escreva uma resposta.');$now=utc_now();$db->prepare("INSERT INTO student_question_messages(message_uuid,question_id,author_role,student_id,body,created_at) VALUES(?,?, 'student',?,?,?)")->execute([student_uuid(),$questionId,$studentId,$body,$now]);$db->prepare('UPDATE student_questions SET updated_at=? WHERE id=?')->execute([$now,$questionId]);}
function student_question_resolve(PDO $db,int $studentId,int $questionId): void {$q=$db->prepare("UPDATE student_questions SET status='resolved',updated_at=? WHERE id=? AND student_id=?");$q->execute([utc_now(),$questionId,$studentId]);}

function student_material_notes_for_page(PDO $db,int $studentId,int $pageId): array {$q=$db->prepare('SELECT * FROM student_material_notes WHERE student_id=? AND page_id=? ORDER BY id');$q->execute([$studentId,$pageId]);$out=[];foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row)$out[(string)$row['section_key']]=$row;return $out;}
function student_material_note_save(PDO $db,int $studentId,int $pageId,string $sectionKey,?int $lessonId,string $body): void {$sectionKey=activity_slug($sectionKey);if($sectionKey==='')throw new RuntimeException('Seção inválida.');$body=student_workspace_text($body,5000);$now=utc_now();if($body===''){$db->prepare('DELETE FROM student_material_notes WHERE student_id=? AND page_id=? AND section_key=?')->execute([$studentId,$pageId,$sectionKey]);return;}$db->prepare('INSERT INTO student_material_notes(note_uuid,student_id,page_id,section_key,lesson_id,body,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?) ON CONFLICT(student_id,page_id,section_key) DO UPDATE SET lesson_id=excluded.lesson_id,body=excluded.body,updated_at=excluded.updated_at')->execute([student_uuid(),$studentId,$pageId,$sectionKey,$lessonId,$body,$now,$now]);}
function student_material_inject_notes(PDO $db,array $student,array $page,array $document): array {
    $html=(string)($document['html']??'');if($html===''||!str_contains($html,'data-cms-section'))return $document;$notes=student_material_notes_for_page($db,(int)$student['id'],(int)$page['id']);$previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');$dom->loadHTML('<?xml encoding="utf-8" ?><div id="student-notes-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$xpath=new DOMXPath($dom);
    foreach(iterator_to_array($xpath->query('//*[@data-cms-section]')?:[]) as $section){if(!$section instanceof DOMElement)continue;$key=activity_slug($section->getAttribute('data-cms-section'));if($key==='')continue;$lessonId=(int)$section->getAttribute('data-cms-lesson-id');$note=$notes[$key]??null;$details=$dom->createElement('details');$details->setAttribute('class','student-material-note');if($note)$details->setAttribute('data-has-note','1');$summary=$dom->createElement('summary',$note?'Sua anotação':'Anotar');$details->appendChild($summary);$form=$dom->createElement('form');$form->setAttribute('method','post');$form->setAttribute('action','/aluno/material-anotacao.php');foreach(['_csrf'=>csrf_token('student-material-note'),'page_id'=>(string)(int)$page['id'],'section_key'=>$key,'lesson_id'=>$lessonId>0?(string)$lessonId:'','return_to'=>(string)($_SERVER['REQUEST_URI']??'/aluno/')] as $name=>$value){$hidden=$dom->createElement('input');$hidden->setAttribute('type','hidden');$hidden->setAttribute('name',$name);$hidden->setAttribute('value',$value);$form->appendChild($hidden);} $textarea=$dom->createElement('textarea');$textarea->setAttribute('name','body');$textarea->setAttribute('rows','4');$textarea->setAttribute('maxlength','5000');$textarea->setAttribute('placeholder','Sua anotação sobre esta seção');$textarea->appendChild($dom->createTextNode((string)($note['body']??'')));$form->appendChild($textarea);$actions=$dom->createElement('div');$actions->setAttribute('class','student-material-note-actions');$save=$dom->createElement('button','Salvar anotação');$save->setAttribute('type','submit');$save->setAttribute('class','button');$actions->appendChild($save);if($note){$remove=$dom->createElement('button','Remover');$remove->setAttribute('type','submit');$remove->setAttribute('name','remove');$remove->setAttribute('value','1');$remove->setAttribute('class','student-material-note-remove');$actions->appendChild($remove);}$form->appendChild($actions);$details->appendChild($form);$section->appendChild($details);}
    $root=$dom->getElementById('student-notes-root');$out='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);libxml_clear_errors();libxml_use_internal_errors($previous);$document['html']=$out;return $document;
}

function student_solution_formulas(): array {
    return [
        'parodinal'=>['label'=>'Parodinal · concentrado','base_volume'=>250.0,'unit'=>'ml','ingredients'=>[['name'=>'Água destilada/desmineralizada','amount'=>250.0,'unit'=>'ml'],['name'=>'Paracetamol','amount'=>15.0,'unit'=>'g'],['name'=>'Sulfito de sódio','amount'=>50.0,'unit'=>'g'],['name'=>'Hidróxido de sódio','amount'=>20.0,'unit'=>'g']]],
        'brewed-caffenol'=>['label'=>'Brewed Caffenol · uso imediato','base_volume'=>1000.0,'unit'=>'ml','fresh'=>true,'ingredients'=>[['name'=>'Café torrado e moído extra-forte','amount'=>37.0,'unit'=>'g'],['name'=>'Carbonato de sódio','amount'=>54.0,'unit'=>'g'],['name'=>'Ácido ascórbico','amount'=>20.0,'unit'=>'g'],['name'=>'Água para completar','amount'=>1000.0,'unit'=>'ml']]],
        'peracetic'=>['label'=>'Solução peroxiacética','base_volume'=>1000.0,'unit'=>'ml','ingredients'=>[['name'=>'Água','amount'=>650.0,'unit'=>'ml'],['name'=>'Vinagre de álcool','amount'=>150.0,'unit'=>'ml'],['name'=>'Peróxido de hidrogênio a 50%','amount'=>200.0,'unit'=>'ml']]],
        'ferric'=>['label'=>'Cloreto férrico 1+4','base_volume'=>500.0,'unit'=>'ml','ingredients'=>[['name'=>'Cloreto férrico em pó','amount'=>100.0,'unit'=>'g'],['name'=>'Água','amount'=>400.0,'unit'=>'ml']]],
        'ammonia'=>['label'=>'Amônia 1+4','base_volume'=>500.0,'unit'=>'ml','ingredients'=>[['name'=>'Amônia','amount'=>100.0,'unit'=>'ml'],['name'=>'Água','amount'=>400.0,'unit'=>'ml']]],
    ];
}

function student_calibrations(PDO $db,int $studentId): array {$q=$db->prepare('SELECT * FROM student_process_calibrations WHERE student_id=? ORDER BY updated_at DESC,id DESC');$q->execute([$studentId]);return $q->fetchAll(PDO::FETCH_ASSOC);}
function student_calibration_save(PDO $db,int $studentId,array $input): void {$label=student_workspace_text($input['label']??'',160);if($label==='')throw new RuntimeException('Dê um nome à calibração.');$id=(int)($input['id']??0);$values=[$label,student_workspace_text($input['film']??'',160),student_workspace_text($input['developer']??'',160),student_workspace_text($input['preparation']??'',160),student_workspace_text($input['temperature']??'',80),student_workspace_text($input['prebath']??'',120),student_workspace_text($input['white_time']??'',120),student_workspace_text($input['notes']??'',2000),utc_now()];if($id>0){$q=$db->prepare('UPDATE student_process_calibrations SET label=?,film=?,developer=?,preparation=?,temperature=?,prebath=?,white_time=?,notes=?,updated_at=? WHERE id=? AND student_id=?');$q->execute([...$values,$id,$studentId]);}else{$q=$db->prepare('INSERT INTO student_process_calibrations(calibration_uuid,student_id,label,film,developer,preparation,temperature,prebath,white_time,notes,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');$now=utc_now();$q->execute([student_uuid(),$studentId,$label,$values[1],$values[2],$values[3],$values[4],$values[5],$values[6],$values[7],$now,$now]);}}
