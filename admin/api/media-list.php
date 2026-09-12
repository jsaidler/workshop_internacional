<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';require_admin();header('Content-Type: application/json; charset=UTF-8');$db=database();$items=media_list($db);foreach($items as &$item)$item['tags']=media_asset_tags($db,(int)$item['id']);unset($item);echo json_encode(['items'=>$items,'capabilities'=>media_capabilities(),'csrf'=>csrf_token('media')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
