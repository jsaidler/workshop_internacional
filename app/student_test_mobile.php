<?php
declare(strict_types=1);

function student_test_update_exposure(PDO $db,int $testId,int $studentId,array $input): array {
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');
    if($test['status']==='reviewed')throw new RuntimeException('Este teste já foi revisado.');
    student_test_assert_enrollment($db,$studentId,(int)$test['cohort_id']);
    $title=student_workspace_text($input['title']??$test['title'],180);
    if($title==='')$title='Teste '.date('d/m/Y');
    $date=trim((string)($input['test_date']??$test['test_date']));
    $film=student_workspace_text($input['film']??'',120);
    $lot=student_workspace_text($input['lot']??'',120);
    $iso=student_workspace_text($input['iso_reference']??'',80);
    $aperture=student_workspace_text($input['aperture']??'',80);
    $calc=student_workspace_text($input['calculated_time']??'',120);
    $recip=student_workspace_text($input['reciprocity_time']??'',120);
    $light=student_workspace_text($input['light_condition']??'',1800);
    $tonal=student_workspace_text($input['tonal_range']??'',1800);
    $db->prepare('UPDATE student_tests SET title=?,test_date=?,film=?,lot=?,iso_reference=?,aperture=?,calculated_time=?,reciprocity_time=?,light_condition=?,tonal_range=?,updated_at=? WHERE id=? AND student_id=?')
        ->execute([$title,$date!==''?$date:null,$film,$lot,$iso,$aperture,$calc,$recip,$light,$tonal,utc_now(),$testId,$studentId]);
    return student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');
}

function student_test_update_development(PDO $db,int $testId,int $studentId,array $input): array {
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');
    if($test['status']==='reviewed')throw new RuntimeException('Este teste já foi revisado.');
    student_test_assert_enrollment($db,$studentId,(int)$test['cohort_id']);
    $developer=student_workspace_text($input['developer']??'',120);
    $dilution=student_workspace_text($input['dilution']??'',120);
    $temperature=student_workspace_text($input['temperature']??'',80);
    $time=student_workspace_text($input['development_time']??'',120);
    $agitation=student_workspace_text($input['agitation']??'',1200);
    $bleach=student_workspace_text($input['bleach']??'',180);
    $notes=student_workspace_text($input['notes']??'',4000);
    $db->prepare('UPDATE student_tests SET developer=?,dilution=?,temperature=?,development_time=?,agitation=?,bleach=?,notes=?,updated_at=? WHERE id=? AND student_id=?')
        ->execute([$developer,$dilution,$temperature,$time,$agitation,$bleach,$notes,utc_now(),$testId,$studentId]);
    return student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');
}

