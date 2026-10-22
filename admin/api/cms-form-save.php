<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();
$input=content_api_guard('cms-editor');
try{$form=cms_form_save(database(),(int)($input['formId']??0),is_array($input['schema']??null)?$input['schema']:[],(int)($input['revision']??0),isset($input['title'])?(string)$input['title']:null);content_json(['ok'=>true,'form'=>['id'=>(int)$form['id'],'title'=>$form['title'],'key'=>$form['form_key'],'locale'=>$form['locale'],'draftRevision'=>(int)$form['draft_revision'],'publishedRevision'=>$form['published_revision']===null?null:(int)$form['published_revision'],'schema'=>cms_form_schema($form,false)]]);}catch(RuntimeException $error){$code=$error->getMessage()==='revision_conflict'?'revision_conflict':'save_failed';content_json(['error'=>['code'=>$code,'message'=>$error->getMessage()]],$code==='revision_conflict'?409:400);}
