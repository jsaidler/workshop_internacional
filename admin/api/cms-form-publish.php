<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();
$input=content_api_guard('cms-editor');
try{$form=cms_form_publish(database(),(int)($input['formId']??0));content_json(['ok'=>true,'draftRevision'=>(int)$form['draft_revision'],'publishedRevision'=>(int)$form['published_revision'],'publishedAt'=>$form['published_at']]);}catch(RuntimeException $error){content_json(['error'=>['code'=>'publish_failed','message'=>$error->getMessage()]],400);}
