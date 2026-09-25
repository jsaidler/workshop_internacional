<?php
declare(strict_types=1);

function student_test_stage_key(string $value): string {
    return in_array($value,['exposure','development','result'],true)?$value:'exposure';
}
function student_test_stage_url(int $testId,string $stage): string {
    return '/aluno/teste.php?'.http_build_query(['id'=>$testId,'step'=>student_test_stage_key($stage)]);
}
function student_test_update_stage(PDO $db,int $testId,int $studentId,string $stage,array $input): array {
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');
    if($test['status']==='reviewed')throw new RuntimeException('Este teste já foi revisado. Envie uma dúvida no histórico se precisar continuar a conversa.');
    student_test_assert_enrollment($db,$studentId,(int)$test['cohort_id']);
    $stage=student_test_stage_key($stage);
    if($stage==='exposure'){
        $title=student_workspace_text($input['title']??'',180);if($title==='')throw new RuntimeException('Dê um título ao teste para poder encontrá-lo depois.');
        $values=[
            'title'=>$title,
            'test_date'=>student_workspace_date($input['test_date']??null),
            'film'=>student_workspace_text($input['film']??'',180),
            'lot'=>student_workspace_text($input['lot']??'',120),
            'iso_reference'=>student_workspace_text($input['iso_reference']??'',80),
            'aperture'=>student_workspace_text($input['aperture']??'',80),
            'calculated_time'=>student_workspace_text($input['calculated_time']??'',120),
            'reciprocity_time'=>student_workspace_text($input['reciprocity_time']??'',120),
            'light_condition'=>student_workspace_text($input['light_condition']??'',800),
            'tonal_range'=>student_workspace_text($input['tonal_range']??'',800),
        ];
    }elseif($stage==='development'){
        $values=[
            'developer'=>student_workspace_text($input['developer']??'',180),
            'dilution'=>student_workspace_text($input['dilution']??'',120),
            'temperature'=>student_workspace_text($input['temperature']??'',80),
            'development_time'=>student_workspace_text($input['development_time']??'',120),
            'agitation'=>student_workspace_text($input['agitation']??'',500),
        ];
    }else{
        $values=['notes'=>student_workspace_text($input['notes']??'',6000)];
    }
    $sets=[];$args=[];
    foreach($values as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
    $sets[]='updated_at=?';$args[]=utc_now();$args[]=$testId;$args[]=$studentId;
    $db->prepare('UPDATE student_tests SET '.implode(',',$sets).' WHERE id=? AND student_id=?')->execute($args);
    return student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');
}
function student_test_media_kind(string $kind): string {
    return in_array($kind,['scene','result'],true)?$kind:'result';
}
function student_test_media_by_kind(PDO $db,int $testId,string $kind): array {
    $q=$db->prepare('SELECT * FROM student_test_media WHERE test_id=? AND media_kind=? ORDER BY id');
    $q->execute([$testId,student_test_media_kind($kind)]);return $q->fetchAll();
}
function student_test_add_media_kind(PDO $db,int $testId,int $studentId,string $kind,array $file): array {
    $kind=student_test_media_kind($kind);
    $test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');
    if($test['status']==='reviewed')throw new RuntimeException('Este teste já foi revisado.');
    $count=$db->prepare('SELECT COUNT(*) FROM student_test_media WHERE test_id=?');$count->execute([$testId]);
    if((int)$count->fetchColumn()>=STUDENT_TEST_MEDIA_MAX_FILES)throw new RuntimeException('Cada teste aceita no máximo '.STUDENT_TEST_MEDIA_MAX_FILES.' imagens.');
    if((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Selecione uma imagem válida.');
    $size=(int)($file['size']??0);if($size<1||$size>STUDENT_TEST_MEDIA_MAX_BYTES)throw new RuntimeException('Cada imagem deve ter no máximo 12 MB.');
    $tmp=(string)($file['tmp_name']??'');$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=(string)$finfo->file($tmp);
    $ext=match($mime){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>''};
    if($ext==='')throw new RuntimeException('Formato de imagem não suportado. Use JPEG, PNG ou WebP.');
    $uuid=student_uuid();$relative=$uuid.'.'.$ext;$destination=student_test_media_storage_root().'/'.$relative;
    if(!move_uploaded_file($tmp,$destination)&&!@rename($tmp,$destination))throw new RuntimeException('Não foi possível armazenar a imagem.');
    @chmod($destination,0660);$now=utc_now();
    try{
        $db->prepare('INSERT INTO student_test_media(media_uuid,test_id,original_name,mime_type,byte_size,storage_path,created_at,media_kind) VALUES(?,?,?,?,?,?,?,?)')->execute([$uuid,(int)$test['id'],student_workspace_text($file['name']??'imagem',255),$mime,$size,$relative,$now,$kind]);
    }catch(Throwable $e){@unlink($destination);throw $e;}
    $q=$db->prepare('SELECT * FROM student_test_media WHERE id=?');$q->execute([(int)$db->lastInsertId()]);return $q->fetch();
}
function student_test_stage_state(array $test,array $sceneMedia,array $resultMedia): array {
    $hasExposure=trim((string)$test['film'])!==''||trim((string)$test['iso_reference'])!==''||trim((string)$test['aperture'])!==''||trim((string)$test['calculated_time'])!==''||$sceneMedia!==[];
    $hasDevelopment=trim((string)$test['developer'])!==''||trim((string)$test['dilution'])!==''||trim((string)$test['temperature'])!==''||trim((string)$test['development_time'])!==''||trim((string)$test['agitation'])!=='';
    $hasResult=trim((string)$test['notes'])!==''||$resultMedia!==[]||in_array((string)$test['status'],['submitted','needs_revision','reviewed'],true);
    return ['exposure'=>$hasExposure,'development'=>$hasDevelopment,'result'=>$hasResult];
}
