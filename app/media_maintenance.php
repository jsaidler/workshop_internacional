<?php
declare(strict_types=1);

function media_maintenance_source_has_transparency(array $source): bool {
    return media_source_has_transparency($source);
}

function media_maintenance_write_image(array $source,int $width,int $height,string $format,string $path): void {
    media_write_image($source,$width,$height,$format,$path);
}

function media_maintenance_verify_transparency(string $path,string $mime,bool $required): void {
    if(!$required)return;
    $check=media_decode_image($path,$mime);
    try{if(!media_source_has_transparency($check))throw new RuntimeException('alpha_channel_lost');}
    finally{media_release_image($check);}
}

function media_regenerated_derivative_dirs(array $paths,string $base,string $keep=''): array {
    $dirs=[];$prefix=$base.'/responsive';
    foreach($paths as $path){
        $dir=str_replace('\\','/',dirname((string)$path));
        if($dir===$keep||($dir!==$prefix&&!str_starts_with($dir,$prefix.'-')))continue;
        $dirs[$dir]=true;
    }
    return array_keys($dirs);
}

function media_regenerate_image_version(PDO $db,int $assetId,int $versionId): array {
    $q=$db->prepare('SELECT a.kind,a.asset_uuid,v.id,v.version_uuid,v.original_path,v.mime_type,v.processing_status FROM media_assets a JOIN media_versions v ON v.asset_id=a.id WHERE a.id=? AND v.id=?');
    $q->execute([$assetId,$versionId]);$row=$q->fetch();
    if(!$row)throw new RuntimeException('image_version_not_found');
    if($row['kind']!=='image')throw new RuntimeException('asset_not_image');
    if($row['processing_status']!=='ready')throw new RuntimeException('image_version_not_ready');

    $original=media_upload_root().'/'.$row['original_path'];
    if(!is_file($original))throw new RuntimeException('original_file_missing');
    $source=media_decode_image($original,(string)$row['mime_type']);
    $requiresTransparency=media_source_has_transparency($source);
    $base=dirname(dirname((string)$row['original_path']));
    $versionRoot=media_upload_root().'/'.$base;
    $token=bin2hex(random_bytes(8));
    $directoryName='responsive-'.$token;
    $relativeDir=$base.'/'.$directoryName;
    $newDir=$versionRoot.'/'.$directoryName;
    $oldQuery=$db->prepare('SELECT path FROM media_derivatives WHERE version_id=?');$oldQuery->execute([$versionId]);
    $oldPaths=array_values(array_map('strval',$oldQuery->fetchAll(PDO::FETCH_COLUMN)));
    $prepared=[];

    try{
        media_mkdir($newDir);
        $formats=media_image_derivative_formats((string)$row['mime_type'],$requiresTransparency,media_webp_supported());
        if(!$formats)throw new RuntimeException('image_format_not_supported');
        foreach(MEDIA_IMAGE_WIDTHS as $target){
            if($target>(int)$source['width'])continue;
            $height=(int)round((int)$source['height']*$target/(int)$source['width']);
            foreach($formats as $format){
                $extension=$format==='jpeg'?'jpg':$format;
                $file=$target.'.'.$extension;
                $absolute=$newDir.'/'.$file;
                $mimeType=$format==='jpeg'?'image/jpeg':'image/'.$format;
                media_write_image($source,$target,$height,$format,$absolute);
                media_verify_image_derivative($absolute,$mimeType,$target,$height,$requiresTransparency&&in_array($format,['png','webp'],true));
                $prepared[]=[
                    'kind'=>'responsive','width'=>$target,'height'=>$height,'format'=>$format,'mime'=>$mimeType,
                    'relative'=>$relativeDir.'/'.$file,'file'=>$file,
                ];
            }
        }
        media_release_image($source);$source=null;

        $db->beginTransaction();
        $db->prepare('DELETE FROM media_derivatives WHERE version_id=?')->execute([$versionId]);
        $insert=$db->prepare('INSERT INTO media_derivatives(version_id,derivative_kind,width,height,format,mime_type,path,byte_size,checksum,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)');
        foreach($prepared as $item){
            $absolute=$newDir.'/'.$item['file'];
            $insert->execute([$versionId,$item['kind'],$item['width'],$item['height'],$item['format'],$item['mime'],$item['relative'],filesize($absolute),hash_file('sha256',$absolute),gmdate('c')]);
        }
        $db->prepare('UPDATE media_versions SET processing_status="ready",error_message=NULL WHERE id=?')->execute([$versionId]);
        $db->prepare('UPDATE media_assets SET processing_status="ready",updated_at=? WHERE id=?')->execute([gmdate('c'),$assetId]);
        $db->commit();

        // Regeneration is cache-safe: when derivatives exist, the database is
        // switched to brand-new immutable URLs before obsolete directories are
        // removed. Small images that need no derivative simply fall back to
        // their immutable original and old derivative rows are discarded.
        $keep=$prepared?$relativeDir:'';
        if(!$prepared&&is_dir($newDir))media_remove_tree($newDir);
        foreach(media_regenerated_derivative_dirs($oldPaths,$base,$keep) as $oldDir){
            $absolute=media_upload_root().'/'.$oldDir;
            if(is_dir($absolute))media_remove_tree($absolute);
        }
        return ['assetId'=>$assetId,'versionId'=>$versionId,'derivatives'=>count($prepared),'transparencyPreserved'=>$requiresTransparency,'derivativeDirectory'=>$prepared?$relativeDir:null];
    }catch(Throwable $e){
        if(isset($source)&&is_array($source))media_release_image($source);
        if($db->inTransaction())$db->rollBack();
        if(is_dir($newDir))media_remove_tree($newDir);
        throw $e;
    }
}

