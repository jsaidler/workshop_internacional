<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';security_headers();require_admin();header('Content-Type: application/json; charset=UTF-8');
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity']??null;if(!$activity){http_response_code(404);echo json_encode(['error'=>'activity_not_found']);exit;}$activityId=(int)$activity['id'];$pageId=(int)($_GET['page']??$_POST['page']??0);$page=cms_page_by_id($db,$pageId);if(!$page||(int)$page['activity_id']!==$activityId||!student_page_is_protected($page)){http_response_code(404);echo json_encode(['error'=>'protected_page_not_found']);exit;}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    $input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))$input=$_POST;if(!verify_csrf('student-area',$input['csrf']??null)){http_response_code(403);echo json_encode(['error'=>'csrf_invalid']);exit;}
    try{$slot=(string)($input['slot']??'');$action=(string)($input['action']??'bind');if($action==='unbind')media_page_slot_unbind($db,$activityId,$pageId,$slot);else media_page_slot_bind($db,$activityId,$pageId,$slot,(int)($input['assetId']??0));}catch(Throwable $e){http_response_code(422);echo json_encode(['error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);exit;}
}
$slots=student_page_private_media_slots_from_document(cms_page_doc($page,false));$bindings=media_page_slot_bindings($db,$pageId);$assets=array_map(static fn(array $asset)=>['id'=>(int)$asset['id'],'title'=>(string)$asset['title'],'original_name'=>(string)$asset['original_name']],media_private_assets($db,'image'));
$out=[];foreach($slots as $key=>$slot){$binding=$bindings[$key]??null;$out[]=['key'=>$key,'label'=>$slot['alt'],'binding'=>$binding?['assetId'=>(int)$binding['media_asset_id'],'title'=>(string)$binding['title']]:null];}
echo json_encode(['slots'=>$out,'assets'=>$assets,'csrf'=>csrf_token('student-area'),'mediaUrl'=>'/admin/media.php?activity='.$activityId],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
