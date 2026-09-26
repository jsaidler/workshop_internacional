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

$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];
if(!$activity){header('Location: /admin/activities.php');exit;}
$activityId=(int)$activity['id'];

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
    header('Location: /admin/pages.php?activity='.$activityId);
    exit;
}

$pages=cms_pages($db,$activityId,null,true);
$active=array_values(array_filter($pages,fn(array $p)=>$p['status']!=='archived'));
$byId=[];
$byLocale=[];
$siblingGroups=[];
foreach($active as $candidate){
    $id=(int)$candidate['id'];
    $locale=(string)$candidate['locale'];
    $parentId=(int)($candidate['parent_page_id']??0);
    $byId[$id]=$candidate;
    $byLocale[$locale][]=$candidate;
    $siblingGroups[$locale.'|'.$parentId][]=$candidate;
}
foreach($siblingGroups as &$siblings){
    usort($siblings,static fn(array $a,array $b): int=>[(int)$a['sort_order'],(int)$a['id']]<=>[(int)$b['sort_order'],(int)$b['id']]);
}
unset($siblings);
$notice=$_SESSION['admin_notice']??null;
unset($_SESSION['admin_notice']);

admin_shell_start('pages','Páginas',$state);?>
<?php if($notice):?><div class="admin-notice"><?=h((string)$notice)?></div><?php endif;?>

<section class="overview-hero pages-hero">
    <div>
        <h2>Páginas do site</h2>
        <p>Organize páginas e subpáginas sem alterar seus endereços. A navegação pública continua sendo configurada separadamente em Navegação.</p>
    </div>
    <div class="hero-actions">
        <button class="admin-button" type="button" data-dialog-open="new-page">Nova página</button>
    </div>
</section>

