<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=UTF-8');
$asset=filter_input(INPUT_GET,'asset',FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
if(!$asset){http_response_code(422);echo json_encode(['error'=>'invalid_asset']);exit;}
try{echo json_encode(['uses'=>media_usage(database(),$asset)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}catch(Throwable $e){http_response_code(404);echo json_encode(['error'=>'asset_not_found']);}
