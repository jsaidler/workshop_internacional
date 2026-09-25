<?php
declare(strict_types=1);

function media_visibility_values(): array {return ['public','private'];}
function media_asset_visibility(array $asset): string {return ($asset['visibility']??'public')==='private'?'private':'public';}
function media_asset_private(array $asset): bool {return media_asset_visibility($asset)==='private';}
function media_private_guard_path(array $asset): string {return media_upload_root().'/'.(string)$asset['asset_uuid'].'/.htaccess';}

function media_set_visibility(PDO $db,int $assetId,string $visibility): array {
    if(!in_array($visibility,media_visibility_values(),true))throw new RuntimeException('invalid_visibility');
    $asset=media_asset($db,$assetId);$current=media_asset_visibility($asset);if($current===$visibility)return media_asset_admin($db,$assetId);
    if($visibility==='private'){
        foreach(media_usage_all($db,$assetId) as $use){
            // Existing page references assume public delivery. Protected material slots
            // use course_page_media_slots and therefore do not appear here.
            if(($use['source']??'')==='legacy'||($use['source']??'')==='cms')throw new RuntimeException('asset_in_public_content');
        }
    }
    $guard=media_private_guard_path($asset);$dir=dirname($guard);
    if($visibility==='private'){
        media_mkdir($dir);
        if(file_put_contents($guard,"Require all denied\n",LOCK_EX)===false)throw new RuntimeException('privacy_guard_write_failed');
    }else if(is_file($guard)&&!@unlink($guard))throw new RuntimeException('privacy_guard_remove_failed');
    $q=$db->prepare('UPDATE media_assets SET visibility=?,updated_at=? WHERE id=?');$q->execute([$visibility,gmdate('c'),$assetId]);
    return media_asset_admin($db,$assetId);
}

function media_private_token(string $path): string {return rtrim(strtr(base64_encode($path),'+/','-_'),'=');}
function media_private_untoken(string $token): string {$pad=strlen($token)%4;if($pad)$token.=str_repeat('=',4-$pad);$decoded=base64_decode(strtr($token,'-_','+/'),true);return is_string($decoded)?$decoded:'';}
function media_admin_private_url(int $assetId,string $path): string {return '/admin/media-private.php?asset='.$assetId.'&file='.rawurlencode(media_private_token($path));}

function media_private_path_for_asset(array $asset,string $relative): ?string {
    $relative=str_replace('\\','/',ltrim($relative,'/'));$uuid=(string)($asset['asset_uuid']??'');
    if($uuid===''||!str_starts_with($relative,$uuid.'/'))return null;
    $root=realpath(media_upload_root());$file=realpath(media_upload_root().'/'.$relative);
    if($root===false||$file===false||!str_starts_with(str_replace('\\','/',$file),str_replace('\\','/',$root).'/')||!is_file($file))return null;
    return $file;
}

function media_rewrite_admin_delivery(array $asset): array {
    if(!media_asset_private($asset))return $asset;$assetId=(int)$asset['id'];
    if(isset($asset['original_path']))$asset['url']=media_admin_private_url($assetId,(string)$asset['original_path']);
    foreach($asset['derivatives']??[] as $i=>$row)if(isset($row['path']))$asset['derivatives'][$i]['src']=media_admin_private_url($assetId,(string)$row['path']);
    foreach($asset['versions']??[] as $vi=>$version){
        if(isset($version['original_path']))$asset['versions'][$vi]['url']=media_admin_private_url($assetId,(string)$version['original_path']);
        elseif(isset($version['url'])&&isset($version['versionUuid'])){
            $qPath='';foreach($asset['versions'][$vi]['derivatives']??[] as $di=>$row)if(isset($row['path']))$asset['versions'][$vi]['derivatives'][$di]['src']=media_admin_private_url($assetId,(string)$row['path']);
        }
    }
    if(isset($asset['poster']['path']))$asset['poster']['src']=media_admin_private_url($assetId,(string)$asset['poster']['path']);
    foreach($asset['posters']??[] as $pi=>$poster)foreach($poster['variants']??[] as $vi=>$variant)if(isset($variant['path']))$asset['posters'][$pi]['variants'][$vi]['src']=media_admin_private_url($assetId,(string)$variant['path']);
    return $asset;
}

