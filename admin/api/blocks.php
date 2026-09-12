<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';security_headers();require_admin();header('Content-Type: application/json; charset=UTF-8');
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity'];if(!$activity){http_response_code(404);echo json_encode(['error'=>'activity_not_found']);exit;}$activityId=(int)$activity['id'];
try{
    if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'){
        $locale=normalize_public_locale($_GET['lang']??PUBLIC_LOCALE_PT_BR)??PUBLIC_LOCALE_PT_BR;
        echo json_encode(['csrf'=>csrf_token('cms-blocks-api'),'items'=>cms_blocks($db,$activityId,$locale)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
    }
    $input=json_decode((string)file_get_contents('php://input'),true);if(!is_array($input))$input=[];
    if(!verify_csrf('cms-blocks-api',$input['csrf']??null)){http_response_code(403);echo json_encode(['error'=>'invalid_csrf']);exit;}
    $action=(string)($input['action']??'save');$locale=normalize_public_locale($input['locale']??PUBLIC_LOCALE_PT_BR)??PUBLIC_LOCALE_PT_BR;
    if($action==='save'){
        $item=cms_block_save($db,$activityId,$locale,(string)($input['name']??''),(string)($input['html']??''),(string)($input['category']??'custom'),isset($input['id'])?(int)$input['id']:null);
        echo json_encode(['item'=>$item],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
    }
    if($action==='delete'){cms_block_delete($db,$activityId,(int)($input['id']??0));echo json_encode(['ok'=>true]);exit;}
    throw new RuntimeException('unsupported_action');
}catch(Throwable $e){http_response_code(400);echo json_encode(['error'=>$e->getMessage()]);}
