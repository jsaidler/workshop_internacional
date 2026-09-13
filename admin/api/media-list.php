<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
require_once __DIR__.'/../../app/media_list_service.php';
require_admin();
header('Content-Type: application/json; charset=UTF-8');
$db=database();

$status=(string)($_GET['status']??'active');
$where=$status==='archived'?'archived_at IS NOT NULL':($status==='all'?'1=1':'archived_at IS NULL');
$rows=$db->query('SELECT id FROM media_assets WHERE '.$where.' ORDER BY id DESC')->fetchAll();
$ids=array_map(fn(array $row)=>(int)$row['id'],$rows);
$tags=media_list_tags_bulk($db,$ids);
$usageCounts=media_list_usage_counts($db,$ids);
$items=[];
foreach($ids as $id){
    $item=media_asset($db,$id);
    $item['tags']=$tags[$id]??[];
    // The grid needs only the count. Detailed references are resolved lazily
    // by media-detail.php when the user opens one asset.
    $item['uses']=array_fill(0,(int)($usageCounts[$id]??0),true);
    $items[]=$item;
}
echo json_encode(['items'=>$items,'capabilities'=>media_capabilities(),'csrf'=>csrf_token('media')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
