<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
student_private_headers();header('X-Content-Type-Options: nosniff');
$db=database();$student=student_account_current($db);if(!$student){http_response_code(404);exit;}
$uuid=strtolower(trim((string)($_GET['asset']??'')));$pageId=(int)($_GET['page']??0);$cohortId=(int)($_GET['cohort']??0);$expires=(int)($_GET['expires']??0);$sig=trim((string)($_GET['sig']??''));
if(!preg_match('/^[a-f0-9]{32}$/',$uuid)||$pageId<1||$cohortId<1||$expires<1||$sig===''){http_response_code(404);exit;}
$asset=student_private_media_authorize($db,$student,$uuid,$pageId,$cohortId,$expires,$sig);if(!$asset){http_response_code(404);exit;}
$root=realpath(student_private_media_root());$file=realpath(student_private_media_root().'/'.(string)$asset['storage_path']);if($root===false||$file===false||!str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$root).'/')||!is_file($file)){http_response_code(404);exit;}
$size=filesize($file);if($size===false){http_response_code(404);exit;}header('Content-Type: '.(string)$asset['mime_type']);header('Content-Length: '.(string)$size);header('Content-Disposition: inline; filename="'.rawurlencode((string)$asset['original_name']).'"');if(($_SERVER['REQUEST_METHOD']??'GET')==='HEAD')exit;readfile($file);
