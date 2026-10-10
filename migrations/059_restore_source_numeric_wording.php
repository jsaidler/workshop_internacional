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
        $replace=[
            '<span class="number">−1 EV</span><h3 data-cms-editable>1/2 da luz</h3>'=>'<span class="number">1 EV abaixo</span><h3 data-cms-editable>1/2 da luz</h3>',
            '<span class="number">−3 EV</span><h3 data-cms-editable>1/8 da luz</h3>'=>'<span class="number">3 EV abaixo</span><h3 data-cms-editable>1/8 da luz</h3>',
            '<span class="number">−5 EV</span><h3 data-cms-editable>1/32 da luz</h3>'=>'<span class="number">5 EV abaixo</span><h3 data-cms-editable>1/32 da luz</h3>',
            '<span class="number">EV 16</span><h3 data-cms-editable>1 s → 1 s</h3>'=>'<span class="number">EV 16</span><h3 data-cms-editable>1 segundo → 1 segundo</h3>',
            '<span class="number">EV 13</span><h3 data-cms-editable>8 s → aproximadamente 18 s</h3>'=>'<span class="number">EV 13</span><h3 data-cms-editable>8 segundos → aproximadamente 18 segundos</h3>',
            '<span class="number">EV 10</span><h3 data-cms-editable>1 min → aproximadamente 5 min</h3>'=>'<span class="number">EV 10</span><h3 data-cms-editable>1 minuto → aproximadamente 5 minutos</h3>',
            '<span class="number">EV 7</span><h3 data-cms-editable>8 min 30 s → aproximadamente 1 h 34 min</h3>'=>'<span class="number">EV 7</span><h3 data-cms-editable>8 minutos e meio → aproximadamente 1 hora e 34 minutos</h3>',
            '<span class="number">EV 4</span><h3 data-cms-editable>1 h 8 min → aproximadamente 28 h</h3>'=>'<span class="number">EV 4</span><h3 data-cms-editable>1 hora e 8 minutos → aproximadamente 28 horas</h3>',
        ];
        $html=str_replace(array_keys($replace),array_values($replace),$html);
        if($html!==$before){$doc['html']=$html;$updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$changed=true;}
    }
    if(!$changed)return;
    $now=gmdate('c');$revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $sets=[];$args=[];foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
    $sets[]='draft_revision=?';$args[]=$revision;$sets[]='published_revision=?';$args[]=$revision;$sets[]='draft_updated_at=?';$args[]=$now;$sets[]='published_at=?';$args[]=$now;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$page['id'];
    $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
