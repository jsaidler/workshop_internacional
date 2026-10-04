<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();
$input=content_api_guard('cms-editor');$db=database();
try{$pageId=(int)($input['pageId']??0);$current=cms_page_by_id($db,$pageId)??throw new RuntimeException('page_not_found');cms_access_validate_document($db,$current,cms_page_doc($current,false));$page=cms_page_publish($db,$pageId);cms_revision_store($db,$page,'published');content_json(['ok'=>true,'draftRevision'=>(int)$page['draft_revision'],'publishedRevision'=>(int)$page['published_revision'],'publishedAt'=>$page['published_at']]);}catch(RuntimeException $error){content_json(['error'=>['code'=>'publish_failed','message'=>$error->getMessage()]],400);}
