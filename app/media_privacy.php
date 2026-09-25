<?php
declare(strict_types=1);

function media_private_storage_root(): string {return dirname(__DIR__).'/storage/private-media';}
function media_private_storage_ensure(): void {$root=media_private_storage_root();if(!is_dir($root)&&!mkdir($root,0750,true)&&!is_dir($root))throw new RuntimeException('private_media_storage_unavailable');}
function media_asset_visibility(array $asset): string {return ($asset['visibility']??'public')==='private'?'private':'public';}
function media_asset_is_private(array $asset): bool {return media_asset_visibility($asset)==='private';}
function media_private_db_prefix(): string {return '../../storage/private-media/';}
function media_private_admin_url(int $assetId,int $versionId=0): string {return '/admin/media-private-file.php?'.http_build_query(['asset'=>$assetId]+($versionId>0?['version'=>$versionId]:[]));}

function media_private_asset_absolute_file(PDO $db,int $assetId,int $versionId=0): ?array {
    $sql="SELECT a.id,a.asset_uuid,a.visibility,a.original_name,a.mime_type,a.active_version_id,v.id version_id,v.original_path FROM media_assets a JOIN media_versions v ON v.asset_id=a.id WHERE a.id=? AND a.visibility='private' AND v.processing_status='ready'";
    $args=[$assetId];
    if($versionId>0){$sql.=' AND v.id=?';$args[]=$versionId;}else $sql.=' AND v.id=a.active_version_id';
    $sql.=' LIMIT 1';$q=$db->prepare($sql);$q->execute($args);$row=$q->fetch();if(!$row)return null;
    $root=realpath(media_private_storage_root());$file=realpath(media_upload_root().'/'.(string)$row['original_path']);
    if($root===false||$file===false||!is_file($file))return null;
    $normRoot=rtrim(str_replace('\\','/',$root),'/').'/';$normFile=str_replace('\\','/',$file);
    if(!str_starts_with($normFile,$normRoot))return null;
    $row['absolute_path']=$file;return $row;
}

function media_private_adminize_asset(array $asset): array {
    if(!media_asset_is_private($asset))return $asset;
    $asset['url']=media_private_admin_url((int)$asset['id']);
    $asset['derivatives']=[];
    foreach(($asset['versions']??[]) as &$version)$version['url']=media_private_admin_url((int)$asset['id'],(int)$version['id']);
    unset($version);
    $asset['poster']=null;$asset['posters']=[];
    return $asset;
}
function media_private_adminize_grid_item(array $asset): array {
    if(!media_asset_is_private($asset))return $asset;
    $asset['url']=media_private_admin_url((int)$asset['id']);$asset['derivatives']=[];$asset['poster']=null;$asset['posters']=[];return $asset;
}

function media_protected_slot_count(PDO $db,int $assetId): int {$q=$db->prepare('SELECT COUNT(*) FROM cms_protected_media_slots WHERE asset_id=?');$q->execute([$assetId]);return (int)$q->fetchColumn();}
function media_private_public_usage(PDO $db,int $assetId): array {return media_usage_all($db,$assetId);}

function media_private_move_tree(string $from,string $to): void {
    if(!is_dir($from))throw new RuntimeException('media_source_storage_missing');
    if(is_dir($to))throw new RuntimeException('media_destination_exists');
    if(!is_dir(dirname($to))&&!mkdir(dirname($to),0750,true)&&!is_dir(dirname($to)))throw new RuntimeException('media_destination_unavailable');
    if(!@rename($from,$to))throw new RuntimeException('media_storage_move_failed');
}
function media_private_update_paths(PDO $db,int $assetId,bool $toPrivate): void {
    $prefix=media_private_db_prefix();
    $versions=$db->prepare('SELECT id,original_path FROM media_versions WHERE asset_id=?');$versions->execute([$assetId]);
    foreach($versions->fetchAll() as $row){$path=(string)$row['original_path'];$new=$toPrivate?(str_starts_with($path,$prefix)?$path:$prefix.$path):(str_starts_with($path,$prefix)?substr($path,strlen($prefix)):$path);$db->prepare('UPDATE media_versions SET original_path=? WHERE id=?')->execute([$new,(int)$row['id']]);}
    $derivatives=$db->prepare('SELECT d.id,d.path FROM media_derivatives d JOIN media_versions v ON v.id=d.version_id WHERE v.asset_id=?');$derivatives->execute([$assetId]);
    foreach($derivatives->fetchAll() as $row){$path=(string)$row['path'];$new=$toPrivate?(str_starts_with($path,$prefix)?$path:$prefix.$path):(str_starts_with($path,$prefix)?substr($path,strlen($prefix)):$path);$db->prepare('UPDATE media_derivatives SET path=? WHERE id=?')->execute([$new,(int)$row['id']]);}
    $posters=$db->prepare('SELECT pv.id,pv.path FROM media_poster_variants pv JOIN media_posters p ON p.id=pv.poster_id WHERE p.video_asset_id=?');$posters->execute([$assetId]);
    foreach($posters->fetchAll() as $row){$path=(string)$row['path'];$new=$toPrivate?(str_starts_with($path,$prefix)?$path:$prefix.$path):(str_starts_with($path,$prefix)?substr($path,strlen($prefix)):$path);$db->prepare('UPDATE media_poster_variants SET path=? WHERE id=?')->execute([$new,(int)$row['id']]);}
}

