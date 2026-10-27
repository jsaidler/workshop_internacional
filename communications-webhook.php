<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
security_headers();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){
    http_response_code(405);header('Allow: POST');echo '{"ok":false}';exit;
}
$authorization=(string)($_SERVER['HTTP_AUTHORIZATION']??$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??'');
if($authorization===''&&function_exists('getallheaders')){
    foreach(getallheaders() as $name=>$value)if(strcasecmp((string)$name,'Authorization')===0){$authorization=(string)$value;break;}
}
if(!communication_tracking_authorized($authorization)){
    http_response_code(401);echo '{"ok":false}';exit;
}
try{
    $raw=(string)file_get_contents('php://input');
    if($raw===''||strlen($raw)>262144){http_response_code(413);echo '{"ok":false}';exit;}
    $payload=json_decode($raw,true,32,JSON_THROW_ON_ERROR);
    if(!is_array($payload)){http_response_code(400);echo '{"ok":false}';exit;}
    $accepted=communication_tracking_accept(database(),$payload);
    http_response_code($accepted?202:204);
    echo json_encode(['ok'=>true],JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){
    error_log('communications webhook rejected: '.get_class($e));
    http_response_code(400);echo '{"ok":false}';
}
