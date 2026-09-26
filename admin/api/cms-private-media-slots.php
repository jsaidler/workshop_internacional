<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();require_admin();
$db=database();$pageId=(int)($_GET['page']??0);$page=cms_page_by_id($db,$pageId);
if(!$page||$page['status']==='archived')content_json(['error'=>['code'=>'page_not_found']],404);
$slots=[];
foreach(student_page_private_media_slots_from_document(cms_page_doc($page,false)) as $slot){
    $slotKey=(string)$slot['key'];$asset=course_page_media_slot($db,$pageId,$slotKey);$bound=$asset&&media_asset_private($asset)&&trim((string)($asset['original_path']??''))!=='';
    $slots[$slotKey]=[
        'key'=>$slotKey,
        'alt'=>(string)$slot['alt'],
        'bound'=>(bool)$bound,
        'assetId'=>$bound?(int)$asset['media_asset_id']:null,
        'src'=>$bound?media_admin_private_url((int)$asset['media_asset_id'],(string)$asset['original_path']):null,
    ];
}
content_json(['pageId'=>$pageId,'slots'=>$slots]);
