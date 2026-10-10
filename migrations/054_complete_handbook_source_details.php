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
        $html=str_replace(
            '<p data-cms-editable>Podemos usar EV para visualizar essas diferenças.</p>',
            '<p data-cms-editable>Podemos usar EV para visualizar essas diferenças.</p><p data-cms-editable>A cada EV abaixo temos metade da luz:</p>',
            $html
        );
        $html=str_replace(
            '<p class="technical-note" data-cms-editable>Para o filme que utilizo, trabalho com esta correção: tempo corrigido = tempo calculado elevado a 1,3854. O cálculo é feito em segundos.</p>',
            '<p class="technical-note" data-cms-editable>Para o filme que utilizo, trabalho com esta correção: tempo corrigido = tempo calculado elevado a 1,3854. O cálculo é feito em segundos.</p><p data-cms-editable>Usando uma pinhole f/256 como referência, podemos ver o tamanho da diferença:</p>',
            $html
        );
        if($html!==$before){$doc['html']=$html;$updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$changed=true;}
    }
    if(!$changed)return;
    $now=gmdate('c');$revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $sets=[];$args=[];foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
    $sets[]='draft_revision=?';$args[]=$revision;$sets[]='published_revision=?';$args[]=$revision;$sets[]='draft_updated_at=?';$args[]=$now;$sets[]='published_at=?';$args[]=$now;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$page['id'];
    $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
