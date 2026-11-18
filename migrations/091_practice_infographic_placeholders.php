<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    if(!$tableExists('cms_pages'))return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;
    $pageId=(int)$page['id'];

    $placeholder=static function(string $key,string $label): string {
        return '<figure class="media-figure study-infographic-placeholder" data-study-image-placeholder="'.htmlspecialchars($key,ENT_QUOTES).'">'
            .'<div class="cms-media-placeholder"><span>'.htmlspecialchars($label,ENT_QUOTES).'</span></div>'
            .'</figure>';
    };
    $replaceSection=static function(string $html,string $key,string $replacement): string {
        $pattern="~<section\\b[^>]*data-cms-section=[\"']".preg_quote($key,'~')."[\"'][^>]*>.*?</section>~si";
        return preg_replace($pattern,$replacement,$html,1)??$html;
    };
    $removeSection=static function(string $html,string $key): string {
        $pattern="~\\s*<section\\b[^>]*data-cms-section=[\"']".preg_quote($key,'~')."[\"'][^>]*>.*?</section>\\s*~si";
        return preg_replace($pattern,"\n\n",$html,1)??$html;
    };
    $replaceFigure=static function(string $html,string $slot,string $replacement): string {
        $pattern="~<figure\\b[^>]*data-private-media-slot=[\"']".preg_quote($slot,'~')."[\"'][^>]*>.*?</figure>~si";
        return preg_replace($pattern,$replacement,$html,1)??$html;
    };

    $lessonQ=$db->prepare("SELECT lesson_id FROM course_page_sections WHERE page_id=? AND section_key='caderno-aula-2' LIMIT 1");
    $lessonQ->execute([$pageId]);
    $lessonId=(int)($lessonQ->fetchColumn()?:0);
    if($lessonId<1&&$tableExists('course_material_sections')){
        $lessonQ=$db->prepare("SELECT lesson_id FROM course_material_sections WHERE page_id=? AND section_key='caderno-aula-2' LIMIT 1");
        $lessonQ->execute([$pageId]);
        $lessonId=(int)($lessonQ->fetchColumn()?:0);
    }
    if($lessonId<1)return;

    $exposurePlaceholder=$placeholder('practice-exposure','Infográfico — O que registrar na exposição');
    $processingPlaceholder=$placeholder('practice-processing','Infográfico — Roteiro previsto x processamento realmente realizado');
    $toolInvite='A Área do Aluno também reúne ferramentas complementares de cálculo, consulta e organização do laboratório. Vale conhecê-las e incorporar à rotina apenas o que fizer sentido para a sua prática; elas servem de apoio e não substituem o registro de cada tentativa.';

    $exposure=<<<HTML
<section class="study-unit" data-cms-section="caderno-22-exposicao" data-cms-section-name="Prática · Registrar a exposição" data-cms-availability="lesson" data-cms-lesson-id="$lessonId">
  <p class="section-label" data-cms-editable>Quando você acabou de fotografar</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Registrar a exposição</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Depois de fotografar, registre o que foi realmente usado: filme e lote, EI, diafragma, tempo efetivo, correção de reciprocidade quando houver e as observações de luz que ajudem a reconstruir a decisão.</p>
      <p data-cms-editable>Cálculos e referências ajudam a chegar à exposição. O registro deve guardar o que efetivamente aconteceu na fotografia.</p>
    </div>
  </div>
  $exposurePlaceholder
</section>
HTML;

    $processing=<<<HTML
<section class="study-unit" data-cms-section="caderno-23-processamento" data-cms-section-name="Prática · Registrar o processamento realizado" data-cms-availability="lesson" data-cms-lesson-id="$lessonId">
  <p class="section-label" data-cms-editable>Quando o processamento começa — ou já aconteceu</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Registrar o processamento realizado</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Um roteiro pode orientar o laboratório, mas só o que foi executado entra como fato experimental. Registre revelador, diluição, temperatura, tempo, movimentação, banhos e as alterações feitas durante o processo.</p>
      <p data-cms-editable>Se uma etapa foi interrompida, encurtada, repetida ou modificada, é essa versão que deve permanecer no histórico. O registro pode ser completado durante ou depois do laboratório.</p>
    </div>
  </div>
  $processingPlaceholder
</section>
HTML;

    $changed=false;$updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');
        if($raw==='')continue;
        $doc=json_decode($raw,true);
        if(!is_array($doc)||!is_string($doc['html']??null))continue;
        $html=(string)$doc['html'];$before=$html;

        $html=$replaceFigure($html,'aula2-caderno',$placeholder('practice-flow','Infográfico — Exposição → Processamento → Resultado'));
        $html=$replaceFigure($html,'aula3-caderno',$placeholder('practice-notebook','Infográfico — O Caderno como registro da prática'));
        $html=$replaceSection($html,'caderno-22-exposicao',$exposure);
        $html=$replaceSection($html,'caderno-23-processamento',$processing);
        $html=$replaceFigure($html,'aula3-avaliacao',$placeholder('practice-result','Infográfico — Da tentativa ao resultado avaliado'));
        $html=$replaceFigure($html,'aula3-comparacao',$placeholder('practice-comparison','Infográfico — Comparação descritiva entre duas tentativas'));
        $html=$replaceFigure($html,'aula3-continuidade',$placeholder('practice-continuity','Infográfico — Continuidade da pesquisa e próxima tentativa'));
        $html=$removeSection($html,'caderno-27-ferramentas');

        $pattern="~(<section\\b[^>]*data-cms-section=[\"']caderno-26-continuar[\"'][^>]*>.*?)(</section>)~si";
        $html=preg_replace_callback($pattern,static function(array $m)use($toolInvite):string{
            $body=(string)$m[1];
            if(str_contains($body,'data-study-tools-invite'))return (string)$m[0];
            return $body."\n  <p class=\"technical-note study-summary\" data-study-tools-invite data-cms-editable>".htmlspecialchars($toolInvite,ENT_QUOTES)."</p>\n".(string)$m[2];
        },$html,1)??$html;

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

        if($tableExists('course_page_sections')){
            $db->prepare("DELETE FROM course_page_sections WHERE page_id=? AND section_key='caderno-27-ferramentas'")->execute([$pageId]);
        }
        if($tableExists('course_material_sections')){
            $db->prepare("DELETE FROM course_material_sections WHERE page_id=? AND section_key='caderno-27-ferramentas'")->execute([$pageId]);
        }
        if($tableExists('course_page_media_slots')){
            $slots=['aula2-caderno','aula3-caderno','aula3-exposicao','aula3-processamentos','aula3-processamento-realizado','aula3-avaliacao','aula3-comparacao','aula3-continuidade','aula3-ferramentas'];
            $placeholders=implode(',',array_fill(0,count($slots),'?'));
            $db->prepare("DELETE FROM course_page_media_slots WHERE page_id=? AND slot_key IN ($placeholders)")->execute(array_merge([$pageId],$slots));
        }

        $db->commit();
    }catch(Throwable $e){
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
};
