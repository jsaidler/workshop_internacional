<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasTable=static fn(string $name): bool => (bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    $columns=static function(string $table)use($db): array{return array_column($db->query('PRAGMA table_info('.$table.')')->fetchAll(PDO::FETCH_ASSOC),'name');};

    if($hasTable('student_tests')){
        $cols=$columns('student_tests');
        if(!in_array('bleach',$cols,true))$db->exec("ALTER TABLE student_tests ADD COLUMN bleach TEXT NOT NULL DEFAULT ''");
    }

    if(!$hasTable('cms_pages'))return;

    $lessonById=[];
    if($hasTable('course_lessons'))foreach($db->query('SELECT id,lesson_key FROM course_lessons')->fetchAll(PDO::FETCH_ASSOC) as $row)$lessonById[(int)$row['id']]=(string)$row['lesson_key'];
    $sectionRules=[];
    if($hasTable('course_page_sections'))foreach($db->query('SELECT page_id,section_key,lesson_id FROM course_page_sections')->fetchAll(PDO::FETCH_ASSOC) as $row){
        $lessonKey=$lessonById[(int)$row['lesson_id']]??'';
        if($lessonKey!=='')$sectionRules[(int)$row['page_id']][(string)$row['section_key']]=$lessonKey;
    }

    $assetById=[];
    if($hasTable('media_assets'))foreach($db->query('SELECT id,asset_uuid FROM media_assets')->fetchAll(PDO::FETCH_ASSOC) as $row)$assetById[(int)$row['id']]=(string)$row['asset_uuid'];
    $slotRules=[];
    if($hasTable('course_page_media_slots'))foreach($db->query('SELECT page_id,slot_key,media_asset_id FROM course_page_media_slots')->fetchAll(PDO::FETCH_ASSOC) as $row){
        $uuid=$assetById[(int)$row['media_asset_id']]??'';
        if($uuid!=='')$slotRules[(int)$row['page_id']][(string)$row['slot_key']]=$uuid;
    }

    $transform=static function(string $json,array $sections,array $slots): string {
        if($json==='')return $json;
        $document=json_decode($json,true);if(!is_array($document))return $json;
        $html=(string)($document['html']??'');if($html==='')return $json;
        $previous=libxml_use_internal_errors(true);
        $dom=new DOMDocument('1.0','UTF-8');
        $loaded=$dom->loadHTML('<?xml encoding="utf-8" ?><div id="cms-access-migration-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
        if(!$loaded){libxml_clear_errors();libxml_use_internal_errors($previous);return $json;}
        $xpath=new DOMXPath($dom);
        foreach(iterator_to_array($xpath->query('//*[@data-cms-section]')?:[]) as $node){
            if(!$node instanceof DOMElement)continue;$key=trim($node->getAttribute('data-cms-section'));$lesson=$sections[$key]??'';
            if($lesson==='')continue;
            $node->setAttribute('data-cms-lesson',$lesson);
            if(!$node->hasAttribute('data-cms-access'))$node->setAttribute('data-cms-access','enrolled');
        }
        foreach(iterator_to_array($xpath->query('//*[@data-private-media-slot]')?:[]) as $node){
            if(!$node instanceof DOMElement)continue;$slot=trim($node->getAttribute('data-private-media-slot'));$uuid=$slots[$slot]??'';
            if($uuid!=='')$node->setAttribute('data-cms-media-asset',$uuid);
        }
        $root=$dom->getElementById('cms-access-migration-root');$out='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);
        libxml_clear_errors();libxml_use_internal_errors($previous);
        $document['html']=$out;
        return json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    };

    $pages=$db->query("SELECT id,draft_document_json,published_document_json FROM cms_pages WHERE status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    $update=$db->prepare('UPDATE cms_pages SET draft_document_json=?,published_document_json=? WHERE id=?');
    foreach($pages as $page){
        $id=(int)$page['id'];$sections=$sectionRules[$id]??[];$slots=$slotRules[$id]??[];
        if(!$sections&&!$slots)continue;
        $draft=$transform((string)($page['draft_document_json']??''),$sections,$slots);
        $publishedRaw=(string)($page['published_document_json']??'');$published=$publishedRaw!==''?$transform($publishedRaw,$sections,$slots):$publishedRaw;
        $update->execute([$draft,$published!==''?$published:null,$id]);
    }
};
