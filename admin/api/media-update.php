<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';security_headers();require_admin();header('Content-Type: application/json; charset=UTF-8');
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);echo json_encode(['error'=>'method_not_allowed']);exit;}
$input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))$input=$_POST;
if(!verify_csrf('media',$input['csrf']??null)){http_response_code(403);echo json_encode(['error'=>'csrf_invalid']);exit;}
$id=(int)($input['assetId']??0);if($id<1){http_response_code(422);echo json_encode(['error'=>'invalid_asset']);exit;}
try{
    $db=database();$visibility=(string)($input['visibility']??'');
    if($visibility!=='')media_set_visibility($db,$id,$visibility);
    $item=media_update_metadata($db,$id,$input);$item=media_private_adminize_asset($item);
    echo json_encode(['item'=>$item],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){http_response_code(422);echo json_encode(['error'=>$e->getMessage()]);}
