<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();

$wantsJson=str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT']??'')),'application/json')
    ||strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH']??''))==='xmlhttprequest';
$jsonExit=static function(array $payload,int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
};
$fail=static function(string $message,int $status=400) use($wantsJson,$jsonExit): never {
    if($wantsJson)$jsonExit(['ok'=>false,'error'=>$message],$status);
    http_response_code($status);exit($message);
};

$db=database();$student=student_account_current($db);
if(!$student)$fail('Autenticação necessária.',401);
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')$fail('Método inválido.',405);

$returnTo=student_safe_next((string)($_POST['return_to']??'/aluno/'));
if(!verify_csrf('student-material-note',$_POST['_csrf']??null))$fail('Solicitação inválida.');
$pageId=(int)($_POST['page_id']??0);$page=cms_page_by_id($db,$pageId);
if(!$page)$fail('Material não encontrado.',404);
$activity=activity_by_id($db,(int)$page['activity_id']);
if(!$activity||!cms_access_page_allowed($db,$activity,$page,$student))$fail('Material indisponível.',403);
$cohortUuid=trim((string)($_POST['cohort']??''));$returnQuery=(string)(parse_url($returnTo,PHP_URL_QUERY)??'');
if($cohortUuid===''&&$returnQuery!==''){parse_str($returnQuery,$returnParams);$cohortUuid=trim((string)($returnParams['cohort']??''));}
$context=student_enrollment_material_context($db,$student,$page,$cohortUuid);
if(!$context)$context=student_account_page_context($db,$student,$page,$cohortUuid);
if(!$context)$fail('Material indisponível para esta matrícula.',403);

$resolveSection=static function(array $page,string $sectionKey): array {
    if($sectionKey==='pagina')return ['exists'=>true,'lesson_id'=>null];
    $document=cms_page_doc($page,true);$html=(string)($document['html']??'');
    $pattern='~<[^>]+data-cms-section=["\']'.preg_quote($sectionKey,'~').'["\'][^>]*>~i';
    if(!preg_match($pattern,$html,$match))return ['exists'=>false,'lesson_id'=>null];
    $lessonId=null;if(preg_match('~data-cms-lesson-id=["\'](\d+)["\']~i',(string)$match[0],$lessonMatch))$lessonId=(int)$lessonMatch[1];
    return ['exists'=>true,'lesson_id'=>$lessonId];
};
$annotationPayload=static function(?array $note): ?array {
    if(!$note)return null;
    return [
        'id'=>(int)$note['id'],
        'anchorType'=>(string)$note['anchor_type'],
        'sectionKey'=>(string)$note['section_key'],
        'body'=>(string)$note['body'],
        'quoteExact'=>(string)$note['quote_exact'],
        'quotePrefix'=>(string)$note['quote_prefix'],
        'quoteSuffix'=>(string)$note['quote_suffix'],
        'blockKey'=>(string)$note['block_key'],
        'start'=>$note['start_offset']===null?null:(int)$note['start_offset'],
        'end'=>$note['end_offset']===null?null:(int)$note['end_offset'],
        'sourceBlockHash'=>(string)$note['source_block_hash'],
        'sourcePageRevision'=>(string)$note['source_page_revision'],
    ];
};

