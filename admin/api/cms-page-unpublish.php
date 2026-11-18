<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();
$input=content_api_guard('cms-editor');$db=database();
try {
    $pageId=(int)($input['pageId']??0);
    $revision=(int)($input['revision']??0);
    $page=cms_page_unpublish($db,$pageId,$revision);
    content_json(['ok'=>true,'draftRevision'=>(int)$page['draft_revision'],'publishedRevision'=>null,'publishedAt'=>null]);
}catch(RuntimeException $error){
    $code=$error->getMessage()==='revision_conflict'?'revision_conflict':$error->getMessage();
    content_json(['error'=>['code'=>$code,'message'=>$error->getMessage()]],$code==='revision_conflict'?409:400);
}
