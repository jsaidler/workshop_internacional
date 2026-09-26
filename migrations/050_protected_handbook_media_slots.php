<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasMedia=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='student_private_media'")->fetchColumn();
    if($hasMedia){
        $columns=$db->query('PRAGMA table_info(student_private_media)')->fetchAll(PDO::FETCH_ASSOC);$names=array_map(static fn(array $row):string=>(string)$row['name'],$columns);
        if(!in_array('slot_key',$names,true))$db->exec('ALTER TABLE student_private_media ADD COLUMN slot_key TEXT');
        $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_student_private_media_page_slot ON student_private_media(page_id,slot_key) WHERE slot_key IS NOT NULL AND slot_key!=''");
    }
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();if(!$hasPages)return;
    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");$q->execute();$page=$q->fetch(PDO::FETCH_ASSOC)?:null;if(!$page)return;
    $slots=[
        'caderno-03-exposicao'=>['energia-cena','Infográfico: distribuição de energia na cena'],
        'caderno-04-reciprocidade'=>['reciprocidade','Infográfico: baixa energia e falha de reciprocidade'],
        'caderno-06-imagem-latente'=>['imagem-latente','Infográfico: da imagem latente à prata metálica'],
    ];
    $changed=false;$updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');if($raw==='')continue;$doc=json_decode($raw,true);if(!is_array($doc)||!is_string($doc['html']??null))continue;$html=(string)$doc['html'];
        $before=$html;
        $html=preg_replace('~<style\b[^>]*id=["\']positive-handbook-style["\'][^>]*>.*?</style>~is','',$html)??$html;
        $html=preg_replace('~<div\b[^>]*class=["\'][^"\']*diagram[^"\']*["\'][^>]*>\s*<svg\b.*?</svg>\s*</div>~is','',$html)??$html;
        $html=preg_replace('~<div\b[^>]*class=["\'][^"\']*cover-visual[^"\']*["\'][^>]*>.*?</div>~is','',$html)??$html;
        $html=preg_replace('/\sstyle=("[^"]*"|\'[^\']*\')/i','',$html)??$html;
        foreach($slots as $sectionKey=>[$slotKey,$alt]){
            if(str_contains($html,'data-private-media-slot="'.$slotKey.'"'))continue;
            $pattern='~(<section\b[^>]*data-cms-section=["\']'.preg_quote($sectionKey,'~').'["\'][^>]*>)(.*?)(</section>)~is';
            $html=preg_replace_callback($pattern,static fn(array $m):string=>$m[1].$m[2].'<figure class="media-figure" data-private-media-slot="'.$slotKey.'" data-private-media-alt="'.$alt.'"></figure>'.$m[3],$html,1)??$html;
        }
        if(!str_contains($html,'data-private-media-slot="fluxo-positivo"')){
            $html=preg_replace_callback('~(<section\b[^>]*>)(.*?Positivo\?.*?Negativo\?.*?)(</section>)~is',static fn(array $m):string=>$m[1].$m[2].'<figure class="media-figure" data-private-media-slot="fluxo-positivo" data-private-media-alt="Infográfico: fluxo do positivo direto em filme"></figure>'.$m[3],$html,1)??$html;
        }
        if($html!==$before){$doc['html']=$html;$updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$changed=true;}
    }
    if(!$changed)return;$now=gmdate('c');$revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;$sets=[];$args=[];foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}$sets[]='draft_revision=?';$args[]=$revision;$sets[]='published_revision=?';$args[]=$revision;$sets[]='draft_updated_at=?';$args[]=$now;$sets[]='published_at=?';$args[]=$now;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$page['id'];$db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
