<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;

    $appendClasses=static function(string $tag,string $classes): string {
        return preg_replace_callback(
            '~\bclass=["\']([^"\']*)["\']~i',
            static function(array $m)use($classes):string{
                $existing=preg_split('/\s+/',trim((string)$m[1]))?:[];
                foreach(preg_split('/\s+/',trim($classes))?:[] as $class){
                    if($class!==''&&!in_array($class,$existing,true))$existing[]=$class;
                }
                return 'class="'.implode(' ',$existing).'"';
            },
            $tag,
            1
        )??$tag;
    };

    $addGridClassInSection=static function(string $html,string $sectionKey,string $classes)use($appendClasses): string {
        $pattern='~(<section\b[^>]*data-cms-section=["\']'.preg_quote($sectionKey,'~').'["\'][^>]*>)(.*?)(</section>)~si';
        return preg_replace_callback($pattern,static function(array $m)use($classes,$appendClasses):string{
            $body=(string)$m[2];
            $body=preg_replace_callback(
                '~<div\b[^>]*\bclass=["\'][^"\']*\bformat-grid\b[^"\']*["\'][^>]*>~i',
                static fn(array $g):string=>$appendClasses((string)$g[0],$classes),
                $body,
                1
            )??$body;
            return (string)$m[1].$body.(string)$m[3];
        },$html,1)??$html;
    };

    $addNoteClass=static function(string $html,string $text,string $class): string {
        $quoted=preg_quote($text,'~');
        return preg_replace_callback(
            '~<p\b[^>]*\bclass=["\']([^"\']*\btechnical-note\b[^"\']*)["\'][^>]*data-cms-editable[^>]*>'.$quoted.'</p>~u',
            static function(array $m)use($class):string{
                $tag=(string)$m[0];
                return preg_replace_callback(
                    '~\bclass=["\']([^"\']*)["\']~i',
                    static function(array $c)use($class):string{
                        $existing=preg_split('/\s+/',trim((string)$c[1]))?:[];
                        if(!in_array($class,$existing,true))$existing[]=$class;
                        return 'class="'.implode(' ',$existing).'"';
                    },
                    $tag,
                    1
                )??$tag;
            },
            $html,
            1
        )??$html;
    };

    $makeEiComparison=static function(string $html): string {
        $lead='Na segunda aula fizemos duas fotografias em condições diferentes e o processo completo até o positivo.';
        $first='A primeira foi exposta em EI 200 e revelada com 10 ml de Parodinal + 550 ml de água, durante 7 minutos, a 26 °C e com agitação leve.';
        $second='Na segunda passamos para EI 400 e 20 ml de Parodinal + 550 ml de água, mantendo os mesmos 7 minutos, 26 °C e a mesma agitação.';
        $sectionPattern='~(<section\b[^>]*data-cms-section=["\']caderno-19-duas-chapas["\'][^>]*>)(.*?)(</section>)~si';
        return preg_replace_callback($sectionPattern,static function(array $m)use($lead,$first,$second):string{
            $body=(string)$m[2];
            if(str_contains($body,'study-comparison'))return (string)$m[1].$body.(string)$m[3];
            $source='<div class="statement-copy"><p data-cms-editable>'.$lead.'</p><p data-cms-editable>'.$first.'</p><p data-cms-editable>'.$second.'</p></div>';
            if(!str_contains($body,$source))return (string)$m[1].$body.(string)$m[3];
            $replacement='<div class="statement-copy"><p data-cms-editable>'.$lead.'</p></div>'
                .'<div class="format-grid study-comparison">'
                .'<div class="format-card"><p data-cms-editable>'.$first.'</p></div>'
                .'<div class="format-card"><p data-cms-editable>'.$second.'</p></div>'
                .'</div>';
            return (string)$m[1].str_replace($source,$replacement,$body).(string)$m[3];
        },$html,1)??$html;
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

        /* Existing reference groups receive different visual roles. No source
           value is rewritten; only the representation changes. */
        $html=$addGridClassInSection($html,'caderno-04-energia','study-data-strip');
        $html=$addGridClassInSection($html,'caderno-05-reciprocidade','study-data-strip study-reciprocity-strip');
        $html=$addGridClassInSection($html,'caderno-12-parodinal','study-compact-values');
        $html=$makeEiComparison($html);
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
