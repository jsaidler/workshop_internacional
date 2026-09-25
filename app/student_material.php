<?php
declare(strict_types=1);

function student_private_media_slot_key(string $value): string {return cms_protected_media_slot_key($value);}

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

function student_private_media_for_slot(PDO $db,int $pageId,string $slotKey): ?array {return cms_protected_media_for_slot($db,$pageId,$slotKey);}

function student_page_resolve_private_media_slots(PDO $db,array $page,array $document): array {
    $html=(string)($document['html']??'');if($html==='')return $document;$pageId=(int)$page['id'];
    $html=preg_replace_callback('~<figure\b([^>]*)data-private-media-slot=["\']([^"\']+)["\']([^>]*)>.*?</figure>~is',static function(array $m)use($db,$pageId):string{
        try{$slot=student_private_media_slot_key((string)$m[2]);}catch(Throwable){return '';}
        $whole=(string)$m[0];$alt=$slot;if(preg_match('~data-private-media-alt=["\']([^"\']*)["\']~i',$whole,$am))$alt=html_entity_decode((string)$am[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
        $asset=student_private_media_for_slot($db,$pageId,$slot);
        if(!$asset){
            if(!current_admin())return '';
            return '<figure class="media-figure" data-private-media-slot="'.h($slot).'"><div class="cms-media-placeholder"><span>Mídia pendente<br>'.h($alt).'<br><br>slot: '.h($slot).'</span></div></figure>';
        }
        $src=current_admin()?media_private_admin_url((int)$asset['id']):('/aluno/media.php?asset='.rawurlencode((string)$asset['asset_uuid']));
        return '<figure class="media-figure" data-private-media-slot="'.h($slot).'"><div class="media-area"><img data-cms-media data-media-asset-id="'.(int)$asset['id'].'" src="'.h($src).'" alt="'.h($alt).'"></div></figure>';
    },$html)??$html;$document['html']=$html;return $document;
}

function student_page_sign_private_media(array $document,array $student,array $page,array $enrollment): array {
    $html=(string)($document['html']??'');if($html==='')return $document;
    $pageId=(int)$page['id'];$cohortUuid=(string)($enrollment['cohort_uuid']??'');
    $html=preg_replace_callback('~(?:https?://[^\"\']+)?/aluno/media\.php\?asset=([a-f0-9]{32})~i',static function(array $m)use($pageId,$cohortUuid):string{
        return h('/aluno/media.php?'.http_build_query(['asset'=>strtolower((string)$m[1]),'page'=>$pageId,'cohort'=>$cohortUuid]));
    },$html)??$html;$document['html']=$html;return $document;
}

function student_protected_media_authorize(PDO $db,array $student,string $uuid,int $pageId,string $cohortUuid): ?array {
    if(!preg_match('/^[a-f0-9]{32}$/',$uuid)||$pageId<1||$cohortUuid==='')return null;
    $page=cms_page_by_id($db,$pageId);if(!$page||!student_page_is_protected($page))return null;
    $q=$db->prepare("SELECT a.* FROM cms_protected_media_slots s JOIN media_assets a ON a.id=s.asset_id WHERE s.page_id=? AND a.asset_uuid=? AND a.visibility='private' AND a.archived_at IS NULL LIMIT 1");$q->execute([$pageId,$uuid]);$asset=$q->fetch()?:null;if(!$asset)return null;
    $q=$db->prepare("SELECT e.id FROM course_enrollments e JOIN course_cohorts c ON c.id=e.cohort_id WHERE e.student_id=? AND e.status='active' AND c.cohort_uuid=? AND c.activity_id=? AND c.status!='archived' LIMIT 1");$q->execute([(int)$student['id'],$cohortUuid,(int)$page['activity_id']]);if(!$q->fetchColumn())return null;
    return $asset;
}

function student_private_media_admin_asset(PDO $db,string $uuid): ?array {
    if(!preg_match('/^[a-f0-9]{32}$/',$uuid))return null;$q=$db->prepare("SELECT a.* FROM media_assets a WHERE a.asset_uuid=? AND a.visibility='private' AND a.archived_at IS NULL LIMIT 1");$q->execute([$uuid]);return $q->fetch()?:null;
}