function media_set_visibility(PDO $db,int $assetId,string $visibility): array {
    $visibility=$visibility==='private'?'private':'public';$asset=media_asset($db,$assetId);$current=media_asset_visibility($asset);if($current===$visibility)return media_private_adminize_asset($asset);
    if($visibility==='private'&&media_private_public_usage($db,$assetId))throw new RuntimeException('asset_in_public_use');
    if($visibility==='public'&&media_protected_slot_count($db,$assetId)>0)throw new RuntimeException('asset_bound_to_protected_content');
    $uuid=(string)$asset['asset_uuid'];media_private_storage_ensure();$publicDir=media_upload_root().'/'.$uuid;$privateDir=media_private_storage_root().'/'.$uuid;
    $from=$visibility==='private'?$publicDir:$privateDir;$to=$visibility==='private'?$privateDir:$publicDir;
    media_private_move_tree($from,$to);
    try{
        $db->beginTransaction();media_private_update_paths($db,$assetId,$visibility==='private');$db->prepare('UPDATE media_assets SET visibility=?,updated_at=? WHERE id=?')->execute([$visibility,gmdate('c'),$assetId]);$db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();@rename($to,$from);throw $e;}
    return media_private_adminize_asset(media_asset($db,$assetId));
}

function media_private_resecure_asset(PDO $db,int $assetId): void {
    $asset=media_asset($db,$assetId);if(!media_asset_is_private($asset))return;
    $uuid=(string)$asset['asset_uuid'];$publicDir=media_upload_root().'/'.$uuid;$privateDir=media_private_storage_root().'/'.$uuid;
    if(!is_dir($publicDir))return;
    // A new version of an existing private asset is initially written by the
    // canonical uploader. Merge it into the already-private asset directory.
    media_private_storage_ensure();
    if(!is_dir($privateDir)&&!mkdir($privateDir,0750,true)&&!is_dir($privateDir))throw new RuntimeException('private_media_storage_unavailable');
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($publicDir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST);
    foreach($iterator as $entry){$relative=substr($entry->getPathname(),strlen($publicDir)+1);$dest=$privateDir.'/'.$relative;if($entry->isDir()){if(!is_dir($dest))mkdir($dest,0750,true);}else{if(!is_dir(dirname($dest)))mkdir(dirname($dest),0750,true);if(!@rename($entry->getPathname(),$dest))throw new RuntimeException('media_storage_move_failed');}}
    $dirs=[];$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($publicDir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $entry)if($entry->isDir())$dirs[]=$entry->getPathname();foreach($dirs as $dir)@rmdir($dir);@rmdir($publicDir);
    $db->beginTransaction();try{media_private_update_paths($db,$assetId,true);$db->commit();}catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}

function cms_protected_media_slot_key(string $value): string {$value=activity_slug(trim($value));if($value===''||strlen($value)>80)throw new RuntimeException('Slot de mídia inválido.');return $value;}
function cms_protected_media_slot_bind(PDO $db,int $activityId,int $pageId,string $slotKey,int $assetId): void {
    $page=cms_page_by_id($db,$pageId);if(!$page||(int)$page['activity_id']!==$activityId||!student_page_is_protected($page))throw new RuntimeException('Selecione uma página protegida.');
    $slotKey=cms_protected_media_slot_key($slotKey);$slots=student_page_private_media_slots_from_document(cms_page_doc($page,false));if(!isset($slots[$slotKey]))throw new RuntimeException('Este espaço de mídia não existe na página.');
    if($assetId===0){$db->prepare('DELETE FROM cms_protected_media_slots WHERE page_id=? AND slot_key=?')->execute([$pageId,$slotKey]);return;}
    $asset=media_asset($db,$assetId);if(!media_asset_is_private($asset)||($asset['kind']??'')!=='image'||!empty($asset['archived_at']))throw new RuntimeException('Escolha uma imagem privada ativa da Biblioteca de mídia.');
    $now=gmdate('c');$db->prepare('INSERT INTO cms_protected_media_slots(page_id,slot_key,asset_id,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(page_id,slot_key) DO UPDATE SET asset_id=excluded.asset_id,updated_at=excluded.updated_at')->execute([$pageId,$slotKey,$assetId,$now,$now]);
}
function cms_protected_media_slots_for_page(PDO $db,int $pageId): array {$q=$db->prepare('SELECT s.*,a.asset_uuid,a.title,a.original_name,a.mime_type,a.visibility,a.active_version_id FROM cms_protected_media_slots s JOIN media_assets a ON a.id=s.asset_id WHERE s.page_id=? ORDER BY s.slot_key');$q->execute([$pageId]);$out=[];foreach($q->fetchAll() as $row)$out[(string)$row['slot_key']]=$row;return $out;}
function cms_protected_media_for_slot(PDO $db,int $pageId,string $slotKey): ?array {$q=$db->prepare("SELECT a.*,s.slot_key FROM cms_protected_media_slots s JOIN media_assets a ON a.id=s.asset_id WHERE s.page_id=? AND s.slot_key=? AND a.visibility='private' AND a.archived_at IS NULL LIMIT 1");$q->execute([$pageId,cms_protected_media_slot_key($slotKey)]);return $q->fetch()?:null;}
function media_private_images_for_picker(PDO $db): array {$rows=$db->query("SELECT id,asset_uuid,title,original_name,active_version_id FROM media_assets WHERE visibility='private' AND kind='image' AND archived_at IS NULL ORDER BY updated_at DESC,id DESC")->fetchAll();return $rows;}
function media_protected_student_url(array $asset,int $pageId,string $cohortUuid): string {return '/aluno/media.php?'.http_build_query(['asset'=>(string)$asset['asset_uuid'],'page'=>$pageId,'cohort'=>$cohortUuid]);}
