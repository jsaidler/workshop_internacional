<?php
declare(strict_types=1);

function cms_video_version(PDO $db,int $assetId,int $versionId): ?array {
    $q=$db->prepare('SELECT id,original_path,mime_type,processing_status FROM media_versions WHERE id=? AND asset_id=? LIMIT 1');
    $q->execute([$versionId,$assetId]);$row=$q->fetch();return $row?:null;
}
function cms_video_poster(PDO $db,int $versionId): string {
    $posters=media_poster_rows($db,$versionId);if(!$posters)return '';$poster=$posters[0];$variants=$poster['variants']??[];if(!$variants)return '';
    usort($variants,fn($a,$b)=>(int)($b['width']??0)<=>(int)($a['width']??0));
    $jpeg=array_values(array_filter($variants,fn($v)=>($v['format']??'')==='jpeg'));$choice=$jpeg[0]??$variants[0]??null;return is_array($choice)?(string)($choice['src']??''):'';
}
function media_resolve_cms_video_html(PDO $db,string $html): string {
    if(!str_contains($html,'data-media-asset-id')||!str_contains(strtolower($html),'<video'))return $html;
    $previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');$dom->loadHTML('<?xml encoding="utf-8" ?><div id="video-media-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$xpath=new DOMXPath($dom);
    foreach(iterator_to_array($xpath->query('//video[@data-media-asset-id]')?:[]) as $node){
        if(!$node instanceof DOMElement)continue;$assetId=(int)$node->getAttribute('data-media-asset-id');if($assetId<1)continue;
        try{$asset=media_asset($db,$assetId);}catch(Throwable){continue;}if(($asset['kind']??'')!=='video')continue;
        $versionId=(int)($asset['active_version_id']??0);if($node->getAttribute('data-media-version-mode')==='pinned'&&$node->hasAttribute('data-media-version-id'))$versionId=(int)$node->getAttribute('data-media-version-id');if($versionId<1)continue;
        $version=cms_video_version($db,$assetId,$versionId);if(!$version||$version['processing_status']!=='ready')continue;
        $node->setAttribute('src',media_video_url($assetId,$versionId,(string)$version['original_path']));$poster=cms_video_poster($db,$versionId);if($poster!=='')$node->setAttribute('poster',$poster);else $node->removeAttribute('poster');
        foreach(iterator_to_array($node->getElementsByTagName('source')) as $source)$node->removeChild($source);
    }
    $root=$dom->getElementById('video-media-root');$out='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);libxml_clear_errors();libxml_use_internal_errors($previous);return $out;
}
