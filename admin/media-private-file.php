<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();require_admin();student_private_headers();header('X-Content-Type-Options: nosniff');
$db=database();$assetId=(int)($_GET['asset']??0);$versionId=(int)($_GET['version']??0);$fileInfo=media_private_asset_absolute_file($db,$assetId,$versionId);
if(!$fileInfo){http_response_code(404);exit;}
$file=(string)$fileInfo['absolute_path'];$size=filesize($file);if($size===false){http_response_code(404);exit;}
header('Content-Type: '.(string)$fileInfo['mime_type']);header('Content-Length: '.(string)$size);header('Content-Disposition: inline; filename="'.rawurlencode((string)$fileInfo['original_name']).'"');if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;readfile($file);
