<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';require __DIR__.'/../app/admin_shell.php';security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity'];if(!$activity){header('Location: /admin/activities.php');exit;}$activityId=(int)$activity['id'];cms_pages_seed($db,$activityId);
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('cms-pages',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $action=(string)($_POST['action']??'');
    try{
        if($action==='create'){$page=cms_page_create($db,$activityId,(string)($_POST['locale']??PUBLIC_LOCALE_PT_BR),(string)($_POST['title']??'Nova página'),(string)($_POST['slug']??''),(int)($_POST['copy_id']??0)?:null);header('Location: /editor/?page='.(int)$page['id']);exit;}
        if($action==='home'){cms_page_set_home($db,(int)($_POST['page_id']??0));}
        if($action==='archive'){cms_page_archive($db,(int)($_POST['page_id']??0));}
    }catch(RuntimeException $error){$_SESSION['admin_notice']=$error->getMessage();}
    header('Location: /admin/pages.php?activity='.$activityId);exit;
}
$pages=cms_pages($db,$activityId,null,true);$active=array_values(array_filter($pages,fn(array $p)=>$p['status']!=='archived'));$notice=$_SESSION['admin_notice']??null;unset($_SESSION['admin_notice']);
admin_shell_start('pages','Páginas',$state);?>
<?php if($notice):?><div class="admin-notice"><?=h((string)$notice)?></div><?php endif;?>
<section class="overview-hero"><div><p class="admin-kicker">CMS</p><h2>Páginas independentes por idioma</h2><p>Cada página tem seu próprio rascunho, publicação, URL e conteúdo. Português e inglês deixam de depender de traduções fixas no código.</p></div><div class="hero-actions"><button class="admin-button" type="button" data-dialog-open="new-page">Nova página</button></div></section>
<section class="overview-grid"><?php foreach($active as $page):$unpublished=$page['published_revision']===null||(int)$page['draft_revision']!==(int)$page['published_revision'];$url=cms_page_url($activity,$page,$page['locale']);?><article class="overview-card"><p class="admin-kicker"><?=h(public_language_label($page['locale']))?><?= (int)$page['is_home']===1?' · Início':'' ?></p><h2><?=h($page['title'])?></h2><p><code><?=h($url)?></code></p><p><?= $unpublished?'Há alterações não publicadas.':'Publicado e atualizado.' ?></p><div class="admin-card-actions"><a href="/editor/?page=<?=(int)$page['id']?>">Editar visualmente</a><a href="<?=h($url)?>" target="_blank" rel="noopener">Abrir</a></div><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-pages'))?>"><input type="hidden" name="page_id" value="<?=(int)$page['id']?>"><?php if(!(int)$page['is_home']):?><button class="link-button" name="action" value="home">Definir como página inicial</button><button class="link-button danger" name="action" value="archive" data-confirm="Arquivar esta página?">Arquivar</button><?php endif;?></form></article><?php endforeach;?></section>
<dialog id="new-page" class="admin-dialog"><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-pages'))?>"><input type="hidden" name="action" value="create"><header><p class="admin-kicker">Nova página</p><h2>Criar página</h2></header><label>Título<input name="title" required></label><label>Idioma<select name="locale"><option value="pt-BR">Português (Brasil)</option><option value="en">English</option></select></label><label>Slug<input name="slug" placeholder="ex.: inscricao"></label><label>Começar a partir de uma página existente<select name="copy_id"><option value="0">Página em branco</option><?php foreach($active as $page):?><option value="<?=(int)$page['id']?>"><?=h(public_language_label($page['locale']).' · '.$page['title'])?></option><?php endforeach;?></select></label><div class="dialog-actions"><button type="button" data-dialog-close>Cancelar</button><button class="admin-button" type="submit">Criar e editar</button></div></form></dialog>
<?php admin_shell_end();
