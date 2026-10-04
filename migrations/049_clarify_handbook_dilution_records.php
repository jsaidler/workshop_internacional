<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;
    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();$page=$q->fetch(PDO::FETCH_ASSOC)?:null;if(!$page)return;
    $now=gmdate('c');$changed=false;$updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');if($raw==='')continue;$doc=json_decode($raw,true);if(!is_array($doc)||!is_string($doc['html']??null))continue;$html=$doc['html'];
        $html=str_replace('<td data-cms-editable>10 ml + água até 550 ml</td>','<td data-cms-editable>10 ml + 550 ml de água</td>',$html,$c1);
        $html=str_replace('<td data-cms-editable>20 ml + água até 550 ml</td>','<td data-cms-editable>20 ml + 550 ml de água</td>',$html,$c2);
        $html=str_replace('10 ml Parodinal · água até 550 ml · 26 °C · 5 min · agitação leve','10 ml Parodinal + 550 ml de água · 26 °C · 5 min · agitação leve',$html,$c3);
        $anchor="</tr></table>\n</section>\n\n<section class=\"sheet\" data-cms-section=\"caderno-06-imagem-latente\"";
        $note="</tr></table>\n  <div class=\"callout\" style=\"margin-top:28px\"><strong data-cms-editable>Nota sobre os registros de diluição.</strong><p data-cms-editable>O resumo de receitas define as diluições como volume final de 550 ml — por exemplo, 15 ml de Parodinal e água até completar 550 ml. O registro do segundo encontro anota literalmente 10 ml ou 20 ml de Parodinal + 550 ml de água. Os dois registros divergem e não são tratados aqui como equivalentes.</p></div>\n</section>\n\n<section class=\"sheet\" data-cms-section=\"caderno-06-imagem-latente\"";
        if(str_contains($html,$anchor)){$html=str_replace($anchor,$note,$html,$c4);}else $c4=0;
        if($c1+$c2+$c3+$c4>0){$doc['html']=$html;$updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$changed=true;}
    }
    if(!$changed)return;$revision=max((int)$page['draft_revision'],(int)($page['published_revision']??0))+1;$sets=[];$args=[];foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}$sets[]='draft_revision=?';$args[]=$revision;$sets[]='published_revision=?';$args[]=$revision;$sets[]='draft_updated_at=?';$args[]=$now;$sets[]='published_at=?';$args[]=$now;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$page['id'];$db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
