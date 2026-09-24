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
<section class="section" aria-label="Aulas do material">
  <dl class="recognition">
    <div><dt>Aula 01</dt><dd><a href="#caderno-aula-1">Filme e exposição</a></dd></div>
    <div><dt>Aula 02</dt><dd><a href="#caderno-aula-2">Processos químicos para positivos</a></dd></div>
    <div><dt>Aula 03</dt><dd><a href="#caderno-aula-3">Revisão de resultados</a></dd></div>
  </dl>
</section>
HTML;

    $lessonBlocks=[
        'caderno-aula-1'=>['Aula 01','Filme e exposição'],
        'caderno-aula-2'=>['Aula 02','Processos químicos para positivos'],
        'caderno-aula-3'=>['Aula 03','Revisão de resultados'],
    ];

    $changed=false;$updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');
        if($raw==='')continue;
        $doc=json_decode($raw,true);
        if(!is_array($doc)||!is_string($doc['html']??null))continue;
        $html=(string)$doc['html'];$before=$html;

        // Remove the pseudo-global document system introduced specifically for this page.
        $html=str_replace('<article class="cms-document">','<div>',$html);
        $html=preg_replace('~</article>\s*$~','</div>',$html,1)??$html;
        $html=preg_replace('~<nav\b[^>]*class=["\']cms-document-index["\'][^>]*>.*?</nav>~is',$lessonIndex,$html,1)??$html;

        foreach($lessonBlocks as $sectionKey=>[$label,$title]){
            $replacement='<section id="'.$sectionKey.'" class="format" data-cms-section="'.$sectionKey.'" data-cms-section-name="'.$label.' — '.$title.'"><div class="format-inner"><div class="format-heading"><div><p class="section-label" data-cms-editable>'.$label.'</p><h2 data-cms-editable>'.$title.'</h2></div></div></div></section>';
            $pattern='~<section\b[^>]*data-cms-section=["\']'.preg_quote($sectionKey,'~').'["\'][^>]*>.*?</section>~is';
            $html=preg_replace($pattern,$replacement,$html,1)??$html;
        }

        $replacements=[
            'class="cms-document-page cms-document-cover"'=>'class="section"',
            'class="cms-document-page cms-document-final"'=>'class="section"',
            'class="cms-document-page"'=>'class="section"',
            'class="cover-copy"'=>'class="statement-grid"',
            'class="eyebrow"'=>'class="section-label"',
            'class="deck"'=>'class="statement-copy"',
            'class="cover-meta"'=>'class="technical-note"',
            'class="section-head"'=>'class="statement-grid"',
            'class="kicker"'=>'class="section-label"',
            'class="lead"'=>'class="statement-copy"',
            'class="body"'=>'class="statement-copy"',
            'class="cols"'=>'class="statement-grid"',
            'class="callout"'=>'class="technical-note"',
            'class="formula"'=>'class="technical-note"',
            'class="notes"'=>'class="format-grid"',
            'class="note"'=>'class="format-card"',
            'class="two-metrics"'=>'class="format-grid"',
            'class="metric"'=>'class="format-card"',
            'class="statement"'=>'class="statement-copy"',
            'class="table"'=>'',
            'class="steps"'=>'',
        ];
        $html=strtr($html,$replacements);
        $html=str_replace(['<h1','</h1>'],['<h2','</h2>'],$html);
        $html=preg_replace('/\sclass=""/','',$html)??$html;

        // No special document-family classes may remain in persisted page content.
        $html=preg_replace('/\sclass="[^"]*\bcms-document[^\"]*"/i','',$html)??$html;
        $html=preg_replace('/\sclass="[^"]*\bcms-lesson[^\"]*"/i','',$html)??$html;

        if($html!==$before){
            $doc['html']=$html;
            $updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $changed=true;
        }
    }

    if(!$changed)return;
    $now=gmdate('c');
    $revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $sets=[];$args=[];
    foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
    $sets[]='draft_revision=?';$args[]=$revision;
    $sets[]='published_revision=?';$args[]=$revision;
    $sets[]='draft_updated_at=?';$args[]=$now;
    $sets[]='published_at=?';$args[]=$now;
    $sets[]='updated_at=?';$args[]=$now;
    $args[]=(int)$page['id'];
    $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
