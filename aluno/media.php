<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
student_private_headers();header('X-Content-Type-Options: nosniff');
$db=database();$uuid=strtolower(trim((string)($_GET['asset']??'')));if(!preg_match('/^[a-f0-9]{32}$/',$uuid)){http_response_code(404);exit;}
$asset=null;
if(current_admin()){$asset=student_private_media_admin_asset($db,$uuid);}
else{
    $student=student_account_current($db);if(!$student){http_response_code(404);exit;}
    $pageId=(int)($_GET['page']??0);$cohortUuid=trim((string)($_GET['cohort']??''));
    $asset=student_protected_media_authorize($db,$student,$uuid,$pageId,$cohortUuid);
}
if(!$asset){http_response_code(404);exit;}
$fileInfo=media_private_asset_absolute_file($db,(int)$asset['id']);if(!$fileInfo){http_response_code(404);exit;}
$file=(string)$fileInfo['absolute_path'];$size=filesize($file);if($size===false){http_response_code(404);exit;}
header('Content-Type: '.(string)$fileInfo['mime_type']);header('Content-Length: '.(string)$size);header('Content-Disposition: inline; filename="'.rawurlencode((string)$fileInfo['original_name']).'"');if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;readfile($file);
