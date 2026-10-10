<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;

    $index=<<<'HTML'
<section class="format" aria-label="Aulas do material">
  <div class="format-inner">
    <div class="format-heading"><div><p class="section-label" data-cms-editable>Material do workshop</p><h2 data-cms-editable>Aulas</h2></div><p data-cms-editable>O conteúdo é liberado por aula. Cada parte abaixo corresponde à sequência do workshop.</p></div>
    <div class="format-grid">
      <a class="format-card" href="#caderno-aula-1"><span class="number">01</span><h3 data-cms-editable>Filme e exposição</h3></a>
      <a class="format-card" href="#caderno-aula-2"><span class="number">02</span><h3 data-cms-editable>Processos químicos para positivos</h3></a>
      <a class="format-card" href="#caderno-aula-3"><span class="number">03</span><h3 data-cms-editable>Revisão de resultados</h3></a>
    </div>
  </div>
</section>
HTML;

    $lesson1=<<<'HTML'
<section id="caderno-aula-1" class="format" data-cms-section="caderno-aula-1" data-cms-section-name="Aula 1 — Filme e exposição">
  <div class="format-inner"><div class="format-heading"><div><p class="section-label" data-cms-editable>Aula 01</p><h2 data-cms-editable>Filme e exposição</h2></div></div></div>
</section>
HTML;
    $lesson2=<<<'HTML'
<section id="caderno-aula-2" class="format" data-cms-section="caderno-aula-2" data-cms-section-name="Aula 2 — Processos químicos para positivos">
  <div class="format-inner"><div class="format-heading"><div><p class="section-label" data-cms-editable>Aula 02</p><h2 data-cms-editable>Processos químicos para positivos</h2></div></div></div>
</section>
HTML;
    $lesson3=<<<'HTML'
<section id="caderno-aula-3" class="format" data-cms-section="caderno-aula-3" data-cms-section-name="Aula 3 — Revisão de resultados">
  <div class="format-inner"><div class="format-heading"><div><p class="section-label" data-cms-editable>Aula 03</p><h2 data-cms-editable>Revisão de resultados</h2></div></div></div>
</section>
HTML;

    $cover=<<<'HTML'
<section class="section" data-layout-background="surface" data-layout-space="xl" data-cms-section="caderno-capa" data-cms-section-name="Capa">
  <p class="section-label" data-cms-editable>João Saidler · fotografia química</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Positivo direto em filme de raio-X</h2></div>
    <div class="statement-copy"><p data-cms-editable>Exposição, energia, revelação e reversão pensadas como partes do mesmo processo.</p><p class="technical-note" data-cms-editable>Caderno de processo · material técnico de referência · Petrópolis · Brasil</p></div>
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

        $html=preg_replace('~<section\b[^>]*data-cms-section=["\']caderno-capa["\'][^>]*>.*?</section>~is',$cover,$html,1)??$html;
        $html=preg_replace('~<nav\s+class=["\']cms-document-index["\'][^>]*>.*?</nav>~is',$index,$html,1)??$html;
        $html=preg_replace('~<section\b[^>]*data-cms-section=["\']caderno-aula-1["\'][^>]*>.*?</section>~is',$lesson1,$html,1)??$html;
        $html=preg_replace('~<section\b[^>]*data-cms-section=["\']caderno-aula-2["\'][^>]*>.*?</section>~is',$lesson2,$html,1)??$html;
        $html=preg_replace('~<section\b[^>]*data-cms-section=["\']caderno-aula-3["\'][^>]*>.*?</section>~is',$lesson3,$html,1)??$html;

        $html=str_replace('<article class="cms-document">','<div>',$html);
        $html=str_replace('</article>','</div>',$html);
        $html=str_replace('class="cms-document-page cms-document-cover"','class="section" data-layout-background="surface" data-layout-space="l"',$html);
        $html=str_replace('class="cms-document-page cms-document-final"','class="section" data-layout-background="inverse" data-layout-space="l"',$html);
        $html=str_replace('class="cms-document-page"','class="section" data-layout-background="surface" data-layout-space="l"',$html);

        $replacements=[
            'class="section-head"'=>'class="statement-grid"',
            'class="kicker"'=>'class="section-label"',
            'class="lead"'=>'class="statement-copy"',
            'class="body"'=>'class="statement-copy"',
            'class="cols"'=>'class="statement-grid"',
            'class="callout"'=>'class="technical-note"',
            'class="formula"'=>'class="technical-note"',
            'class="two-metrics"'=>'class="format-grid"',
            'class="metric"'=>'class="format-card"',
            'class="notes"'=>'class="format-grid"',
            'class="note"'=>'class="format-card"',
            'class="statement"'=>'class="about-lead"',
            'class="table"'=>'',
            'class="diagram"'=>'class="cms-image-generic"',
        ];
        $html=str_replace(array_keys($replacements),array_values($replacements),$html);

        $html=preg_replace_callback('~<div\s+class=["\']steps["\']>(.*?)</div>\s*(?=</section>)~is',static function(array $m): string {
            $i=0;
            $inner=preg_replace_callback('~<div>\s*<p([^>]*)>(.*?)</p>\s*</div>~is',static function(array $row) use (&$i): string {
                $i++;
                return '<div class="process-item"><span>'.str_pad((string)$i,2,'0',STR_PAD_LEFT).'</span><p'.$row[1].'>'.$row[2].'</p></div>';
            },$m[1])??$m[1];
            return '<div class="process-list">'.$inner.'</div>';
        },$html)??$html;
        $html=str_replace('class="steps"','class="process-list"',$html);

        // Remove every class family introduced only for this handbook.
        $html=preg_replace('~\sclass=["\'][^"\']*\bcms-(?:document|lesson)[^"\']*["\']~i','',$html)??$html;

        if($html!==$before){
            $doc['html']=$html;
            $updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $changed=true;
        }
    }

    if(!$changed)return;
    $now=gmdate('c');$revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $sets=[];$args=[];
    foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
    $sets[]='draft_revision=?';$args[]=$revision;$sets[]='published_revision=?';$args[]=$revision;
    $sets[]='draft_updated_at=?';$args[]=$now;$sets[]='published_at=?';$args[]=$now;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$page['id'];
    $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
