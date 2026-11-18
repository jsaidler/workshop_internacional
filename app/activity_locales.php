<?php
declare(strict_types=1);

function activity_locale_normalize(string $locale): string {
    $normalized=normalize_public_locale($locale);
    if($normalized===null)throw new RuntimeException('Idioma do curso inválido.');
    return $normalized;
}
function activity_locales_available(PDO $db): bool {
    static $cache=[];
    $key=spl_object_id($db);
    if(array_key_exists($key,$cache))return $cache[$key];
    $q=$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='activity_locales'");
    return $cache[$key]=(bool)$q->fetchColumn();
}
function activity_locales(PDO $db,int $activityId): array {
    if(!activity_locales_available($db))return [];
    $q=$db->prepare('SELECT * FROM activity_locales WHERE activity_id=? ORDER BY locale');
    $q->execute([$activityId]);
    $out=[];
    foreach($q->fetchAll() as $row)$out[(string)$row['locale']]=$row;
    return $out;
}
function activity_public_title(PDO $db,array $activity,string $locale): string {
    $locale=activity_locale_normalize($locale);
    if(activity_locales_available($db)){
        $q=$db->prepare('SELECT public_title FROM activity_locales WHERE activity_id=? AND locale=? LIMIT 1');
        $q->execute([(int)$activity['id'],$locale]);
        $title=$q->fetchColumn();
        if(is_string($title)&&trim($title)!=='')return trim($title);
    }
    return trim((string)($activity['public_title']??''));
}
function activity_locale_save(PDO $db,int $activityId,string $locale,string $publicTitle): array {
    if(!activity_locales_available($db))throw new RuntimeException('activity_locales_missing');
    $locale=activity_locale_normalize($locale);$publicTitle=trim($publicTitle);
    if($publicTitle==='')throw new RuntimeException('Informe o nome público do curso.');
    if(mb_strlen($publicTitle)>220)throw new RuntimeException('Nome público muito longo.');
    $now=gmdate('c');
    $q=$db->prepare('INSERT INTO activity_locales(activity_id,locale,public_title,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(activity_id,locale) DO UPDATE SET public_title=excluded.public_title,updated_at=excluded.updated_at');
    $q->execute([$activityId,$locale,$publicTitle,$now,$now]);
    $read=$db->prepare('SELECT * FROM activity_locales WHERE activity_id=? AND locale=?');$read->execute([$activityId,$locale]);
    return $read->fetch()?:throw new RuntimeException('activity_locale_save_failed');
}
function activity_with_locale(PDO $db,array $activity,string $locale): array {
    $activity['public_title']=activity_public_title($db,$activity,$locale);
    $activity['resolved_locale']=activity_locale_normalize($locale);
    return $activity;
}
