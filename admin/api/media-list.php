<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
require_once __DIR__.'/../../app/media_list_service.php';
require_admin();
header('Content-Type: application/json; charset=UTF-8');
$db=database();
$status=(string)($_GET['status']??'active');$includePrivate=!empty($_GET['include_private']);
$items=media_list_grid_items($db,$status);
if(!$includePrivate)$items=array_values(array_filter($items,static fn(array $item):bool=>media_asset_visibility($item)!=='private'));
$items=array_map('media_private_adminize_grid_item',$items);
echo json_encode(['items'=>$items,'capabilities'=>media_capabilities(),'csrf'=>csrf_token('media')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
