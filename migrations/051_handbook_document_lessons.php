<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;

    $lessonIndex=<<<'HTML'
<nav class="cms-document-index" aria-label="Aulas do material">
  <a href="#caderno-aula-1"><span>Aula 01</span><strong>Filme e exposição</strong></a>
  <a href="#caderno-aula-2"><span>Aula 02</span><strong>Processos químicos para positivos</strong></a>
  <a href="#caderno-aula-3"><span>Aula 03</span><strong>Revisão de resultados</strong></a>
</nav>
HTML;

    $lesson1=<<<'HTML'
<section id="caderno-aula-1" class="cms-lesson-divider" data-cms-section="caderno-aula-1" data-cms-section-name="Aula 1 — Filme e exposição">
  <p class="section-label" data-cms-editable>Aula 01</p>
  <div class="cms-lesson-heading"><h2 data-cms-editable>Filme e exposição</h2></div>
</section>
HTML;

    $lesson2=<<<'HTML'
<section id="caderno-aula-2" class="cms-lesson-divider" data-cms-section="caderno-aula-2" data-cms-section-name="Aula 2 — Processos químicos para positivos">
  <p class="section-label" data-cms-editable>Aula 02</p>
  <div class="cms-lesson-heading"><h2 data-cms-editable>Processos químicos para positivos</h2></div>
</section>
HTML;

    $lesson3=<<<'HTML'
<section id="caderno-aula-3" class="cms-lesson-divider" data-cms-section="caderno-aula-3" data-cms-section-name="Aula 3 — Revisão de resultados">
  <p class="section-label" data-cms-editable>Aula 03</p>
  <div class="cms-lesson-heading"><h2 data-cms-editable>Revisão de resultados</h2></div>
</section>
HTML;

    $changed=false;$updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');
        if($raw==='')continue;
        $doc=json_decode($raw,true);
        if(!is_array($doc)||!is_string($doc['html']??null))continue;
        $html=(string)$doc['html'];$before=$html;

        $html=str_replace('<div class="positive-handbook">','<article class="cms-document">',$html);
        $html=str_replace('class="sheet cover"','class="cms-document-page cms-document-cover"',$html);
        $html=str_replace('class="sheet final"','class="cms-document-page cms-document-final"',$html);
        $html=str_replace('class="sheet"','class="cms-document-page"',$html);

        if(str_contains($html,'<article class="cms-document">')){
            $html=preg_replace('~</div>\s*$~','</article>',$html,1)??$html;
        }

        if(!str_contains($html,'class="cms-document-index"')){
            $html=preg_replace('~(</section>\s*)(?=<section\b[^>]*data-cms-section=["\']caderno-01-principio["\'])~is','$1'.$lessonIndex."\n",$html,1)??$html;
        }
        if(!str_contains($html,'data-cms-section="caderno-aula-1"')){
            $html=preg_replace('~(?=<section\b[^>]*data-cms-section=["\']caderno-01-principio["\'])~is',$lesson1."\n",$html,1)??$html;
        }
        if(!str_contains($html,'data-cms-section="caderno-aula-2"')){
            $html=preg_replace('~(?=<section\b[^>]*data-cms-section=["\']caderno-06-imagem-latente["\'])~is',$lesson2."\n",$html,1)??$html;
        }
        if(!str_contains($html,'data-cms-section="caderno-aula-3"')){
            $html=preg_replace('~\s*</article>\s*$~',"\n".$lesson3."\n</article>",$html,1)??$html;
        }

        if($html!==$before){
            $doc['html']=$html;
            $updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $changed=true;
        }
    }

    if($changed){
        $now=gmdate('c');$revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
        $sets=[];$args=[];
        foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
        $sets[]='draft_revision=?';$args[]=$revision;$sets[]='published_revision=?';$args[]=$revision;
        $sets[]='draft_updated_at=?';$args[]=$now;$sets[]='published_at=?';$args[]=$now;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$page['id'];
        $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
    }

    $hasMap=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='course_page_sections'")->fetchColumn();
    $hasLessons=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='course_lessons'")->fetchColumn();
    if(!$hasMap||!$hasLessons)return;
    $lessonQ=$db->prepare('SELECT id,lesson_key FROM course_lessons WHERE activity_id=?');
    $lessonQ->execute([(int)$page['activity_id']]);$lessons=[];
    foreach($lessonQ->fetchAll(PDO::FETCH_ASSOC) as $row)$lessons[(string)$row['lesson_key']]=(int)$row['id'];
    $map=$db->prepare('INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(page_id,section_key) DO UPDATE SET lesson_id=excluded.lesson_id,updated_at=excluded.updated_at');
    $now=gmdate('c');
    foreach(['aula-1'=>'caderno-aula-1','aula-2'=>'caderno-aula-2','aula-3'=>'caderno-aula-3'] as $lessonKey=>$sectionKey){
        if(isset($lessons[$lessonKey]))$map->execute([(int)$page['id'],$sectionKey,$lessons[$lessonKey],$now,$now]);
    }
};
