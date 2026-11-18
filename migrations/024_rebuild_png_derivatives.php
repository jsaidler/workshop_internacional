<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $rows=$db->query(
        "SELECT a.id asset_id,v.id version_id
         FROM media_assets a
         JOIN media_versions v ON v.asset_id=a.id
         WHERE a.kind='image'
           AND v.mime_type='image/png'
           AND v.processing_status='ready'
         ORDER BY a.id,v.id"
    )->fetchAll();

    foreach($rows as $row){
        $assetId=(int)$row['asset_id'];
        $versionId=(int)$row['version_id'];
        if($assetId<1||$versionId<1)continue;
        try{
            media_regenerate_image_version($db,$assetId,$versionId);
        }catch(Throwable){
            // A legacy derivative must never remain eligible after a failed
            // repair. Removing only its database references makes delivery
            // fall back to the untouched original while keeping the asset and
            // version intact for a later explicit regeneration.
            $q=$db->prepare('DELETE FROM media_derivatives WHERE version_id=?');
            $q->execute([$versionId]);
        }
    }
};
