<?php
declare(strict_types=1);
/**
 * Explicit administrator-only visual test send. Never queues or sends to students.
 * The client submits MJML and media IDs; trusted HTML is generated in PHP.
 */
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/communications_builder_test_send.php';
security_headers();require_admin();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store, max-age=0');
try{
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){
        http_response_code(405);throw new RuntimeException('Método não permitido.');
    }
    if((int)($_SERVER['CONTENT_LENGTH']??0)>110000){
        http_response_code(413);throw new RuntimeException('Documento muito extenso.');
    }
    if(!verify_csrf('communications-admin',$_POST['_csrf']??null)){
        http_response_code(403);throw new RuntimeException('Sessão expirada. Atualize a página.');
    }
    $db=database();
    $state=admin_activity_resolution($db);
    $activityId=(int)($state['activity']['id']??0);
    if($activityId<1)throw new RuntimeException('Área administrativa não encontrada.');
    $refs=json_decode((string)($_POST['media_refs']??'{}'),true,32,JSON_THROW_ON_ERROR);
    if(!is_array($refs)||count($refs)>12)throw new RuntimeException('Imagens do rascunho inválidas.');
    foreach($refs as $url=>$id)if(!is_string($url)||!is_int($id)||$id<1)
        throw new RuntimeException('Referência da Biblioteca inválida.');

    // Rate-limit both accepted and failed attempts to prevent accidental repeats.
    $now=time();
    if($now-(int)($_SESSION['communications_visual_test_last_at']??0)<30){
        http_response_code(429);throw new RuntimeException('Aguarde 30 segundos antes de enviar outro teste.');
    }
    $_SESSION['communications_visual_test_last_at']=$now;
    $result=communication_builder_send_admin_test(
        $db,$activityId,max(0,(int)($_POST['campaign_id']??0)),(string)($_POST['mjml']??''),$refs
    );
    echo json_encode(['ok'=>true,'test'=>$result],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(Throwable $e){
    if(http_response_code()<400)http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>$e instanceof JsonException?'Referências inválidas.':$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}
