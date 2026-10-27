<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();
require_admin();

function admin_set_cms_home(PDO $db,int $id): void {
    $page=cms_page_by_id($db,$id)??throw new RuntimeException('page_not_found');
    if((int)$page['is_home']===1)return;
    $db->beginTransaction();
    try{
        $q=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND locale=? AND is_home=1 AND status!='archived' LIMIT 1");
        $q->execute([(int)$page['activity_id'],$page['locale']]);
        $old=$q->fetch()?:null;
        if($old){
            $replacement=cms_page_unique_slug($db,(int)$old['activity_id'],(string)$old['locale'],(string)$old['title'],(int)$old['id']);
            $db->prepare('UPDATE cms_pages SET is_home=0,slug=?,updated_at=? WHERE id=?')->execute([$replacement,utc_now(),(int)$old['id']]);
        }
        $db->prepare("UPDATE cms_pages SET is_home=1,slug='',show_in_nav=1,updated_at=? WHERE id=?")->execute([utc_now(),$id]);
        $db->commit();
    }catch(Throwable $error){
        if($db->inTransaction())$db->rollBack();
        throw $error;
    }
}

function admin_pages_list_url(int $activityId,string $query='',string $locale=''): string {
    $args=['activity'=>$activityId];
    if($query!=='')$args['q']=$query;
    if($locale!=='')$args['lang']=public_locale_query($locale);
    return '/admin/pages.php?'.http_build_query($args);
}

$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];
if(!$activity){header('Location: /admin/activities.php');exit;}
$activityId=(int)$activity['id'];
$query=trim((string)($_GET['q']??$_POST['return_q']??''));
$rawLocale=trim((string)($_GET['lang']??$_POST['return_lang']??''));
$localeFilter=$rawLocale!==''?(normalize_public_locale($rawLocale)??''):'';

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('cms-pages',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $action=(string)($_POST['action']??'');
    try{
        if($action==='create'){
            $page=cms_page_create($db,$activityId,(string)($_POST['locale']??PUBLIC_LOCALE_PT_BR),(string)($_POST['title']??'Nova página'),(string)($_POST['slug']??''),(int)($_POST['copy_id']??0)?:null);
            header('Location: /editor/?page='.(int)$page['id']);
            exit;
        }
        if($action==='home')admin_set_cms_home($db,(int)($_POST['page_id']??0));
        if($action==='archive')cms_page_archive($db,(int)($_POST['page_id']??0));
        if($action==='up')cms_page_move_sibling($db,(int)($_POST['page_id']??0),-1);
        if($action==='down')cms_page_move_sibling($db,(int)($_POST['page_id']??0),1);
        if($action==='parent'){
            cms_page_set_parent($db,(int)($_POST['page_id']??0),(int)($_POST['parent_page_id']??0)?:null);
            $_SESSION['admin_notice']='Hierarquia da página atualizada.';
        }
        if($action==='translation'){
            cms_page_set_translation_peer($db,(int)($_POST['page_id']??0),(int)($_POST['translation_page_id']??0)?:null);
            $_SESSION['admin_notice']='Equivalência entre idiomas atualizada.';
        }
    }catch(RuntimeException $error){
        $_SESSION['admin_notice']=$error->getMessage();
    }
    header('Location: '.admin_pages_list_url($activityId,$query,$localeFilter));
    exit;
}

$pages=cms_pages($db,$activityId,null,true);
$active=array_values(array_filter($pages,static fn(array $page): bool=>(string)$page['status']!=='archived'));
$byId=[];$siblingGroups=[];
foreach($active as $candidate){
    $id=(int)$candidate['id'];$parentId=(int)($candidate['parent_page_id']??0);$byId[$id]=$candidate;
    $siblingGroups[(string)$candidate['locale'].'|'.$parentId][]=$candidate;
}
foreach($siblingGroups as &$siblings)usort($siblings,static fn(array $a,array $b): int=>[(int)$a['sort_order'],(int)$a['id']]<=>[(int)$b['sort_order'],(int)$b['id']]);
unset($siblings);

