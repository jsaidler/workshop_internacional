<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;
    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();$page=$q->fetch(PDO::FETCH_ASSOC)?:null;if(!$page)return;

    $addClass=static function(string $tag,string $class): string {
        if(preg_match('~\bclass=["\']([^"\']*)["\']~i',$tag,$m)){
            $classes=preg_split('/\s+/',trim((string)$m[1]))?:[];
            if(in_array($class,$classes,true))return $tag;
            return preg_replace('~\bclass=["\'][^"\']*["\']~i','class="'.trim((string)$m[1].' '.$class).'"',$tag,1)??$tag;
        }
        return preg_replace('~<section\b~i','<section class="'.$class.'"',$tag,1)??$tag;
    };

    $changed=false;$updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');if($raw==='')continue;
        $doc=json_decode($raw,true);if(!is_array($doc)||!is_string($doc['html']??null))continue;
        $html=(string)$doc['html'];$before=$html;

        $html=str_replace(['editorial-cover','editorial-index','editorial-chapter','editorial-unit'],['','','',''],$html);
        $html=preg_replace_callback(
            '~<section\b[^>]*data-cms-section=["\']([^"\']+)["\'][^>]*>~i',
            static function(array $m)use($addClass):string{
                $key=(string)$m[1];$tag=(string)$m[0];
                if($key==='caderno-capa')return $addClass($tag,'study-cover');
                if($key==='caderno-indice')return $addClass($tag,'study-index');
                if(in_array($key,['caderno-aula-1','caderno-aula-2','caderno-aula-3'],true))return $addClass($tag,'study-chapter');
                if(preg_match('/^caderno-\d{2}-/',$key))return $addClass($tag,'study-unit');
                return $tag;
            },$html
        )??$html;
        if(!str_contains($html,'class="study-material"'))$html='<div class="study-material">'.$html.'</div>';

        $html=str_replace('Três aulas, um único processo','Aulas',$html);
        $html=str_replace('<p data-cms-editable>O conteúdo é liberado por aula. As seções abaixo permanecem editáveis individualmente no CMS.</p>','',$html);
        $html=str_replace('<p data-cms-editable>O filme de raio-X, a distribuição de energia na cena, a construção do positivo, a reciprocidade e o Índice de Exposição.</p>','',$html);
        $html=str_replace('<p data-cms-editable>O que a exposição registra, como o revelador transforma essa informação e como as rotas de branqueamento conduzem ao positivo.</p>','',$html);
        $html=str_replace('<p data-cms-editable>Os próprios e-mails já definem como observar uma chapa: preservar separação entre valores, registrar as condições e alterar conscientemente uma variável de cada vez.</p>','',$html);

        $html=str_replace('<p class="section-label" data-cms-editable>O filme</p>','<p class="section-label" data-cms-editable>Filmes de raio-X</p>',$html);
        $html=str_replace('<h2 data-cms-editable>Fuji Super HR-U</h2>','<h2 data-cms-editable>ALGUMAS COISAS IMPORTANTES SOBRE O FILME DE RAIO-X</h2>',$html);
        $html=str_replace('<h2 data-cms-editable>Pretos absolutos. Altas luzes na transparência da base. E fotografia entre os dois extremos.</h2>','',$html);
        $html=str_replace('<h2 data-cms-editable>A câmera fará uma única exposição para uma cena inteira que contém quantidades muito diferentes de luz.</h2>','<h2 data-cms-editable>EXPOSIÇÃO E ENERGIA</h2>',$html);
        $html=str_replace('<h2 data-cms-editable>Quantidade de luz e tempo deixam de ser perfeitamente intercambiáveis nas exposições longas.</h2>','<h2 data-cms-editable>FALHA DE RECIPROCIDADE</h2>',$html);
        $html=str_replace('<h2 data-cms-editable>O filme não muda. A exposição muda.</h2>','<h2 data-cms-editable>EI</h2>',$html);
        $html=str_replace('<p class="section-label" data-cms-editable>Luz de segurança e registro</p>','<p class="section-label" data-cms-editable>ALGUMAS DICAS PARA OS TESTES</p>',$html);

        $exactIntro='A ideia não é transformar isso numa bula. Principalmente porque, como vimos na aula, boa parte do processo depende da relação entre exposição, revelação, temperatura, diluição e movimento. Uma receita pode ser um excelente ponto de partida; dificilmente será a resposta para todas as fotografias.';
        $html=preg_replace(
            '~(<section\b[^>]*data-cms-section=["\']caderno-01-ponto-partida["\'][^>]*>).*?(</section>)~si',
            '$1<div class="statement-copy"><p data-cms-editable>'.htmlspecialchars($exactIntro,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'</p></div>$2',
            $html,1
        )??$html;

        $html=preg_replace('~<p class="technical-note" data-cms-editable>Registro preservado literalmente do segundo e-mail:.*?</p>~si','',$html)??$html;
        $html=str_replace('<div><h3 data-cms-editable>Possível condição de referência</h3></div>','<div><h3 data-cms-editable>Para os próximos testes, considero mais útil estabelecer primeiro uma condição de referência:</h3></div>',$html);

        $eiAnchor='<p data-cms-editable>Passar de EI 200 para EI 400 retira uma parada de exposição. A segunda chapa recebeu metade da luz da primeira.</p>';
        if(str_contains($html,$eiAnchor)&&!str_contains($html,'<p data-cms-editable>O filme não muda. A exposição muda.</p>'.$eiAnchor)){
            $html=str_replace($eiAnchor,'<p data-cms-editable>O filme não muda. A exposição muda.</p>'.$eiAnchor,$html);
        }

        $html=preg_replace('/class="\s+/','class="',$html)??$html;
        $html=preg_replace('/\s{2,}/',' ',$html)??$html;

        if($html!==$before){$doc['html']=$html;$updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$changed=true;}
    }
    if(!$changed)return;
    $now=gmdate('c');$revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $sets=[];$args=[];foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
    $sets[]='draft_revision=?';$args[]=$revision;$sets[]='published_revision=?';$args[]=$revision;$sets[]='draft_updated_at=?';$args[]=$now;$sets[]='published_at=?';$args[]=$now;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$page['id'];
    $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
