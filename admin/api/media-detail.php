<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';security_headers();require_admin();header('Content-Type: application/json; charset=UTF-8');
$id=filter_input(INPUT_GET,'asset',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);if(!$id){http_response_code(422);echo json_encode(['error'=>'invalid_asset']);exit;}
try{echo json_encode(['item'=>media_asset_admin(database(),(int)$id),'csrf'=>csrf_token('media')],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}catch(Throwable $e){http_response_code(404);echo json_encode(['error'=>'asset_not_found']);}
