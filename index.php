<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
security_headers();
try{
    $db=database();
    $activity=public_activity();
    $locale=public_locale();
    $page=cms_current_page($db,$activity,$locale);
    if(!$page||empty($page['published_document_json']))throw new RuntimeException('page_not_found');
    cms_render_public_page($activity,$page,cms_page_doc($page,true),false);
}catch(RuntimeException $error){
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Page not found';
}
