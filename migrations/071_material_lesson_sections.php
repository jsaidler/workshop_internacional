<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tables=array_column($db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('cms_pages',$tables,true)||!in_array('course_lessons',$tables,true))return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();$page=$q->fetch(PDO::FETCH_ASSOC)?:null;if(!$page)return;

    $lessonQ=$db->prepare('SELECT id,lesson_key FROM course_lessons WHERE activity_id=?');
    $lessonQ->execute([(int)$page['activity_id']]);$lessonIds=[];
    foreach($lessonQ->fetchAll(PDO::FETCH_ASSOC) as $row)$lessonIds[(string)$row['lesson_key']]=(int)$row['id'];
    foreach(['aula-1','aula-2','aula-3'] as $required)if(empty($lessonIds[$required]))return;

    $updates=[];$changed=false;
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');if($raw==='')continue;$doc=json_decode($raw,true);if(!is_array($doc)||!is_string($doc['html']??null))continue;
        $html=(string)$doc['html'];$slotKeys=[];if(preg_match_all('~data-private-media-slot=["\']([^"\']+)["\']~i',$html,$slotMatches))$slotKeys=$slotMatches[1];
        $previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');$dom->loadHTML('<?xml encoding="utf-8" ?><div id="material-lessons-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$xpath=new DOMXPath($dom);$documentChanged=false;
        foreach(iterator_to_array($xpath->query('//*[@data-cms-section]')?:[]) as $node){
            if(!$node instanceof DOMElement)continue;$key=trim($node->getAttribute('data-cms-section'));$name=trim($node->getAttribute('data-cms-section-name'));$lessonKey='';
            if($key==='caderno-aula-1'||str_starts_with($name,'Aula 1 ·')||str_starts_with($name,'Aula 1 —'))$lessonKey='aula-1';
            elseif($key==='caderno-aula-2'||str_starts_with($name,'Aula 2 ·')||str_starts_with($name,'Aula 2 —'))$lessonKey='aula-2';
            elseif($key==='caderno-aula-3'||str_starts_with($name,'Aula 3 ·')||str_starts_with($name,'Aula 3 —'))$lessonKey='aula-3';

            if($lessonKey!==''){
                $lessonId=(string)$lessonIds[$lessonKey];
                if($node->getAttribute('data-cms-availability')!=='lesson'){$node->setAttribute('data-cms-availability','lesson');$documentChanged=true;}
                if($node->getAttribute('data-cms-lesson-id')!==$lessonId){$node->setAttribute('data-cms-lesson-id',$lessonId);$documentChanged=true;}
            }elseif(in_array($key,['caderno-capa','caderno-indice'],true)){
                if($node->hasAttribute('data-cms-availability')){$node->removeAttribute('data-cms-availability');$documentChanged=true;}
                if($node->hasAttribute('data-cms-lesson-id')){$node->removeAttribute('data-cms-lesson-id');$documentChanged=true;}
            }
        }
        if($documentChanged){
            $root=$dom->getElementById('material-lessons-root');$newHtml='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$newHtml.=$dom->saveHTML($child);
            $newSlots=[];if(preg_match_all('~data-private-media-slot=["\']([^"\']+)["\']~i',$newHtml,$newSlotMatches))$newSlots=$newSlotMatches[1];
            if($slotKeys!==$newSlots)throw new RuntimeException('material_lesson_assignment_changed_media_slots');
            $doc['html']=$newHtml;$updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$changed=true;
        }
        libxml_clear_errors();libxml_use_internal_errors($previous);
    }
    if(!$changed)return;

    $now=gmdate('c');$revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;$sets=[];$args=[];
    foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
    $sets[]='draft_revision=?';$args[]=$revision;$sets[]='published_revision=?';$args[]=$revision;$sets[]='draft_updated_at=?';$args[]=$now;$sets[]='published_at=?';$args[]=$now;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$page['id'];
    $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
