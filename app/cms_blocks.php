<?php
declare(strict_types=1);

function cms_block_uuid(): string { return bin2hex(random_bytes(16)); }
function cms_blocks(PDO $db,int $activityId,string $locale): array {
    $locale=normalize_public_locale($locale)??PUBLIC_LOCALE_PT_BR;
    $q=$db->prepare('SELECT * FROM cms_reusable_blocks WHERE activity_id=? AND locale=? ORDER BY name COLLATE NOCASE,id');
    $q->execute([$activityId,$locale]);return $q->fetchAll();
}
function cms_block_by_id(PDO $db,int $id): ?array {$q=$db->prepare('SELECT * FROM cms_reusable_blocks WHERE id=?');$q->execute([$id]);return $q->fetch()?:null;}
function cms_block_clean_name(string $name): string {$name=trim($name);if($name==='')throw new RuntimeException('block_name_required');if(mb_strlen($name)>160)throw new RuntimeException('block_name_too_long');return $name;}
function cms_block_save(PDO $db,int $activityId,string $locale,string $name,string $html,string $category='custom',?int $id=null): array {
    $locale=normalize_public_locale($locale)??PUBLIC_LOCALE_PT_BR;$name=cms_block_clean_name($name);$category=activity_slug($category?:'custom');$html=cms_sanitize_html($html);if(trim($html)==='')throw new RuntimeException('block_html_required');$now=gmdate('c');
    if($id){$existing=cms_block_by_id($db,$id)??throw new RuntimeException('block_not_found');if((int)$existing['activity_id']!==$activityId)throw new RuntimeException('block_not_found');$q=$db->prepare('UPDATE cms_reusable_blocks SET locale=?,name=?,category=?,html=?,updated_at=? WHERE id=?');$q->execute([$locale,$name,$category,$html,$now,$id]);return cms_block_by_id($db,$id)??throw new RuntimeException('block_not_found');}
    $q=$db->prepare('INSERT INTO cms_reusable_blocks(block_uuid,activity_id,locale,name,category,html,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)');$q->execute([cms_block_uuid(),$activityId,$locale,$name,$category,$html,$now,$now]);return cms_block_by_id($db,(int)$db->lastInsertId())??throw new RuntimeException('block_create_failed');
}
function cms_block_rename(PDO $db,int $activityId,int $id,string $name): array {$block=cms_block_by_id($db,$id)??throw new RuntimeException('block_not_found');if((int)$block['activity_id']!==$activityId)throw new RuntimeException('block_not_found');$name=cms_block_clean_name($name);$db->prepare('UPDATE cms_reusable_blocks SET name=?,updated_at=? WHERE id=?')->execute([$name,gmdate('c'),$id]);return cms_block_by_id($db,$id)??throw new RuntimeException('block_not_found');}
function cms_block_delete(PDO $db,int $activityId,int $id): void {$block=cms_block_by_id($db,$id)??throw new RuntimeException('block_not_found');if((int)$block['activity_id']!==$activityId)throw new RuntimeException('block_not_found');$db->prepare('DELETE FROM cms_reusable_blocks WHERE id=?')->execute([$id]);}
