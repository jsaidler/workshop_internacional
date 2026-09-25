<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
require_once __DIR__.'/../../app/media_detail_service.php';
security_headers();require_admin();header('Content-Type: application/json; charset=UTF-8');
$csrf=csrf_token('media');
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
$id=filter_input(INPUT_GET,'asset',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id){http_response_code(422);echo json_encode(['error'=>'invalid_asset']);exit;}
try{$item=media_private_adminize_asset(media_detail_admin(database(),(int)$id));echo json_encode(['item'=>$item,'csrf'=>$csrf],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}catch(Throwable $e){http_response_code(404);echo json_encode(['error'=>'asset_not_found']);}
