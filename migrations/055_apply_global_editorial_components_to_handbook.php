<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;

    $addClass=static function(string $tag,string $class): string {
        if(preg_match('~\bclass=["\']([^"\']*)["\']~i',$tag,$m)){
            $classes=preg_split('/\s+/',trim((string)$m[1]))?:[];
            if(in_array($class,$classes,true))return $tag;
            $replacement='class="'.trim((string)$m[1].' '.$class).'"';
            return preg_replace('~\bclass=["\'][^"\']*["\']~i',$replacement,$tag,1)??$tag;
        }
        return preg_replace('~<section\b~i','<section class="'.$class.'"',$tag,1)??$tag;
    };

    $changed=false;$updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');if($raw==='')continue;
        $doc=json_decode($raw,true);if(!is_array($doc)||!is_string($doc['html']??null))continue;
        $html=(string)$doc['html'];$before=$html;

        $html=preg_replace_callback(
            '~<section\b[^>]*data-cms-section=["\']([^"\']+)["\'][^>]*>~i',
            static function(array $m)use($addClass):string{
                $section=(string)$m[1];$tag=(string)$m[0];
                if($section==='caderno-capa')return $addClass($tag,'editorial-cover');
                if($section==='caderno-indice')return $addClass($tag,'editorial-index');
                if(in_array($section,['caderno-aula-1','caderno-aula-2','caderno-aula-3'],true))return $addClass($tag,'editorial-chapter');
                if(preg_match('/^caderno-\d{2}-/',$section))return $addClass($tag,'editorial-unit');
                return $tag;
            },
            $html
        )??$html;

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
    $sets[]='draft_revision=?';$args[]=$revision;
    $sets[]='published_revision=?';$args[]=$revision;
    $sets[]='draft_updated_at=?';$args[]=$now;
    $sets[]='published_at=?';$args[]=$now;
    $sets[]='updated_at=?';$args[]=$now;
    $args[]=(int)$page['id'];
    $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
