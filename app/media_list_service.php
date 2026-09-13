<?php
declare(strict_types=1);

/**
 * Lightweight data used by the media grid. Detailed usage is intentionally
 * resolved only when a single asset is opened; doing it for every thumbnail
 * makes the library cost grow with assets × pages.
 */
function media_list_tags_bulk(PDO $db,array $assetIds): array {
    $ids=array_values(array_unique(array_filter(array_map('intval',$assetIds),fn(int $id)=>$id>0)));
    if(!$ids)return [];
    $out=array_fill_keys($ids,[]);
    $marks=implode(',',array_fill(0,count($ids),'?'));
    $q=$db->prepare("SELECT at.asset_id,t.name FROM media_asset_tags at JOIN media_tags t ON t.id=at.tag_id WHERE at.asset_id IN ($marks) ORDER BY t.name");
    $q->execute($ids);
    foreach($q->fetchAll() as $row)$out[(int)$row['asset_id']][]=(string)$row['name'];
    return $out;
}

function media_list_usage_counts(PDO $db,array $assetIds): array {
    $ids=array_values(array_unique(array_filter(array_map('intval',$assetIds),fn(int $id)=>$id>0)));
    if(!$ids)return [];
    $wanted=array_fill_keys($ids,true);
    $counts=array_fill_keys($ids,0);

    // Legacy structured documents: scan the documents once, not once per asset.
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

    // CMS pages: one pass through all page documents, counting at most once
    // per asset/document just like media_usage_all().
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
