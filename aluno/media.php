<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
student_private_headers();header('X-Content-Type-Options: nosniff');
$db=database();

$mediaId=(int)($_GET['media']??0);
if($mediaId>0){
    $asset=null;
    if(current_admin()){try{$candidate=media_asset($db,$mediaId);$asset=media_is_private($candidate)?$candidate:null;}catch(Throwable){$asset=null;}}
    else{
        $student=student_account_current($db);if(!$student){http_response_code(404);exit;}
        $pageId=(int)($_GET['page']??0);$cohortId=(int)($_GET['cohort']??0);$expires=(int)($_GET['expires']??0);$sig=trim((string)($_GET['sig']??''));
        if($pageId<1||$cohortId<1||$expires<1||$sig===''){http_response_code(404);exit;}
        $asset=media_private_authorized_asset($db,$student,$mediaId,$pageId,$cohortId,$expires,$sig);
    }
    if(!$asset||empty($asset['original_path'])){http_response_code(404);exit;}
    $root=realpath(media_upload_root());$file=realpath(media_upload_root().'/'.(string)$asset['original_path']);
    if($root===false||$file===false||!str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$root).'/')||!is_file($file)){http_response_code(404);exit;}
    $size=filesize($file);if($size===false){http_response_code(404);exit;}header('Content-Type: '.(string)$asset['mime_type']);header('Content-Length: '.(string)$size);header('Content-Disposition: inline; filename="'.rawurlencode((string)$asset['original_name']).'"');if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;readfile($file);exit;
}

// Legacy protected-media URLs remain readable until their assets are rebound
// through the canonical media library.
$uuid=strtolower(trim((string)($_GET['asset']??'')));if(!preg_match('/^[a-f0-9]{32}$/',$uuid)){http_response_code(404);exit;}
$asset=null;
if(current_admin()){$asset=student_private_media_admin_asset($db,$uuid);}
else{$student=student_account_current($db);if(!$student){http_response_code(404);exit;}$pageId=(int)($_GET['page']??0);$cohortId=(int)($_GET['cohort']??0);$expires=(int)($_GET['expires']??0);$sig=trim((string)($_GET['sig']??''));if($pageId<1||$cohortId<1||$expires<1||$sig===''){http_response_code(404);exit;}$asset=student_private_media_authorize($db,$student,$uuid,$pageId,$cohortId,$expires,$sig);}
if(!$asset){http_response_code(404);exit;}
$root=realpath(student_private_media_root());$file=realpath(student_private_media_root().'/'.(string)$asset['storage_path']);if($root===false||$file===false||!str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$root).'/')||!is_file($file)){http_response_code(404);exit;}
$size=filesize($file);if($size===false){http_response_code(404);exit;}header('Content-Type: '.(string)$asset['mime_type']);header('Content-Length: '.(string)$size);header('Content-Disposition: inline; filename="'.rawurlencode((string)$asset['original_name']).'"');if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;readfile($file);
