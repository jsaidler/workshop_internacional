<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
security_headers();

try{
    $db=database();
    $pageSlug=is_string($_GET['page']??null)?trim((string)$_GET['page']):'';
    if($pageSlug!==''){
        $activity=activity_for_request($db);
        $locale=public_locale();
        $page=cms_page_by_slug($db,(int)$activity['id'],$locale,$pageSlug);
        if(!$page||empty($page['published_document_json']))throw new RuntimeException('page_not_found');
        cms_render_public_page($activity,$page,cms_page_doc($page,true),false);
        exit;
    }

    require __DIR__.'/template/public.php';
    render_public_page([],[],isset($_GET['success']));
}catch(RuntimeException $error){
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Page not found';
}
