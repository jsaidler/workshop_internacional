<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();
$input=content_api_guard('cms-editor');$db=database();
try{
    $pageId=(int)($input['pageId']??0);$access=(string)($input['access']??'public');if(!in_array($access,['public','authenticated','activity'],true))throw new RuntimeException('Acesso inválido.');
    $page=cms_page_by_id($db,$pageId)??throw new RuntimeException('Página inválida.');$db->prepare('UPDATE cms_pages SET access_level=?,show_in_nav=CASE WHEN ?="public" THEN show_in_nav ELSE 0 END,updated_at=? WHERE id=?')->execute([$access,$access,utc_now(),$pageId]);
    content_json(['ok'=>true,'access'=>$access]);
}catch(Throwable $e){content_json(['error'=>['code'=>'save_failed','message'=>$e->getMessage()]],400);}
