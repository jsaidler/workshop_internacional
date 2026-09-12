<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();
$input=content_api_guard('cms-editor');$db=database();
try{$page=cms_page_publish($db,(int)($input['pageId']??0));content_json(['ok'=>true,'draftRevision'=>(int)$page['draft_revision'],'publishedRevision'=>(int)$page['published_revision'],'publishedAt'=>$page['published_at']]);}catch(RuntimeException $error){content_json(['error'=>['code'=>'publish_failed','message'=>$error->getMessage()]],400);}
