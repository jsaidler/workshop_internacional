<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
security_headers();

$activity=null;$locale=public_locale();
try{
    $db=database();
    $activity=activity_for_request($db);
    $pageSlug=is_string($_GET['page']??null)?trim((string)$_GET['page']):'';
    $page=$pageSlug!==''
        ? cms_page_by_slug($db,(int)$activity['id'],$locale,$pageSlug)
        : cms_page_home($db,(int)$activity['id'],$locale);

    if($page&&$page['status']!=='archived'&&!empty($page['published_document_json'])){
        $document=cms_page_doc($page,true);
        $student=student_account_current($db);
        if(student_page_is_protected($page)){
            student_account_reconcile_confirmed_registrations($db,(int)$activity['id']);
            $student=student_account_current($db);
            if(!$student){
                $next=student_safe_next((string)($_SERVER['REQUEST_URI']??cms_page_url($activity,$page,$locale)));
                header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;
            }
            $cohortUuid=trim((string)($_GET['cohort']??''));
            $enrollment=student_account_page_context($db,$student,$page,$cohortUuid);
            if(!$enrollment){student_private_headers();http_response_code(403);exit('Este material não está disponível para esta matrícula.');}
            student_private_headers();
            $document=student_page_filter_document($db,$page,$document,$enrollment);
            $document=student_page_sign_private_media($document,$student,$page,$enrollment);
            student_page_prefill_for_page($db,$student,$page,$document);
        }elseif($student){
            student_page_prefill_for_page($db,$student,$page,$document);
        }
        try{analytics_record_pageview($db,$activity,$page,$locale);}catch(Throwable $analyticsError){error_log('Analytics pageview failed: '.$analyticsError->getMessage());}
        cms_render_public_page($activity,$page,$document,false);
        exit;
    }

    // A requested CMS slug that does not exist must not fall back to the old
    // landing page: that would create duplicate 200-status pages for bad URLs.
    if($pageSlug!=='')cms_render_not_found($activity,$locale);

    // Compatibility fallback only for the root landing page on installations
    // that have not completed the CMS migration yet.
    require __DIR__.'/template/public.php';
    render_public_page([],[],isset($_GET['success']));
}catch(RuntimeException $error){
    cms_render_not_found($activity,$locale);
}