$visibleIds=[];
foreach($active as $candidate){
    if($localeFilter!==''&&(string)$candidate['locale']!==$localeFilter)continue;
    if($query!==''){
        $haystack=mb_strtolower(implode(' ',[(string)$candidate['title'],(string)($candidate['slug']??''),(string)($candidate['nav_title']??'')]));
        if(!str_contains($haystack,mb_strtolower($query)))continue;
    }
    $cursor=$candidate;
    while($cursor){
        $visibleIds[(int)$cursor['id']]=true;
        $parentId=(int)($cursor['parent_page_id']??0);
        if($parentId<1)break;
        $cursor=$byId[$parentId]??null;
        if($cursor&&$localeFilter!==''&&(string)$cursor['locale']!==$localeFilter)break;
    }
}
$visible=array_values(array_filter($active,static fn(array $page): bool=>isset($visibleIds[(int)$page['id']])));
$byLocale=[];foreach($visible as $candidate)$byLocale[(string)$candidate['locale']][]=$candidate;

$total=count($active);$draftCount=0;$publishedCount=0;
foreach($active as $page){$isDraft=$page['published_revision']===null||(int)$page['draft_revision']!==(int)$page['published_revision'];if($isDraft)$draftCount++;else $publishedCount++;}
$notice=$_SESSION['admin_notice']??null;unset($_SESSION['admin_notice']);
$clearUrl=admin_pages_list_url($activityId);

admin_shell_start('pages','Páginas',$state,['/assets/admin-site.css']);?>
<?php if($notice):?><div class="admin-notice"><?=h((string)$notice)?></div><?php endif;?>

<section class="site-intro">
  <p class="site-intro-copy">Organize a estrutura editorial do site. Hierarquia, idioma e estado ficam visíveis na própria lista; a navegação pública é administrada separadamente em Navegação.</p>
  <div class="site-intro-actions"><button class="admin-button" type="button" data-dialog-open="new-page">Nova página</button></div>
</section>

<div class="admin-stat-grid" aria-label="Resumo das páginas">
  <div class="admin-stat"><span>Ativas</span><strong><?=$total?></strong></div>
  <div class="admin-stat"><span>Publicadas</span><strong><?=$publishedCount?></strong></div>
  <div class="admin-stat"><span>Com alterações</span><strong><?=$draftCount?></strong></div>
</div>

<form class="admin-data-toolbar" method="get">
  <input type="hidden" name="activity" value="<?=$activityId?>">
  <label class="grow">Buscar página<input type="search" name="q" value="<?=h($query)?>" placeholder="Título, endereço ou rótulo"></label>
  <label>Idioma<select name="lang"><option value="">Todos</option><option value="pt-br"<?=$localeFilter===PUBLIC_LOCALE_PT_BR?' selected':''?>>Português</option><option value="en"<?=$localeFilter===PUBLIC_LOCALE_EN?' selected':''?>>English</option></select></label>
  <button class="admin-button secondary" type="submit">Filtrar</button>
  <?php if($query!==''||$localeFilter!==''):?><a class="link-button" href="<?=h($clearUrl)?>">Limpar filtros</a><?php endif;?>
</form>

<div class="site-summary"><span><strong><?=count($visible)?></strong> <?=count($visible)===1?'página exibida':'páginas exibidas'?></span><?php if($query!==''):?><span>Busca: “<?=h($query)?>”</span><?php endif;?></div>

