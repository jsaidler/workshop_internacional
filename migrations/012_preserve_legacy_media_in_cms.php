<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasCms=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    $hasLegacy=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='content_documents'")->fetchColumn();
    if(!$hasCms||!$hasLegacy)return;

    $normalize=static function(string $src): string {
        $src=trim($src);
        if(str_starts_with($src,'../assets/'))return '/assets/'.substr($src,10);
        if(str_starts_with($src,'assets/'))return '/'.$src;
        return $src;
    };

    $imageSource=static function(PDO $db,array $value) use($normalize): string {
        $fallback=$normalize((string)($value['src']??''));
        if(function_exists('media_image_sources')){
            try{
                $resolved=media_image_sources(
                    $db,
                    isset($value['mediaAssetId'])?(int)$value['mediaAssetId']:null,
                    isset($value['mediaVersionId'])?(int)$value['mediaVersionId']:null,
                    $fallback
                );
                return $normalize((string)($resolved['src']??$fallback));
            }catch(Throwable){ }
        }
        return $fallback;
    };

    $posterSource=static function(PDO $db,array $value) use($normalize): string {
        $fallback=$normalize((string)($value['poster']??''));
        if(function_exists('media_poster_url')){
            try{
                return $normalize(media_poster_url(
                    $db,
                    isset($value['mediaAssetId'])?(int)$value['mediaAssetId']:null,
                    isset($value['mediaVersionId'])?(int)$value['mediaVersionId']:null,
                    isset($value['posterId'])?(int)$value['posterId']:null,
                    $fallback
                ));
            }catch(Throwable){ }
        }
        return $fallback;
    };

    $activities=$db->query('SELECT id FROM activities')->fetchAll(PDO::FETCH_COLUMN);
    foreach($activities as $activityId){
        $q=$db->prepare('SELECT published_json FROM content_documents WHERE activity_id=? LIMIT 1');
        $q->execute([(int)$activityId]);
        $legacyJson=$q->fetchColumn();
        if(!is_string($legacyJson)||$legacyJson==='')continue;
        $legacy=json_decode($legacyJson,true);
        if(!is_array($legacy))continue;

        $hero=$imageSource($db,is_array($legacy['images']['hero-image']??null)?$legacy['images']['hero-image']:[]);
        $author=$imageSource($db,is_array($legacy['images']['author-portrait']??null)?$legacy['images']['author-portrait']:[]);
        $nina=$posterSource($db,is_array($legacy['videos']['nina-video']??null)?$legacy['videos']['nina-video']:[]);

        $replacements=[];
        if($hero!=='')$replacements['/assets/media/hero-medusa.webp']=$hero;
        if($author!=='')$replacements['/assets/media/joao-portrait.webp']=$author;
        if($nina!=='')$replacements['/assets/media/nina-poster.webp']=$nina;
        if(!$replacements)continue;

        $pages=$db->prepare("SELECT id,draft_document_json,published_document_json FROM cms_pages WHERE activity_id=? AND status!='archived'");
        $pages->execute([(int)$activityId]);
        foreach($pages->fetchAll() as $page){
            $updates=[];
            foreach(['draft_document_json','published_document_json'] as $column){
                $json=$page[$column]??null;
                if(!is_string($json)||$json==='')continue;
                $doc=json_decode($json,true);
                if(!is_array($doc)||!is_string($doc['html']??null))continue;
                $html=str_replace(array_keys($replacements),array_values($replacements),$doc['html'],$count);
                if($count<1)continue;
                $doc['html']=$html;
                $updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            }
            if(!$updates)continue;
            $sets=[];$args=[];
            foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
            $sets[]='updated_at=?';$args[]=gmdate('c');$args[]=(int)$page['id'];
            $stmt=$db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?');
            $stmt->execute($args);
        }
    }
};
