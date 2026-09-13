<?php
declare(strict_types=1);

/**
 * Lightweight data used by the media grid. Detailed usage/version history is
 * intentionally resolved only when a single asset is opened. The grid should
 * scale with the number of assets, not assets × pages × versions.
 */
function media_list_ids(array $ids): array {
    return array_values(array_unique(array_filter(array_map('intval',$ids),fn(int $id)=>$id>0)));
}

function media_list_tags_bulk(PDO $db,array $assetIds): array {
    $ids=media_list_ids($assetIds);
    if(!$ids)return [];
    $out=array_fill_keys($ids,[]);
    $marks=implode(',',array_fill(0,count($ids),'?'));
    $q=$db->prepare("SELECT at.asset_id,t.name FROM media_asset_tags at JOIN media_tags t ON t.id=at.tag_id WHERE at.asset_id IN ($marks) ORDER BY t.name");
    $q->execute($ids);
    foreach($q->fetchAll() as $row)$out[(int)$row['asset_id']][]=(string)$row['name'];
    return $out;
}

function media_list_usage_counts(PDO $db,array $assetIds): array {
    $ids=media_list_ids($assetIds);
    if(!$ids)return [];
    $wanted=array_fill_keys($ids,true);
    $counts=array_fill_keys($ids,0);

    try{
        $q=$db->query('SELECT draft_json,published_json FROM content_documents');
        foreach($q->fetchAll() as $row){
            foreach(['draft_json','published_json'] as $column){
                $document=json_decode((string)($row[$column]??''),true);
                if(!is_array($document))continue;
                $seen=[];
                foreach(($document['images']??[]) as $value){
                    $id=(int)($value['mediaAssetId']??0);
                    if(isset($wanted[$id]))$seen[$id]=true;
                }
                foreach(array_keys($seen) as $id)$counts[$id]++;
            }
        }
    }catch(Throwable){ }

    try{
        $q=$db->query("SELECT draft_document_json,published_document_json FROM cms_pages WHERE status!='archived'");
        foreach($q->fetchAll() as $row){
            foreach(['draft_document_json','published_document_json'] as $column){
                $json=$row[$column]??null;
                if(!is_string($json)||$json==='')continue;
                $document=json_decode($json,true);
                $html=is_array($document)?(string)($document['html']??''):'';
                if($html===''||!str_contains($html,'data-media-asset-id'))continue;
                preg_match_all('/data-media-asset-id=["\'](\d+)["\']/',$html,$matches);
                foreach(array_unique(array_map('intval',$matches[1]??[])) as $id)if(isset($wanted[$id]))$counts[$id]++;
            }
        }
    }catch(Throwable){ }

    return $counts;
}

function media_list_derivatives_bulk(PDO $db,array $versionIds): array {
    $ids=media_list_ids($versionIds);
    if(!$ids)return [];
    $out=array_fill_keys($ids,[]);
    $marks=implode(',',array_fill(0,count($ids),'?'));
    $q=$db->prepare("SELECT * FROM media_derivatives WHERE version_id IN ($marks) ORDER BY width,format");
    $q->execute($ids);
    foreach($q->fetchAll() as $row){
        $versionId=(int)$row['version_id'];
        $out[$versionId][]=[
            'id'=>(int)$row['id'],
            'kind'=>(string)$row['derivative_kind'],
            'width'=>(int)$row['width'],
            'height'=>(int)$row['height'],
            'format'=>(string)$row['format'],
            'mimeType'=>$row['mime_type']??($row['format']==='jpeg'?'image/jpeg':'image/'.$row['format']),
            'path'=>(string)$row['path'],
            'sizeBytes'=>(int)$row['byte_size'],
            'checksum'=>$row['checksum']??'',
            'src'=>media_url((string)$row['path']),
        ];
    }
    return $out;
}

function media_list_posters_bulk(PDO $db,array $assetVersionMap): array {
    $assetIds=media_list_ids(array_keys($assetVersionMap));
    if(!$assetIds)return [];
    $marks=implode(',',array_fill(0,count($assetIds),'?'));
    $q=$db->prepare("SELECT p.video_asset_id,p.video_version_id,p.id poster_id,p.timestamp_seconds,p.created_at,v.path,v.mime_type,v.format,v.width FROM media_posters p JOIN media_poster_variants v ON v.poster_id=p.id WHERE p.video_asset_id IN ($marks) ORDER BY p.video_asset_id,p.id DESC,CASE v.format WHEN 'jpeg' THEN 0 WHEN 'webp' THEN 1 ELSE 2 END,v.width DESC");
    $q->execute($assetIds);
    $out=[];
    foreach($q->fetchAll() as $row){
        $assetId=(int)$row['video_asset_id'];
        if(isset($out[$assetId]))continue;
        if((int)($assetVersionMap[$assetId]??0)!==(int)$row['video_version_id'])continue;
        $out[$assetId]=[
            'id'=>(int)$row['poster_id'],
            'timestamp'=>(float)$row['timestamp_seconds'],
            'createdAt'=>(string)$row['created_at'],
            'src'=>media_url((string)$row['path']),
            'mimeType'=>(string)$row['mime_type'],
        ];
    }
    return $out;
}

function media_list_grid_items(PDO $db,string $status='active'): array {
    $where=$status==='archived'?'a.archived_at IS NOT NULL':($status==='all'?'1=1':'a.archived_at IS NULL');
    $rows=$db->query("SELECT a.*,v.original_path,v.version_uuid,v.processing_status version_processing_status FROM media_assets a LEFT JOIN media_versions v ON v.id=a.active_version_id WHERE $where ORDER BY a.id DESC")->fetchAll();
    if(!$rows)return [];

    $assetIds=array_map(fn(array $row)=>(int)$row['id'],$rows);
    $versionIds=array_values(array_filter(array_map(fn(array $row)=>(int)($row['active_version_id']??0),$rows)));
    $tags=media_list_tags_bulk($db,$assetIds);
    $usageCounts=media_list_usage_counts($db,$assetIds);
    $derivatives=media_list_derivatives_bulk($db,$versionIds);
    $videoVersionMap=[];
    foreach($rows as $row)if(($row['kind']??'')==='video')$videoVersionMap[(int)$row['id']]=(int)($row['active_version_id']??0);
    $posters=media_list_posters_bulk($db,$videoVersionMap);

    $items=[];
    foreach($rows as $row){
        $id=(int)$row['id'];
        $versionId=(int)($row['active_version_id']??0);
        $row['id']=$id;
        $row['active_version_id']=$versionId?:null;
        // PNG artwork is delivered from the untouched original. Older
        // responsive PNG derivatives can be visually corrupt while keeping
        // valid dimensions/checksums, so they must not drive library/editor
        // previews. JPEG/WebP assets keep their responsive derivatives.
        $row['derivatives']=(($row['kind']??'')==='image'&&($row['mime_type']??'')==='image/png')?[]:($versionId?($derivatives[$versionId]??[]):[]);
        $row['versions']=[];
        $row['tags']=$tags[$id]??[];
        $row['uses']=array_fill(0,(int)($usageCounts[$id]??0),true);
        $row['url']=($row['kind']??'')==='video'
            ?media_video_url($id,$versionId,(string)($row['original_path']??''))
            :media_url((string)($row['original_path']??''));
        $row['poster']=$posters[$id]??null;
        $row['posters']=[];
        $items[]=$row;
    }
    return $items;
}
