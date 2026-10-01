<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);
if(!$student){http_response_code(401);exit('Autenticação necessária.');}
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);exit;}

$returnTo=student_safe_next((string)($_POST['return_to']??'/aluno/'));
if(!verify_csrf('student-material-note',$_POST['_csrf']??null)){http_response_code(400);exit('Solicitação inválida.');}
$pageId=(int)($_POST['page_id']??0);$page=cms_page_by_id($db,$pageId);
if(!$page){http_response_code(404);exit('Material não encontrado.');}
$activity=activity_by_id($db,(int)$page['activity_id']);
if(!$activity||!cms_access_page_allowed($db,$activity,$page,$student)){http_response_code(403);exit('Material indisponível.');}
$cohortUuid='';$returnQuery=(string)(parse_url($returnTo,PHP_URL_QUERY)??'');
if($returnQuery!==''){parse_str($returnQuery,$returnParams);$cohortUuid=trim((string)($returnParams['cohort']??''));}
$context=student_enrollment_material_context($db,$student,$page,$cohortUuid);
if(!$context)$context=student_account_page_context($db,$student,$page,$cohortUuid);
if(!$context){http_response_code(403);exit('Material indisponível para esta matrícula.');}

$resolveSection=static function(array $page,string $sectionKey): array {
    if($sectionKey==='pagina')return ['exists'=>true,'lesson_id'=>null];
    $document=cms_page_doc($page,true);$html=(string)($document['html']??'');
    $pattern='~<[^>]+data-cms-section=["\']'.preg_quote($sectionKey,'~').'["\'][^>]*>~i';
    if(!preg_match($pattern,$html,$match))return ['exists'=>false,'lesson_id'=>null];
    $lessonId=null;if(preg_match('~data-cms-lesson-id=["\'](\d+)["\']~i',(string)$match[0],$lessonMatch))$lessonId=(int)$lessonMatch[1];
    return ['exists'=>true,'lesson_id'=>$lessonId];
};

try{
    $studentId=(int)$student['id'];$action=trim((string)($_POST['annotation_action']??''));
    if($action!==''){
        $annotationId=(int)($_POST['annotation_id']??0);
        if($action==='create_page'){
            student_material_annotation_create($db,$studentId,$pageId,null,(string)($_POST['body']??''),'page',['source_page_revision'=>(string)($_POST['source_page_revision']??'')]);
        }elseif($action==='create_selection'){
            $sectionKey=activity_slug((string)($_POST['section_key']??''));$resolved=$resolveSection($page,$sectionKey);
            if(!$resolved['exists'])throw new RuntimeException('O trecho selecionado não existe mais no material. Selecione-o novamente.');
            student_material_annotation_create($db,$studentId,$pageId,$resolved['lesson_id'],(string)($_POST['body']??''),'selection',$_POST);
        }elseif($action==='update'){
            student_material_annotation_update_body($db,$annotationId,$studentId,$pageId,(string)($_POST['body']??''));
        }elseif($action==='reanchor'){
            $sectionKey=activity_slug((string)($_POST['section_key']??''));$resolved=$resolveSection($page,$sectionKey);
            if(!$resolved['exists'])throw new RuntimeException('O novo trecho não existe mais no material. Selecione-o novamente.');
            student_material_annotation_reanchor($db,$annotationId,$studentId,$pageId,$resolved['lesson_id'],$_POST);
        }elseif($action==='detach'){
            $postedBody=(string)($_POST['body']??'');
            if(trim($postedBody)!=='')student_material_annotation_update_body($db,$annotationId,$studentId,$pageId,$postedBody);
            student_material_annotation_detach($db,$annotationId,$studentId,$pageId);
        }elseif($action==='remove'){
            student_material_annotation_delete($db,$annotationId,$studentId,$pageId);
        }else throw new RuntimeException('Ação de anotação inválida.');
        header('Location: '.$returnTo,true,303);exit;
    }

    /* Compatibility path for notes created before inline annotations existed. */
    $sectionKey=activity_slug((string)($_POST['section_key']??''));
    if($sectionKey==='')throw new RuntimeException('Contexto da anotação não encontrado.');
    $resolved=$resolveSection($page,$sectionKey);
    if(!$resolved['exists']&&!empty($_POST['remove'])){$body='';$lessonId=null;}
    elseif(!$resolved['exists'])throw new RuntimeException('O trecho original desta anotação não existe mais. Crie uma nova anotação sobre o texto atual.');
    else{$body=!empty($_POST['remove'])?'':(string)($_POST['body']??'');$lessonId=$resolved['lesson_id'];}
    student_material_note_save($db,$studentId,$pageId,$sectionKey,$lessonId,$body);
    header('Location: '.$returnTo,true,303);exit;
}catch(RuntimeException $e){
    http_response_code(400);echo h($e->getMessage());
}
