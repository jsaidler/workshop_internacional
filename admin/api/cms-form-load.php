<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();require_admin();
$form=cms_form_by_id(database(),(int)($_GET['form']??0));if(!$form||$form['status']==='archived')content_json(['error'=>['code'=>'form_not_found']],404);
content_json(['csrf'=>csrf_token('cms-editor'),'form'=>['id'=>(int)$form['id'],'uuid'=>$form['form_uuid'],'activityId'=>(int)$form['activity_id'],'locale'=>$form['locale'],'key'=>$form['form_key'],'title'=>$form['title'],'draftRevision'=>(int)$form['draft_revision'],'publishedRevision'=>$form['published_revision']===null?null:(int)$form['published_revision'],'schema'=>cms_form_schema($form,false)]]);
