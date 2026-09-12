<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();
$input=content_api_guard('cms-editor');$db=database();
try{
    $page=cms_page_save($db,(int)($input['pageId']??0),is_array($input['document']??null)?$input['document']:[],(int)($input['revision']??0),is_array($input['settings']??null)?$input['settings']:[]);
    cms_revision_store($db,$page,'draft');
    content_json(['ok'=>true,'page'=>['id'=>(int)$page['id'],'locale'=>$page['locale'],'slug'=>$page['slug'],'title'=>$page['title'],'navTitle'=>$page['nav_title'],'showInNav'=>(bool)$page['show_in_nav'],'draftRevision'=>(int)$page['draft_revision'],'publishedRevision'=>$page['published_revision']===null?null:(int)$page['published_revision'],'document'=>cms_page_doc($page,false)]]);
}catch(RuntimeException $error){$code=$error->getMessage()==='revision_conflict'?'revision_conflict':'save_failed';content_json(['error'=>['code'=>$code,'message'=>$error->getMessage()]],$code==='revision_conflict'?409:400);}
