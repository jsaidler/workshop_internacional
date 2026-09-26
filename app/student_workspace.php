<?php
declare(strict_types=1);

const STUDENT_IMPORT_MAX_BYTES=5242880;
const STUDENT_IMPORT_MAX_ROWS=2000;
const STUDENT_TEST_MEDIA_MAX_BYTES=12582912;
const STUDENT_TEST_MEDIA_MAX_FILES=6;

function student_workspace_text(mixed $value,int $max=5000): string {
    $value=trim((string)$value);
    return mb_strlen($value)>$max?mb_substr($value,0,$max):$value;
}
function student_workspace_date(mixed $value): ?string {
    $value=trim((string)$value);if($value==='')return null;
    $d=DateTimeImmutable::createFromFormat('!Y-m-d',$value);
    if(!$d||$d->format('Y-m-d')!==$value)throw new RuntimeException('Data inválida. Use o formato AAAA-MM-DD.');
    return $value;
}
function course_update_cohort_details(PDO $db,int $activityId,int $cohortId,array $input): array {
    $cohort=course_cohort_by_id($db,$cohortId);
    if(!$cohort||(int)$cohort['activity_id']!==$activityId)throw new RuntimeException('Turma inválida.');
    $title=student_workspace_text($input['title']??'',160);if($title==='')throw new RuntimeException('Informe o nome da turma.');
    $slug=course_cohort_slug((string)($input['slug']??$title));
    $status=(string)($input['status']??$cohort['status']);if(!in_array($status,['active','closed','archived'],true))throw new RuntimeException('Estado inválido.');
    $starts=student_workspace_date($input['starts_at']??null);$ends=student_workspace_date($input['ends_at']??null);
    if($starts!==null&&$ends!==null&&$starts>$ends)throw new RuntimeException('A data final não pode ser anterior à data inicial.');
    $notes=student_workspace_text($input['notes']??'',4000);$makeDefault=!empty($input['make_default']);$now=utc_now();
    $slugCheck=$db->prepare('SELECT id FROM course_cohorts WHERE activity_id=? AND slug=? AND id!=?');$slugCheck->execute([$activityId,$slug,$cohortId]);if($slugCheck->fetchColumn())throw new RuntimeException('Já existe uma turma com este slug.');
    if($status==='archived'&&$makeDefault)throw new RuntimeException('Uma turma arquivada não pode ser a turma padrão.');
    $db->beginTransaction();
    try{
        if($makeDefault)$db->prepare('UPDATE course_cohorts SET is_registration_default=0,updated_at=? WHERE activity_id=?')->execute([$now,$activityId]);
        $default=$status==='archived'?0:($makeDefault?1:(int)$cohort['is_registration_default']);
        $db->prepare('UPDATE course_cohorts SET title=?,slug=?,status=?,is_registration_default=?,starts_at=?,ends_at=?,notes=?,updated_at=? WHERE id=? AND activity_id=?')->execute([$title,$slug,$status,$default,$starts,$ends,$notes,$now,$cohortId,$activityId]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    if($status==='archived'&&!course_default_cohort($db,$activityId,false))course_default_cohort($db,$activityId,true);
    return course_cohort_by_id($db,$cohortId)??throw new RuntimeException('Turma não encontrada após a atualização.');
}

function student_import_header_key(string $value): string {
    $value=trim($value);$value=preg_replace('/^\xEF\xBB\xBF/','',$value)??$value;
    if(function_exists('iconv')){$ascii=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value);if(is_string($ascii))$value=$ascii;}
    $value=strtolower($value);return preg_replace('/[^a-z0-9]+/','',$value)??'';
}
function student_import_header_aliases(): array {
    return [
        'name'=>['nome','name','aluno','aluna','nomecompleto'],
        'email'=>['email','emaildoaluno','emaildaaluna','correioeletronico'],
        'cpf'=>['cpf','documento','cpfdoaluno','cpfdaaluna'],
        'phone'=>['telefone','phone','celular','whatsapp','fone'],
        'instagram'=>['instagram','insta','perfilinstagram'],
        'address'=>['endereco','address','logradouro'],
        'city_state'=>['cidadeuf','cidadeestado','citystate','cidade'],
        'postal_code'=>['cep','postalcode','codigopostal'],
    ];
}
function student_import_detect_columns(array $header): array {
    $normalized=[];foreach($header as $i=>$label)$normalized[$i]=student_import_header_key((string)$label);
    $found=[];foreach(student_import_header_aliases() as $field=>$aliases){foreach($normalized as $i=>$key)if(in_array($key,$aliases,true)){$found[$field]=$i;break;}}
    foreach(['name','email','cpf'] as $required)if(!array_key_exists($required,$found))throw new RuntimeException('A planilha precisa ter as colunas Nome, E-mail e CPF.');
    return $found;
}
function student_import_csv_rows(string $path): array {
    $handle=fopen($path,'rb');if(!$handle)throw new RuntimeException('Não foi possível abrir a planilha.');
    try{
        $sample=(string)fgets($handle);rewind($handle);$counts=[';'=>substr_count($sample,';'),','=>substr_count($sample,','),"\t"=>substr_count($sample,"\t")];arsort($counts);$delimiter=(string)array_key_first($counts);if(($counts[$delimiter]??0)===0)$delimiter=';';
        $rows=[];while(($row=fgetcsv($handle,0,$delimiter))!==false){$rows[]=array_map(static fn($v)=>trim((string)$v),$row);if(count($rows)>STUDENT_IMPORT_MAX_ROWS+1)throw new RuntimeException('A planilha excede o limite de '.STUDENT_IMPORT_MAX_ROWS.' alunos por importação.');}return $rows;
    }finally{fclose($handle);}
}
function student_import_xlsx_cell_text(DOMElement $cell,array $shared,DOMXPath $xpath): string {
    $type=$cell->getAttribute('t');
    if($type==='inlineStr'){$parts=[];foreach($xpath->query('.//*[local-name()="t"]',$cell)?:[] as $node)$parts[]=$node->textContent;return trim(implode('',$parts));}
    $v=$xpath->query('./*[local-name()="v"]',$cell)?->item(0)?->textContent??'';
    if($type==='s')return trim((string)($shared[(int)$v]??''));
    if($type==='b')return $v==='1'?'1':'0';return trim($v);
}
function student_import_xlsx_rows(string $path): array {
    if(!class_exists('ZipArchive'))throw new RuntimeException('Este servidor não possui suporte a XLSX. Exporte a planilha como CSV e tente novamente.');
    $zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('Não foi possível abrir o arquivo XLSX.');
    try{
        $shared=[];$sharedXml=$zip->getFromName('xl/sharedStrings.xml');
        if(is_string($sharedXml)){$dom=new DOMDocument();if(@$dom->loadXML($sharedXml)){foreach($dom->getElementsByTagName('si') as $si){$text='';foreach($si->getElementsByTagName('t') as $t)$text.=$t->textContent;$shared[]=$text;}}}
        $sheet=$zip->getFromName('xl/worksheets/sheet1.xml');if(!is_string($sheet))throw new RuntimeException('A primeira aba da planilha não pôde ser lida.');
        $dom=new DOMDocument();if(!@$dom->loadXML($sheet))throw new RuntimeException('O arquivo XLSX está corrompido.');$xpath=new DOMXPath($dom);$rows=[];
        foreach($xpath->query('//*[local-name()="sheetData"]/*[local-name()="row"]')?:[] as $rowNode){
            if(!$rowNode instanceof DOMElement)continue;$row=[];
            foreach($xpath->query('./*[local-name()="c"]',$rowNode)?:[] as $cell){if(!$cell instanceof DOMElement)continue;$ref=$cell->getAttribute('r');preg_match('/^([A-Z]+)/i',$ref,$m);$letters=strtoupper((string)($m[1]??'A'));$index=0;for($i=0;$i<strlen($letters);$i++)$index=$index*26+(ord($letters[$i])-64);$row[$index-1]=student_import_xlsx_cell_text($cell,$shared,$xpath);}
            if($row){$max=max(array_keys($row));$rows[]=array_map(static fn($i)=>(string)($row[$i]??''),range(0,$max));if(count($rows)>STUDENT_IMPORT_MAX_ROWS+1)throw new RuntimeException('A planilha excede o limite de '.STUDENT_IMPORT_MAX_ROWS.' alunos por importação.');}
        }
        return $rows;
    }finally{$zip->close();}
}
function student_import_rows_from_upload(array $file): array {
    if((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Selecione uma planilha válida.');
    $size=(int)($file['size']??0);if($size<1||$size>STUDENT_IMPORT_MAX_BYTES)throw new RuntimeException('A planilha deve ter no máximo 5 MB.');
    $path=(string)($file['tmp_name']??'');$name=(string)($file['name']??'planilha');$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
    if(in_array($ext,['csv','txt','tsv'],true))return student_import_csv_rows($path);
    if($ext==='xlsx')return student_import_xlsx_rows($path);
    throw new RuntimeException('Formato não suportado. Envie CSV ou XLSX.');
}
function student_import_payload_from_row(array $row,array $columns): array {
    $out=[];foreach($columns as $field=>$index)$out[$field]=trim((string)($row[$index]??''));return $out;
}
function student_import_upsert_student(PDO $db,int $cohortId,array $payload): array {
    $name=student_workspace_text($payload['name']??'',180);$email=student_normalize_email((string)($payload['email']??''));$cpf=student_account_normalize_cpf((string)($payload['cpf']??''));
    if($name==='')throw new RuntimeException('Nome ausente.');if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('E-mail inválido.');if(strlen($cpf)!==11)throw new RuntimeException('CPF precisa ter 11 dígitos.');
    $hash=student_account_cpf_lookup_hash($cpf);$user=student_account_find_identity($db,$email,$hash);$now=utc_now();$created=false;
    if(!$user){
        $db->beginTransaction();try{
            $db->prepare("INSERT INTO student_users(user_uuid,name,email,password_hash,status,must_change_password,last_login_at,created_at,updated_at,activated_at,privacy_ack_at,privacy_notice_version) VALUES(?,?,?,?, 'active',1,NULL,?,?,NULL,NULL,'')")->execute([student_uuid(),$name,$email,student_account_random_unusable_hash(),$now,$now]);$studentId=(int)$db->lastInsertId();
            $db->prepare('INSERT INTO student_profiles(student_id,cpf_lookup_hash,cpf_ciphertext,cpf_last4,phone,instagram,address,city_state,postal_code,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute([$studentId,$hash,student_account_encrypt_cpf($cpf),substr($cpf,-4),student_workspace_text($payload['phone']??'',80),student_workspace_text($payload['instagram']??'',120),student_workspace_text($payload['address']??'',500),student_workspace_text($payload['city_state']??'',180),student_workspace_text($payload['postal_code']??'',32),$now,$now]);
            $db->commit();$created=true;
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    }else{
        $studentId=(int)$user['id'];
        if(empty($user['activated_at'])){
            $existing=student_account_profile_values($db,$studentId);$merged=['name'=>$name,'email'=>$email,'cpf'=>$cpf];foreach(['phone','instagram','address','city_state','postal_code'] as $field)$merged[$field]=trim((string)($payload[$field]??''))!==''?(string)$payload[$field]:(string)($existing[$field]??'');student_account_update_profile($db,$studentId,$merged);
        }
        $db->prepare("UPDATE student_users SET status='active',updated_at=? WHERE id=?")->execute([$now,$studentId]);
    }
    $db->prepare("INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,source_submission_id,status,confirmed_at,created_at,updated_at) VALUES(?,?,?,NULL,'active',?,?,?) ON CONFLICT(student_id,cohort_id) DO UPDATE SET status='active',updated_at=excluded.updated_at")->execute([student_uuid(),$studentId,$cohortId,$now,$now,$now]);
    return ['student_id'=>$studentId,'created'=>$created,'name'=>$name,'email'=>$email];
}
function student_import_historical_students(PDO $db,int $activityId,int $cohortId,array $file): array {
    $cohort=course_cohort_by_id($db,$cohortId);if(!$cohort||(int)$cohort['activity_id']!==$activityId||$cohort['status']==='archived')throw new RuntimeException('Escolha uma turma válida.');
    $rows=student_import_rows_from_upload($file);if(count($rows)<2)throw new RuntimeException('A planilha não possui alunos para importar.');$header=array_shift($rows);$columns=student_import_detect_columns($header);$now=utc_now();
    $db->prepare('INSERT INTO student_import_batches(import_uuid,cohort_id,original_name,created_at) VALUES(?,?,?,?)')->execute([student_uuid(),$cohortId,student_workspace_text($file['name']??'planilha',255),$now]);$batchId=(int)$db->lastInsertId();$total=0;$imported=0;$existing=0;$errors=0;$errorList=[];
    $insertRow=$db->prepare('INSERT INTO student_import_rows(batch_id,row_number,student_id,name,email,status,message,created_at) VALUES(?,?,?,?,?,?,?,?)');
    foreach($rows as $offset=>$row){$payload=student_import_payload_from_row($row,$columns);if(implode('',array_values($payload))==='')continue;$total++;$rowNumber=$offset+2;
        try{$result=student_import_upsert_student($db,$cohortId,$payload);$status=$result['created']?'imported':'existing';$result['created']?$imported++:$existing++;$insertRow->execute([$batchId,$rowNumber,(int)$result['student_id'],(string)$result['name'],(string)$result['email'],$status,'',$now]);}
        catch(Throwable $e){$errors++;$message=$e->getMessage()==='student_identity_conflict'?'E-mail e CPF pertencem a contas diferentes. Revise esta linha.':$e->getMessage();$errorList[]=['row'=>$rowNumber,'name'=>(string)($payload['name']??''),'email'=>(string)($payload['email']??''),'message'=>$message];$insertRow->execute([$batchId,$rowNumber,null,student_workspace_text($payload['name']??'',180),student_workspace_text($payload['email']??'',180),'error',student_workspace_text($message,500),$now]);}
    }
    $db->prepare('UPDATE student_import_batches SET total_rows=?,imported_rows=?,existing_rows=?,error_rows=? WHERE id=?')->execute([$total,$imported,$existing,$errors,$batchId]);
    return ['batch_id'=>$batchId,'total'=>$total,'imported'=>$imported,'existing'=>$existing,'errors'=>$errors,'error_list'=>$errorList];
}
function student_import_batches(PDO $db,int $activityId,int $limit=20): array {$q=$db->prepare('SELECT b.*,c.title cohort_title FROM student_import_batches b JOIN course_cohorts c ON c.id=b.cohort_id WHERE c.activity_id=? ORDER BY b.id DESC LIMIT ?');$q->bindValue(1,$activityId,PDO::PARAM_INT);$q->bindValue(2,$limit,PDO::PARAM_INT);$q->execute();return $q->fetchAll();}
function student_import_batch_rows(PDO $db,int $batchId,int $activityId): array {$q=$db->prepare('SELECT r.* FROM student_import_rows r JOIN student_import_batches b ON b.id=r.batch_id JOIN course_cohorts c ON c.id=b.cohort_id WHERE r.batch_id=? AND c.activity_id=? ORDER BY r.row_number');$q->execute([$batchId,$activityId]);return $q->fetchAll();}

function student_test_status_label(string $status): string {return match($status){'draft'=>'Rascunho','submitted'=>'Aguardando avaliação','needs_revision'=>'Ajustes solicitados','reviewed'=>'Revisado',default=>$status};}
function student_test_assert_enrollment(PDO $db,int $studentId,int $cohortId): array {$q=$db->prepare("SELECT e.*,c.activity_id,c.title cohort_title,a.public_title FROM course_enrollments e JOIN course_cohorts c ON c.id=e.cohort_id JOIN activities a ON a.id=c.activity_id WHERE e.student_id=? AND e.cohort_id=? AND e.status='active' AND c.status!='archived' LIMIT 1");$q->execute([$studentId,$cohortId]);return $q->fetch()?:throw new RuntimeException('Matrícula inválida para este teste.');}
function student_test_fields(array $input): array {
    $title=student_workspace_text($input['title']??'',180);if($title==='')throw new RuntimeException('Dê um título ao teste para poder encontrá-lo depois.');
    return ['title'=>$title,'test_date'=>student_workspace_date($input['test_date']??null),'film'=>student_workspace_text($input['film']??'',180),'lot'=>student_workspace_text($input['lot']??'',120),'iso_reference'=>student_workspace_text($input['iso_reference']??'',80),'aperture'=>student_workspace_text($input['aperture']??'',80),'calculated_time'=>student_workspace_text($input['calculated_time']??'',120),'reciprocity_time'=>student_workspace_text($input['reciprocity_time']??'',120),'light_condition'=>student_workspace_text($input['light_condition']??'',800),'tonal_range'=>student_workspace_text($input['tonal_range']??'',800),'developer'=>student_workspace_text($input['developer']??'',180),'dilution'=>student_workspace_text($input['dilution']??'',120),'temperature'=>student_workspace_text($input['temperature']??'',80),'development_time'=>student_workspace_text($input['development_time']??'',120),'agitation'=>student_workspace_text($input['agitation']??'',500),'notes'=>student_workspace_text($input['notes']??'',6000)];
}
function student_test_create(PDO $db,int $studentId,int $cohortId,array $input): array {student_test_assert_enrollment($db,$studentId,$cohortId);$f=student_test_fields($input);$now=utc_now();$db->prepare('INSERT INTO student_tests(test_uuid,student_id,cohort_id,title,test_date,film,lot,iso_reference,aperture,calculated_time,reciprocity_time,light_condition,tonal_range,developer,dilution,temperature,development_time,agitation,notes,status,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"draft",?,?)')->execute([student_uuid(),$studentId,$cohortId,$f['title'],$f['test_date'],$f['film'],$f['lot'],$f['iso_reference'],$f['aperture'],$f['calculated_time'],$f['reciprocity_time'],$f['light_condition'],$f['tonal_range'],$f['developer'],$f['dilution'],$f['temperature'],$f['development_time'],$f['agitation'],$f['notes'],$now,$now]);return student_test_for_student($db,(int)$db->lastInsertId(),$studentId)??throw new RuntimeException('Não foi possível criar o teste.');}
function student_test_for_student(PDO $db,int $testId,int $studentId): ?array {$q=$db->prepare('SELECT t.*,c.title cohort_title,c.activity_id,a.public_title FROM student_tests t JOIN course_cohorts c ON c.id=t.cohort_id JOIN activities a ON a.id=c.activity_id WHERE t.id=? AND t.student_id=? LIMIT 1');$q->execute([$testId,$studentId]);return $q->fetch()?:null;}
function student_test_for_admin(PDO $db,int $testId,int $activityId): ?array {$q=$db->prepare('SELECT t.*,c.title cohort_title,c.activity_id,u.name student_name,u.email student_email,a.public_title FROM student_tests t JOIN course_cohorts c ON c.id=t.cohort_id JOIN student_users u ON u.id=t.student_id JOIN activities a ON a.id=c.activity_id WHERE t.id=? AND c.activity_id=? LIMIT 1');$q->execute([$testId,$activityId]);return $q->fetch()?:null;}
function student_tests_for_student(PDO $db,int $studentId): array {$q=$db->prepare('SELECT t.*,c.title cohort_title,a.public_title FROM student_tests t JOIN course_cohorts c ON c.id=t.cohort_id JOIN activities a ON a.id=c.activity_id WHERE t.student_id=? ORDER BY t.updated_at DESC,t.id DESC');$q->execute([$studentId]);return $q->fetchAll();}
function student_tests_for_admin(PDO $db,int $activityId,int $cohortId=0,string $status='',string $search=''): array {$where=['c.activity_id=?'];$args=[$activityId];if($cohortId>0){$where[]='c.id=?';$args[]=$cohortId;}if(in_array($status,['draft','submitted','needs_revision','reviewed'],true)){$where[]='t.status=?';$args[]=$status;}if(trim($search)!==''){$where[]='(u.name LIKE ? OR u.email LIKE ? OR t.title LIKE ?)';$like='%'.trim($search).'%';array_push($args,$like,$like,$like);} $q=$db->prepare('SELECT t.*,c.title cohort_title,u.name student_name,u.email student_email FROM student_tests t JOIN course_cohorts c ON c.id=t.cohort_id JOIN student_users u ON u.id=t.student_id WHERE '.implode(' AND ',$where).' ORDER BY CASE t.status WHEN "submitted" THEN 0 WHEN "needs_revision" THEN 1 WHEN "draft" THEN 2 ELSE 3 END,t.updated_at DESC,t.id DESC LIMIT 300');$q->execute($args);return $q->fetchAll();}
function student_test_update(PDO $db,int $testId,int $studentId,array $input): array {$test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');if($test['status']==='reviewed')throw new RuntimeException('Este teste já foi revisado. Envie uma dúvida no histórico se precisar continuar a conversa.');student_test_assert_enrollment($db,$studentId,(int)$test['cohort_id']);$f=student_test_fields($input);$db->prepare('UPDATE student_tests SET title=?,test_date=?,film=?,lot=?,iso_reference=?,aperture=?,calculated_time=?,reciprocity_time=?,light_condition=?,tonal_range=?,developer=?,dilution=?,temperature=?,development_time=?,agitation=?,notes=?,updated_at=? WHERE id=? AND student_id=?')->execute([$f['title'],$f['test_date'],$f['film'],$f['lot'],$f['iso_reference'],$f['aperture'],$f['calculated_time'],$f['reciprocity_time'],$f['light_condition'],$f['tonal_range'],$f['developer'],$f['dilution'],$f['temperature'],$f['development_time'],$f['agitation'],$f['notes'],utc_now(),$testId,$studentId]);return student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');}
function student_test_submit(PDO $db,int $testId,int $studentId): void {$test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');if($test['status']==='reviewed')throw new RuntimeException('Este teste já foi revisado.');$now=utc_now();$db->prepare("UPDATE student_tests SET status='submitted',submitted_at=COALESCE(submitted_at,?),reviewed_at=NULL,updated_at=? WHERE id=? AND student_id=?")->execute([$now,$now,$testId,$studentId]);}
function student_test_set_review_status(PDO $db,int $testId,int $activityId,string $status): void {if(!in_array($status,['submitted','needs_revision','reviewed'],true))throw new RuntimeException('Estado de avaliação inválido.');$test=student_test_for_admin($db,$testId,$activityId)??throw new RuntimeException('Teste inválido.');$now=utc_now();$db->prepare('UPDATE student_tests SET status=?,reviewed_at=?,updated_at=? WHERE id=?')->execute([$status,$status==='reviewed'?$now:null,$now,(int)$test['id']]);}
function student_test_messages(PDO $db,int $testId): array {$q=$db->prepare('SELECT m.*,u.name student_name FROM student_test_messages m LEFT JOIN student_users u ON u.id=m.student_id WHERE m.test_id=? ORDER BY m.id');$q->execute([$testId]);return $q->fetchAll();}
function student_test_add_student_message(PDO $db,int $testId,int $studentId,string $body): void {$test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');$body=student_workspace_text($body,4000);if($body==='')throw new RuntimeException('Escreva a dúvida ou comentário.');$db->prepare('INSERT INTO student_test_messages(message_uuid,test_id,author_role,student_id,body,created_at) VALUES(?,?,"student",?,?,?)')->execute([student_uuid(),(int)$test['id'],$studentId,$body,utc_now()]);$db->prepare('UPDATE student_tests SET updated_at=? WHERE id=?')->execute([utc_now(),(int)$test['id']]);}
function student_test_add_admin_message(PDO $db,int $testId,int $activityId,string $body): void {$test=student_test_for_admin($db,$testId,$activityId)??throw new RuntimeException('Teste inválido.');$body=student_workspace_text($body,4000);if($body==='')throw new RuntimeException('Escreva o comentário para o aluno.');$db->prepare('INSERT INTO student_test_messages(message_uuid,test_id,author_role,student_id,body,created_at) VALUES(?,?,"admin",NULL,?,?)')->execute([student_uuid(),(int)$test['id'],$body,utc_now()]);$db->prepare('UPDATE student_tests SET updated_at=? WHERE id=?')->execute([utc_now(),(int)$test['id']]);}
function student_test_media(PDO $db,int $testId): array {$q=$db->prepare('SELECT * FROM student_test_media WHERE test_id=? ORDER BY id');$q->execute([$testId]);return $q->fetchAll();}
function student_test_media_storage_root(): string {$root=dirname(__DIR__).'/storage/student-test-media';if(!is_dir($root)&&!@mkdir($root,0770,true)&&!is_dir($root))throw new RuntimeException('Não foi possível preparar o armazenamento das imagens.');return $root;}
function student_test_add_media(PDO $db,int $testId,int $studentId,array $file): array {$test=student_test_for_student($db,$testId,$studentId)??throw new RuntimeException('Teste não encontrado.');$count=$db->prepare('SELECT COUNT(*) FROM student_test_media WHERE test_id=?');$count->execute([$testId]);if((int)$count->fetchColumn()>=STUDENT_TEST_MEDIA_MAX_FILES)throw new RuntimeException('Cada teste aceita no máximo '.STUDENT_TEST_MEDIA_MAX_FILES.' imagens.');if((int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new RuntimeException('Selecione uma imagem válida.');$size=(int)($file['size']??0);if($size<1||$size>STUDENT_TEST_MEDIA_MAX_BYTES)throw new RuntimeException('Cada imagem deve ter no máximo 12 MB.');$tmp=(string)($file['tmp_name']??'');$finfo=new finfo(FILEINFO_MIME_TYPE);$mime=(string)$finfo->file($tmp);$ext=match($mime){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>''};if($ext==='')throw new RuntimeException('Formato de imagem não suportado. Use JPEG, PNG ou WebP.');$uuid=student_uuid();$relative=$uuid.'.'.$ext;$destination=student_test_media_storage_root().'/'.$relative;if(!move_uploaded_file($tmp,$destination)&&!@rename($tmp,$destination))throw new RuntimeException('Não foi possível armazenar a imagem.');@chmod($destination,0660);$now=utc_now();try{$db->prepare('INSERT INTO student_test_media(media_uuid,test_id,original_name,mime_type,byte_size,storage_path,created_at) VALUES(?,?,?,?,?,?,?)')->execute([$uuid,(int)$test['id'],student_workspace_text($file['name']??'imagem',255),$mime,$size,$relative,$now]);}catch(Throwable $e){@unlink($destination);throw $e;}$q=$db->prepare('SELECT * FROM student_test_media WHERE id=?');$q->execute([(int)$db->lastInsertId()]);return $q->fetch();}
function student_test_media_for_student(PDO $db,int $mediaId,int $studentId): ?array {$q=$db->prepare('SELECT m.*,t.student_id,t.cohort_id FROM student_test_media m JOIN student_tests t ON t.id=m.test_id WHERE m.id=? AND t.student_id=?');$q->execute([$mediaId,$studentId]);return $q->fetch()?:null;}
function student_test_media_for_admin(PDO $db,int $mediaId,int $activityId): ?array {$q=$db->prepare('SELECT m.*,t.student_id,t.cohort_id,c.activity_id FROM student_test_media m JOIN student_tests t ON t.id=m.test_id JOIN course_cohorts c ON c.id=t.cohort_id WHERE m.id=? AND c.activity_id=?');$q->execute([$mediaId,$activityId]);return $q->fetch()?:null;}
function student_test_media_absolute_path(array $media): string {$relative=basename((string)$media['storage_path']);return student_test_media_storage_root().'/'.$relative;}
function student_test_delete_media(PDO $db,int $mediaId,int $studentId): void {$media=student_test_media_for_student($db,$mediaId,$studentId)??throw new RuntimeException('Imagem inválida.');$path=student_test_media_absolute_path($media);$db->prepare('DELETE FROM student_test_media WHERE id=?')->execute([$mediaId]);if(is_file($path))@unlink($path);}
