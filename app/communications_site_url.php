<?php
declare(strict_types=1);
/** Canonical public HTTPS site URL for newsletter unsubscribe and admin links. */
function communication_site_url_valid(string $value): bool {
    if($value===''||strlen($value)>240||preg_match('/[\x00-\x20\x7F]/',$value))return false;
    $p=parse_url($value);
    if(!is_array($p)||strtolower((string)($p['scheme']??''))!=='https')return false;
    if(!isset($p['host'])||!preg_match('/^[a-z0-9.-]+$/i',(string)$p['host'])||str_contains((string)$p['host'],'..'))return false;
    if(isset($p['user'])||isset($p['pass'])||isset($p['query'])||isset($p['fragment']))return false;
    if(isset($p['path'])&&$p['path']!==''&&$p['path']!=='/')return false;
    return filter_var($value,FILTER_VALIDATE_URL)!==false;
}
function communication_site_url(PDO $db):string {
    $exists=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='communication_site_origin'")->fetchColumn();
    if($exists){
        $v=$db->query('SELECT public_url FROM communication_site_origin WHERE id=1')->fetchColumn();
        if(is_string($v)&&communication_site_url_valid($v))return rtrim($v,'/');
    }
    $fallback=trim((string)(app_config()['public_url']??''));
    return communication_site_url_valid($fallback)?rtrim($fallback,'/'):'';
}
function communication_site_url_save(PDO $db,string $value):void {
    $value=trim($value);
    if(!communication_site_url_valid($value))throw new RuntimeException('Informe o endereço completo do site, começando por https://, sem caminhos adicionais.');
    $q=$db->prepare('INSERT INTO communication_site_origin(id,public_url,updated_at) VALUES(1,?,?) ON CONFLICT(id) DO UPDATE SET public_url=excluded.public_url,updated_at=excluded.updated_at');
    $q->execute([rtrim($value,'/'),utc_now()]);
}
