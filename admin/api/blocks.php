<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';security_headers();require_admin();header('Content-Type: application/json; charset=UTF-8');
$db=database();
try{
    $input=[];if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){$input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))$input=[];}
    $pageId=(int)($_GET['page']??$input['pageId']??0);$page=$pageId?cms_page_by_id($db,$pageId):null;
    if($page){$activity=activity_by_id($db,(int)$page['activity_id']);$activityId=(int)$page['activity_id'];}
    else{$state=admin_activity_resolution($db);$activity=$state['activity'];$activityId=$activity?(int)$activity['id']:0;}
    if(!$activity||$activityId<1)throw new RuntimeException('activity_not_found');
    if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'){
        $locale=normalize_public_locale($_GET['lang']??($page['locale']??PUBLIC_LOCALE_PT_BR))??PUBLIC_LOCALE_PT_BR;
        echo json_encode(['csrf'=>csrf_token('cms-blocks-api'),'items'=>cms_blocks($db,$activityId,$locale)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
    }
    if(!verify_csrf('cms-blocks-api',$input['csrf']??null)){http_response_code(403);echo json_encode(['error'=>'invalid_csrf']);exit;}
    $action=(string)($input['action']??'save');$locale=normalize_public_locale($input['locale']??($page['locale']??PUBLIC_LOCALE_PT_BR))??PUBLIC_LOCALE_PT_BR;
    if($action==='save'){
        $item=cms_block_save($db,$activityId,$locale,(string)($input['name']??''),(string)($input['html']??''),(string)($input['category']??'custom'),isset($input['id'])?(int)$input['id']:null);
        echo json_encode(['item'=>$item],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
    }
    if($action==='delete'){cms_block_delete($db,$activityId,(int)($input['id']??0));echo json_encode(['ok'=>true]);exit;}
    throw new RuntimeException('unsupported_action');
}catch(Throwable $e){http_response_code(400);echo json_encode(['error'=>$e->getMessage()]);}
