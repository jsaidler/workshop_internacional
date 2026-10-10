<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasCms=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    $hasLegacy=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='content_documents'")->fetchColumn();
    if(!$hasCms||!$hasLegacy)return;
    $activities=$db->query('SELECT id FROM activities')->fetchAll(PDO::FETCH_COLUMN);
    foreach($activities as $activityId){
        $q=$db->prepare('SELECT published_json FROM content_documents WHERE activity_id=? LIMIT 1');$q->execute([(int)$activityId]);$json=$q->fetchColumn();if(!is_string($json)||$json==='')continue;$legacy=json_decode($json,true);if(!is_array($legacy))continue;
        $hero=is_array($legacy['images']['hero-image']??null)?$legacy['images']['hero-image']:[];
        $author=is_array($legacy['images']['author-portrait']??null)?$legacy['images']['author-portrait']:[];
        $nina=is_array($legacy['videos']['nina-video']??null)?$legacy['videos']['nina-video']:[];
        $resolveImage=static function(array $value) use($db): array {$fallback=(string)($value['src']??'');try{$s=media_image_sources($db,isset($value['mediaAssetId'])?(int)$value['mediaAssetId']:null,isset($value['mediaVersionId'])?(int)$value['mediaVersionId']:null,$fallback);return ['src'=>(string)($s['src']??$fallback),'assetId'=>(int)($value['mediaAssetId']??0),'versionId'=>(int)($value['mediaVersionId']??0)];}catch(Throwable){return ['src'=>$fallback,'assetId'=>0,'versionId'=>0];}};
        $heroResolved=$resolveImage($hero);$authorResolved=$resolveImage($author);$ninaPoster=(string)($nina['poster']??'');try{$ninaPoster=media_poster_url($db,isset($nina['mediaAssetId'])?(int)$nina['mediaAssetId']:null,isset($nina['mediaVersionId'])?(int)$nina['mediaVersionId']:null,isset($nina['posterId'])?(int)$nina['posterId']:null,$ninaPoster);}catch(Throwable){}
        $pages=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND is_home=1 AND status!='archived'");$pages->execute([(int)$activityId]);
        foreach($pages->fetchAll() as $page){$updates=[];foreach(['draft_document_json','published_document_json'] as $column){$docJson=$page[$column]??null;if(!is_string($docJson)||$docJson==='')continue;$document=json_decode($docJson,true);if(!is_array($document)||!is_string($document['html']??null))continue;$previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');$dom->loadHTML('<?xml encoding="utf-8" ?><div id="migration-root">'.$document['html'].'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$xp=new DOMXPath($dom);
            $setImage=static function(?DOMNode $node,array $resolved):void{if(!$node instanceof DOMElement||$resolved['src']==='')return;$node->setAttribute('src',$resolved['src']);if($resolved['assetId']>0){$node->setAttribute('data-media-asset-id',(string)$resolved['assetId']);$node->setAttribute('data-media-version-id',(string)$resolved['versionId']);}};
            $setImage(($xp->query('//*[@data-cms-section="hero"]//img')->item(0))?:null,$heroResolved);$setImage(($xp->query('//*[@data-cms-section="about"]//img')->item(0))?:null,$authorResolved);$camera=$xp->query('//*[@data-cms-section="camera"]//img')->item(0);if($camera instanceof DOMElement&&$ninaPoster!=='')$camera->setAttribute('src',$ninaPoster);
            $root=$dom->getElementById('migration-root');$out='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);libxml_clear_errors();libxml_use_internal_errors($previous);$document['html']=$out;$updates[$column]=json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
            if($updates){$sets=[];$args=[];foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}$sets[]='updated_at=?';$args[]=gmdate('c');$args[]=(int)$page['id'];$db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);}
        }
    }
};
