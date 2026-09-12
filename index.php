<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
security_headers();

try{
    $db=database();
    $activity=activity_for_request($db);
    $locale=public_locale();
    $pageSlug=is_string($_GET['page']??null)?trim((string)$_GET['page']):'';
    $page=$pageSlug!==''
        ? cms_page_by_slug($db,(int)$activity['id'],$locale,$pageSlug)
        : cms_page_home($db,(int)$activity['id'],$locale);

    if($page&&$page['status']!=='archived'&&!empty($page['published_document_json'])){
        cms_render_public_page($activity,$page,cms_page_doc($page,true),false);
        exit;
    }

    // Compatibility fallback for installations that have not completed the CMS
    // migration yet. Once a published CMS page exists it always takes precedence.
    require __DIR__.'/template/public.php';
    render_public_page([],[],isset($_GET['success']));
}catch(RuntimeException $error){
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Page not found';
}