function course_page_media_slot(PDO $db,int $pageId,string $slotKey): ?array {
    $q=$db->prepare('SELECT s.page_id,s.slot_key,s.media_asset_id,a.* ,v.original_path FROM course_page_media_slots s JOIN media_assets a ON a.id=s.media_asset_id LEFT JOIN media_versions v ON v.id=a.active_version_id WHERE s.page_id=? AND s.slot_key=? AND a.archived_at IS NULL LIMIT 1');
    $q->execute([$pageId,student_private_media_slot_key($slotKey)]);$row=$q->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
}
function course_page_media_slots(PDO $db,int $pageId): array {$q=$db->prepare('SELECT s.page_id,s.slot_key,s.media_asset_id,a.title,a.original_name,a.visibility,a.asset_uuid FROM course_page_media_slots s JOIN media_assets a ON a.id=s.media_asset_id WHERE s.page_id=? ORDER BY s.slot_key');$q->execute([$pageId]);return $q->fetchAll(PDO::FETCH_ASSOC);}
function course_page_media_bind(PDO $db,int $pageId,string $slotKey,int $assetId): void {
    $page=cms_page_by_id($db,$pageId)??throw new RuntimeException('Página inválida.');if(!student_page_is_protected($page))throw new RuntimeException('Selecione uma página protegida.');
    $slots=student_page_private_media_slots_from_document(cms_page_doc($page,false));$slotKey=student_private_media_slot_key($slotKey);if(!isset($slots[$slotKey]))throw new RuntimeException('Este espaço não existe na página.');
    $asset=media_asset($db,$assetId);if(($asset['kind']??'')!=='image')throw new RuntimeException('O espaço aceita apenas imagens.');if(!media_asset_private($asset))throw new RuntimeException('Marque a mídia como privada na Biblioteca de mídia antes de vinculá-la.');
    $now=gmdate('c');$db->prepare('INSERT INTO course_page_media_slots(page_id,slot_key,media_asset_id,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(page_id,slot_key) DO UPDATE SET media_asset_id=excluded.media_asset_id,updated_at=excluded.updated_at')->execute([$pageId,$slotKey,$assetId,$now,$now]);
}
function course_page_media_unbind(PDO $db,int $pageId,string $slotKey): void {$db->prepare('DELETE FROM course_page_media_slots WHERE page_id=? AND slot_key=?')->execute([$pageId,student_private_media_slot_key($slotKey)]);}
function media_private_images(PDO $db): array {$q=$db->query("SELECT id,asset_uuid,title,original_name,width,height FROM media_assets WHERE kind='image' AND visibility='private' AND archived_at IS NULL ORDER BY title COLLATE NOCASE,id DESC");return $q->fetchAll(PDO::FETCH_ASSOC);}

function student_library_media_asset_by_uuid(PDO $db,string $uuid): ?array {
    if(!preg_match('/^[a-f0-9]{32}$/',$uuid))return null;$q=$db->prepare("SELECT a.*,v.original_path FROM media_assets a LEFT JOIN media_versions v ON v.id=a.active_version_id WHERE a.asset_uuid=? AND a.visibility='private' AND a.archived_at IS NULL LIMIT 1");$q->execute([$uuid]);$row=$q->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
}
function student_library_media_bound_to_page(PDO $db,int $assetId,int $pageId): bool {$q=$db->prepare('SELECT 1 FROM course_page_media_slots WHERE page_id=? AND media_asset_id=? LIMIT 1');$q->execute([$pageId,$assetId]);return (bool)$q->fetchColumn();}
function student_library_media_authorize(PDO $db,array $student,string $uuid,int $pageId,int $cohortId,int $expires,string $sig): ?array {
    if($expires<time()||$expires>time()+STUDENT_MEDIA_URL_TTL_SECONDS+60)return null;$expected=student_private_media_sig((int)$student['id'],$uuid,$pageId,$cohortId,$expires);if(!hash_equals($expected,$sig))return null;
    $page=cms_page_by_id($db,$pageId);if(!$page||!student_page_is_protected($page))return null;$context=student_account_enrollment_for_activity($db,(int)$student['id'],(int)$page['activity_id']);if(!$context||(int)$context['cohort_id']!==$cohortId)return null;
    $asset=student_library_media_asset_by_uuid($db,$uuid);if(!$asset||!student_library_media_bound_to_page($db,(int)$asset['id'],$pageId))return null;return $asset+['_library'=>1];
}
function student_library_media_admin_asset(PDO $db,string $uuid): ?array {$asset=student_library_media_asset_by_uuid($db,$uuid);if(!$asset)return null;$q=$db->prepare('SELECT 1 FROM course_page_media_slots WHERE media_asset_id=? LIMIT 1');$q->execute([(int)$asset['id']]);return $q->fetchColumn()?($asset+['_library'=>1]):null;}
