<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';security_headers();require_admin();
$db=database();$page=cms_page_by_id($db,(int)($_GET['page']??0));if(!$page||$page['status']==='archived'){http_response_code(404);exit('Page not found');}$activity=activity_by_id($db,(int)$page['activity_id']);if(!$activity){http_response_code(404);exit('Activity not found');}
cms_render_public_page($activity,$page,cms_page_doc($page,false),true);
