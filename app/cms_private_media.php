<?php
declare(strict_types=1);

function cms_private_media_asset_by_uuid(PDO $db,string $uuid): ?array {
    if(!preg_match('/^[a-f0-9]{32}$/',$uuid))return null;
    $q=$db->prepare("SELECT a.*,v.original_path FROM media_assets a LEFT JOIN media_versions v ON v.id=a.active_version_id WHERE a.asset_uuid=? AND a.visibility='private' AND a.archived_at IS NULL LIMIT 1");
    $q->execute([$uuid]);$row=$q->fetch(PDO::FETCH_ASSOC);return is_array($row)?$row:null;
}
function cms_private_media_page_uses_asset(array $page,string $uuid,bool $published=true): bool {
    $document=cms_page_doc($page,$published);$html=(string)($document['html']??'');
    return (bool)preg_match('~data-cms-media-asset=["\']'.preg_quote($uuid,'~').'["\']~i',$html);
}
function cms_private_media_resolve(PDO $db,array $page,array $document,bool $admin=false): array {
    $html=(string)($document['html']??'');if($html===''||!str_contains($html,'data-private-media-slot'))return $document;
    $html=preg_replace_callback('~<figure\b([^>]*)data-private-media-slot=["\']([^"\']+)["\']([^>]*)>.*?</figure>~is',static function(array $m)use($db,$admin):string{
        $whole=(string)$m[0];$slot=trim((string)$m[2]);$alt=$slot;
        if(preg_match('~data-private-media-alt=["\']([^"\']*)["\']~i',$whole,$am))$alt=html_entity_decode((string)$am[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
        $uuid='';if(preg_match('~data-cms-media-asset=["\']([a-f0-9]{32})["\']~i',$whole,$um))$uuid=strtolower((string)$um[1]);
        $asset=$uuid!==''?cms_private_media_asset_by_uuid($db,$uuid):null;
        if(!$asset){
            if(!$admin)return '';
            return '<figure class="media-figure" data-private-media-slot="'.h($slot).'"'.($uuid!==''?' data-cms-media-asset="'.h($uuid).'"':'').'><div class="cms-media-placeholder"><span>'.h($alt).'<br><br>'.($uuid!==''?'Mídia privada indisponível':'Selecione uma mídia privada no inspetor da seção').'</span></div></figure>';
        }
        $src=$admin?media_admin_private_url((int)$asset['id'],(string)$asset['original_path']):'/aluno/media.php?asset='.rawurlencode($uuid);
        return '<figure class="media-figure" data-private-media-slot="'.h($slot).'" data-cms-media-asset="'.h($uuid).'"><div class="media-area"><img data-cms-media src="'.h($src).'" alt="'.h($alt).'" loading="lazy"></div></figure>';
    },$html)??$html;
    $document['html']=$html;return $document;
}
function cms_private_media_sig(int $userId,string $uuid,int $pageId,int $cohortId,int $expires): string {
    return hash_hmac('sha256','cms-media|'.$userId.'|'.$uuid.'|'.$pageId.'|'.$cohortId.'|'.$expires,(string)app_config()['app_secret']);
}
function cms_private_media_url(int $userId,string $uuid,int $pageId,int $cohortId=0): string {
    $expires=time()+STUDENT_MEDIA_URL_TTL_SECONDS;$sig=cms_private_media_sig($userId,$uuid,$pageId,$cohortId,$expires);
    return '/aluno/media.php?'.http_build_query(['asset'=>$uuid,'page'=>$pageId,'cohort'=>$cohortId,'expires'=>$expires,'sig'=>$sig]);
}
function cms_private_media_sign_document(array $document,array $user,array $page,?array $enrollment=null): array {
    $html=(string)($document['html']??'');$userId=(int)$user['id'];$pageId=(int)$page['id'];$cohortId=(int)($enrollment['cohort_id']??0);
    $html=preg_replace_callback('~(?:https?://[^\"\']+)?/aluno/media\.php\?asset=([a-f0-9]{32})(?:&amp;|&[^\"\']*)?~i',static fn(array $m):string=>h(cms_private_media_url($userId,strtolower((string)$m[1]),$pageId,$cohortId)),$html)??$html;
    $document['html']=$html;return $document;
}
function cms_private_media_authorize(PDO $db,array $user,string $uuid,int $pageId,int $cohortId,int $expires,string $sig): ?array {
    if($expires<time()||$expires>time()+STUDENT_MEDIA_URL_TTL_SECONDS+60)return null;
    $expected=cms_private_media_sig((int)$user['id'],$uuid,$pageId,$cohortId,$expires);if(!hash_equals($expected,$sig))return null;
    $page=cms_page_by_id($db,$pageId);if(!$page||$page['status']==='archived'||!cms_private_media_page_uses_asset($page,$uuid,true))return null;
    if($cohortId>0){$enrollment=student_account_enrollment_for_activity($db,(int)$user['id'],(int)$page['activity_id']);if(!$enrollment||(int)$enrollment['cohort_id']!==$cohortId)return null;}
    $asset=cms_private_media_asset_by_uuid($db,$uuid);return $asset?($asset+['_library'=>1]):null;
}
function cms_private_media_admin_asset(PDO $db,string $uuid): ?array {$asset=cms_private_media_asset_by_uuid($db,$uuid);return $asset?($asset+['_library'=>1]):null;}
