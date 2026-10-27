<?php
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
security_headers();
require_admin();

$db=database();
$pageId=(int)($_GET['page']??0);
$page=cms_page_by_id($db,$pageId);
if(!$page||($page['status']??'')==='archived')content_json(['error'=>['code'=>'page_not_found']],404);
$activity=activity_by_id($db,(int)$page['activity_id']);
if(!$activity)content_json(['error'=>['code'=>'activity_not_found']],404);

$pages=[];
foreach(cms_pages($db,(int)$page['activity_id']) as $candidate){
    if(($candidate['status']??'')==='archived')continue;
    $pages[]=[
        'id'=>(int)$candidate['id'],
        'title'=>(string)$candidate['title'],
        'navTitle'=>(string)$candidate['nav_title'],
        'locale'=>(string)$candidate['locale'],
        'isHome'=>(bool)$candidate['is_home'],
        'draftRevision'=>(int)$candidate['draft_revision'],
        'publishedRevision'=>$candidate['published_revision']===null?null:(int)$candidate['published_revision'],
    ];
}
usort($pages,static function(array $a,array $b): int{
    if($a['locale']!==$b['locale'])return $a['locale']==='pt-BR'?-1:1;
    if($a['isHome']!==$b['isHome'])return $a['isHome']?-1:1;
    return strnatcasecmp($a['title'],$b['title']);
});

$q=$db->prepare("SELECT COUNT(*) FROM cms_form_submissions WHERE activity_id=? AND status='new'");
$q->execute([(int)$page['activity_id']]);
$newResponses=(int)$q->fetchColumn();
$publicUrl=(int)($activity['is_root']??0)===1?'/':'/'.rawurlencode((string)$activity['slug']).'/';

content_json([
    'activity'=>[
        'id'=>(int)$activity['id'],
        'title'=>(string)($activity['admin_name']??$activity['public_title']??'Site'),
        'publicUrl'=>$publicUrl,
    ],
    'pages'=>$pages,
    'newResponses'=>$newResponses,
]);
