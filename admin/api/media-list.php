<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
require_once __DIR__.'/../../app/media_list_service.php';
require_admin();
header('Content-Type: application/json; charset=UTF-8');
$db=database();
$status=(string)($_GET['status']??'active');
$items=array_map('media_rewrite_admin_delivery',media_list_grid_items($db,$status));
echo json_encode(['items'=>$items,'capabilities'=>media_capabilities(),'csrf'=>csrf_token('media')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
