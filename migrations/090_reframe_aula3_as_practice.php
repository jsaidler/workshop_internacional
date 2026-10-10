<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    if(!$tableExists('cms_pages')||!$tableExists('course_page_sections'))return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;
    $pageId=(int)$page['id'];

    // A Prática acontece depois da Aula 2 e precisa estar disponível junto com ela.
    // O identificador histórico caderno-aula-3 é preservado para não quebrar links,
    // screenshots, migrations anteriores ou referências internas.
    $lessonQ=$db->prepare("SELECT lesson_id FROM course_page_sections WHERE page_id=? AND section_key='caderno-aula-2' LIMIT 1");
    $lessonQ->execute([$pageId]);
    $practiceLessonId=(int)($lessonQ->fetchColumn()?:0);
    if($practiceLessonId<1&&$tableExists('course_material_sections')){
        $lessonQ=$db->prepare("SELECT lesson_id FROM course_material_sections WHERE page_id=? AND section_key='caderno-aula-2' LIMIT 1");
        $lessonQ->execute([$pageId]);
        $practiceLessonId=(int)($lessonQ->fetchColumn()?:0);
    }
    if($practiceLessonId<1)return;

    $practiceKeys=[
        'caderno-aula-3',
        'caderno-21-fluxo-pesquisa',
        'caderno-22-exposicao',
        'caderno-23-processamento',
        'caderno-24-resultado-avaliacao',
        'caderno-25-comparacao',
        'caderno-26-continuar',
        'caderno-27-ferramentas',
        'caderno-28-rotina',
    ];

    $replaceOpeningLesson=static function(string $html,string $key,int $lessonId): string {
        $pattern="~<section\\b[^>]*data-cms-section=[\"']".preg_quote($key,'~')."[\"'][^>]*>~i";
        return preg_replace_callback($pattern,static function(array $m)use($lessonId):string{
            $tag=(string)$m[0];
            if(preg_match("~\\bdata-cms-availability=[\"'][^\"']*[\"']~i",$tag)){
                $tag=preg_replace("~\\bdata-cms-availability=[\"'][^\"']*[\"']~i",'data-cms-availability="lesson"',$tag,1)??$tag;
            }else{
                $tag=rtrim(substr($tag,0,-1)).' data-cms-availability="lesson">';
            }
            if(preg_match("~\\bdata-cms-lesson-id=[\"'][^\"']*[\"']~i",$tag)){
                $tag=preg_replace("~\\bdata-cms-lesson-id=[\"'][^\"']*[\"']~i",'data-cms-lesson-id="'.$lessonId.'"',$tag,1)??$tag;
            }else{
                $tag=rtrim(substr($tag,0,-1)).' data-cms-lesson-id="'.$lessonId.'">';
            }
            return $tag;
        },$html,1)??$html;
    };

    $replaceSection=static function(string $html,string $key,string $replacement): string {
        $pattern="~<section\\b[^>]*data-cms-section=[\"']".preg_quote($key,'~')."[\"'][^>]*>.*?</section>~si";
        return preg_replace($pattern,$replacement,$html,1)??$html;
    };

    $removeSection=static function(string $html,string $key): string {
        $pattern="~\\s*<section\\b[^>]*data-cms-section=[\"']".preg_quote($key,'~')."[\"'][^>]*>.*?</section>\\s*~si";
        return preg_replace($pattern,"\n\n",$html,1)??$html;
    };

    $chapter=<<<HTML
<section id="caderno-aula-3" class="format study-chapter" data-cms-section="caderno-aula-3" data-cms-section-name="Prática — registro, avaliação e continuidade da pesquisa" data-cms-availability="lesson" data-cms-lesson-id="$practiceLessonId">
  <div class="format-inner">
    <div class="format-heading">
      <div><p class="section-label" data-cms-editable>Entre o segundo e o terceiro encontro</p><h2 data-cms-editable>Prática</h2></div>
      <p data-cms-editable>Depois da segunda aula, use este material enquanto produz suas próprias tentativas. A Área do Aluno organiza o registro da exposição, do processamento realmente realizado e do resultado para que você possa observar, comparar e decidir o que testar em seguida.</p>
    </div>
  </div>
</section>
HTML;

    $closing=<<<HTML
