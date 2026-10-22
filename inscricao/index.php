<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();

try{
    $db=database();
    $activity=root_activity($db);
    $page=cms_page_by_slug($db,(int)$activity['id'],PUBLIC_LOCALE_PT_BR,'inscricao');
    if(!$page||empty($page['published_document_json']))throw new RuntimeException('registration_page_not_found');
    cms_render_public_page($activity,$page,cms_page_doc($page,true),false);
}catch(Throwable $error){
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Inscrição indisponível.';
}
