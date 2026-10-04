<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $columns=array_column($db->query('PRAGMA table_info(media_assets)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('visibility',$columns,true))$db->exec("ALTER TABLE media_assets ADD COLUMN visibility TEXT NOT NULL DEFAULT 'public'");
    $db->exec("UPDATE media_assets SET visibility='public' WHERE visibility IS NULL OR visibility NOT IN ('public','private')");
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS course_page_media_slots (
    page_id INTEGER NOT NULL,
    slot_key TEXT NOT NULL,
    media_asset_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(page_id,slot_key),
    FOREIGN KEY(page_id) REFERENCES cms_pages(id) ON DELETE CASCADE,
    FOREIGN KEY(media_asset_id) REFERENCES media_assets(id) ON DELETE RESTRICT
);
CREATE INDEX IF NOT EXISTS idx_course_page_media_slots_asset ON course_page_media_slots(media_asset_id,page_id);
CREATE INDEX IF NOT EXISTS idx_media_assets_visibility ON media_assets(visibility,archived_at,id DESC);
SQL);

    $legacyExists=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='student_private_media'")->fetchColumn();
    if(!$legacyExists)return;
    $legacyColumns=array_column($db->query('PRAGMA table_info(student_private_media)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('slot_key',$legacyColumns,true))return;

    $root=dirname(__DIR__);
    $legacyRoot=$root.'/storage/private-media';
    $mediaRoot=$root.'/uploads/media';
    if(!is_dir($mediaRoot)&&!mkdir($mediaRoot,0755,true)&&!is_dir($mediaRoot))throw new RuntimeException('media_root_unavailable');
    $rows=$db->query("SELECT * FROM student_private_media WHERE COALESCE(slot_key,'')<>'' ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $row){
        $pageId=(int)$row['page_id'];$slot=trim((string)$row['slot_key']);if($pageId<1||$slot==='')continue;
        $bound=$db->prepare('SELECT media_asset_id FROM course_page_media_slots WHERE page_id=? AND slot_key=?');$bound->execute([$pageId,$slot]);if($bound->fetchColumn())continue;
        $uuid=strtolower(trim((string)$row['asset_uuid']));if(!preg_match('/^[a-f0-9]{32}$/',$uuid))$uuid=bin2hex(random_bytes(16));
        $find=$db->prepare('SELECT id FROM media_assets WHERE asset_uuid=?');$find->execute([$uuid]);$assetId=(int)($find->fetchColumn()?:0);
        if($assetId===0){
            $source=$legacyRoot.'/'.ltrim((string)$row['storage_path'],'/');
            if(!is_file($source))continue;
            $mime=(string)$row['mime_type'];$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??strtolower(pathinfo((string)$row['original_name'],PATHINFO_EXTENSION));if($ext==='')$ext='bin';
            $versionUuid=bin2hex(random_bytes(16));$relative=$uuid.'/'.$versionUuid.'/original/file.'.$ext;$destination=$mediaRoot.'/'.$relative;
            $directory=dirname($destination);if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))throw new RuntimeException('private_media_migration_storage_failed');
            if(!copy($source,$destination))throw new RuntimeException('private_media_migration_copy_failed');
            $info=@getimagesize($destination);$width=is_array($info)?(int)$info[0]:null;$height=is_array($info)?(int)$info[1]:null;$now=gmdate('c');
            $db->prepare('INSERT INTO media_assets(asset_uuid,kind,title,default_alt,original_name,mime_type,byte_size,width,height,checksum,processing_status,visibility,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,"ready","private",?,?)')
                ->execute([$uuid,'image',(string)$row['title'],'',(string)$row['original_name'],$mime,(int)$row['byte_size'],$width,$height,(string)$row['checksum'],$now,$now]);
            $assetId=(int)$db->lastInsertId();
            $db->prepare('INSERT INTO media_versions(asset_id,version_uuid,original_path,mime_type,byte_size,width,height,checksum,processing_status,created_at) VALUES(?,?,?,?,?,?,?,?,"ready",?)')
                ->execute([$assetId,$versionUuid,$relative,$mime,(int)$row['byte_size'],$width,$height,(string)$row['checksum'],$now]);
            $versionId=(int)$db->lastInsertId();$db->prepare('UPDATE media_assets SET active_version_id=? WHERE id=?')->execute([$versionId,$assetId]);
            $denyRoot=$mediaRoot.'/'.$uuid;if(!is_dir($denyRoot)&&!mkdir($denyRoot,0755,true)&&!is_dir($denyRoot))throw new RuntimeException('private_media_migration_storage_failed');
            file_put_contents($denyRoot.'/.htaccess',"Require all denied\n",LOCK_EX);
        }else{
            $db->prepare("UPDATE media_assets SET visibility='private',updated_at=? WHERE id=?")->execute([gmdate('c'),$assetId]);
            $assetUuid=$db->prepare('SELECT asset_uuid FROM media_assets WHERE id=?');$assetUuid->execute([$assetId]);$assetUuid=(string)$assetUuid->fetchColumn();
            $denyRoot=$mediaRoot.'/'.$assetUuid;if(is_dir($denyRoot))file_put_contents($denyRoot.'/.htaccess',"Require all denied\n",LOCK_EX);
        }
        $now=gmdate('c');$db->prepare('INSERT INTO course_page_media_slots(page_id,slot_key,media_asset_id,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(page_id,slot_key) DO UPDATE SET media_asset_id=excluded.media_asset_id,updated_at=excluded.updated_at')
            ->execute([$pageId,$slot,$assetId,$now,$now]);
    }
};
