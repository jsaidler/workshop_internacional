<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();require_admin();
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST')content_json(['error'=>['code'=>'method_not_allowed']],405);
$body=json_decode((string)file_get_contents('php://input'),true);if(!is_array($body))$body=[];
if(!verify_csrf('cms-editor',$body['csrf']??null))content_json(['error'=>['code'=>'csrf']],403);
$db=database();$pageId=(int)($body['pageId']??0);$page=cms_page_by_id($db,$pageId);if(!$page||$page['status']==='archived')content_json(['error'=>['code'=>'page_not_found']],404);
$level=(string)($body['accessLevel']??'public');if(!in_array($level,cms_access_page_levels(),true))content_json(['error'=>['code'=>'invalid_access_level']],422);
$showInNav=$level==='public'?(int)$page['show_in_nav']:0;
$db->prepare('UPDATE cms_pages SET access_level=?,show_in_nav=?,updated_at=? WHERE id=?')->execute([$level,$showInNav,utc_now(),$pageId]);
content_json(['ok'=>true,'accessLevel'=>$level,'showInNav'=>(bool)$showInNav]);
