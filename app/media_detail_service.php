<?php
declare(strict_types=1);

/**
 * Detail payload for one media asset.
 *
 * The old media_asset_admin() calls media_usage_all(), which scans every
 * document in PHP. That is acceptable for maintenance jobs but is the wrong
 * cost model for an interactive drawer. Here SQLite first narrows the rows to
 * documents that actually contain this asset id; PHP only decodes the small
 * matching set.
 */
function media_detail_admin(PDO $db,int $assetId): array {
    $asset=media_asset($db,$assetId);
    $asset['tags']=media_asset_tags($db,$assetId);
    $asset['uses']=[];
    $activeVersion=(int)($asset['active_version_id']??0);

    // Legacy content_documents references are JSON produced by json_encode.
    // Accept both compact and pretty-printed separators when narrowing rows.
    try{
        $compact='"mediaAssetId":'.$assetId;
        $spaced='"mediaAssetId": '.$assetId;
        $q=$db->prepare(
            "SELECT a.admin_name,a.slug,c.draft_json,c.published_json
             FROM activities a
             JOIN content_documents c ON c.activity_id=a.id
             WHERE instr(COALESCE(c.draft_json,''),?)>0
                OR instr(COALESCE(c.draft_json,''),?)>0
                OR instr(COALESCE(c.published_json,''),?)>0
                OR instr(COALESCE(c.published_json,''),?)>0
             ORDER BY a.id"
        );
        $q->execute([$compact,$spaced,$compact,$spaced]);
        foreach($q->fetchAll() as $row){
            foreach(['draft_json'=>'Rascunho','published_json'=>'Publicado'] as $column=>$state){
                $document=json_decode((string)($row[$column]??''),true);
                if(!is_array($document))continue;
                foreach(($document['images']??[]) as $element=>$value){
                    if((int)($value['mediaAssetId']??0)!==$assetId)continue;
                    $versionId=(int)($value['mediaVersionId']??0);
                    $asset['uses'][]=[
                        'source'=>'legacy',
                        'activity'=>$row['admin_name'],
                        'slug'=>$row['slug'],
                        'state'=>$state,
                        'element'=>$element,
                        'versionId'=>$versionId,
                        'isActiveVersion'=>$versionId===$activeVersion,
                    ];
                }
            }
        }
    }catch(Throwable){ }

    // cms_pages stores HTML inside JSON, so the quotes in the HTML attribute
    // are escaped in the raw database value. Narrow on the encoded form, then
    // decode only the matching rows and verify against the real HTML string.
    $htmlNeedle='data-media-asset-id="'.$assetId.'"';
    $encodedNeedle='data-media-asset-id=\\"'.$assetId.'\\"';
    $q=$db->prepare(
        "SELECT p.id,p.title,p.slug,p.locale,p.draft_document_json,p.published_document_json,
                a.admin_name,a.slug activity_slug
         FROM cms_pages p
         JOIN activities a ON a.id=p.activity_id
         WHERE p.status!='archived'
           AND (instr(COALESCE(p.draft_document_json,''),?)>0
             OR instr(COALESCE(p.published_document_json,''),?)>0)"
    );
    $q->execute([$encodedNeedle,$encodedNeedle]);
    foreach($q->fetchAll() as $row){
        foreach(['draft_document_json'=>'Rascunho','published_document_json'=>'Publicado'] as $column=>$state){
            $json=$row[$column]??null;
            if(!is_string($json)||$json==='')continue;
            $document=json_decode($json,true);
            $html=is_array($document)?(string)($document['html']??''):'';
            if($html===''||!str_contains($html,$htmlNeedle))continue;
            $asset['uses'][]=[
                'source'=>'cms',
                'activity'=>$row['admin_name'],
                'slug'=>$row['activity_slug'],
                'state'=>$state,
                'pageId'=>(int)$row['id'],
                'page'=>$row['title'],
                'pageSlug'=>$row['slug'],
                'locale'=>$row['locale'],
                'element'=>'structured media',
            ];
        }
    }

    return $asset;
}