function media_regenerate_image_asset(PDO $db,int $assetId,bool $allVersions=false): array {
    $asset=media_asset($db,$assetId);
    if(($asset['kind']??'')!=='image')throw new RuntimeException('asset_not_image');
    $versionIds=[];
    if($allVersions){
        $q=$db->prepare('SELECT id FROM media_versions WHERE asset_id=? AND processing_status="ready" ORDER BY id');$q->execute([$assetId]);
        $versionIds=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
    }else $versionIds=[(int)$asset['active_version_id']];
    $results=[];foreach($versionIds as $versionId)if($versionId>0)$results[]=media_regenerate_image_version($db,$assetId,$versionId);
    return ['assetId'=>$assetId,'versions'=>$results,'asset'=>media_asset_admin($db,$assetId)];
}

function media_delete_asset_permanently(PDO $db,int $assetId): array {
    $asset=media_asset_admin($db,$assetId);
    $uses=$asset['uses']??[];
    if($uses)throw new RuntimeException('asset_in_use');
    $uuid=(string)$asset['asset_uuid'];
    if($uuid===''||!preg_match('/^[a-f0-9]{32}$/',$uuid))throw new RuntimeException('invalid_asset_storage');
    $assetDir=media_upload_root().'/'.$uuid;
    $trashDir=media_upload_root().'/.delete-'.$uuid.'-'.bin2hex(random_bytes(4));
    $moved=false;
    if(is_dir($assetDir)){
        if(!@rename($assetDir,$trashDir))throw new RuntimeException('asset_storage_delete_prepare_failed');
        $moved=true;
    }
    try{
        $db->beginTransaction();
        $versionIds=$db->prepare('SELECT id FROM media_versions WHERE asset_id=?');$versionIds->execute([$assetId]);$ids=array_map('intval',$versionIds->fetchAll(PDO::FETCH_COLUMN));
        if($ids){
            $placeholders=implode(',',array_fill(0,count($ids),'?'));
            $posterIds=$db->prepare("SELECT id FROM media_posters WHERE video_version_id IN ($placeholders)");$posterIds->execute($ids);$posters=array_map('intval',$posterIds->fetchAll(PDO::FETCH_COLUMN));
            if($posters){$p=implode(',',array_fill(0,count($posters),'?'));$db->prepare("DELETE FROM media_poster_variants WHERE poster_id IN ($p)")->execute($posters);}
            $db->prepare("DELETE FROM media_posters WHERE video_version_id IN ($placeholders)")->execute($ids);
            $db->prepare("DELETE FROM media_derivatives WHERE version_id IN ($placeholders)")->execute($ids);
        }
        $db->prepare('DELETE FROM media_asset_tags WHERE asset_id=?')->execute([$assetId]);
        $db->prepare('UPDATE media_assets SET active_version_id=NULL WHERE id=?')->execute([$assetId]);
        $db->prepare('DELETE FROM media_versions WHERE asset_id=?')->execute([$assetId]);
        $db->prepare('DELETE FROM media_assets WHERE id=?')->execute([$assetId]);
        $db->exec('DELETE FROM media_tags WHERE id NOT IN (SELECT DISTINCT tag_id FROM media_asset_tags)');
        $db->commit();
    }catch(Throwable $e){
        if($db->inTransaction())$db->rollBack();
        if($moved&&is_dir($trashDir))@rename($trashDir,$assetDir);
        throw $e;
    }
    if($moved&&is_dir($trashDir))media_remove_tree($trashDir);
    return ['deleted'=>true,'assetId'=>$assetId,'title'=>(string)$asset['title'],'originalName'=>(string)$asset['original_name']];
}
