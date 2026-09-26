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

function student_page_sign_private_media_for_access(array $document,array $student,array $page,?array $enrollment=null): array {
    $html=(string)($document['html']??'');if($html==='')return $document;$studentId=(int)$student['id'];$pageId=(int)$page['id'];$cohortId=(int)($enrollment['cohort_id']??0);
    $html=preg_replace_callback('~(?:https?://[^\"\']+)?/aluno/media\.php\?asset=([a-f0-9]{32})(?:&amp;|&[^\"\']*)?~i',static fn(array $m):string=>h(student_private_media_url($studentId,strtolower((string)$m[1]),$pageId,$cohortId)),$html)??$html;
    $document['html']=$html;return $document;
}

function student_private_media_admin_asset(PDO $db,string $uuid): ?array {
    $library=student_library_media_admin_asset($db,$uuid);if($library)return $library;
    if(!preg_match('/^[a-f0-9]{32}$/',$uuid))return null;$asset=student_private_media_by_uuid($db,$uuid);if(!$asset)return null;
    $page=cms_page_by_id($db,(int)$asset['page_id']);if(!$page||!in_array((string)($page['access_level']??'public'),['authenticated','activity','cohort','enrolled'],true))return null;return $asset;
}