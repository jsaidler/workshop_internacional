<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';require_admin();header('Content-Type: application/json; charset=UTF-8');$db=database();
$status=(string)($_GET['status']??'active');$where=$status==='archived'?'archived_at IS NOT NULL':($status==='all'?'1=1':'archived_at IS NULL');$rows=$db->query('SELECT id FROM media_assets WHERE '.$where.' ORDER BY id DESC')->fetchAll();$items=[];foreach($rows as $row){$item=media_asset($db,(int)$row['id']);$item['tags']=media_asset_tags($db,(int)$item['id']);$item['uses']=media_usage_all($db,(int)$item['id']);$items[]=$item;}echo json_encode(['items'=>$items,'capabilities'=>media_capabilities(),'csrf'=>csrf_token('media')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
