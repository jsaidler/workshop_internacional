<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=UTF-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);echo json_encode(['error'=>'method_not_allowed']);exit;}
if((int)($_SERVER['CONTENT_LENGTH']??0)>media_effective_upload_limit()){http_response_code(413);echo json_encode(['error'=>'file_too_large']);exit;}
if(!verify_csrf('media',$_POST['csrf']??null)){http_response_code(403);echo json_encode(['error'=>'csrf_invalid']);exit;}
try{
    $replace=trim((string)($_POST['replace_asset']??''));
    $db=database();$replaceId=$replace===''?null:(int)$replace;$wasPrivate=false;
    if($replaceId!==null){$existing=media_asset($db,$replaceId);$wasPrivate=media_asset_is_private($existing);}
    $asset=media_create($db,$_FILES['file']??[],$replaceId);
    if(($asset['kind']??'')==='image')media_regenerate_image_asset($db,(int)$asset['id'],false);
    if($wasPrivate)media_private_resecure_asset($db,(int)$asset['id']);
    $asset=media_private_adminize_asset(media_asset_admin($db,(int)$asset['id']));
    echo json_encode(['item'=>$asset],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
}catch(Throwable $e){http_response_code(422);error_log('media.upload '.$e->getMessage());echo json_encode(['error'=>$e->getMessage()]);}