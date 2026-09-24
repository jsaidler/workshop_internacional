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

function student_private_media_for_slot(PDO $db,int $pageId,string $slotKey): ?array {
    $q=$db->prepare("SELECT * FROM student_private_media WHERE page_id=? AND slot_key=? ORDER BY id DESC LIMIT 1");
    $q->execute([$pageId,student_private_media_slot_key($slotKey)]);return $q->fetch()?:null;
}

function student_private_media_bind_slot(PDO $db,int $activityId,int $pageId,string $slotKey,string $title,array $file): array {
    $slotKey=student_private_media_slot_key($slotKey);
    $page=cms_page_by_id($db,$pageId);
    if(!$page||(int)$page['activity_id']!==$activityId||!student_page_is_protected($page))throw new RuntimeException('Selecione uma página protegida.');
    $slots=student_page_private_media_slots_from_document(cms_page_doc($page,false));
    if(!isset($slots[$slotKey]))throw new RuntimeException('Este espaço de imagem não existe na página.');
    if(($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK||!is_file((string)($file['tmp_name']??''))||($file['size']??0)<1)throw new RuntimeException('Arquivo inválido.');
    if((int)$file['size']>25*1024*1024)throw new RuntimeException('O arquivo excede 25 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file((string)$file['tmp_name'])?:'';$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
    if($ext===null)throw new RuntimeException('Use JPEG, PNG ou WebP.');
    student_private_media_mkdir();$uuid=student_uuid();$relative=$uuid.'.'.$ext;$dest=student_private_media_root().'/'.$relative;
    if(!move_uploaded_file((string)$file['tmp_name'],$dest))throw new RuntimeException('Falha ao armazenar arquivo.');
    $existing=student_private_media_for_slot($db,$pageId,$slotKey);$oldPath=$existing?(string)$existing['storage_path']:'';$now=utc_now();
    try{
        $db->beginTransaction();
        if($existing){
            $db->prepare('UPDATE student_private_media SET asset_uuid=?,title=?,original_name=?,mime_type=?,byte_size=?,storage_path=?,checksum=?,slot_key=?,updated_at=? WHERE id=?')
                ->execute([$uuid,trim($title)!==''?trim($title):pathinfo((string)($file['name']??'imagem'),PATHINFO_FILENAME),(string)($file['name']??$relative),$mime,(int)$file['size'],$relative,hash_file('sha256',$dest),$slotKey,$now,(int)$existing['id']]);
        }else{
            $db->prepare('INSERT INTO student_private_media(asset_uuid,activity_id,page_id,title,original_name,mime_type,byte_size,storage_path,checksum,slot_key,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')
                ->execute([$uuid,$activityId,$pageId,trim($title)!==''?trim($title):pathinfo((string)($file['name']??'imagem'),PATHINFO_FILENAME),(string)($file['name']??$relative),$mime,(int)$file['size'],$relative,hash_file('sha256',$dest),$slotKey,$now,$now]);
        }
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();@unlink($dest);throw $e;}
    if($oldPath!==''&&$oldPath!==$relative){$old=student_private_media_root().'/'.$oldPath;if(is_file($old))@unlink($old);}
    return student_private_media_by_uuid($db,$uuid)??throw new RuntimeException('media_bind_failed');
}

function student_page_resolve_private_media_slots(PDO $db,array $page,array $document): array {
    $html=(string)($document['html']??'');if($html==='')return $document;$pageId=(int)$page['id'];
    $html=preg_replace_callback('~<figure\b([^>]*)data-private-media-slot=["\']([^"\']+)["\']([^>]*)>.*?</figure>~is',static function(array $m)use($db,$pageId):string{
        try{$slot=student_private_media_slot_key((string)$m[2]);}catch(Throwable){return '';}
        $whole=(string)$m[0];$alt=$slot;if(preg_match('~data-private-media-alt=["\']([^"\']*)["\']~i',$whole,$am))$alt=html_entity_decode((string)$am[1],ENT_QUOTES|ENT_HTML5,'UTF-8');
        $asset=student_private_media_for_slot($db,$pageId,$slot);if(!$asset)return '';
        $src=student_private_media_placeholder($asset);
        return '<figure class="media-figure" data-private-media-slot="'.h($slot).'"><div class="media-area"><img data-cms-media src="'.h($src).'" alt="'.h($alt).'"></div></figure>';
    },$html)??$html;$document['html']=$html;return $document;
}

function student_private_media_admin_asset(PDO $db,string $uuid): ?array {
    if(!preg_match('/^[a-f0-9]{32}$/',$uuid))return null;$asset=student_private_media_by_uuid($db,$uuid);if(!$asset)return null;
    $page=cms_page_by_id($db,(int)$asset['page_id']);if(!$page||!student_page_is_protected($page))return null;return $asset;
}
