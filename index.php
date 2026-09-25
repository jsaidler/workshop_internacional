<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
security_headers();

$activity=null;$locale=public_locale();
try{
    $db=database();
    $activity=activity_for_request($db);
    $pageSlug=is_string($_GET['page']??null)?trim((string)$_GET['page']):'';
    $page=$pageSlug!==''?cms_page_by_slug($db,(int)$activity['id'],$locale,$pageSlug):cms_page_home($db,(int)$activity['id'],$locale);
    if($page&&$page['status']!=='archived'&&!empty($page['published_document_json'])){
        $document=cms_page_doc($page,true);$admin=current_admin();$student=student_account_current($db);$pageAccess=(string)($page['access_level']??'public');
        if(!$admin&&!cms_access_page_allowed($db,$activity,$page,$student)){
            if(!$student&&$pageAccess!=='public'){$next=student_safe_next((string)($_SERVER['REQUEST_URI']??cms_page_url($activity,$page,$locale)));header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
            http_response_code(403);student_private_headers();exit('Este conteúdo não está disponível para esta conta.');
        }
        $hasSectionRules=preg_match('~data-cms-(?:access|availability|visible-from|visible-until|lesson-id|cohort-id)=~',(string)($document['html']??''))===1;
        if($pageAccess!=='public'||$hasSectionRules)student_private_headers();
        if(!$admin){$document['html']=cms_access_filter_html($db,$activity,(string)$document['html'],$student,false);}
        if($pageAccess!=='public'){
            if($admin){$document=student_page_resolve_private_media_slots($db,$page,$document);}
            elseif($student){
                $cohortUuid=trim((string)($_GET['cohort']??''));$enrollment=student_account_page_context($db,$student,$page,$cohortUuid);
                if($enrollment){$document=student_page_resolve_private_media_slots($db,$page,$document);$document=student_page_sign_private_media($document,$student,$page,$enrollment);}
            }
        }
        if($student)student_page_prefill_for_page($db,$student,$page,$document);
        try{analytics_record_pageview($db,$activity,$page,$locale);}catch(Throwable $analyticsError){error_log('Analytics pageview failed: '.$analyticsError->getMessage());}
        cms_render_public_page($activity,$page,$document,false);exit;
    }
    if($pageSlug!=='')cms_render_not_found($activity,$locale);
    require __DIR__.'/template/public.php';render_public_page([],[],isset($_GET['success']));
}catch(RuntimeException $error){cms_render_not_found($activity,$locale);}
