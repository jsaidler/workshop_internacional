<?php
require __DIR__.'/../../app/bootstrap.php';require_admin();header('Content-Type: application/json; charset=UTF-8');
if($_SERVER['REQUEST_METHOD']!=='POST'||!verify_csrf('media',$_POST['csrf']??null)){http_response_code(403);echo json_encode(['error'=>'csrf_invalid']);exit;}try{echo json_encode(['ok'=>true,'poster'=>media_create_poster(database(),(int)($_POST['mediaAssetId']??0),(int)($_POST['mediaVersionId']??0),(float)($_POST['timestamp']??-1),$_FILES['frame']??[])],JSON_UNESCAPED_SLASHES);}catch(Throwable $e){http_response_code(422);echo json_encode(['error'=>$e->getMessage()]);}