<section class="study-unit" data-cms-section="caderno-28-rotina" data-cms-section-name="Prática · Depois de cada sessão" data-cms-availability="lesson" data-cms-lesson-id="$practiceLessonId">
  <p class="section-label" data-cms-editable>Depois de cada sessão</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Depois de cada sessão</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Antes de encerrar uma tentativa, confira se o Caderno contém a exposição realmente feita, o processamento realmente executado, a imagem do resultado e as observações que você gostaria de encontrar se abrisse esse registro daqui a seis meses.</p>
      <p data-cms-editable>Quando houver dúvida, envie para avaliação. Quando houver duas tentativas relacionadas, compare. Quando surgir uma hipótese para o passo seguinte, crie a próxima variação e escreva a intenção antes de começar.</p>
      <p data-cms-editable>No terceiro encontro, vamos partir das experiências realizadas durante este período. A proposta é observar os resultados, discutir as diferenças entre as tentativas e analisar em conjunto as decisões de exposição e processamento que ficaram registradas. Não é necessário chegar a um resultado “correto”: o importante é registrar com precisão o que foi feito e trazer essas experiências para a discussão.</p>
    </div>
  </div>
</section>
HTML;

    $changed=false;$updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');
        if($raw==='')continue;
        $doc=json_decode($raw,true);
        if(!is_array($doc)||!is_string($doc['html']??null))continue;
        $html=(string)$doc['html'];$before=$html;

        // A ponte permanece no final da Aula 2. A antiga seção autônoma sobre o
        // terceiro encontro é removida: a proposta do encontro passa a fechar Prática.
        $html=$removeSection($html,'caderno-20b-antes-terceiro-encontro');
        $html=$replaceSection($html,'caderno-aula-3',$chapter);
        $html=$replaceSection($html,'caderno-28-rotina',$closing);
        foreach($practiceKeys as $key)$html=$replaceOpeningLesson($html,$key,$practiceLessonId);

        // O índice do material continua com a mesma estrutura visual e o mesmo
        // destino interno, mas apresenta ao aluno o nome pedagógico correto.
        $html=str_replace(
            '<a class="format-card" href="#caderno-aula-3"><span class="number">03</span><h3 data-cms-editable>Área do aluno e continuidade</h3></a>',
            '<a class="format-card" href="#caderno-aula-3"><span class="number">03</span><h3 data-cms-editable>Prática</h3></a>',
            $html
        );

        if($html!==$before){
            $doc['html']=$html;
            $updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $changed=true;
        }
    }

    $now=gmdate('c');
    $db->beginTransaction();
    try{
        if($changed){
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
        }

        $placeholders=implode(',',array_fill(0,count($practiceKeys),'?'));
        $mapArgs=array_merge([$practiceLessonId,$now,$pageId],$practiceKeys);
        $db->prepare("UPDATE course_page_sections SET lesson_id=?,updated_at=? WHERE page_id=? AND section_key IN ($placeholders)")->execute($mapArgs);
        $db->prepare("DELETE FROM course_page_sections WHERE page_id=? AND section_key='caderno-20b-antes-terceiro-encontro'")->execute([$pageId]);

        if($tableExists('course_material_sections')){
            $courseLessonQ=$db->prepare("SELECT course_id,lesson_id FROM course_material_sections WHERE page_id=? AND section_key='caderno-aula-2'");
            $courseLessonQ->execute([$pageId]);
            $courseLessons=[];
            foreach($courseLessonQ->fetchAll(PDO::FETCH_ASSOC) as $row)$courseLessons[(int)$row['course_id']]=(int)$row['lesson_id'];

            $coursesQ=$db->prepare("SELECT DISTINCT course_id FROM course_material_sections WHERE page_id=? AND section_key IN ($placeholders)");
            $coursesQ->execute(array_merge([$pageId],$practiceKeys));
            $updateCourse=$db->prepare("UPDATE course_material_sections SET lesson_id=?,updated_at=? WHERE course_id=? AND page_id=? AND section_key IN ($placeholders)");
            foreach($coursesQ->fetchAll(PDO::FETCH_COLUMN) as $courseIdRaw){
                $courseId=(int)$courseIdRaw;
                $lessonId=(int)($courseLessons[$courseId]??0);
                if($lessonId<1)continue;
                $updateCourse->execute(array_merge([$lessonId,$now,$courseId,$pageId],$practiceKeys));
            }
            $db->prepare("DELETE FROM course_material_sections WHERE page_id=? AND section_key='caderno-20b-antes-terceiro-encontro'")->execute([$pageId]);
        }

        $db->commit();
    }catch(Throwable $e){
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
};
