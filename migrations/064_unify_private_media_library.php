<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $now=gmdate('c');
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    $viewExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='view' AND name=".$db->quote($name))->fetchColumn();

    $mediaColumns=$db->query('PRAGMA table_info(media_assets)')->fetchAll(PDO::FETCH_COLUMN,1);
    if(!in_array('visibility',$mediaColumns,true))$db->exec("ALTER TABLE media_assets ADD COLUMN visibility TEXT NOT NULL DEFAULT 'public'");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_media_assets_visibility ON media_assets(visibility,archived_at,id DESC)");

    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS cms_protected_media_slots (
    page_id INTEGER NOT NULL,
    slot_key TEXT NOT NULL,
    asset_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(page_id,slot_key),
    FOREIGN KEY(page_id) REFERENCES cms_pages(id) ON DELETE CASCADE,
    FOREIGN KEY(asset_id) REFERENCES media_assets(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_cms_protected_media_slots_asset ON cms_protected_media_slots(asset_id,page_id);
SQL);

    if($tableExists('student_private_media')){
        $oldColumns=$db->query('PRAGMA table_info(student_private_media)')->fetchAll(PDO::FETCH_COLUMN,1);
        $hasSlot=in_array('slot_key',$oldColumns,true);
        $rows=$db->query('SELECT * FROM student_private_media ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $oldRoot=dirname(__DIR__).'/storage/student-media';
        $privateRoot=dirname(__DIR__).'/storage/private-media';
        if(!is_dir($privateRoot)&&!mkdir($privateRoot,0750,true)&&!is_dir($privateRoot))throw new RuntimeException('private_media_storage_unavailable');

        foreach($rows as $row){
            $uuid=(string)$row['asset_uuid'];
            $existing=$db->prepare('SELECT id FROM media_assets WHERE asset_uuid=? LIMIT 1');$existing->execute([$uuid]);$assetId=(int)($existing->fetchColumn()?:0);
            if($assetId===0){
                $mime=(string)$row['mime_type'];
                $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??strtolower(pathinfo((string)$row['original_name'],PATHINFO_EXTENSION));
                if($ext==='')$ext='bin';
                $versionUuid=bin2hex(random_bytes(16));
                $relative=$uuid.'/'.$versionUuid.'/original/file.'.$ext;
                $destination=$privateRoot.'/'.$relative;
                $source=$oldRoot.'/'.ltrim((string)$row['storage_path'],'/');
                if(!is_dir(dirname($destination))&&!mkdir(dirname($destination),0750,true)&&!is_dir(dirname($destination)))throw new RuntimeException('private_media_storage_unavailable');
                if(is_file($source)){
                    if(!@rename($source,$destination)){
                        if(!@copy($source,$destination))throw new RuntimeException('private_media_migration_failed');
                        @unlink($source);
                    }
                }elseif(!is_file($destination)){
                    // Preserve the metadata row even when an old file was already missing.
                    @file_put_contents($destination,'');
                }
                $stored='../../storage/private-media/'.$relative;
                $db->prepare("INSERT INTO media_assets(asset_uuid,kind,title,default_alt,original_name,mime_type,byte_size,width,height,checksum,processing_status,active_version_id,archived_at,created_at,updated_at,caption,description,focal_x,focal_y,visibility) VALUES(?, 'image', ?, '', ?, ?, ?, NULL, NULL, ?, 'ready', NULL, NULL, ?, ?, '', '', 50, 50, 'private')")
                    ->execute([$uuid,(string)$row['title'],(string)$row['original_name'],$mime,(int)$row['byte_size'],(string)$row['checksum'],(string)$row['created_at'],(string)$row['updated_at']]);
                $assetId=(int)$db->lastInsertId();
                $db->prepare("INSERT INTO media_versions(asset_id,version_uuid,original_path,mime_type,byte_size,width,height,duration,checksum,processing_status,error_message,created_at) VALUES(?,?,?,?,?,NULL,NULL,NULL,?,'ready',NULL,?)")
                    ->execute([$assetId,$versionUuid,$stored,$mime,(int)$row['byte_size'],(string)$row['checksum'],(string)$row['created_at']]);
                $versionId=(int)$db->lastInsertId();
                $db->prepare('UPDATE media_assets SET active_version_id=? WHERE id=?')->execute([$versionId,$assetId]);
            }else{
                $db->prepare("UPDATE media_assets SET visibility='private',updated_at=? WHERE id=?")->execute([$now,$assetId]);
            }
            $slot=$hasSlot?trim((string)($row['slot_key']??'')):'';
            if($slot!==''){
                $db->prepare('INSERT INTO cms_protected_media_slots(page_id,slot_key,asset_id,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(page_id,slot_key) DO UPDATE SET asset_id=excluded.asset_id,updated_at=excluded.updated_at')
                    ->execute([(int)$row['page_id'],$slot,$assetId,(string)$row['created_at'],$now]);
            }
        }

        $db->exec('DROP INDEX IF EXISTS idx_student_private_media_page_slot');
        $db->exec('DROP INDEX IF EXISTS idx_student_private_media_page');
        $db->exec('DROP TABLE student_private_media');
        if(is_dir($oldRoot))@rmdir($oldRoot);
    }

    if($viewExists('student_private_media'))$db->exec('DROP VIEW student_private_media');
    // Temporary read-only compatibility for code paths from older installations.
    // There is no second media store: the view projects the canonical library.
    $db->exec(<<<'SQL'
CREATE VIEW IF NOT EXISTS student_private_media AS
SELECT a.id,
       a.asset_uuid,
       p.activity_id,
       s.page_id,
       a.title,
       a.original_name,
       a.mime_type,
       a.byte_size,
       v.original_path AS storage_path,
       a.checksum,
       a.created_at,
       a.updated_at,
       s.slot_key
FROM cms_protected_media_slots s
JOIN media_assets a ON a.id=s.asset_id
JOIN cms_pages p ON p.id=s.page_id
LEFT JOIN media_versions v ON v.id=a.active_version_id
WHERE a.visibility='private';
SQL);
};
