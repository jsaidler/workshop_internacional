<?php
declare(strict_types=1);

require __DIR__.'/../app/bootstrap.php';
require_admin();

function editor_asset_version(): string {
    $info=dirname(__DIR__).'/deploy-info.json';
    if(is_file($info)){
        $decoded=json_decode((string)@file_get_contents($info),true);
        $sha=is_array($decoded)?trim((string)($decoded['sourceSha']??'')):'';
        if($sha!==''&&preg_match('/^[a-f0-9]{7,64}$/i',$sha))return substr($sha,0,16);
    }
    $mtime=@filemtime(__DIR__.'/index.html');
    return $mtime!==false?(string)$mtime:'1';
}

$html=(string)file_get_contents(__DIR__.'/index.html');
$version=rawurlencode(editor_asset_version());
$html=preg_replace_callback(
    '/\b(href|src)="(\/editor\/[^"?]+\.(?:css|js))"/i',
    static fn(array $m): string=>$m[1].'="'.$m[2].'?v='.$version.'"',
    $html
)??$html;

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, must-revalidate');
echo $html;
