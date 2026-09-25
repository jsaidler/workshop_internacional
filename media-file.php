<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';

$db=database();$assetId=(int)($_GET['asset']??0);$path='';$asset=null;
if($assetId>0){try{$asset=media_asset($db,$assetId);$path=(string)($asset['original_path']??'');}catch(Throwable){$asset=null;}}
else{$path=ltrim(str_replace('\\','/',trim((string)($_GET['path']??''))),'/');if($path!==''&&!str_contains($path,'..'))$asset=media_asset_by_storage_path($db,$path);}
if(!$asset||$path===''||str_contains($path,'..')){http_response_code(404);exit;}
if(media_is_private($asset)&&!current_admin()){http_response_code(404);exit;}
$root=realpath(media_upload_root());$file=realpath(media_upload_root().'/'.$path);if($root===false||$file===false||!str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$root).'/')||!is_file($file)){http_response_code(404);exit;}
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file)?:'application/octet-stream';$size=filesize($file);if($size===false){http_response_code(404);exit;}
header('X-Content-Type-Options: nosniff');header('Content-Type: '.$mime);header('Content-Length: '.(string)$size);header('Cache-Control: '.(media_is_private($asset)?'private, no-store':'public, max-age=3600'));if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;readfile($file);
