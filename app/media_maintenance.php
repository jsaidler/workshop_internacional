<?php
declare(strict_types=1);

function media_maintenance_source_has_transparency(array $source): bool {
    if(empty($source['alpha'])) return false;
    if(($source['engine']??'')==='imagick'){
        try{
            $range=Imagick::getQuantumRange();
            $max=(float)($range['quantumRangeLong']??$range['quantumRangeString']??65535);
            $extrema=$source['image']->getImageChannelExtrema(Imagick::CHANNEL_ALPHA);
            return isset($extrema['min']) && (float)$extrema['min'] < $max;
        }catch(Throwable){return true;}
    }
    if(($source['engine']??'')==='gd') return media_gd_has_alpha($source['image'],(int)$source['width'],(int)$source['height']);
    return false;
}

function media_maintenance_write_image(array $source,int $width,int $height,string $format,string $path): void {
    $transparent=media_maintenance_source_has_transparency($source);
    $preserve=media_preserve_lossless((string)($source['mime']??''),$transparent);
    if($format==='jpeg'&&$preserve) throw new RuntimeException('lossy_format_not_allowed');

    if(($source['engine']??'')==='imagick'){
        $image=clone $source['image'];
        try{
            if($preserve){
                $image->setImageBackgroundColor(new ImagickPixel('transparent'));
                if(method_exists($image,'setImageAlphaChannel'))$image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            }
            $image->resizeImage($width,$height,Imagick::FILTER_LANCZOS,1,true);
            if($format==='jpeg'){
                $image->setImageFormat('jpeg');
                $image->setImageCompressionQuality(86);
            }elseif($format==='png'){
                $image->setImageFormat('png');
                if(method_exists($image,'setImageAlphaChannel'))$image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
                $image->setOption('png:compression-level','9');
            }elseif($format==='webp'){
                $image->setImageFormat('webp');
                if($preserve){
                    if(method_exists($image,'setImageAlphaChannel'))$image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
                    $image->setOption('webp:lossless','true');
                    $image->setOption('webp:alpha-quality','100');
                    $image->setImageCompressionQuality(100);
                }else $image->setImageCompressionQuality(84);
            }else throw new RuntimeException('unsupported_derivative_format');
            if(!$image->writeImage($path))throw new RuntimeException('derivative_write_failed');
        }finally{$image->clear();}
        return;
    }

    if(($source['engine']??'')!=='gd')throw new RuntimeException('image_engine_unavailable');
    $out=imagecreatetruecolor($width,$height);
    if(in_array($format,['png','webp'],true)){
        imagealphablending($out,false);
        imagesavealpha($out,true);
        $transparentColor=imagecolorallocatealpha($out,0,0,0,127);
        imagefill($out,0,0,$transparentColor);
    }else imagefill($out,0,0,imagecolorallocate($out,255,255,255));
    imagecopyresampled($out,$source['image'],0,0,0,0,$width,$height,(int)$source['width'],(int)$source['height']);
    $ok=match($format){
        'jpeg'=>imagejpeg($out,$path,86),
        'png'=>imagepng($out,$path,9),
        'webp'=>function_exists('imagewebp')&&imagewebp($out,$path,$preserve?100:84),
        default=>false,
    };
    imagedestroy($out);
    if(!$ok)throw new RuntimeException('derivative_write_failed');
}

function media_maintenance_verify_transparency(string $path,string $mime,bool $required): void {
    if(!$required)return;
    $check=media_decode_image($path,$mime);
    try{if(!media_maintenance_source_has_transparency($check))throw new RuntimeException('alpha_channel_lost');}
    finally{media_release_image($check);}
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
    $requiresTransparency=media_maintenance_source_has_transparency($source);
    $base=dirname(dirname((string)$row['original_path']));
    $versionRoot=media_upload_root().'/'.$base;
    $liveDir=$versionRoot.'/responsive';
    $token=bin2hex(random_bytes(5));
    $tempDir=$versionRoot.'/responsive-build-'.$token;
    $backupDir=$versionRoot.'/responsive-backup-'.$token;
    $prepared=[];$swapped=false;$hadLive=is_dir($liveDir);

    try{
        media_mkdir($tempDir);
        $formats=media_image_derivative_formats((string)$row['mime_type'],$requiresTransparency,media_webp_supported());
        if(!$formats)throw new RuntimeException('image_format_not_supported');
        foreach(MEDIA_IMAGE_WIDTHS as $target){
            if($target>(int)$source['width'])continue;
            $height=(int)round((int)$source['height']*$target/(int)$source['width']);
            foreach($formats as $format){
                $extension=$format==='jpeg'?'jpg':$format;
                $tempPath=$tempDir.'/'.$target.'.'.$extension;
                media_maintenance_write_image($source,$target,$height,$format,$tempPath);
                if(!is_file($tempPath)||filesize($tempPath)<1)throw new RuntimeException('derivative_write_failed');
                $mimeType=$format==='jpeg'?'image/jpeg':'image/'.$format;
                media_maintenance_verify_transparency($tempPath,$mimeType,$requiresTransparency&&in_array($format,['png','webp'],true));
                $prepared[]=[
                    'kind'=>'responsive','width'=>$target,'height'=>$height,'format'=>$format,'mime'=>$mimeType,
                    'relative'=>$base.'/responsive/'.$target.'.'.$extension,
                    'file'=>$target.'.'.$extension,
                ];
            }
        }
        media_release_image($source);$source=null;

        if($hadLive&&!@rename($liveDir,$backupDir))throw new RuntimeException('derivative_backup_failed');
        if(!@rename($tempDir,$liveDir)){
            if($hadLive&&is_dir($backupDir))@rename($backupDir,$liveDir);
            throw new RuntimeException('derivative_swap_failed');
        }
        $swapped=true;

        $db->beginTransaction();
        $db->prepare('DELETE FROM media_derivatives WHERE version_id=?')->execute([$versionId]);
        $insert=$db->prepare('INSERT INTO media_derivatives(version_id,derivative_kind,width,height,format,mime_type,path,byte_size,checksum,created_at) VALUES(?,?,?,?,?,?,?,?,?,?)');
        foreach($prepared as $item){
            $absolute=$liveDir.'/'.$item['file'];
            $insert->execute([$versionId,$item['kind'],$item['width'],$item['height'],$item['format'],$item['mime'],$item['relative'],filesize($absolute),hash_file('sha256',$absolute),gmdate('c')]);
        }
        $db->prepare('UPDATE media_versions SET processing_status="ready",error_message=NULL WHERE id=?')->execute([$versionId]);
        $db->prepare('UPDATE media_assets SET processing_status="ready",updated_at=? WHERE id=?')->execute([gmdate('c'),$assetId]);
        $db->commit();
        if(is_dir($backupDir))media_remove_tree($backupDir);
        return ['assetId'=>$assetId,'versionId'=>$versionId,'derivatives'=>count($prepared),'transparencyPreserved'=>$requiresTransparency];
    }catch(Throwable $e){
        if(isset($source)&&is_array($source))media_release_image($source);
        if($db->inTransaction())$db->rollBack();
        if($swapped&&is_dir($liveDir))media_remove_tree($liveDir);
        if(is_dir($backupDir))@rename($backupDir,$liveDir);
        if(is_dir($tempDir))media_remove_tree($tempDir);
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
