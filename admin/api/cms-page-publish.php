<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();
$input=content_api_guard('cms-editor');$db=database();
try {
    $pageId=(int)($input['pageId']??0);
    $revision=(int)($input['revision']??0);
    if($revision<1)throw new RuntimeException('revision_conflict');
    $current=cms_page_by_id($db,$pageId)??throw new RuntimeException('page_not_found');
    cms_access_validate_document($db,$current,cms_page_doc($current,false));
    $page=cms_page_publish($db,$pageId,$revision);
    cms_revision_store($db,$page,'published');
    content_json(['ok'=>true,'draftRevision'=>(int)$page['draft_revision'],'publishedRevision'=>(int)$page['published_revision'],'publishedAt'=>$page['published_at']]);
}catch(RuntimeException $error){
    $conflict=$error->getMessage()==='revision_conflict';
    content_json(['error'=>['code'=>$conflict?'revision_conflict':'publish_failed','message'=>$error->getMessage()]],$conflict?409:400);
}
