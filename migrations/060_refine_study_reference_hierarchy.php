<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;

    $sourceLead='Para os próximos testes, considero mais útil estabelecer primeiro uma condição de referência:';
    $longHeading='<div><h3 data-cms-editable>'.$sourceLead.'</h3></div>';
    $shortHeading='<div><h3 data-cms-editable>Condição de referência</h3></div>';
    $leadParagraph='<p data-cms-editable>'.$sourceLead.'</p>';

    $changed=false;
    $updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');
        if($raw==='')continue;
        $doc=json_decode($raw,true);
        if(!is_array($doc)||!is_string($doc['html']??null))continue;

        $html=(string)$doc['html'];
        $before=$html;
        $html=str_replace($longHeading,$shortHeading,$html);

        $sectionPos=strpos($html,'data-cms-section="caderno-19-duas-chapas"');
        if($sectionPos===false)$sectionPos=strpos($html,'data-cms-section="caderno-19-duas-chapas"');
        if($sectionPos===false)$sectionPos=strpos($html,'caderno-19-duas-chapas');
        if($sectionPos!==false){
            $head=substr($html,0,$sectionPos);
            $tail=substr($html,$sectionPos);
            $referencePos=strpos($tail,$shortHeading);
            if($referencePos!==false){
                $beforeReference=substr($tail,0,$referencePos);
                $referenceTail=substr($tail,$referencePos);
                if(!str_contains(substr($referenceTail,0,900),$leadParagraph)){
                    $pattern='~(<h3 data-cms-editable>Condição de referência</h3>\s*</div>\s*<div class="statement-copy">\s*)(<p data-cms-editable>EI 200</p>)~u';
                    $referenceTail=preg_replace($pattern,'$1'.$leadParagraph.'$2',$referenceTail,1)??$referenceTail;
                }
                $html=$head.$beforeReference.$referenceTail;
            }
        }

        if($html!==$before){
            $doc['html']=$html;
            $updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $changed=true;
        }
    }
    if(!$changed)return;

    $now=gmdate('c');
    $revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $sets=[];
    $args=[];
    foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
    $sets[]='draft_revision=?';$args[]=$revision;
    $sets[]='published_revision=?';$args[]=$revision;
    $sets[]='draft_updated_at=?';$args[]=$now;
    $sets[]='published_at=?';$args[]=$now;
    $sets[]='updated_at=?';$args[]=$now;
    $args[]=(int)$page['id'];
    $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