<?php if(!$visible):?>
<section class="admin-empty"><h2>Nenhuma página encontrada</h2><p>Ajuste os filtros ou crie uma nova página.</p></section>
<?php else:?>
<section class="site-page-groups" aria-label="Páginas do site">
<?php foreach($byLocale as $locale=>$localePages):$treeRows=cms_page_tree_rows($localePages);?>
  <section class="site-page-group" aria-labelledby="pages-locale-<?=h(activity_slug((string)$locale))?>">
    <header class="site-page-group-header"><strong id="pages-locale-<?=h(activity_slug((string)$locale))?>"><?=h(public_language_label((string)$locale))?></strong><span><?=admin_quantity_label(count($treeRows),'página','páginas')?></span></header>
    <div>
    <?php foreach($treeRows as $treeRow):
      $page=$treeRow['page'];$depth=(int)$treeRow['depth'];$pageId=(int)$page['id'];
      $unpublished=$page['published_revision']===null||(int)$page['draft_revision']!==(int)$page['published_revision'];
      $url=cms_page_url($activity,$page,$page['locale']);$parentId=(int)($page['parent_page_id']??0);$parent=$parentId>0?($byId[$parentId]??null):null;
      $parentCandidates=cms_page_parent_candidates($db,$page);$translationCandidates=cms_page_translation_candidates($db,$page);$translationGroup=cms_page_translation_group_value($page);$translationPeerId=0;
      if($translationGroup!=='')foreach($translationCandidates as $translationCandidate)if(cms_page_translation_group_value($translationCandidate)===$translationGroup){$translationPeerId=(int)$translationCandidate['id'];break;}
      $siblings=$siblingGroups[(string)$page['locale'].'|'.$parentId]??[];$siblingIds=array_map(static fn(array $item): int=>(int)$item['id'],$siblings);$siblingIndex=array_search($pageId,$siblingIds,true);
      $canMoveUp=$siblingIndex!==false&&$siblingIndex>0;$canMoveDown=$siblingIndex!==false&&$siblingIndex<count($siblings)-1;
    ?>
      <article class="site-page-row<?=$depth>0?' is-child':''?>" style="--page-depth:<?=$depth?>">
        <div class="site-page-node">
          <div class="site-page-title"><strong><?=h((string)$page['title'])?></strong><?php if((int)$page['is_home']===1):?><?=admin_badge('Inicial','neutral')?><?php endif;?></div>
          <a class="site-page-url" href="<?=h($url)?>" target="_blank" rel="noopener"><?=h($url)?></a>
          <div class="site-page-parent"><span>Superior:</span><span><?=$parent?h((string)$parent['title']):'nível principal'?></span>
          <?php if(!(int)$page['is_home']):?><details><summary>Alterar</summary><form method="post" class="site-page-parent-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-pages'))?>"><input type="hidden" name="page_id" value="<?=$pageId?>"><input type="hidden" name="return_q" value="<?=h($query)?>"><input type="hidden" name="return_lang" value="<?=h($localeFilter)?>"><label>Página superior<select name="parent_page_id"><option value="0">Nenhuma — nível principal</option><?php foreach($parentCandidates as $candidate):?><option value="<?=(int)$candidate['id']?>"<?=(int)$candidate['id']===$parentId?' selected':''?>><?=h((string)$candidate['title'])?></option><?php endforeach;?></select></label><button class="admin-button secondary" name="action" value="parent">Salvar</button></form></details><?php endif;?>
          </div>
        </div>
        <div class="site-page-status"><?=admin_badge($unpublished?'Alterações não publicadas':'Publicada',$unpublished?'attention':'good')?></div>
        <div class="site-page-actions">
          <a class="admin-button admin-table-action" href="/editor/?page=<?=$pageId?>">Editar</a>
          <a class="link-button" href="<?=h($url)?>" target="_blank" rel="noopener">Abrir ↗</a>
          <details class="admin-list-menu"><summary aria-label="Mais ações">•••</summary><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-pages'))?>"><input type="hidden" name="page_id" value="<?=$pageId?>"><input type="hidden" name="return_q" value="<?=h($query)?>"><input type="hidden" name="return_lang" value="<?=h($localeFilter)?>"><a class="link-button" href="/admin/page-history.php?page=<?=$pageId?>">Histórico</a><label class="admin-field">Equivalente em outro idioma<select name="translation_page_id"><option value="0">Nenhuma</option><?php foreach($translationCandidates as $candidate):?><option value="<?=(int)$candidate['id']?>"<?=(int)$candidate['id']===$translationPeerId?' selected':''?>><?=h(public_language_label((string)$candidate['locale']).' · '.(string)$candidate['title'])?></option><?php endforeach;?></select></label><button name="action" value="translation">Salvar equivalência</button><button name="action" value="up"<?=$canMoveUp?'':' disabled'?>>Mover para cima</button><button name="action" value="down"<?=$canMoveDown?'':' disabled'?>>Mover para baixo</button><?php if(!(int)$page['is_home']):?><button name="action" value="home">Definir como inicial</button><button class="danger" name="action" value="archive" data-confirm="Arquivar esta página?">Arquivar página</button><?php endif;?></form></details>
        </div>
      </article>
    <?php endforeach;?>
    </div>
  </section>
<?php endforeach;?>
</section>
<?php endif;?>

<dialog id="new-page" class="admin-dialog"><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-pages'))?>"><input type="hidden" name="action" value="create"><header><h2>Criar página</h2></header><label>Título<input name="title" required></label><label>Idioma<select name="locale"><option value="pt-BR">Português (Brasil)</option><option value="en">English</option></select></label><label>Endereço da página<input name="slug" placeholder="ex.: inscricao"></label><label>Começar a partir de<select name="copy_id"><option value="0">Página em branco</option><?php foreach($active as $page):?><option value="<?=(int)$page['id']?>"><?=h(public_language_label((string)$page['locale']).' · '.(string)$page['title'])?></option><?php endforeach;?></select></label><div class="dialog-actions"><button type="button" data-dialog-close>Cancelar</button><button class="admin-button" type="submit">Criar e editar</button></div></form></dialog>
<?php admin_shell_end();