function student_test_add_media_phase(PDO $db,int $testId,int $studentId,array $file,string $phase): array {
    $phase=in_array($phase,['scene','result'],true)?$phase:'result';
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');
    if($test['status']==='reviewed')throw new RuntimeException('Este teste já foi revisado.');
    $count=$db->prepare('SELECT COUNT(*) FROM student_test_media WHERE test_id=?');$count->execute([$testId]);
    if((int)$count->fetchColumn()>=STUDENT_TEST_MEDIA_MAX_FILES)throw new RuntimeException('Cada teste aceita no máximo '.STUDENT_TEST_MEDIA_MAX_FILES.' imagens.');
    if((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Selecione uma imagem válida.');
    $size=(int)($file['size']??0);if($size<1||$size>STUDENT_TEST_MEDIA_MAX_BYTES)throw new RuntimeException('Cada imagem deve ter no máximo 12 MB.');
    $tmp=(string)($file['tmp_name']??'');$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=(string)$finfo->file($tmp);
    $ext=match($mime){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>''};if($ext==='')throw new RuntimeException('Formato de imagem não suportado. Use JPEG, PNG ou WebP.');
    $uuid=student_uuid();$relative=$uuid.'.'.$ext;$destination=student_test_media_storage_root().'/'.$relative;
    if(!move_uploaded_file($tmp,$destination)&&!@rename($tmp,$destination))throw new RuntimeException('Não foi possível armazenar a imagem.');@chmod($destination,0660);$now=utc_now();
    try{$db->prepare('INSERT INTO student_test_media(media_uuid,test_id,original_name,mime_type,byte_size,storage_path,phase,created_at) VALUES(?,?,?,?,?,?,?,?)')
        ->execute([$uuid,(int)$test['id'],student_workspace_text($file['name']??'imagem',255),$mime,$size,$relative,$phase,$now]);}
    catch(Throwable $e){@unlink($destination);throw $e;}
    $q=$db->prepare('SELECT * FROM student_test_media WHERE id=?');$q->execute([(int)$db->lastInsertId()]);return $q->fetch();
}


/**
 * Normaliza o shape de `$_FILES['images']` para os mesmos objetos usados pelo
 * upload individual. Rejeita lote parcialmente formado antes de gravar qualquer
 * imagem, e não perde arquivos quando o seletor móvel permite seleção múltipla.
 */
function student_test_normalize_media_batch(array $uploads): array {
    if(!is_array($uploads['name']??null))throw new RuntimeException('Selecione uma ou mais imagens.');
    $names=$uploads['name'];
    $count=count($names);
    if($count<1)throw new RuntimeException('Selecione uma ou mais imagens.');
    if($count>STUDENT_TEST_MEDIA_MAX_FILES)throw new RuntimeException('Cada registro aceita no máximo '.STUDENT_TEST_MEDIA_MAX_FILES.' imagens.');
    foreach(['name','type','tmp_name','error','size'] as $key){
        if(!is_array($uploads[$key]??null)||array_keys($uploads[$key])!==array_keys($names))throw new RuntimeException('O envio das imagens está incompleto. Selecione os arquivos novamente.');
    }
    $result=[];
    foreach(array_keys($names) as $i){
        $result[]=[
            'name'=>(string)$uploads['name'][$i],
            'type'=>(string)$uploads['type'][$i],
            'tmp_name'=>(string)$uploads['tmp_name'][$i],
            'error'=>(int)$uploads['error'][$i],
            'size'=>(int)$uploads['size'][$i],
        ];
    }
    return $result;
}
function student_test_add_media_batch_phase(PDO $db,int $testId,int $studentId,array $uploads,string $phase): int {
    $files=student_test_normalize_media_batch($uploads);
    $count=$db->prepare('SELECT COUNT(*) FROM student_test_media WHERE test_id=?');
    $count->execute([$testId]);
    $remaining=STUDENT_TEST_MEDIA_MAX_FILES-(int)$count->fetchColumn();
    if(count($files)>$remaining)throw new RuntimeException('Este registro tem espaço para apenas '.max(0,$remaining).' imagem(ns). Remova alguma imagem ou selecione menos arquivos.');
    // Valide todos os arquivos antes de iniciar a gravação, evitando lote parcial
    // por tamanho, formato ou erro no seletor do navegador.
    $finfo=new finfo(FILEINFO_MIME_TYPE);
    foreach($files as $file){
        if($file['error']!==UPLOAD_ERR_OK||$file['name']==='')throw new RuntimeException('Não foi possível receber todas as imagens. Selecione os arquivos novamente.');
        if($file['size']<1||$file['size']>STUDENT_TEST_MEDIA_MAX_BYTES)throw new RuntimeException('Cada imagem deve ter no máximo 12 MB.');
        $tmp=$file['tmp_name'];
        if($tmp===''||!is_file($tmp)||!in_array((string)$finfo->file($tmp),['image/jpeg','image/png','image/webp'],true))throw new RuntimeException('Formato de imagem não suportado. Use JPEG, PNG ou WebP.');
    }
    $added=[];
    try{
        foreach($files as $file)$added[]=student_test_add_media_phase($db,$testId,$studentId,$file,$phase);
    }catch(Throwable $e){
        // Compensação de erro de armazenamento: não deixar lote pela metade.
        foreach($added as $item)student_test_delete_media($db,(int)$item['id'],$studentId);
        throw $e;
    }
    return count($added);
}

function student_test_media_by_phase(array $media,string $phase): array {
    return array_values(array_filter($media,static fn(array $item): bool=>(string)($item['phase']??'result')===$phase));
}

function student_test_message_date(string $value): string {
    $ts=strtotime($value);return $ts===false?$value:date('d/m/Y H:i',$ts);
}

function student_review_value(mixed $value): string {
    $value=trim((string)$value);return $value===''?'—':h($value);
}
