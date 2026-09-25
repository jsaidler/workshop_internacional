<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();require_admin();
$db=database();$pageId=(int)($_GET['page']??0);$page=cms_page_by_id($db,$pageId);if(!$page||$page['status']==='archived')content_json(['error'=>['code'=>'page_not_found']],404);
$cohorts=array_map(static fn(array $row)=>['id'=>(int)$row['id'],'title'=>(string)$row['title'],'status'=>(string)$row['status']],course_cohorts($db,(int)$page['activity_id']));
$lessons=array_map(static fn(array $row)=>['id'=>(int)$row['id'],'title'=>(string)$row['title'],'key'=>(string)$row['lesson_key']],course_lessons($db,(int)$page['activity_id']));
content_json(['pageId'=>$pageId,'pageAccess'=>(string)($page['access_level']??'public'),'cohorts'=>$cohorts,'lessons'=>$lessons,'timezone'=>(string)(app_config()['timezone']??'UTC')]);
