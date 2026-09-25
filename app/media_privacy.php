<?php
declare(strict_types=1);

function media_visibility(string $value): string {return $value==='private'?'private':'public';}
function media_is_private(array $asset): bool {return media_visibility((string)($asset['visibility']??'public'))==='private';}

function media_private_assets(PDO $db,string $kind='image'): array {
    $q=$db->prepare("SELECT id FROM media_assets WHERE visibility='private' AND archived_at IS NULL AND (?='' OR kind=?) ORDER BY updated_at DESC,id DESC");
    $q->execute([$kind,$kind]);$out=[];foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id)$out[]=media_asset($db,(int)$id);return $out;
}
function media_page_slot_binding(PDO $db,int $pageId,string $slotKey): ?array {
    $q=$db->prepare('SELECT s.*,a.asset_uuid,a.kind,a.title,a.default_alt,a.visibility,a.archived_at FROM course_page_media_slots s JOIN media_assets a ON a.id=s.media_asset_id WHERE s.page_id=? AND s.slot_key=?');
    $q->execute([$pageId,student_private_media_slot_key($slotKey)]);return $q->fetch()?:null;
}
function media_page_slot_bind(PDO $db,int $activityId,int $pageId,string $slotKey,int $assetId): void {
    $page=cms_page_by_id($db,$pageId);if(!$page||(int)$page['activity_id']!==$activityId||!student_page_is_protected($page))throw new RuntimeException('Selecione uma página protegida.');
    $slotKey=student_private_media_slot_key($slotKey);$slots=student_page_private_media_slots_from_document(cms_page_doc($page,false));if(!isset($slots[$slotKey]))throw new RuntimeException('Este espaço de mídia não existe na página.');
    $asset=media_asset($db,$assetId);if(($asset['kind']??'')!=='image')throw new RuntimeException('Este espaço aceita uma imagem.');if(!media_is_private($asset))throw new RuntimeException('Marque a mídia como privada na Biblioteca antes de vinculá-la.');if(!empty($asset['archived_at']))throw new RuntimeException('Mídia arquivada não pode ser vinculada.');
    $now=utc_now();$db->prepare('INSERT INTO course_page_media_slots(page_id,slot_key,media_asset_id,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(page_id,slot_key) DO UPDATE SET media_asset_id=excluded.media_asset_id,updated_at=excluded.updated_at')->execute([$pageId,$slotKey,$assetId,$now,$now]);
}
function media_page_slot_unbind(PDO $db,int $activityId,int $pageId,string $slotKey): void {
    $page=cms_page_by_id($db,$pageId);if(!$page||(int)$page['activity_id']!==$activityId)throw new RuntimeException('Página inválida.');
    $db->prepare('DELETE FROM course_page_media_slots WHERE page_id=? AND slot_key=?')->execute([$pageId,student_private_media_slot_key($slotKey)]);
}
function media_page_slot_bindings(PDO $db,int $pageId): array {
    $q=$db->prepare('SELECT s.*,a.asset_uuid,a.kind,a.title,a.default_alt,a.visibility,a.archived_at FROM course_page_media_slots s JOIN media_assets a ON a.id=s.media_asset_id WHERE s.page_id=? ORDER BY s.slot_key');$q->execute([$pageId]);$out=[];foreach($q->fetchAll() as $row)$out[(string)$row['slot_key']]=$row;return $out;
}
function media_private_signature(int $studentId,int $assetId,int $pageId,int $cohortId,int $expires): string {return hash_hmac('sha256','media|'.$studentId.'|'.$assetId.'|'.$pageId.'|'.$cohortId.'|'.$expires,(string)app_config()['app_secret']);}
function media_private_student_url(int $studentId,int $assetId,int $pageId,int $cohortId): string {$expires=time()+STUDENT_MEDIA_URL_TTL_SECONDS;$sig=media_private_signature($studentId,$assetId,$pageId,$cohortId,$expires);return '/aluno/media.php?media='.$assetId.'&page='.$pageId.'&cohort='.$cohortId.'&expires='.$expires.'&sig='.$sig;}
function media_private_authorized_asset(PDO $db,array $student,int $assetId,int $pageId,int $cohortId,int $expires,string $sig): ?array {
    if($expires<time()||$expires>time()+STUDENT_MEDIA_URL_TTL_SECONDS+60)return null;$expected=media_private_signature((int)$student['id'],$assetId,$pageId,$cohortId,$expires);if(!hash_equals($expected,$sig))return null;
    $page=cms_page_by_id($db,$pageId);if(!$page||!student_page_is_protected($page))return null;$context=student_account_enrollment_for_activity($db,(int)$student['id'],(int)$page['activity_id']);if(!$context||(int)$context['cohort_id']!==$cohortId)return null;
    $bindingQ=$db->prepare('SELECT 1 FROM course_page_media_slots WHERE page_id=? AND media_asset_id=? LIMIT 1');$bindingQ->execute([$pageId,$assetId]);if(!$bindingQ->fetchColumn())return null;
    try{$asset=media_asset($db,$assetId);}catch(Throwable){return null;}return media_is_private($asset)?$asset:null;
}
function media_asset_by_storage_path(PDO $db,string $path): ?array {
    $path=ltrim(str_replace('\\','/',$path),'/');if($path===''||str_contains($path,'..'))return null;
    $q=$db->prepare("SELECT DISTINCT a.id FROM media_assets a LEFT JOIN media_versions v ON v.asset_id=a.id LEFT JOIN media_derivatives d ON d.version_id=v.id LEFT JOIN media_posters p ON p.video_asset_id=a.id LEFT JOIN media_poster_variants pv ON pv.poster_id=p.id WHERE v.original_path=? OR d.path=? OR pv.path=? LIMIT 1");$q->execute([$path,$path,$path]);$id=(int)$q->fetchColumn();if($id<1)return null;try{return media_asset($db,$id);}catch(Throwable){return null;}
}
