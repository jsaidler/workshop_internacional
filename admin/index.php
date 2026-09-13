<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();
require_admin();

$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];

if(!$activity){
    admin_shell_start('overview','Início',$state);
    ?><section class="admin-empty"><h2>Crie o primeiro site</h2><p>Configure um site para começar a editar páginas, inscrições e mídia.</p><a class="admin-button" href="/admin/activities.php">Configurar site</a></section><?php
    admin_shell_end();
    exit;
}

$id=(int)$activity['id'];
cms_pages_seed($db,$id);
$pages=array_values(array_filter(cms_pages($db,$id),fn(array $page)=>(string)($page['status']??'')!=='archived'));

$target=null;
foreach($pages as $page){
    if(($page['locale']??'')===PUBLIC_LOCALE_PT_BR && (int)($page['is_home']??0)===1){$target=$page;break;}
}
if(!$target){
    foreach($pages as $page){if((int)($page['is_home']??0)===1){$target=$page;break;}}
}
if(!$target && $pages)$target=$pages[0];

if($target){
    header('Location: /editor/?page='.(int)$target['id'],true,302);
    exit;
}

header('Location: /admin/pages.php?activity='.$id,true,302);
exit;
