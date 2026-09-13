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
        cms_render_public_page($activity,$page,cms_page_doc($page,true),false);
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