try{
    $studentId=(int)$student['id'];$action=trim((string)($_POST['annotation_action']??''));$createQuestion=!empty($_POST['create_question'])||$action==='question';
    $questionPayload=null;$questionUrl='';
    $publishQuestion=static function(int $annotationId,array $note) use($db,$studentId,$context,$cohortUuid,&$questionPayload,&$questionUrl): void {
        $cohortId=(int)($context['cohort_id']??0);if($cohortId<1)throw new RuntimeException('Não foi possível identificar a turma desta dúvida.');
        $existing=student_question_from_annotation($db,$studentId,$annotationId);
        if($existing){$questionPayload=['id'=>(int)$existing['id'],'title'=>(string)($existing['title']??''),'status'=>(string)($existing['status']??'open')];}
        else{
            $title=student_workspace_text($_POST['question_title']??'',180);if($title==='')throw new RuntimeException('Dê um título à dúvida.');
            $visibility=(string)($_POST['question_visibility']??'private');if(!in_array($visibility,['private','cohort'],true))$visibility='private';
            $created=student_question_create($db,$studentId,$cohortId,['topic'=>'Material','title'=>$title,'body'=>(string)$note['body'],'visibility'=>$visibility,'test_id'=>'']);
            $questionId=(int)($created['id']??0);if($questionId<1)throw new RuntimeException('Não foi possível criar a dúvida.');
            student_question_attach_annotation($db,$questionId,$studentId,$cohortId,$annotationId);
            $questionPayload=['id'=>$questionId,'title'=>(string)($created['title']??$title),'status'=>(string)($created['status']??'open')];
        }
        $params=[];if($cohortUuid!=='')$params['cohort']=$cohortUuid;$params['id']=(int)$questionPayload['id'];$questionUrl='/aluno/duvidas.php?'.http_build_query($params);
    };
    if($action!==''){
        $annotationId=(int)($_POST['annotation_id']??0);$note=null;$transactional=$createQuestion&&in_array($action,['create_page','create_selection','question'],true);$started=$transactional&&!$db->inTransaction();if($started)$db->beginTransaction();
        try{
        if($action==='create_page'){
            $annotationId=student_material_annotation_create($db,$studentId,$pageId,null,(string)($_POST['body']??''),'page',['source_page_revision'=>(string)($_POST['source_page_revision']??'')]);
            $note=student_material_annotation_owned($db,$annotationId,$studentId,$pageId);
        }elseif($action==='create_selection'){
            $sectionKey=activity_slug((string)($_POST['section_key']??''));$resolved=$resolveSection($page,$sectionKey);
            if(!$resolved['exists'])throw new RuntimeException('O trecho selecionado não existe mais no material. Selecione-o novamente.');
            $annotationId=student_material_annotation_create($db,$studentId,$pageId,$resolved['lesson_id'],(string)($_POST['body']??''),'selection',$_POST);
            $note=student_material_annotation_owned($db,$annotationId,$studentId,$pageId);
        }elseif($action==='update'){
            student_material_annotation_update_body($db,$annotationId,$studentId,$pageId,(string)($_POST['body']??''));
            $note=student_material_annotation_owned($db,$annotationId,$studentId,$pageId);
        }elseif($action==='question'){
            student_material_annotation_update_body($db,$annotationId,$studentId,$pageId,(string)($_POST['body']??''));
            $note=student_material_annotation_owned($db,$annotationId,$studentId,$pageId);
        }elseif($action==='reanchor'){
            $sectionKey=activity_slug((string)($_POST['section_key']??''));$resolved=$resolveSection($page,$sectionKey);
            if(!$resolved['exists'])throw new RuntimeException('O novo trecho não existe mais no material. Selecione-o novamente.');
            student_material_annotation_reanchor($db,$annotationId,$studentId,$pageId,$resolved['lesson_id'],$_POST);
            $note=student_material_annotation_owned($db,$annotationId,$studentId,$pageId);
        }elseif($action==='detach'){
            $postedBody=(string)($_POST['body']??'');
            if(trim($postedBody)!=='')student_material_annotation_update_body($db,$annotationId,$studentId,$pageId,$postedBody);
            student_material_annotation_detach($db,$annotationId,$studentId,$pageId);
            $note=student_material_annotation_owned($db,$annotationId,$studentId,$pageId);
        }elseif($action==='remove'){
            student_material_annotation_delete($db,$annotationId,$studentId,$pageId);
        }else throw new RuntimeException('Ação de anotação inválida.');
        if($createQuestion){
            if(!$note)throw new RuntimeException('Anotação não encontrada para criar a dúvida.');
            $publishQuestion($annotationId,$note);
        }
        if($started)$db->commit();
        }catch(Throwable $e){if($started&&$db->inTransaction())$db->rollBack();throw $e;}
        if($wantsJson)$jsonExit(['ok'=>true,'action'=>$action,'annotation_id'=>$annotationId,'annotation'=>$annotationPayload($note),'question'=>$questionPayload,'question_url'=>$questionUrl]);
        header('Location: '.$returnTo,true,303);exit;
    }

    /* Compatibility path for notes created before inline annotations existed. */
    $sectionKey=activity_slug((string)($_POST['section_key']??''));
    if($sectionKey==='')throw new RuntimeException('Contexto da anotação não encontrado.');
    $resolved=$resolveSection($page,$sectionKey);$remove=!empty($_POST['remove']);
    if(!$resolved['exists']&&$remove){$body='';$lessonId=null;}
    elseif(!$resolved['exists'])throw new RuntimeException('O trecho original desta anotação não existe mais. Crie uma nova anotação sobre o texto atual.');
    else{$body=$remove?'':(string)($_POST['body']??'');$lessonId=$resolved['lesson_id'];}
    student_material_note_save($db,$studentId,$pageId,$sectionKey,$lessonId,$body);
    if($wantsJson)$jsonExit(['ok'=>true,'legacy'=>true,'removed'=>$remove,'section_key'=>$sectionKey]);
    header('Location: '.$returnTo,true,303);exit;
}catch(RuntimeException $e){
    if($wantsJson)$jsonExit(['ok'=>false,'error'=>$e->getMessage()],400);
    http_response_code(400);echo h($e->getMessage());
}
