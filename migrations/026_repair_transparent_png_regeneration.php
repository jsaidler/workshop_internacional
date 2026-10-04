<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasMedia=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='media_versions'")->fetchColumn();
    if(!$hasMedia||!function_exists('media_regenerate_image_version'))return;

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
            // A failed repair must never leave delivery pinned to an already
            // corrupt transparent derivative. The immutable original is kept,
            // so removing derivative rows makes the resolver fall back to it.
            $q=$db->prepare('DELETE FROM media_derivatives WHERE version_id=?');
            $q->execute([$versionId]);
        }
    }
};
