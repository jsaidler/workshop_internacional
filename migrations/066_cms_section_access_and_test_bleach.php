<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $now=gmdate('c');
    $tables=array_column($db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC),'name');
    if(in_array('student_tests',$tables,true)){
        $columns=array_column($db->query('PRAGMA table_info(student_tests)')->fetchAll(PDO::FETCH_ASSOC),'name');
        if(!in_array('bleach',$columns,true))$db->exec("ALTER TABLE student_tests ADD COLUMN bleach TEXT NOT NULL DEFAULT ''");
    }
    if(in_array('cms_pages',$tables,true))$db->exec("UPDATE cms_pages SET access_level='activity' WHERE access_level='enrolled'");
    if(!in_array('cms_pages',$tables,true)||!in_array('course_page_sections',$tables,true))return;

    $mappings=$db->query('SELECT page_id,section_key,lesson_id FROM course_page_sections ORDER BY page_id,section_key')->fetchAll(PDO::FETCH_ASSOC);
    $byPage=[];foreach($mappings as $row)$byPage[(int)$row['page_id']][(string)$row['section_key']]=(int)$row['lesson_id'];
    $select=$db->query("SELECT id,draft_document_json,published_document_json FROM cms_pages WHERE status!='archived'");
    foreach($select->fetchAll(PDO::FETCH_ASSOC) as $page){
        $pageId=(int)$page['id'];$map=$byPage[$pageId]??[];if(!$map)continue;$sets=[];$args=[];
        foreach(['draft_document_json','published_document_json'] as $column){
            $json=(string)($page[$column]??'');if($json==='')continue;$doc=json_decode($json,true);if(!is_array($doc)||!isset($doc['html']))continue;
            $previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');$dom->loadHTML('<?xml encoding="utf-8" ?><div id="migration-root">'.(string)$doc['html'].'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$xpath=new DOMXPath($dom);$changed=false;
            foreach(iterator_to_array($xpath->query('//*[@data-cms-section]')?:[]) as $node){if(!$node instanceof DOMElement)continue;$key=trim($node->getAttribute('data-cms-section'));$lessonId=$map[$key]??0;if($lessonId<1)continue;$node->setAttribute('data-cms-availability','lesson');$node->setAttribute('data-cms-lesson-id',(string)$lessonId);$changed=true;}
            if($changed){$root=$dom->getElementById('migration-root');$html='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$html.=$dom->saveHTML($child);$doc['html']=$html;$sets[]=$column.'=?';$args[]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);}
            libxml_clear_errors();libxml_use_internal_errors($previous);
        }
        if($sets){$sets[]='updated_at=?';$args[]=$now;$args[]=$pageId;$db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);}
    }
};
