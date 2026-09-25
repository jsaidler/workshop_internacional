<?php
declare(strict_types=1);

function student_private_media_slot_key(string $value): string {
    $value=activity_slug(trim($value));
    if($value===''||strlen($value)>80)throw new RuntimeException('Slot de mídia inválido.');
    return $value;
}
function student_page_private_media_slots_from_document(array $document): array {
    $html=(string)($document['html']??'');$slots=[];if($html==='')return $slots;
    if(preg_match_all('~<figure\b[^>]*data-private-media-slot=["\']([^"\']+)["\'][^>]*>.*?</figure>~is',$html,$matches,PREG_SET_ORDER))foreach($matches as $match){$tag=(string)$match[0];$key=student_private_media_slot_key((string)$match[1]);$alt='';if(preg_match('~data-private-media-alt=["\']([^"\']*)["\']~i',$tag,$am))$alt=html_entity_decode((string)$am[1],ENT_QUOTES|ENT_HTML5,'UTF-8');$slots[$key]=['key'=>$key,'alt'=>$alt!==''?$alt:$key];}
    return $slots;
}

/* Legacy read-only fallback. New editorial media must be bound from media_assets. */
function student_private_media_for_slot(PDO $db,int $pageId,string $slotKey): ?array {$q=$db->prepare('SELECT * FROM student_private_media WHERE page_id=? AND slot_key=? ORDER BY id DESC LIMIT 1');$q->execute([$pageId,student_private_media_slot_key($slotKey)]);return $q->fetch()?:null;}
function student_private_media_admin_asset(PDO $db,string $uuid): ?array {if(!preg_match('/^[a-f0-9]{32}$/',$uuid))return null;$asset=student_private_media_by_uuid($db,$uuid);if(!$asset)return null;$page=cms_page_by_id($db,(int)$asset['page_id']);return $page&&student_page_is_protected($page)?$asset:null;}

function student_page_resolve_private_media_slots(PDO $db,array $page,array $document): array {
    $html=(string)($document['html']??'');if($html==='')return $document;$pageId=(int)$page['id'];
    $html=preg_replace_callback('~<figure\b([^>]*)data-private-media-slot=["\']([^"\']+)["\']([^>]*)>.*?</figure>~is',static function(array $m)use($db,$pageId):string{
        try{$slot=student_private_media_slot_key((string)$m[2]);}catch(Throwable){return '';}
        $whole=(string)$m[0];$alt=$slot;if(preg_match('~data-private-media-alt=["\']([^"\']*)["\']~i',$whole,$am))$alt=html_entity_decode((string)$am[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
        $binding=media_page_slot_binding($db,$pageId,$slot);
        if($binding){$src=current_admin()?'/media-file.php?asset='.(int)$binding['media_asset_id']:('/aluno/media.php?media='.(int)$binding['media_asset_id']);return '<figure class="media-figure" data-private-media-slot="'.h($slot).'"><div class="media-area"><img data-cms-media src="'.h($src).'" alt="'.h($alt).'"></div></figure>';}
        $legacy=student_private_media_for_slot($db,$pageId,$slot);
        if($legacy){$src=student_private_media_placeholder($legacy);return '<figure class="media-figure" data-private-media-slot="'.h($slot).'"><div class="media-area"><img data-cms-media src="'.h($src).'" alt="'.h($alt).'"></div></figure>';}
        if(!current_admin())return '';
        return '<figure class="media-figure" data-private-media-slot="'.h($slot).'"><div class="cms-media-placeholder"><span>Infográfico pendente<br>'.h($alt).'<br><br>slot: '.h($slot).'</span></div></figure>';
    },$html)??$html;$document['html']=$html;return $document;
}
function student_page_sign_media_library(array $document,array $student,array $page,array $enrollment): array {
    $html=(string)($document['html']??'');$studentId=(int)$student['id'];$pageId=(int)$page['id'];$cohortId=(int)$enrollment['cohort_id'];
    $html=preg_replace_callback('~/aluno/media\.php\?media=(\d+)~i',static fn($m)=>h(media_private_student_url($studentId,(int)$m[1],$pageId,$cohortId)),$html)??$html;$document['html']=$html;return $document;
}
