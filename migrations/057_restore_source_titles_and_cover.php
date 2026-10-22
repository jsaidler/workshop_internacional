<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;
    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();$page=$q->fetch(PDO::FETCH_ASSOC)?:null;if(!$page)return;

    $changed=false;$updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');if($raw==='')continue;
        $doc=json_decode($raw,true);if(!is_array($doc)||!is_string($doc['html']??null))continue;
        $html=(string)$doc['html'];$before=$html;

        $cover=<<<'HTML'
<section class="study-cover" data-layout-background="surface" data-layout-space="xl" data-cms-section="caderno-capa" data-cms-section-name="Capa">
  <p class="section-label" data-cms-editable>Material de estudo</p>
  <h1 data-cms-editable>POSITIVO DIRETO EM FILME DE RAIO-X</h1>
  <p class="study-subtitle" data-cms-editable>Receitas, materiais, exposição e algumas referências para os testes</p>
</section>
HTML;
        $html=preg_replace('~<section\b[^>]*data-cms-section=["\']caderno-capa["\'][^>]*>.*?</section>~si',$cover,$html,1)??$html;

        $replacements=[
            '<p class="section-label" data-cms-editable>O positivo procurado</p>'=>'',
            '<p class="section-label" data-cms-editable>Parodinal · química e receita</p>'=>'<p class="section-label" data-cms-editable>O Parodinal</p>',
            '<p class="section-label" data-cms-editable>Brewed Caffenol · química e receita</p>'=>'<p class="section-label" data-cms-editable>O Caffenol</p>',
            '<p class="section-label" data-cms-editable>Branqueamento · solução peracética</p>'=>'<p class="section-label" data-cms-editable>O branqueamento</p>',
            '<p class="section-label" data-cms-editable>Branqueamento · cloreto férrico + amônia</p>'=>'<p class="section-label" data-cms-editable>O branqueamento</p>',
            '<p class="section-label" data-cms-editable>Duas condições comparadas</p>'=>'',
            '<p class="section-label" data-cms-editable>Como ler os resultados</p>'=>'',
            '<div><h3 data-cms-editable>Concentrado</h3></div>'=>'<div><h3 data-cms-editable>1. PARODINAL</h3></div>',
            '<div><h3 data-cms-editable>Uma das soluções que utilizo</h3></div>'=>'<div><h3 data-cms-editable>3. SOLUÇÃO PERACÉTICA</h3></div>',
        ];
        $html=str_replace(array_keys($replacements),array_values($replacements),$html);

        if($html!==$before){$doc['html']=$html;$updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$changed=true;}
    }
    if(!$changed)return;
    $now=gmdate('c');$revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $sets=[];$args=[];foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
    $sets[]='draft_revision=?';$args[]=$revision;$sets[]='published_revision=?';$args[]=$revision;$sets[]='draft_updated_at=?';$args[]=$now;$sets[]='published_at=?';$args[]=$now;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$page['id'];
    $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
