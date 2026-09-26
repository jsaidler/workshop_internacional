<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();require_admin();

function cms_private_media_editor_state(PDO $db,int $pageId): array {
    $page=cms_page_by_id($db,$pageId);
    if(!$page||$page['status']==='archived')throw new RuntimeException('page_not_found');
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
    $images=[];
    foreach(media_private_images($db) as $row){
        try{$asset=media_asset_admin($db,(int)$row['id']);}catch(Throwable){continue;}
        if(($asset['kind']??'')!=='image'||($asset['processing_status']??'ready')!=='ready')continue;
        $images[]=[
            'id'=>(int)$asset['id'],
            'title'=>(string)($asset['title']??$asset['original_name']??'Imagem'),
            'originalName'=>(string)($asset['original_name']??''),
            'width'=>(int)($asset['width']??0),
            'height'=>(int)($asset['height']??0),
            'src'=>(string)($asset['url']??''),
        ];
    }
    return ['pageId'=>$pageId,'csrf'=>csrf_token('cms-private-media'),'images'=>$images,'slots'=>$slots];
}

$db=database();
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    $input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))$input=[];
    if(!verify_csrf('cms-private-media',$input['csrf']??null))content_json(['error'=>['code'=>'csrf_invalid']],403);
    $pageId=(int)($input['pageId']??0);$slotKey=(string)($input['slotKey']??'');$action=(string)($input['action']??'');
    try{
        $page=cms_page_by_id($db,$pageId);if(!$page||$page['status']==='archived')throw new RuntimeException('page_not_found');
        $slots=student_page_private_media_slots_from_document(cms_page_doc($page,false));$slotKey=student_private_media_slot_key($slotKey);if(!isset($slots[$slotKey]))throw new RuntimeException('slot_not_found');
        if($action==='bind')course_page_media_bind($db,$pageId,$slotKey,(int)($input['assetId']??0));
        elseif($action==='unbind')course_page_media_unbind($db,$pageId,$slotKey);
        else throw new RuntimeException('invalid_action');
        content_json(cms_private_media_editor_state($db,$pageId));
    }catch(Throwable $error){content_json(['error'=>['code'=>$error->getMessage()]],422);}
}
$pageId=(int)($_GET['page']??0);
try{content_json(cms_private_media_editor_state($db,$pageId));}catch(Throwable $error){content_json(['error'=>['code'=>$error->getMessage()]],404);}
