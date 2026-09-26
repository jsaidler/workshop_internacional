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
        if((int)$parent['activity_id']!==(int)$page['activity_id']||(string)$parent['locale']!==(string)$page['locale'])throw new RuntimeException('A página superior deve pertencer ao mesmo curso e idioma.');
        if((string)$parent['status']==='archived')throw new RuntimeException('Uma página arquivada não pode ser página superior.');
        if(in_array($parentId,cms_page_descendant_ids($db,$pageId),true))throw new RuntimeException('Essa hierarquia criaria um ciclo.');
    }
    $db->prepare('UPDATE cms_pages SET parent_page_id=?,updated_at=? WHERE id=?')->execute([$parentId,utc_now(),$pageId]);
    return cms_page_by_id($db,$pageId)??$page;
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
    if((int)$peer['activity_id']!==(int)$page['activity_id'])throw new RuntimeException('A página equivalente deve pertencer ao mesmo curso.');
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
