<?php
declare(strict_types=1);

/**
 * Move the existing list of materials/inputs to the end of Aula 1.
 *
 * The section's editorial body, stable section key and private media (if any)
 * are preserved verbatim. Only its position, lesson metadata and lesson
 * associations change. This migration never rebuilds the handbook from a seed.
 */
return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool =>
        (bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    if(!$tableExists('cms_pages'))return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;
    $pageId=(int)$page['id'];

    // The document-level lesson ID must come from this same material page.
    $lessonIds=[];
    if($tableExists('course_page_sections')){
        $q=$db->prepare("SELECT lesson_id FROM course_page_sections WHERE page_id=? AND section_key='caderno-aula-1'");
        $q->execute([$pageId]);
        foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id)$lessonIds[(int)$id]=true;
    }
    if(!$lessonIds&&$tableExists('course_material_sections')){
        $q=$db->prepare("SELECT DISTINCT lesson_id FROM course_material_sections WHERE page_id=? AND section_key='caderno-aula-1'");
        $q->execute([$pageId]);
        foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id)$lessonIds[(int)$id]=true;
    }
    // Ambiguous or unavailable lesson IDs must not cause a guessed release.
    if(count($lessonIds)!==1)return;
    $aula1Id=(int)array_key_first($lessonIds);
    if($aula1Id<1)return;

    $move=static function(string $raw) use($aula1Id): ?string {
        if(trim($raw)==='')return null;
        $doc=json_decode($raw,true);
        if(!is_array($doc)||!is_string($doc['html']??null))return null;
        $html=$doc['html'];

        $materialPattern='~<section\\b[^>]*\\bdata-cms-section=["\\\']caderno-20-materiais["\\\'][^>]*>.*?</section>~si';
        if(preg_match_all($materialPattern,$html,$all,PREG_OFFSET_CAPTURE)!==1)return null;
        $original=(string)$all[0][0][0];
        $offset=(int)$all[0][0][1];

        $openingPattern='~^<section\\b[^>]*>~si';
        if(!preg_match($openingPattern,$original,$openMatch))return null;
        $opening=(string)$openMatch[0];
        $opening=preg_replace(
            '~\\bdata-cms-section-name=(["\\\']).*?\\1~si',
            'data-cms-section-name="Aula 1 · Onde encontrar os materiais"',
            $opening,1
        )??$opening;
        if(!str_contains($opening,'data-cms-section-name=')){
            $opening=substr($opening,0,-1).' data-cms-section-name="Aula 1 · Onde encontrar os materiais">';
        }
        if(preg_match('~\\bdata-cms-availability=(["\\\']).*?\\1~si',$opening)){
            $opening=preg_replace('~\\bdata-cms-availability=(["\\\']).*?\\1~si','data-cms-availability="lesson"',$opening,1)??$opening;
        }else{
            $opening=substr($opening,0,-1).' data-cms-availability="lesson">';
        }
        if(preg_match('~\\bdata-cms-lesson-id=(["\\\']).*?\\1~si',$opening)){
            $opening=preg_replace('~\\bdata-cms-lesson-id=(["\\\']).*?\\1~si','data-cms-lesson-id="'.$aula1Id.'"',$opening,1)??$opening;
        }else{
            $opening=substr($opening,0,-1).' data-cms-lesson-id="'.$aula1Id.'">';
        }
        $material=$opening.substr($original,strlen($openMatch[0]));

        $remaining=substr_replace($html,'',$offset,strlen($original));
        if(!preg_match('~<section\\b[^>]*\\bdata-cms-section=["\\\']caderno-aula-2["\\\'][^>]*>~si',$remaining,$chapter,PREG_OFFSET_CAPTURE))return null;
        $chapterOffset=(int)$chapter[0][1];

        // The section immediately precedes Aula 2, and thus closes Aula 1.
        $updated=substr_replace($remaining,$material."\n\n",$chapterOffset,0);
        if($updated===$html)return null;
        $doc['html']=$updated;
        return json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    };

    $updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $before=(string)($page[$column]??'');
        $after=$move($before);
        if($after!==null&&$after!==$before)$updates[$column]=$after;
    }
    if(!$updates)return;

    $now=gmdate('c');
    $db->beginTransaction();
    try{
        $revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
        $sets=[];$args=[];
        foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
        $sets[]='draft_revision=?';$args[]=$revision;
        $sets[]='published_revision=?';$args[]=$revision;
        $sets[]='draft_updated_at=?';$args[]=$now;
        $sets[]='published_at=?';$args[]=$now;
        $sets[]='updated_at=?';$args[]=$now;
        $args[]=$pageId;
        $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);

        if($tableExists('course_page_sections')){
            $sql=<<<'SQL'
INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at)
SELECT page_id,'caderno-20-materiais',lesson_id,?,?
FROM course_page_sections WHERE page_id=? AND section_key='caderno-aula-1'
ON CONFLICT(page_id,section_key) DO UPDATE SET lesson_id=excluded.lesson_id,updated_at=excluded.updated_at
SQL;
            $db->prepare($sql)->execute([$now,$now,$pageId]);
        }
        if($tableExists('course_material_sections')){
            $sql=<<<'SQL'
INSERT INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at)
SELECT course_id,page_id,'caderno-20-materiais',lesson_id,?,?
FROM course_material_sections WHERE page_id=? AND section_key='caderno-aula-1'
ON CONFLICT(course_id,page_id,section_key) DO UPDATE SET lesson_id=excluded.lesson_id,updated_at=excluded.updated_at
SQL;
            $db->prepare($sql)->execute([$now,$now,$pageId]);
        }
        $db->commit();
    }catch(Throwable $e){
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
};
