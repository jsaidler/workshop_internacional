<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();
$input=content_api_guard('cms-editor');$db=database();
try{
    $pageId=(int)($input['pageId']??0);$access=(string)($input['access']??'public');$cohortId=(int)($input['cohortId']??0);
    $page=cms_page_by_id($db,$pageId)??throw new RuntimeException('Página inválida.');
    $saved=cms_access_set_page($db,$page,$access,$cohortId?:null);
    content_json(['ok'=>true,'access'=>(string)$saved['access_level'],'cohortId'=>cms_access_page_cohort_id($saved)]);
}catch(Throwable $e){content_json(['error'=>['code'=>'save_failed','message'=>$e->getMessage()]],400);}
