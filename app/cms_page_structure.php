<?php
declare(strict_types=1);

function cms_page_structure_available(PDO $db): bool {
    static $cache=[];$key=spl_object_id($db);
    if(array_key_exists($key,$cache))return $cache[$key];
    $columns=$db->query('PRAGMA table_info(cms_pages)')->fetchAll(PDO::FETCH_ASSOC);
    $names=array_map(static fn(array $row): string=>(string)$row['name'],$columns);
    return $cache[$key]=in_array('parent_page_id',$names,true)&&in_array('translation_group_uuid',$names,true);
}
function cms_page_descendant_ids(PDO $db,int $pageId): array {
    if(!cms_page_structure_available($db))return [];
    $seen=[];$frontier=[$pageId];
    while($frontier){
        $placeholders=implode(',',array_fill(0,count($frontier),'?'));
        $q=$db->prepare("SELECT id FROM cms_pages WHERE parent_page_id IN ($placeholders)");$q->execute($frontier);
        $next=[];foreach($q->fetchAll(PDO::FETCH_COLUMN) as $raw){$id=(int)$raw;if($id<=0||isset($seen[$id])||$id===$pageId)continue;$seen[$id]=true;$next[]=$id;}
        $frontier=$next;
    }
    return array_map('intval',array_keys($seen));
}
function cms_page_parent_candidates(PDO $db,array $page): array {
    if(!cms_page_structure_available($db))return [];
    $q=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND locale=? AND status!='archived' AND id<>? ORDER BY is_home DESC,sort_order,id");
    $q->execute([(int)$page['activity_id'],(string)$page['locale'],(int)$page['id']]);
    $blocked=array_flip(cms_page_descendant_ids($db,(int)$page['id']));
    return array_values(array_filter($q->fetchAll(),static fn(array $candidate): bool=>!isset($blocked[(int)$candidate['id']])));
}
function cms_page_set_parent(PDO $db,int $pageId,?int $parentId): array {
    if(!cms_page_structure_available($db))throw new RuntimeException('Estrutura editorial de páginas indisponível.');
    $page=cms_page_by_id($db,$pageId)??throw new RuntimeException('Página não encontrada.');
    if((int)$page['is_home']===1&&$parentId!==null)throw new RuntimeException('A página inicial não pode ser subpágina.');
    if($parentId!==null){
        if($parentId===$pageId)throw new RuntimeException('Uma página não pode ser filha dela mesma.');
        $parent=cms_page_by_id($db,$parentId)??throw new RuntimeException('Página superior não encontrada.');
        if((int)$parent['activity_id']!==(int)$page['activity_id']||(string)$parent['locale']!==(string)$page['locale'])throw new RuntimeException('A página superior deve pertencer ao mesmo site e idioma.');
        if((string)$parent['status']==='archived')throw new RuntimeException('Uma página arquivada não pode ser página superior.');
        if(in_array($parentId,cms_page_descendant_ids($db,$pageId),true))throw new RuntimeException('Essa hierarquia criaria um ciclo.');
    }
    $db->prepare('UPDATE cms_pages SET parent_page_id=?,updated_at=? WHERE id=?')->execute([$parentId,utc_now(),$pageId]);
    return cms_page_by_id($db,$pageId)??$page;
}
function cms_page_tree_rows(array $pages): array {
    $byParent=[];$known=[];
    foreach($pages as $page){$id=(int)($page['id']??0);if($id<1)continue;$known[$id]=true;}
    foreach($pages as $page){$id=(int)($page['id']??0);if($id<1)continue;$parentId=(int)($page['parent_page_id']??0);if($parentId<1||!isset($known[$parentId]))$parentId=0;$byParent[$parentId][]=$page;}
    $sort=static function(array &$rows): void {usort($rows,static fn(array $a,array $b): int=>[(int)($a['sort_order']??0),(int)($a['id']??0)]<=>[(int)($b['sort_order']??0),(int)($b['id']??0)]);};
    foreach($byParent as &$siblings)$sort($siblings);unset($siblings);
    $out=[];$seen=[];$walk=function(int $parentId,int $depth)use(&$walk,&$out,&$seen,$byParent):void{foreach($byParent[$parentId]??[] as $page){$id=(int)$page['id'];if(isset($seen[$id]))continue;$seen[$id]=true;$out[]=['page'=>$page,'depth'=>$depth];$walk($id,$depth+1);}};$walk(0,0);
    foreach($pages as $page){$id=(int)($page['id']??0);if($id>0&&!isset($seen[$id]))$out[]=['page'=>$page,'depth'=>0];}
    return $out;
}
function cms_page_siblings(PDO $db,array $page): array {
    $parentId=(int)($page['parent_page_id']??0);$sql="SELECT * FROM cms_pages WHERE activity_id=? AND locale=? AND status!='archived' AND ".($parentId>0?'parent_page_id=?':'parent_page_id IS NULL')." ORDER BY sort_order,id";$args=[(int)$page['activity_id'],(string)$page['locale']];if($parentId>0)$args[]=$parentId;$q=$db->prepare($sql);$q->execute($args);return $q->fetchAll();
}
function cms_page_move_sibling(PDO $db,int $pageId,int $direction): void {
    if(!in_array($direction,[-1,1],true))throw new RuntimeException('Direção inválida.');$page=cms_page_by_id($db,$pageId)??throw new RuntimeException('Página não encontrada.');$rows=cms_page_siblings($db,$page);$index=null;foreach($rows as $i=>$row)if((int)$row['id']===$pageId){$index=$i;break;}if($index===null)return;$target=$index+$direction;if($target<0||$target>=count($rows))return;$a=$rows[$index];$b=$rows[$target];$db->beginTransaction();try{$now=utc_now();$db->prepare('UPDATE cms_pages SET sort_order=?,updated_at=? WHERE id=?')->execute([(int)$b['sort_order'],$now,(int)$a['id']]);$db->prepare('UPDATE cms_pages SET sort_order=?,updated_at=? WHERE id=?')->execute([(int)$a['sort_order'],$now,(int)$b['id']]);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function cms_page_translation_candidates(PDO $db,array $page): array {
    if(!cms_page_structure_available($db))return [];
    $q=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND locale<>? AND status!='archived' ORDER BY locale,is_home DESC,sort_order,id");
    $q->execute([(int)$page['activity_id'],(string)$page['locale']]);
    return $q->fetchAll();
}
function cms_page_translation_group_value(array $page): string {return trim((string)($page['translation_group_uuid']??''));}
function cms_page_translation_counterpart(PDO $db,array $page,string $locale): ?array {
    $locale=activity_locale_normalize($locale);
    if((string)$page['locale']===$locale)return $page;
    $group=cms_page_translation_group_value($page);
    if($group!==''&&cms_page_structure_available($db)){
        $q=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND translation_group_uuid=? AND locale=? AND status!='archived' LIMIT 1");
        $q->execute([(int)$page['activity_id'],$group,$locale]);$candidate=$q->fetch();
        if($candidate)return $candidate;
    }
    return (int)$page['is_home']===1
        ?cms_page_home($db,(int)$page['activity_id'],$locale)
        :cms_page_by_slug($db,(int)$page['activity_id'],$locale,(string)$page['slug']);
}
function cms_page_set_translation_peer(PDO $db,int $pageId,?int $peerId): array {
    if(!cms_page_structure_available($db))throw new RuntimeException('Equivalência de páginas indisponível.');
    $page=cms_page_by_id($db,$pageId)??throw new RuntimeException('Página não encontrada.');
    if($peerId===null){
        $db->prepare('UPDATE cms_pages SET translation_group_uuid=NULL,updated_at=? WHERE id=?')->execute([utc_now(),$pageId]);
        return cms_page_by_id($db,$pageId)??$page;
    }
    if($peerId===$pageId)throw new RuntimeException('Selecione uma página de outro idioma.');
    $peer=cms_page_by_id($db,$peerId)??throw new RuntimeException('Página equivalente não encontrada.');
    if((int)$peer['activity_id']!==(int)$page['activity_id'])throw new RuntimeException('A página equivalente deve pertencer ao mesmo site.');
    if((string)$peer['locale']===(string)$page['locale'])throw new RuntimeException('A página equivalente deve estar em outro idioma.');
    if((string)$peer['status']==='archived')throw new RuntimeException('Uma página arquivada não pode ser equivalente.');
    $group=cms_page_translation_group_value($peer)?:cms_page_translation_group_value($page)?:cms_page_uuid();
    $check=$db->prepare('SELECT id FROM cms_pages WHERE activity_id=? AND translation_group_uuid=? AND locale=? AND id NOT IN (?,?) LIMIT 1');
    foreach([(string)$page['locale'],(string)$peer['locale']] as $locale){$check->execute([(int)$page['activity_id'],$group,$locale,$pageId,$peerId]);if($check->fetchColumn())throw new RuntimeException('Esse grupo de tradução já possui uma página nesse idioma.');}
    $db->beginTransaction();
    try{
        $now=utc_now();$q=$db->prepare('UPDATE cms_pages SET translation_group_uuid=?,updated_at=? WHERE id=?');$q->execute([$group,$now,$pageId]);$q->execute([$group,$now,$peerId]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return cms_page_by_id($db,$pageId)??$page;
}

/** Resolve the editorial workshop authority for any page.
 * The workshop is the highest non-archived ancestor in the same site and locale.
 */
function cms_page_workshop_root(PDO $db,array|int $page): ?array {
    $current=is_array($page)?$page:cms_page_by_id($db,$page);if(!$current)return null;
    $activityId=(int)($current['activity_id']??0);$locale=(string)($current['locale']??'');$seen=[];
    while(true){
        $id=(int)($current['id']??0);if($id<1||isset($seen[$id]))throw new RuntimeException('Hierarquia de páginas inválida.');$seen[$id]=true;
        if((string)($current['status']??'active')==='archived')return null;
        $parentId=(int)($current['parent_page_id']??0);if($parentId<1)return $current;
        $parent=cms_page_by_id($db,$parentId);if(!$parent)return $current;
        if((int)$parent['activity_id']!==$activityId||(string)$parent['locale']!==$locale)throw new RuntimeException('Hierarquia de páginas cruza site ou idioma.');
        $current=$parent;
    }
}
function cms_page_workshop_root_id(PDO $db,array|int $page): int {return (int)(cms_page_workshop_root($db,$page)['id']??0);}
function cms_page_workshop_ids(PDO $db,int $workshopPageId): array {
    $root=cms_page_workshop_root($db,$workshopPageId);if(!$root||(int)$root['id']!==$workshopPageId)return [];
    return array_values(array_unique(array_merge([$workshopPageId],cms_page_descendant_ids($db,$workshopPageId))));
}
function cms_page_belongs_to_workshop(PDO $db,array|int $page,int $workshopPageId): bool {return cms_page_workshop_root_id($db,$page)===$workshopPageId;}
