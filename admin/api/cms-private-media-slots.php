<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();require_admin();header('Content-Type: application/json; charset=UTF-8');
$db=database();

function cms_private_media_page(PDO $db,int $pageId): array {
    $page=cms_page_by_id($db,$pageId);
    if(!$page||$page['status']==='archived')throw new RuntimeException('page_not_found');
    return $page;
}
function cms_private_media_payload(PDO $db,array $page): array {
    $pageId=(int)$page['id'];$slots=[];
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
    $images=[];$pathQuery=$db->prepare('SELECT v.original_path FROM media_assets a LEFT JOIN media_versions v ON v.id=a.active_version_id WHERE a.id=? LIMIT 1');
    foreach(media_private_images($db) as $image){
        $pathQuery->execute([(int)$image['id']]);$path=trim((string)($pathQuery->fetchColumn()?:''));if($path==='')continue;
        $images[]=[
            'id'=>(int)$image['id'],
            'title'=>(string)($image['title']?:$image['original_name']),
            'originalName'=>(string)$image['original_name'],
            'width'=>(int)($image['width']??0),
            'height'=>(int)($image['height']??0),
            'src'=>media_admin_private_url((int)$image['id'],$path),
        ];
    }
    return ['pageId'=>$pageId,'csrf'=>csrf_token('cms-private-media'),'slots'=>$slots,'images'=>$images];
}

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    $input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))$input=[];
    if(!verify_csrf('cms-private-media',$input['csrf']??null)){http_response_code(403);echo json_encode(['error'=>'csrf_invalid']);exit;}
    try{
        $page=cms_private_media_page($db,(int)($input['pageId']??0));$pageId=(int)$page['id'];$slotKey=student_private_media_slot_key((string)($input['slotKey']??''));
        $documentSlots=student_page_private_media_slots_from_document(cms_page_doc($page,false));if($slotKey===''||!isset($documentSlots[$slotKey]))throw new RuntimeException('slot_not_found');
        $action=(string)($input['action']??'');
        if($action==='bind')course_page_media_bind($db,$pageId,$slotKey,(int)($input['assetId']??0));
        elseif($action==='unbind')course_page_media_unbind($db,$pageId,$slotKey);
        else throw new RuntimeException('invalid_action');
        echo json_encode(cms_private_media_payload($db,$page),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    }catch(Throwable $error){http_response_code(422);echo json_encode(['error'=>$error->getMessage()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
    exit;
}

try{$page=cms_private_media_page($db,(int)($_GET['page']??0));echo json_encode(cms_private_media_payload($db,$page),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}catch(RuntimeException $error){http_response_code(404);echo json_encode(['error'=>$error->getMessage()]);}
