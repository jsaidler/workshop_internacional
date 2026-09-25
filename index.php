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
        $document=cms_page_doc($page,true);$sourceHtml=(string)($document['html']??'');$admin=current_admin();$previewCohortId=$admin?(int)($_GET['preview_cohort']??0):0;
        $dynamic=cms_access_page_level($page)!=='public'||preg_match('~data-cms-(?:access|lesson|available-from|available-until)|data-private-media-slot~i',$sourceHtml);
        if($dynamic)student_private_headers();

        if($admin&&$previewCohortId<=0){
            $document=cms_private_media_resolve($db,$page,$document,true);
        }elseif($admin){
            $enrollment=cms_access_preview_enrollment($db,$page,$previewCohortId);if(!$enrollment){http_response_code(404);exit('Turma de visualização não encontrada.');}
            $previewUser=['id'=>0,'name'=>'Prévia administrativa'];
            $document=cms_access_filter_document($db,$page,$document,$previewUser,$enrollment);
            $document=cms_private_media_resolve($db,$page,$document,true);
            header('X-CMS-Preview-Cohort: '.rawurlencode((string)$enrollment['cohort_slug']));
        }else{
            student_account_reconcile_confirmed_registrations($db,(int)$activity['id']);$user=student_account_current($db);$cohortUuid=trim((string)($_GET['cohort']??''));$context=cms_access_page_context($db,$page,$user,$cohortUuid);
            if(!$context['allowed']){
                if(!$user&&cms_access_page_requires_login($page)){$next=student_safe_next((string)($_SERVER['REQUEST_URI']??cms_page_url($activity,$page,$locale)));header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
                http_response_code(403);exit('Este conteúdo não está disponível para esta conta.');
            }
            $enrollment=$context['enrollment'];
            $document=cms_access_filter_document($db,$page,$document,$user,$enrollment);
            $document=cms_private_media_resolve($db,$page,$document,false);
            if($user){$document=cms_private_media_sign_document($document,$user,$page,$enrollment);student_page_prefill_for_page($db,$user,$page,$document);}
        }
        try{analytics_record_pageview($db,$activity,$page,$locale);}catch(Throwable $analyticsError){error_log('Analytics pageview failed: '.$analyticsError->getMessage());}
        cms_render_public_page($activity,$page,$document,false);exit;
    }
    if($pageSlug!=='')cms_render_not_found($activity,$locale);
    require __DIR__.'/template/public.php';render_public_page([],[],isset($_GET['success']));
}catch(RuntimeException $error){cms_render_not_found($activity,$locale);}
