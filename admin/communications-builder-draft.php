<?php
declare(strict_types=1);
/** Dedicated experimental MJML storage; NEVER triggers a message delivery. */
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/communications_builder_drafts.php';
security_headers();require_admin();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store, max-age=0');
try {
    $db=database();
    $state=admin_activity_resolution($db);
    $activityId=(int)($state['activity']['id']??0);
    if($activityId<1)throw new RuntimeException('Área administrativa não encontrada.');
    $method=(string)($_SERVER['REQUEST_METHOD']??'GET');
    if($method==='GET') {
        $campaignId=(int)($_GET['campaign_id']??0);
        $result=communication_builder_draft_read($db,$activityId,$campaignId);
        echo json_encode(['ok'=>true,'draft'=>$result],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
        exit;
    }
    if($method!=='POST'){http_response_code(405);throw new RuntimeException('Método não permitido.');}
    if((int)($_SERVER['CONTENT_LENGTH']??0)>110000){http_response_code(413);throw new RuntimeException('O rascunho excede o tamanho permitido.');}
    if(!verify_csrf('communications-admin',$_POST['_csrf']??null)){
        http_response_code(403);throw new RuntimeException('Sessão expirada. Atualize a página.');
    }
    $campaignId=(int)($_POST['campaign_id']??0);
    $revision=filter_var((string)($_POST['revision']??''),FILTER_VALIDATE_INT,
        ['options'=>['min_range'=>0]]);
    if($revision===false)throw new RuntimeException('Número de revisão inválido.');
    $mediaRefs=json_decode((string)($_POST['media_refs']??'{}'),true,32,JSON_THROW_ON_ERROR);
    if(!is_array($mediaRefs)||count($mediaRefs)>12)throw new RuntimeException('Referências de imagem inválidas.');
    foreach($mediaRefs as $src=>$id){
        if(!is_string($src)||!is_int($id)||$id<1)throw new RuntimeException('Referência de imagem inválida.');
    }
    $saved=communication_builder_draft_write($db,$activityId,$campaignId,(string)($_POST['mjml']??''),$mediaRefs,(int)$revision);
    echo json_encode(['ok'=>true,'draft'=>$saved],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(Throwable $e){
    if(http_response_code()<400)http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>$e instanceof JsonException?'Referências inválidas.':$e->getMessage()],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
}
