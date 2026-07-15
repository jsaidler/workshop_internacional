<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=UTF-8');
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf('media',$_POST['csrf']??null)){http_response_code(403);echo json_encode(['error'=>'csrf_invalid']);exit;}
try{$replace=trim((string)($_POST['replace_asset']??''));$asset=media_create(database(),$_FILES['file']??[],$replace===''?null:(int)$replace);echo json_encode(['item'=>$asset],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);}catch(Throwable $e){http_response_code(422);error_log('media.upload '.$e->getMessage());echo json_encode(['error'=>$e->getMessage()]);}
