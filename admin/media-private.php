<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();require_admin();header('X-Content-Type-Options: nosniff');
$db=database();$assetId=(int)($_GET['asset']??0);$relative=media_private_untoken((string)($_GET['file']??''));
if($assetId<1||$relative===''){http_response_code(404);exit;}
try{$asset=media_asset($db,$assetId);}catch(Throwable){http_response_code(404);exit;}
if(!media_asset_private($asset)){http_response_code(404);exit;}
$file=media_private_path_for_asset($asset,$relative);if($file===null){http_response_code(404);exit;}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file)?:'application/octet-stream';$size=filesize($file);if($size===false){http_response_code(404);exit;}
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');header('Pragma: no-cache');header('Content-Type: '.$mime);header('Content-Length: '.(string)$size);
if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;readfile($file);
