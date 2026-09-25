<?php
declare(strict_types=1);

function media_asset_tags(PDO $db,int $assetId): array {
    $q=$db->prepare('SELECT t.name FROM media_tags t JOIN media_asset_tags at ON at.tag_id=t.id WHERE at.asset_id=? ORDER BY t.name');
    $q->execute([$assetId]);
    return array_values(array_map('strval',$q->fetchAll(PDO::FETCH_COLUMN)));
}

function media_clean_tags(mixed $tags): array {
    if(is_string($tags))$tags=preg_split('/[,\n]+/u',$tags)?:[];
    if(!is_array($tags))return [];
    $out=[];
    foreach($tags as $tag){$tag=trim((string)$tag);if($tag===''||mb_strlen($tag)>60)continue;$out[mb_strtolower($tag)]=$tag;}
    return array_values($out);
}

function media_set_tags(PDO $db,int $assetId,array $tags): void {
    $db->prepare('DELETE FROM media_asset_tags WHERE asset_id=?')->execute([$assetId]);
    $insertTag=$db->prepare('INSERT OR IGNORE INTO media_tags(name,created_at) VALUES(?,?)');
    $find=$db->prepare('SELECT id FROM media_tags WHERE name=? COLLATE NOCASE LIMIT 1');
    $link=$db->prepare('INSERT OR IGNORE INTO media_asset_tags(asset_id,tag_id) VALUES(?,?)');
    foreach(media_clean_tags($tags) as $tag){$insertTag->execute([$tag,gmdate('c')]);$find->execute([$tag]);$id=(int)$find->fetchColumn();if($id)$link->execute([$assetId,$id]);}
}

function media_asset_admin(PDO $db,int $id): array {
    $asset=media_asset($db,$id);
    $asset['tags']=media_asset_tags($db,$id);
    $asset['uses']=media_usage_all($db,$id);
    return function_exists('media_rewrite_admin_delivery')?media_rewrite_admin_delivery($asset):$asset;
}

function media_update_metadata(PDO $db,int $assetId,array $input): array {
    $asset=media_asset($db,$assetId);
    $title=trim((string)($input['title']??$asset['title']));
    $alt=trim((string)($input['default_alt']??$asset['default_alt']??''));
    $caption=trim((string)($input['caption']??$asset['caption']??''));
    $description=trim((string)($input['description']??$asset['description']??''));
    $focalX=max(0,min(100,(float)($input['focal_x']??$asset['focal_x']??50)));
    $focalY=max(0,min(100,(float)($input['focal_y']??$asset['focal_y']??50)));
    $visibility=(string)($input['visibility']??(function_exists('media_asset_visibility')?media_asset_visibility($asset):(string)($asset['visibility']??'public')));
    $allowed=function_exists('media_visibility_values')?media_visibility_values():['public','private'];
    if(!in_array($visibility,$allowed,true))throw new RuntimeException('invalid_visibility');
    if($title==='')$title=pathinfo((string)$asset['original_name'],PATHINFO_FILENAME);
    foreach([$title,$alt,$caption] as $value)if(mb_strlen($value)>500)throw new RuntimeException('metadata_too_long');
    if(mb_strlen($description)>5000)throw new RuntimeException('metadata_too_long');
    $tags=media_clean_tags($input['tags']??[]);
    $db->beginTransaction();
    try{
        $q=$db->prepare('UPDATE media_assets SET title=?,default_alt=?,caption=?,description=?,focal_x=?,focal_y=?,updated_at=? WHERE id=?');
        $q->execute([$title,$alt,$caption,$description,$focalX,$focalY,gmdate('c'),$assetId]);
        media_set_tags($db,$assetId,$tags);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    $currentVisibility=function_exists('media_asset_visibility')?media_asset_visibility($asset):(string)($asset['visibility']??'public');
    if($visibility!==$currentVisibility){
        if(!function_exists('media_set_visibility'))throw new RuntimeException('media_visibility_service_unavailable');
        return media_set_visibility($db,$assetId,$visibility);
    }
    return media_asset_admin($db,$assetId);
}

function media_archive_asset(PDO $db,int $assetId,bool $archive=true): array {
    media_asset($db,$assetId);
    if($archive&&media_usage_all($db,$assetId))throw new RuntimeException('asset_in_use');
    $q=$db->prepare('UPDATE media_assets SET archived_at=?,updated_at=? WHERE id=?');
    $q->execute([$archive?gmdate('c'):null,gmdate('c'),$assetId]);
    return media_asset_admin($db,$assetId);
}

function media_find_asset_by_url(PDO $db,string $url): ?array {
    $url=trim($url);if($url==='')return null;
    foreach(media_list($db) as $asset){
        if(($asset['url']??'')===$url)return $asset;
        foreach(($asset['derivatives']??[]) as $d)if(($d['src']??'')===$url)return $asset;
        if(($asset['poster']['src']??'')===$url)return $asset;
    }
    return null;
}

function media_usage_all(PDO $db,int $assetId): array {
    $uses=[];
    try{foreach(media_usage($db,$assetId) as $use)$uses[]=$use+['source'=>'legacy'];}catch(Throwable){ }
    $q=$db->prepare("SELECT p.id,p.title,p.slug,p.locale,p.draft_document_json,p.published_document_json,a.admin_name,a.slug activity_slug FROM cms_pages p JOIN activities a ON a.id=p.activity_id WHERE p.status!='archived'");
    $q->execute();
    foreach($q->fetchAll() as $row){
        foreach(['draft_document_json'=>'Rascunho','published_document_json'=>'Publicado'] as $column=>$state){
            $json=$row[$column]??null;if(!is_string($json)||$json==='')continue;
            $document=json_decode($json,true);$html=is_array($document)?(string)($document['html']??''):'';if($html==='')continue;
            $needle='data-media-asset-id="'.$assetId.'"';
            if(str_contains($html,$needle))$uses[]=['source'=>'cms','activity'=>$row['admin_name'],'slug'=>$row['activity_slug'],'state'=>$state,'pageId'=>(int)$row['id'],'page'=>$row['title'],'pageSlug'=>$row['slug'],'locale'=>$row['locale'],'element'=>'structured media'];
        }
    }
    try{
        $q=$db->prepare('SELECT s.page_id,s.slot_key,p.title,p.slug,p.locale,a.admin_name,a.slug activity_slug FROM course_page_media_slots s JOIN cms_pages p ON p.id=s.page_id JOIN activities a ON a.id=p.activity_id WHERE s.media_asset_id=?');$q->execute([$assetId]);
        foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row)$uses[]=['source'=>'protected-slot','activity'=>$row['admin_name'],'slug'=>$row['activity_slug'],'state'=>'Protegido','pageId'=>(int)$row['page_id'],'page'=>$row['title'],'pageSlug'=>$row['slug'],'locale'=>$row['locale'],'element'=>'slot: '.$row['slot_key']];
    }catch(Throwable){ }
    return $uses;
}