<?php if(!$active):?>
<section class="admin-empty"><h2>Nenhuma página</h2><p>Crie a primeira página para começar.</p></section>
<?php else:?>
<section class="pages-tree" aria-label="Páginas do site">
<?php foreach($byLocale as $locale=>$localePages):$treeRows=cms_page_tree_rows($localePages);?>
    <section class="pages-locale-group" aria-labelledby="pages-locale-<?=h(activity_slug((string)$locale))?>">
        <header class="pages-locale-header">
            <div>
                <span>Idioma</span>
                <strong id="pages-locale-<?=h(activity_slug((string)$locale))?>"><?=h(public_language_label((string)$locale))?></strong>
            </div>
            <span class="pages-locale-count"><?=count($treeRows)?> <?=count($treeRows)===1?'página':'páginas'?></span>
        </header>
        <div class="pages-tree-head" aria-hidden="true"><div>Página</div><div>Estado</div><div>Ações</div></div>
        <div class="pages-tree-body">
        <?php foreach($treeRows as $treeRow):
            $page=$treeRow['page'];
            $depth=(int)$treeRow['depth'];
            $pageId=(int)$page['id'];
            $unpublished=$page['published_revision']===null||(int)$page['draft_revision']!==(int)$page['published_revision'];
            $url=cms_page_url($activity,$page,$page['locale']);
            $parentId=(int)($page['parent_page_id']??0);
            $parent=$parentId>0?($byId[$parentId]??null):null;
            $parentCandidates=cms_page_parent_candidates($db,$page);
            $translationCandidates=cms_page_translation_candidates($db,$page);
            $translationGroup=cms_page_translation_group_value($page);
            $translationPeerId=0;
            if($translationGroup!=='')foreach($translationCandidates as $translationCandidate)if(cms_page_translation_group_value($translationCandidate)===$translationGroup){$translationPeerId=(int)$translationCandidate['id'];break;}
            $siblingKey=(string)$page['locale'].'|'.$parentId;
            $siblings=$siblingGroups[$siblingKey]??[];
            $siblingIds=array_map(static fn(array $item): int=>(int)$item['id'],$siblings);
            $siblingIndex=array_search($pageId,$siblingIds,true);
            $canMoveUp=$siblingIndex!==false&&$siblingIndex>0;
            $canMoveDown=$siblingIndex!==false&&$siblingIndex<count($siblings)-1;
        ?>
            <article class="page-tree-row<?=$depth>0?' is-child':''?>" data-page-depth="<?=$depth?>" style="--page-depth:<?=$depth?>">
                <div class="page-tree-main">
                    <div class="page-tree-node">
                        <div class="page-title-line">
                            <strong><?=h((string)$page['title'])?></strong>
                            <?php if((int)$page['is_home']===1):?><span class="page-chip">Inicial</span><?php endif;?>
                        </div>
                        <a class="page-url" href="<?=h($url)?>" target="_blank" rel="noopener"><?=h($url)?></a>
                        <div class="page-hierarchy">
                            <span class="page-hierarchy-label">Página superior</span>
                            <span class="page-hierarchy-value"><?=$parent?h((string)$parent['title']):'Nenhuma — nível principal'?></span>
                            <?php if(!(int)$page['is_home']):?>
                            <details class="page-parent-editor">
                                <summary>Alterar</summary>
                                <form method="post" class="page-parent-popover">
                                    <input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-pages'))?>">
                                    <input type="hidden" name="page_id" value="<?=$pageId?>">
                                    <label class="admin-field">Página superior
                                        <select name="parent_page_id">
                                            <option value="0">Nenhuma — nível principal</option>
                                            <?php foreach($parentCandidates as $candidate):?><option value="<?=(int)$candidate['id']?>"<?=(int)$candidate['id']===$parentId?' selected':''?>><?=h((string)$candidate['title'])?></option><?php endforeach;?>
                                        </select>
                                    </label>
                                    <div class="page-popover-actions">
                                        <button class="admin-button" name="action" value="parent">Salvar hierarquia</button>
                                    </div>
                                </form>
                            </details>
                            <?php endif;?>
                        </div>
                    </div>
                </div>
                <div class="page-tree-status">
                    <span class="page-status-badge" data-state="<?=$unpublished?'draft':'published'?>"><?=$unpublished?'Rascunho com alterações':'Publicado'?></span>
                </div>
                <div class="page-tree-actions">
                    <a class="page-action page-action-primary" href="/editor/?page=<?=$pageId?>">Editar</a>
                    <a class="page-action" href="<?=h($url)?>" target="_blank" rel="noopener">Abrir ↗</a>
                    <details class="admin-list-menu page-more-menu">
                        <summary aria-label="Mais ações">•••</summary>
                        <form method="post" class="page-more-panel">
                            <input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-pages'))?>">
                            <input type="hidden" name="page_id" value="<?=$pageId?>">
                            <a class="page-menu-link" href="/admin/page-history.php?page=<?=$pageId?>">Histórico</a>
                            <div class="page-menu-section">
                                <label class="admin-field">Equivalente em outro idioma
                                    <select name="translation_page_id">
                                        <option value="0">Nenhuma</option>
                                        <?php foreach($translationCandidates as $candidate):?><option value="<?=(int)$candidate['id']?>"<?=(int)$candidate['id']===$translationPeerId?' selected':''?>><?=h(public_language_label((string)$candidate['locale']).' · '.(string)$candidate['title'])?></option><?php endforeach;?>
                                    </select>
                                </label>
                                <button name="action" value="translation">Salvar equivalência</button>
                            </div>
                            <div class="page-menu-section page-menu-actions">
                                <button name="action" value="up"<?=$canMoveUp?'':' disabled'?>>Mover para cima</button>
                                <button name="action" value="down"<?=$canMoveDown?'':' disabled'?>>Mover para baixo</button>
                                <?php if(!(int)$page['is_home']):?>
                                <button name="action" value="home">Definir como página inicial</button>
                                <button class="danger" name="action" value="archive" data-confirm="Arquivar esta página?">Arquivar página</button>
                                <?php endif;?>
                            </div>
                        </form>
                    </details>
                </div>
            </article>
        <?php endforeach;?>
        </div>
    </section>
<?php endforeach;?>
</section>
<?php endif;?>

<dialog id="new-page" class="admin-dialog">
    <form method="post">
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('cms-pages'))?>">
        <input type="hidden" name="action" value="create">
        <header><h2>Criar página</h2></header>
        <label>Título<input name="title" required></label>
        <label>Idioma<select name="locale"><option value="pt-BR">Português (Brasil)</option><option value="en">English</option></select></label>
        <label>Endereço da página<input name="slug" placeholder="ex.: inscricao"></label>
        <label>Começar a partir de<select name="copy_id"><option value="0">Página em branco</option><?php foreach($active as $page):?><option value="<?=(int)$page['id']?>"><?=h(public_language_label($page['locale']).' · '.$page['title'])?></option><?php endforeach;?></select></label>
        <div class="dialog-actions"><button type="button" data-dialog-close>Cancelar</button><button class="admin-button" type="submit">Criar e editar</button></div>
    </form>
</dialog>
<?php admin_shell_end();
