<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    if(!$tableExists('cms_pages'))return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;

    $unitKeys=[
        'caderno-20a-agora-e-a-vez',
        'caderno-20b-antes-terceiro-encontro',
        'caderno-21-fluxo-pesquisa',
        'caderno-22-exposicao',
        'caderno-23-processamento',
        'caderno-24-resultado-avaliacao',
        'caderno-25-comparacao',
        'caderno-26-continuar',
        'caderno-27-ferramentas',
        'caderno-28-rotina',
    ];

    $replaceOpeningTag=static function(string $html,string $key,string $classes): string {
        $pattern='~<section\\b[^>]*data-cms-section=["\\']'.preg_quote($key,'~').'["\\'][^>]*>~i';
        return preg_replace_callback($pattern,static function(array $m)use($classes):string{
            $tag=(string)$m[0];
            if(preg_match('~\\bclass=["\\'][^"\\']*["\\']~i',$tag)){
                $tag=preg_replace('~\\bclass=["\\'][^"\\']*["\\']~i','class="'.$classes.'"',$tag,1)??$tag;
            }else{
                $tag=preg_replace('~<section\\b~i','<section class="'.$classes.'"',$tag,1)??$tag;
            }
            $tag=preg_replace('~\\sdata-layout-background=["\\'][^"\\']*["\\']~i','',$tag)??$tag;
            $tag=preg_replace('~\\sdata-layout-space=["\\'][^"\\']*["\\']~i','',$tag)??$tag;
            return $tag;
        },$html,1)??$html;
    };

    $replaceHeading=static function(string $html,string $old,string $new): string {
        return str_replace('<h2 data-cms-editable>'.$old.'</h2>','<h2 data-cms-editable>'.$new.'</h2>',$html);
    };

    $rebuildTools=static function(string $html): string {
        $pattern='~<section\\b[^>]*data-cms-section=["\\']caderno-27-ferramentas["\\'][^>]*>(.*?)</section>~si';
        return preg_replace_callback($pattern,static function(array $m):string{
            $section=(string)$m[0];
            if(!preg_match('~<div\\b[^>]*class=["\\'][^"\\']*format-grid[^"\\']*["\\'][^>]*>.*?</div>~si',$section,$gridMatch))return $section;
            if(!preg_match('~<figure\\b[^>]*data-private-media-slot=["\\']aula3-ferramentas["\\'][^>]*>.*?</figure>~si',$section,$figureMatch))return $section;
            $grid=(string)$gridMatch[0];
            $figure=(string)$figureMatch[0];
            preg_match('~data-cms-availability=["\\']lesson["\\'][^>]*data-cms-lesson-id=["\\'](\\d+)["\\']~i',$section,$lessonMatch);
            $availability=isset($lessonMatch[1])?' data-cms-availability="lesson" data-cms-lesson-id="'.$lessonMatch[1].'"':'';
            return '<section class="study-unit" data-cms-section="caderno-27-ferramentas" data-cms-section-name="Aula 3 · Ferramentas de apoio"'.$availability.'>\n'
                .'  <p class="section-label" data-cms-editable>Ferramentas de apoio</p>\n'
                .'  <div class="statement-grid">\n'
                .'    <div><h2 data-cms-editable>Ferramentas da área do aluno</h2></div>\n'
                .'    <div class="statement-copy">\n'
                .'      <p data-cms-editable>O botão Ferramentas reúne cálculos e referências de laboratório. Eles ajudam a organizar o trabalho e a refazer contas, mas não substituem a leitura da cena, a escolha da exposição nem a interpretação do resultado.</p>\n'
                .'      <p data-cms-editable>Os recursos abaixo funcionam como apoio ao Caderno. O Caderno continua sendo o lugar onde fica registrado o que efetivamente aconteceu em cada tentativa.</p>\n'
                .'    </div>\n'
                .'  </div>\n  '.$grid.'\n  '.$figure.'\n'
                .'  <p class="technical-note" data-cms-editable>Use as ferramentas para calcular e organizar. Use o Caderno para registrar o que aconteceu.</p>\n'
                .'</section>';
        },$html,1)??$html;
    };

    $changed=false;$updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');
        if($raw==='')continue;
        $doc=json_decode($raw,true);
        if(!is_array($doc)||!is_string($doc['html']??null))continue;
        $html=(string)$doc['html'];$before=$html;

        $html=$replaceOpeningTag($html,'caderno-aula-3','format study-chapter');
        foreach($unitKeys as $key)$html=$replaceOpeningTag($html,$key,'study-unit');

        $headingMap=[
            'Depois de acompanhar o processo completo, o próximo passo é fazer as próprias fotografias e produzir os próprios resultados.'=>'Registro da prática entre as aulas',
            'Não é preciso chegar com uma fotografia “certa”. Precisamos chegar com resultados que possamos observar, reconstruir e discutir.'=>'Preparação para o terceiro encontro',
            'O registro só vale a pena quando ajuda a entender o que aconteceu e a escolher conscientemente o que mudar depois.'=>'O Caderno como registro da pesquisa',
            'Registre a exposição que foi feita, não apenas a conta que levou até ela.'=>'Registrar a exposição',
            'Roteiro associado e processamento realizado são duas coisas diferentes.'=>'Registrar o processamento realizado',
            'O resultado precisa permanecer ligado às condições que o produziram.'=>'Resultado e avaliação',
            'Comparar serve para enxergar diferenças registradas. Não para inventar uma causa que o experimento não demonstrou.'=>'Comparar duas tentativas',
            'Use uma tentativa anterior como ponto de partida, mas declare o que pretende investigar antes de produzir o próximo resultado.'=>'Criar a próxima tentativa',
            'O objetivo não é preencher campos. É chegar à próxima fotografia sabendo mais do que você sabia antes da anterior.'=>'Depois de cada sessão',
        ];
        foreach($headingMap as $old=>$new)$html=$replaceHeading($html,$old,$new);

        $html=$rebuildTools($html);

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
