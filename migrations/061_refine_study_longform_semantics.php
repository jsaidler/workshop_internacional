<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;

    $addGridClassInSection=static function(string $html,string $sectionKey,string $classes): string {
        $pattern='~(<section\b[^>]*data-cms-section=["\']'.preg_quote($sectionKey,'~').'["\'][^>]*>)(.*?)(</section>)~si';
        return preg_replace_callback($pattern,static function(array $m)use($classes):string{
            $body=(string)$m[2];
            $body=preg_replace('~<div class="format-grid">~','<div class="format-grid '.$classes.'">',$body,1)??$body;
            return (string)$m[1].$body.(string)$m[3];
        },$html,1)??$html;
    };

    $addNoteClass=static function(string $html,string $text,string $class): string {
        $from='<p class="technical-note" data-cms-editable>'.$text.'</p>';
        $to='<p class="technical-note '.$class.'" data-cms-editable>'.$text.'</p>';
        return str_replace($from,$to,$html);
    };

    $changed=false;
    $updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');
        if($raw==='')continue;
        $doc=json_decode($raw,true);
        if(!is_array($doc)||!is_string($doc['html']??null))continue;

        $html=(string)$doc['html'];
        $before=$html;

        /* The source contains five non-navigation grids. They do not all carry
           the same semantic weight, so they must not all look like card walls. */
        $html=$addGridClassInSection($html,'caderno-04-energia','study-data-strip');
        $html=$addGridClassInSection($html,'caderno-05-reciprocidade','study-data-strip study-reciprocity-strip');
        $html=$addGridClassInSection($html,'caderno-12-parodinal','study-compact-values');
        $html=$addGridClassInSection($html,'caderno-19-duas-chapas','study-comparison');
        $html=$addGridClassInSection($html,'caderno-20-materiais','study-resource-list');

        /* Preserve every word from the source while distinguishing equation,
           quotation, summary and operational instruction from a generic note. */
        $html=$addNoteClass($html,'De maneira simplificada: mais energia durante a exposição → região mais transparente no positivo. Menos energia durante a exposição → região mais densa no positivo.','study-summary');
        $html=$addNoteClass($html,'Ag⁺ + elétron → Ag⁰','study-equation');
        $html=$addNoteClass($html,'Exponha para as sombras e revele para as luzes.','study-quote');
        $html=$addNoteClass($html,'Misturar nesta ordem: água → vinagre → peróxido de hidrogênio.','study-instruction');
        $html=$addNoteClass($html,'prata metálica → cloreto de prata','study-equation');

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
