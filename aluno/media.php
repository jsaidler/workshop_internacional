<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
student_private_headers();header('X-Content-Type-Options: nosniff');
$db=database();$uuid=strtolower(trim((string)($_GET['asset']??'')));if(!preg_match('/^[a-f0-9]{32}$/',$uuid)){http_response_code(404);exit;}
$asset=null;
if(current_admin()){
    $asset=cms_private_media_admin_asset($db,$uuid)?:student_library_media_admin_asset($db,$uuid)?:student_private_media_admin_asset($db,$uuid);
}else{
    $user=student_account_current($db);if(!$user){http_response_code(404);exit;}
    $pageId=(int)($_GET['page']??0);$cohortId=max(0,(int)($_GET['cohort']??0));$expires=(int)($_GET['expires']??0);$sig=trim((string)($_GET['sig']??''));
    if($pageId<1||$expires<1||$sig===''){http_response_code(404);exit;}
    $asset=cms_private_media_authorize($db,$user,$uuid,$pageId,$cohortId,$expires,$sig)
        ?:student_library_media_authorize($db,$user,$uuid,$pageId,$cohortId,$expires,$sig)
        ?:student_private_media_authorize($db,$user,$uuid,$pageId,$cohortId,$expires,$sig);
}
if(!$asset){http_response_code(404);exit;}
if(!empty($asset['_library'])){
    $file=media_private_path_for_asset($asset,(string)$asset['original_path']);
}else{
    $root=realpath(student_private_media_root());$file=realpath(student_private_media_root().'/'.(string)$asset['storage_path']);
    if($root===false||$file===false||!str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$root).'/'))$file=false;
}
if(!$file||!is_file($file)){http_response_code(404);exit;}
$size=filesize($file);if($size===false){http_response_code(404);exit;}
header('Content-Type: '.(string)$asset['mime_type']);header('Content-Length: '.(string)$size);header('Content-Disposition: inline; filename="'.rawurlencode((string)$asset['original_name']).'"');if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;readfile($file);