function media_resolve_cms_html(PDO $db,string $html): string {
    if(!str_contains($html,'data-media-asset-id'))return $html;
    $previous=libxml_use_internal_errors(true);
    $dom=new DOMDocument('1.0','UTF-8');
    $dom->loadHTML('<?xml encoding="utf-8" ?><div id="media-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
    $xpath=new DOMXPath($dom);
    foreach(iterator_to_array($xpath->query('//*[@data-media-asset-id]')?:[]) as $node){
        if(!$node instanceof DOMElement)continue;
        $assetId=(int)$node->getAttribute('data-media-asset-id');
        if($assetId<1)continue;
        try{$asset=media_asset($db,$assetId);}catch(Throwable){continue;}
        $isPrivate=(string)($asset['visibility']??'public')==='private';
        $isAdmin=$isPrivate&&function_exists('current_admin')?(bool)current_admin():false;
        if($isPrivate&&!$isAdmin){$node->parentNode?->removeChild($node);continue;}
        $versionId=(int)($asset['active_version_id']??0);
        if($node->getAttribute('data-media-version-mode')==='pinned'&&$node->hasAttribute('data-media-version-id'))$versionId=(int)$node->getAttribute('data-media-version-id');
        if(strtolower($node->tagName)==='img'&&$asset['kind']==='image'){
            $sources=media_image_sources($db,$assetId,$versionId,(string)$node->getAttribute('src'));
            if($isPrivate&&function_exists('media_admin_private_url')){
                $srcPath=(string)($asset['original_path']??'');$sources=['src'=>media_admin_private_url($assetId,$srcPath),'srcset'=>''];
            }
            $node->setAttribute('src',$sources['src']);
            if($sources['srcset']!==''){$node->setAttribute('srcset',$sources['srcset']);$node->setAttribute('sizes','(max-width: 720px) 100vw, 50vw');}else{$node->removeAttribute('srcset');$node->removeAttribute('sizes');}
            $x=$node->hasAttribute('data-focal-x')?(float)$node->getAttribute('data-focal-x'):(float)($asset['focal_x']??50);
            $y=$node->hasAttribute('data-focal-y')?(float)$node->getAttribute('data-focal-y'):(float)($asset['focal_y']??50);
            $fit=$node->getAttribute('data-fit')==='contain'?'contain':'cover';
            $existing=trim($node->getAttribute('style'));$existing=preg_replace('/(?:object-fit|object-position|--cms-media-fit|--cms-media-position)\s*:[^;]+;?/i','',$existing)??$existing;
            $node->setAttribute('style',trim($existing.';--cms-media-fit:'.$fit.';--cms-media-position:'.$x.'% '.$y.'%;',';'));
            if(trim($node->getAttribute('alt'))===''&&trim((string)($asset['default_alt']??''))!=='')$node->setAttribute('alt',(string)$asset['default_alt']);
        }
    }
    $root=$dom->getElementById('media-root');$out='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);
    libxml_clear_errors();libxml_use_internal_errors($previous);return $out;
}
