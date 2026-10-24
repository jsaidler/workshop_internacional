<?php
declare(strict_types=1);
/** Server-owned experimental rendering; read only, no campaign writes or sends. */
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/communications_builder_renderer.php';
security_headers();require_admin();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: private, no-store, max-age=0');
try{
    if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);throw new RuntimeException('Método não permitido.');}
    if((int)($_SERVER['CONTENT_LENGTH']??0)>110000){http_response_code(413);throw new RuntimeException('Documento muito extenso.');}
    if(!verify_csrf('communications-admin',$_POST['_csrf']??null)){http_response_code(403);throw new RuntimeException('Sessão expirada. Atualize a página.');}
    $refs=json_decode((string)($_POST['media_refs']??'{}'),true,32,JSON_THROW_ON_ERROR);
    if(!is_array($refs)||count($refs)>12)throw new RuntimeException('Referências de imagem inválidas.');
    foreach($refs as $url=>$id)if(!is_string($url)||!is_int($id)||$id<1)
        throw new RuntimeException('Referência de imagem inválida.');
    $result=communication_builder_render_email(database(),(string)($_POST['mjml']??''),$refs);
    echo json_encode(['ok'=>true,'render'=>$result],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}catch(Throwable $e){
    if(http_response_code()<400)http_response_code(422);
    echo json_encode(['ok'=>false,'error'=>$e instanceof JsonException?'Referências inválidas.':$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}
