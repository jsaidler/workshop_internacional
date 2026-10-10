<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
require_admin();

$db=database();$state=admin_activity_resolution($db);$activity=$state['activity'];
if(!$activity){http_response_code(404);exit;}
$mediaId=(int)($_GET['id']??0);$media=student_test_media_for_admin($db,$mediaId,(int)$activity['id']);
if(!$media){http_response_code(404);exit;}
$path=student_test_media_absolute_path($media);if(!is_file($path)){http_response_code(404);exit;}
header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');header('X-Content-Type-Options: nosniff');
header('Content-Type: '.(string)$media['mime_type']);header('Content-Length: '.(string)filesize($path));
$filename=preg_replace('/[^A-Za-z0-9._ -]+/','_',basename((string)$media['original_name']))?:'imagem';
header('Content-Disposition: inline; filename="'.str_replace('"','',$filename).'"');
readfile($path);
