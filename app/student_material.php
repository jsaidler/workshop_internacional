<?php
declare(strict_types=1);

function student_private_media_slot_key(string $value): string {
    $value=activity_slug(trim($value));
    if($value===''||strlen($value)>80)throw new RuntimeException('Slot de mídia inválido.');
    return $value;
}

function student_page_private_media_slots_from_document(array $document): array {
    $html=(string)($document['html']??'');$slots=[];
    if($html==='')return $slots;
    if(preg_match_all('~<figure\b[^>]*data-private-media-slot=["\']([^"\']+)["\'][^>]*>.*?</figure>~is',$html,$matches,PREG_SET_ORDER)){
        foreach($matches as $match){
            $tag=(string)$match[0];$key=student_private_media_slot_key((string)$match[1]);$alt='';
            if(preg_match('~data-private-media-alt=["\']([^"\']*)["\']~i',$tag,$altMatch))$alt=html_entity_decode((string)$altMatch[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
            $slots[$key]=['key'=>$key,'alt'=>$alt!==''?$alt:$key];
        }
    }
    return $slots;
}

/* Legacy helpers remain readable only so migration 065 can preserve installations
   that already contain student_private_media. New editorial media must use media_assets. */
function student_private_media_for_slot(PDO $db,int $pageId,string $slotKey): ?array {
    $q=$db->prepare("SELECT * FROM student_private_media WHERE page_id=? AND slot_key=? ORDER BY id DESC LIMIT 1");
    $q->execute([$pageId,student_private_media_slot_key($slotKey)]);return $q->fetch()?:null;
}

function student_private_media_bind_slot(PDO $db,int $activityId,int $pageId,string $slotKey,string $title,array $file): array {
    throw new RuntimeException('O upload editorial privado foi unificado na Biblioteca de mídia.');
}

function student_page_resolve_private_media_slots(PDO $db,array $page,array $document): array {
    $html=(string)($document['html']??'');if($html==='')return $document;$pageId=(int)$page['id'];
    $html=preg_replace_callback('~<figure\b([^>]*)data-private-media-slot=["\']([^"\']+)["\']([^>]*)>.*?</figure>~is',static function(array $m)use($db,$pageId):string{
        try{$slot=student_private_media_slot_key((string)$m[2]);}catch(Throwable){return '';}
        $whole=(string)$m[0];$alt=$slot;if(preg_match('~data-private-media-alt=["\']([^"\']*)["\']~i',$whole,$am))$alt=html_entity_decode((string)$am[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
        $asset=course_page_media_slot($db,$pageId,$slot);
        if(!$asset){
            if(!current_admin())return '';
            return '<figure class="media-figure" data-private-media-slot="'.h($slot).'"><div class="cms-media-placeholder"><span>Infográfico pendente<br>'.h($alt).'<br><br>slot: '.h($slot).'</span></div></figure>';
        }
        if(!media_asset_private($asset))return '';
        $src=current_admin()
            ?media_admin_private_url((int)$asset['media_asset_id'],(string)$asset['original_path'])
            :'/aluno/media.php?asset='.rawurlencode((string)$asset['asset_uuid']);
        return '<figure class="media-figure" data-private-media-slot="'.h($slot).'"><div class="media-area"><img data-cms-media src="'.h($src).'" alt="'.h($alt).'"></div></figure>';
    },$html)??$html;$document['html']=$html;return $document;
}

function student_private_media_admin_asset(PDO $db,string $uuid): ?array {
    $library=student_library_media_admin_asset($db,$uuid);if($library)return $library;
    if(!preg_match('/^[a-f0-9]{32}$/',$uuid))return null;$asset=student_private_media_by_uuid($db,$uuid);if(!$asset)return null;
    $page=cms_page_by_id($db,(int)$asset['page_id']);if(!$page)return null;
    $access=(string)($page['access_level']??'public');if($access==='public')return null;
    return $asset;
}

function student_material_legacy_media_authorize(PDO $db,array $student,string $uuid,int $pageId,int $cohortId,int $expires,string $sig): ?array {
    if($expires<time()||$expires>time()+STUDENT_MEDIA_URL_TTL_SECONDS+60)return null;
    $expected=student_private_media_sig((int)$student['id'],$uuid,$pageId,$cohortId,$expires);if(!hash_equals($expected,$sig))return null;
    $page=cms_page_by_id($db,$pageId);if(!$page)return null;$activity=activity_by_id($db,(int)$page['activity_id']);if(!$activity)return null;
    if(!cms_access_page_allowed($db,$activity,$page,$student))return null;
    $enrollment=student_account_enrollment_for_activity($db,(int)$student['id'],(int)$page['activity_id']);if(!$enrollment||(int)$enrollment['cohort_id']!==$cohortId)return null;
    $asset=student_private_media_by_uuid($db,$uuid);if(!$asset||(int)$asset['page_id']!==$pageId||(int)$asset['activity_id']!==(int)$page['activity_id'])return null;
    return $asset;
}
