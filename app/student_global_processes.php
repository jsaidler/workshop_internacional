<?php
declare(strict_types=1);

function student_global_processes(PDO $db,bool $includeArchived=true): array {
    $where=$includeArchived?'':'WHERE p.status!=\'archived\'';
    $q=$db->query("SELECT p.*,v.version_number active_version_number,v.status active_version_status,v.published_at active_published_at,
        (SELECT COUNT(*) FROM student_global_process_versions dv WHERE dv.process_id=p.id AND dv.status='draft') draft_count,
        (SELECT COUNT(*) FROM student_process_plans sp WHERE sp.source_global_version_id IN (SELECT id FROM student_global_process_versions vv WHERE vv.process_id=p.id)) usage_count
        FROM student_global_processes p
        LEFT JOIN student_global_process_versions v ON v.id=p.active_version_id
        $where
        ORDER BY CASE p.status WHEN 'active' THEN 0 ELSE 1 END,p.name COLLATE NOCASE,p.id");
    return $q->fetchAll(PDO::FETCH_ASSOC);
}

function student_global_process_by_id(PDO $db,int $processId): ?array {
    $q=$db->prepare('SELECT * FROM student_global_processes WHERE id=? LIMIT 1');$q->execute([$processId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_global_process_by_key(PDO $db,string $key): ?array {
    $q=$db->prepare('SELECT * FROM student_global_processes WHERE process_key=? LIMIT 1');$q->execute([trim($key)]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_global_process_versions(PDO $db,int $processId): array {
    $q=$db->prepare('SELECT * FROM student_global_process_versions WHERE process_id=? ORDER BY version_number DESC,id DESC');$q->execute([$processId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

function student_global_process_version(PDO $db,int $versionId): ?array {
    $q=$db->prepare('SELECT v.*,p.process_key,p.status process_status,p.active_version_id FROM student_global_process_versions v JOIN student_global_processes p ON p.id=v.process_id WHERE v.id=? LIMIT 1');$q->execute([$versionId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_global_process_draft(PDO $db,int $processId): ?array {
    $q=$db->prepare("SELECT * FROM student_global_process_versions WHERE process_id=? AND status='draft' ORDER BY version_number DESC,id DESC LIMIT 1");$q->execute([$processId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}

function student_global_process_version_steps(PDO $db,int $versionId): array {
    $q=$db->prepare('SELECT * FROM student_global_process_version_steps WHERE version_id=? ORDER BY position,id');$q->execute([$versionId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}

function student_global_process_version_as_standard(PDO $db,array $version): array {
    $steps=[];
    foreach(student_global_process_version_steps($db,(int)$version['id']) as $step){
        $payload=student_process_json_array((string)$step['payload_json']);
        $payload['stage_key']=(string)$step['stage_key'];
        $payload['duration']=(string)$step['duration'];
        if((string)$step['agitation_interval']!=='')$payload['agitation_interval']=(string)$step['agitation_interval'];
        $steps[]=$payload;
    }
    return [
        'name'=>(string)$version['name'],
        'description'=>(string)$version['description'],
        'version_id'=>(int)$version['id'],
        'version_number'=>(int)$version['version_number'],
        'steps'=>$steps,
    ];
}

function student_global_process_catalog(PDO $db): array {
    $q=$db->query("SELECT v.*,p.process_key FROM student_global_processes p JOIN student_global_process_versions v ON v.id=p.active_version_id WHERE p.status='active' AND v.status='published' ORDER BY p.name COLLATE NOCASE,p.id");
    $out=[];
    foreach($q->fetchAll(PDO::FETCH_ASSOC) as $version)$out[(string)$version['process_key']]=student_global_process_version_as_standard($db,$version);
    return $out;
}

function student_global_process_for_key(PDO $db,string $key): ?array {
    $q=$db->prepare("SELECT v.*,p.process_key FROM student_global_processes p JOIN student_global_process_versions v ON v.id=p.active_version_id WHERE p.process_key=? AND p.status='active' AND v.status='published' LIMIT 1");
    $q->execute([trim($key)]);$version=$q->fetch(PDO::FETCH_ASSOC);return $version?student_global_process_version_as_standard($db,$version):null;
}

function student_process_change_log(PDO $db,string $actorRole,?int $adminId,?int $studentId,string $entityType,int $entityId,string $action,array $before=[],array $after=[],string $note=''): void {
    $q=$db->prepare('INSERT INTO student_process_change_log(change_uuid,actor_role,actor_admin_id,actor_student_id,entity_type,entity_id,action,before_json,after_json,note,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([student_uuid(),$actorRole,$adminId,$studentId,$entityType,$entityId,$action,json_encode($before,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),json_encode($after,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),student_workspace_text($note,1000),utc_now()]);
}

function student_global_process_create(PDO $db,int $adminId,array $input): array {
    $name=student_workspace_text($input['name']??'',180);if($name==='')throw new RuntimeException('Informe o nome do processo.');
    $key=trim((string)($input['process_key']??''));
    if($key==='')$key=trim(preg_replace('/[^a-z0-9]+/','-',strtolower(iconv('UTF-8','ASCII//TRANSLIT',$name)?:$name))??'','-');
    if($key===''||!preg_match('/^[a-z0-9][a-z0-9-]{1,119}$/',$key))throw new RuntimeException('A chave do processo é inválida.');
    if(student_global_process_by_key($db,$key))throw new RuntimeException('Já existe um processo com esta chave.');
    $description=student_workspace_text($input['description']??'',2000);$now=utc_now();
    $db->beginTransaction();try{
        $q=$db->prepare("INSERT INTO student_global_processes(process_uuid,process_key,name,description,status,created_by_admin_id,created_at,updated_at) VALUES(?,?,?,?, 'active',?,?,?)");
        $q->execute([student_uuid(),$key,$name,$description,$adminId,$now,$now]);$processId=(int)$db->lastInsertId();
        $q=$db->prepare("INSERT INTO student_global_process_versions(version_uuid,process_id,version_number,status,name,description,created_by_admin_id,created_at,updated_at) VALUES(?,?,1,'draft',?,?,?,?,?)");
        $q->execute([student_uuid(),$processId,$name,$description,$adminId,$now,$now]);$versionId=(int)$db->lastInsertId();
        student_process_change_log($db,'admin',$adminId,null,'global_process',$processId,'create',[],['name'=>$name,'description'=>$description,'draft_version_id'=>$versionId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_global_process_by_id($db,$processId)??[];
}

function student_global_process_ensure_draft(PDO $db,int $processId,int $adminId): array {
    $process=student_global_process_by_id($db,$processId)??throw new RuntimeException('Processo global não encontrado.');
    $draft=student_global_process_draft($db,$processId);if($draft)return $draft;
    $versions=student_global_process_versions($db,$processId);$next=1;foreach($versions as $version)$next=max($next,(int)$version['version_number']+1);
    $sourceId=(int)($process['active_version_id']??0);$source=$sourceId>0?student_global_process_version($db,$sourceId):null;
    $name=(string)($source['name']??$process['name']);$description=(string)($source['description']??$process['description']);$now=utc_now();
    $db->beginTransaction();try{
        $q=$db->prepare("INSERT INTO student_global_process_versions(version_uuid,process_id,version_number,status,name,description,created_by_admin_id,created_at,updated_at) VALUES(?,?,?,'draft',?,?,?,?,?)");
        $q->execute([student_uuid(),$processId,$next,$name,$description,$adminId,$now,$now]);$draftId=(int)$db->lastInsertId();
        if($source){
            $insert=$db->prepare('INSERT INTO student_global_process_version_steps(version_id,position,stage_key,label,duration,agitation_interval,payload_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');
            foreach(student_global_process_version_steps($db,(int)$source['id']) as $step)$insert->execute([$draftId,(int)$step['position'],(string)$step['stage_key'],(string)$step['label'],(string)$step['duration'],(string)$step['agitation_interval'],(string)$step['payload_json'],$now,$now]);
        }
        student_process_change_log($db,'admin',$adminId,null,'global_process',$processId,'create_draft',['active_version_id'=>$sourceId],['draft_version_id'=>$draftId,'version_number'=>$next]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_global_process_version($db,$draftId)??[];
}

function student_global_process_update_draft(PDO $db,int $versionId,int $adminId,array $input): array {
    $version=student_global_process_version($db,$versionId)??throw new RuntimeException('Versão não encontrada.');
    if((string)$version['status']!=='draft')throw new RuntimeException('Versões publicadas são imutáveis. Crie uma nova versão para alterar o processo.');
    $name=student_workspace_text($input['name']??$version['name'],180);if($name==='')throw new RuntimeException('Informe o nome do processo.');
    $description=student_workspace_text($input['description']??$version['description'],2000);$note=student_workspace_text($input['change_note']??$version['change_note'],1000);$now=utc_now();
    $before=['name'=>$version['name'],'description'=>$version['description'],'change_note'=>$version['change_note']];
    $db->beginTransaction();try{
        $db->prepare('UPDATE student_global_process_versions SET name=?,description=?,change_note=?,updated_at=? WHERE id=?')->execute([$name,$description,$note,$now,$versionId]);
        $db->prepare('UPDATE student_global_processes SET name=?,description=?,updated_at=? WHERE id=? AND active_version_id IS NULL')->execute([$name,$description,$now,(int)$version['process_id']]);
        student_process_change_log($db,'admin',$adminId,null,'global_process_version',$versionId,'update',$before,['name'=>$name,'description'=>$description,'change_note'=>$note]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_global_process_version($db,$versionId)??$version;
}

function student_global_process_step_payload(array $input): array {
    $stageKey=(string)($input['stage_key']??'');$data=student_process_stage_data($stageKey,$input);$payload=student_process_template_payload_from_stage($data,$stageKey,$input);
    unset($payload['stage_key'],$payload['duration'],$payload['agitation_interval']);
    return ['stage_key'=>$stageKey,'label'=>(string)$data['label'],'duration'=>(string)$data['duration'],'agitation_interval'=>student_workspace_text($input['agitation_interval']??'',80),'payload'=>$payload];
}

function student_global_process_add_step(PDO $db,int $versionId,int $adminId,array $input): array {
    $version=student_global_process_version($db,$versionId)??throw new RuntimeException('Versão não encontrada.');if((string)$version['status']!=='draft')throw new RuntimeException('A versão publicada não pode ser alterada.');
    $resolved=student_global_process_step_payload($input);$position=(int)$db->query('SELECT COALESCE(MAX(position),0)+1 FROM student_global_process_version_steps WHERE version_id='.(int)$versionId)->fetchColumn();$now=utc_now();
    $q=$db->prepare('INSERT INTO student_global_process_version_steps(version_id,position,stage_key,label,duration,agitation_interval,payload_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');
    $q->execute([$versionId,$position,$resolved['stage_key'],$resolved['label'],$resolved['duration'],$resolved['agitation_interval'],json_encode($resolved['payload'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$now]);$id=(int)$db->lastInsertId();
    student_process_change_log($db,'admin',$adminId,null,'global_process_version',$versionId,'add_step',[],['step_id'=>$id,'position'=>$position,'stage_key'=>$resolved['stage_key']]);
    $q=$db->prepare('SELECT * FROM student_global_process_version_steps WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:[];
}

function student_global_process_step(PDO $db,int $versionId,int $stepId): ?array {$q=$db->prepare('SELECT * FROM student_global_process_version_steps WHERE id=? AND version_id=? LIMIT 1');$q->execute([$stepId,$versionId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}

function student_global_process_update_step(PDO $db,int $versionId,int $stepId,int $adminId,array $input): array {
    $version=student_global_process_version($db,$versionId)??throw new RuntimeException('Versão não encontrada.');if((string)$version['status']!=='draft')throw new RuntimeException('A versão publicada não pode ser alterada.');
    $step=student_global_process_step($db,$versionId,$stepId)??throw new RuntimeException('Etapa não encontrada.');$resolved=student_global_process_step_payload($input);$now=utc_now();
    $db->prepare('UPDATE student_global_process_version_steps SET stage_key=?,label=?,duration=?,agitation_interval=?,payload_json=?,updated_at=? WHERE id=? AND version_id=?')->execute([$resolved['stage_key'],$resolved['label'],$resolved['duration'],$resolved['agitation_interval'],json_encode($resolved['payload'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$stepId,$versionId]);
    student_process_change_log($db,'admin',$adminId,null,'global_process_version',$versionId,'update_step',$step,['step_id'=>$stepId,'stage_key'=>$resolved['stage_key'],'label'=>$resolved['label'],'duration'=>$resolved['duration']]);
    return student_global_process_step($db,$versionId,$stepId)??$step;
}

function student_global_process_delete_step(PDO $db,int $versionId,int $stepId,int $adminId): void {
    $version=student_global_process_version($db,$versionId)??throw new RuntimeException('Versão não encontrada.');if((string)$version['status']!=='draft')throw new RuntimeException('A versão publicada não pode ser alterada.');
    $step=student_global_process_step($db,$versionId,$stepId)??throw new RuntimeException('Etapa não encontrada.');$db->beginTransaction();try{
        $db->prepare('DELETE FROM student_global_process_version_steps WHERE id=? AND version_id=?')->execute([$stepId,$versionId]);
        $rows=student_global_process_version_steps($db,$versionId);$u=$db->prepare('UPDATE student_global_process_version_steps SET position=?,updated_at=? WHERE id=?');$now=utc_now();foreach($rows as $i=>$row)$u->execute([$i+1,$now,(int)$row['id']]);
        student_process_change_log($db,'admin',$adminId,null,'global_process_version',$versionId,'delete_step',$step,[]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}

function student_global_process_move_step(PDO $db,int $versionId,int $stepId,int $adminId,int $direction): void {
    $version=student_global_process_version($db,$versionId)??throw new RuntimeException('Versão não encontrada.');if((string)$version['status']!=='draft')throw new RuntimeException('A versão publicada não pode ser alterada.');
    $rows=student_global_process_version_steps($db,$versionId);$index=null;foreach($rows as $i=>$row)if((int)$row['id']===$stepId){$index=$i;break;}if($index===null)throw new RuntimeException('Etapa não encontrada.');$target=$index+($direction<0?-1:1);if($target<0||$target>=count($rows))return;
    [$rows[$index],$rows[$target]]=[$rows[$target],$rows[$index]];$db->beginTransaction();try{$u=$db->prepare('UPDATE student_global_process_version_steps SET position=?,updated_at=? WHERE id=?');$now=utc_now();foreach($rows as $i=>$row)$u->execute([$i+1,$now,(int)$row['id']]);student_process_change_log($db,'admin',$adminId,null,'global_process_version',$versionId,'move_step',[],['step_id'=>$stepId,'direction'=>$direction]);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}

function student_global_process_publish(PDO $db,int $versionId,int $adminId): array {
    $version=student_global_process_version($db,$versionId)??throw new RuntimeException('Versão não encontrada.');if((string)$version['status']!=='draft')throw new RuntimeException('Esta versão já foi publicada.');
    $steps=student_global_process_version_steps($db,$versionId);if(!$steps)throw new RuntimeException('Adicione pelo menos uma etapa antes de publicar.');$processId=(int)$version['process_id'];$process=student_global_process_by_id($db,$processId)??throw new RuntimeException('Processo não encontrado.');$now=utc_now();
    $db->beginTransaction();try{
        if((int)($process['active_version_id']??0)>0)$db->prepare("UPDATE student_global_process_versions SET status='superseded',updated_at=? WHERE id=? AND status='published'")->execute([$now,(int)$process['active_version_id']]);
        $db->prepare("UPDATE student_global_process_versions SET status='published',published_by_admin_id=?,published_at=?,updated_at=? WHERE id=?")->execute([$adminId,$now,$now,$versionId]);
        $db->prepare("UPDATE student_global_processes SET name=?,description=?,status='active',active_version_id=?,archived_at=NULL,updated_at=? WHERE id=?")->execute([(string)$version['name'],(string)$version['description'],$versionId,$now,$processId]);
        student_process_change_log($db,'admin',$adminId,null,'global_process',$processId,'publish',['active_version_id'=>$process['active_version_id']],['active_version_id'=>$versionId,'version_number'=>$version['version_number']],(string)$version['change_note']);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_global_process_version($db,$versionId)??$version;
}

function student_global_process_archive(PDO $db,int $processId,int $adminId,bool $archive): array {
    $process=student_global_process_by_id($db,$processId)??throw new RuntimeException('Processo não encontrado.');$now=utc_now();$status=$archive?'archived':'active';$archivedAt=$archive?$now:null;
    $db->prepare('UPDATE student_global_processes SET status=?,archived_at=?,updated_at=? WHERE id=?')->execute([$status,$archivedAt,$now,$processId]);
    student_process_change_log($db,'admin',$adminId,null,'global_process',$processId,$archive?'archive':'restore',['status'=>$process['status']],['status'=>$status]);
    return student_global_process_by_id($db,$processId)??$process;
}

function student_process_stage_catalog_rows(PDO $db,bool $onlyEnabled=false): array {$sql='SELECT * FROM student_process_stage_catalog'.($onlyEnabled?' WHERE enabled=1':'').' ORDER BY sort_order,label COLLATE NOCASE';return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);}
function student_process_developer_catalog_rows(PDO $db,bool $onlyEnabled=false): array {$sql='SELECT * FROM student_process_developer_catalog'.($onlyEnabled?' WHERE enabled=1':'').' ORDER BY sort_order,label COLLATE NOCASE';return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);}

function student_process_admin_update_stage(PDO $db,int $adminId,string $key,array $input): void {
    $key=trim($key);if($key===''||!preg_match('/^[a-z0-9_\-]+$/',$key))throw new RuntimeException('Chave de etapa inválida.');$label=student_workspace_text($input['label']??'',120);if($label==='')throw new RuntimeException('Informe o nome da etapa.');$type=(string)($input['stage_type']??'custom');if(!in_array($type,['development','wash','chemical','dry','custom'],true))throw new RuntimeException('Tipo de etapa inválido.');$chemical=student_workspace_text($input['chemical_name']??'',180);$enabled=!empty($input['enabled'])?1:0;$sort=max(0,(int)($input['sort_order']??0));$now=utc_now();
    $existing=$db->prepare('SELECT * FROM student_process_stage_catalog WHERE stage_key=?');$existing->execute([$key]);$before=$existing->fetch(PDO::FETCH_ASSOC)?:[];
    $q=$db->prepare('INSERT INTO student_process_stage_catalog(stage_key,label,stage_type,chemical_name,enabled,sort_order,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?) ON CONFLICT(stage_key) DO UPDATE SET label=excluded.label,stage_type=excluded.stage_type,chemical_name=excluded.chemical_name,enabled=excluded.enabled,sort_order=excluded.sort_order,updated_at=excluded.updated_at');$q->execute([$key,$label,$type,$chemical,$enabled,$sort,$now,$now]);student_process_change_log($db,'admin',$adminId,null,'stage_catalog',0,'upsert',$before,['stage_key'=>$key,'label'=>$label,'stage_type'=>$type,'chemical_name'=>$chemical,'enabled'=>$enabled,'sort_order'=>$sort]);
}

function student_process_admin_update_developer(PDO $db,int $adminId,string $key,array $input): void {
    $key=trim($key);if($key===''||!preg_match('/^[a-z0-9_\-]+$/',$key))throw new RuntimeException('Chave de revelador inválida.');$label=student_workspace_text($input['label']??'',160);if($label==='')throw new RuntimeException('Informe o nome do revelador.');$mode=(string)($input['preparation_mode']??'custom');if(!in_array($mode,['concentrate','stock','fresh','custom'],true))throw new RuntimeException('Modo de preparo inválido.');$storable=!empty($input['storable'])?1:0;$enabled=!empty($input['enabled'])?1:0;$sort=max(0,(int)($input['sort_order']??0));$now=utc_now();
    $existing=$db->prepare('SELECT * FROM student_process_developer_catalog WHERE developer_key=?');$existing->execute([$key]);$before=$existing->fetch(PDO::FETCH_ASSOC)?:[];
    $q=$db->prepare('INSERT INTO student_process_developer_catalog(developer_key,label,preparation_mode,storable,enabled,sort_order,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?) ON CONFLICT(developer_key) DO UPDATE SET label=excluded.label,preparation_mode=excluded.preparation_mode,storable=excluded.storable,enabled=excluded.enabled,sort_order=excluded.sort_order,updated_at=excluded.updated_at');$q->execute([$key,$label,$mode,$storable,$enabled,$sort,$now,$now]);student_process_change_log($db,'admin',$adminId,null,'developer_catalog',0,'upsert',$before,['developer_key'=>$key,'label'=>$label,'preparation_mode'=>$mode,'storable'=>$storable,'enabled'=>$enabled,'sort_order'=>$sort]);
}
