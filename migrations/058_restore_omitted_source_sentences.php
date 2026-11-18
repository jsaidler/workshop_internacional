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
            '<p class="section-label" data-cms-editable>Duas coisas diferentes, um mesmo problema de energia</p>',
            '<h2 data-cms-editable>DUAS COISAS DIFERENTES, UM MESMO PROBLEMA DE ENERGIA</h2>',
            $html
        );

        $eiStart='<p data-cms-editable>Até agora usei algumas vezes ISO como referência, mas, no nosso caso, EI é o termo mais adequado.</p>';
        $eiLead='<p data-cms-editable>Isso nos leva a um conceito que eu deveria ter apresentado durante a aula e acabei deixando passar: o EI, ou Índice de Exposição.</p>';
        if(str_contains($html,$eiStart)&&!str_contains($html,$eiLead))$html=str_replace($eiStart,$eiLead.$eiStart,$html);

        $observedAnchor='<p data-cms-editable>Isso importa especialmente nas regiões que já recebem pouca energia, porque são elas as primeiras a perder separação.</p>';
        $observed='<p data-cms-editable>Foi justamente o que observamos.</p>';
        if(str_contains($html,$observedAnchor)&&!str_contains($html,$observedAnchor.$observed))$html=str_replace($observedAnchor,$observedAnchor.$observed,$html);

        $latentStart='<p data-cms-editable>Os haletos de prata são cristais formados por prata (Ag) e haletos: cloretos (Cl), brometos (Br) e iodetos (I), organizados numa estrutura estável.</p>';
        $latentLead='<p data-cms-editable>Para entender por que isso acontece, precisamos voltar um pouco e olhar o que estamos realmente revelando.</p>';
        if(str_contains($html,$latentStart)&&!str_contains($html,$latentLead))$html=str_replace($latentStart,$latentLead.$latentStart,$html);

        $developerStart='<p data-cms-editable>Em uma definição prática, um revelador é uma solução formada por três componentes básicos: um agente revelador, um alcalinizante e um meio aquoso.</p>';
        $developerLead='<p data-cms-editable>Agora podemos olhar para os químicos com mais clareza.</p>';
        if(str_contains($html,$developerStart)&&!str_contains($html,$developerLead))$html=str_replace($developerStart,$developerLead.$developerStart,$html);

        $paramsStart='<p data-cms-editable>Trabalhamos com quatro parâmetros que interferem diretamente na revelação: concentração, temperatura, tempo e agitação.</p>';
        $paramsLead='<p data-cms-editable>Agora podemos voltar às duas fotografias da aula.</p>';
        if(str_contains($html,$paramsStart)&&!str_contains($html,$paramsLead))$html=str_replace($paramsStart,$paramsLead.$paramsStart,$html);

        $gridPattern='~<div class="format-grid">\s*<div class="format-card"><span class="number">EI 200</span>.*?</div>\s*<div class="format-card"><span class="number">EI 400</span>.*?</div>\s*</div>~si';
        $conditions='<div class="statement-copy"><p data-cms-editable>Na segunda aula fizemos duas fotografias em condições diferentes e o processo completo até o positivo.</p><p data-cms-editable>A primeira foi exposta em EI 200 e revelada com 10 ml de Parodinal + 550 ml de água, durante 7 minutos, a 26 °C e com agitação leve.</p><p data-cms-editable>Na segunda passamos para EI 400 e 20 ml de Parodinal + 550 ml de água, mantendo os mesmos 7 minutos, 26 °C e a mesma agitação.</p></div>';
        $sectionPos=strpos($html,'data-cms-section="caderno-19-duas-chapas"');
        if($sectionPos!==false){
            $head=substr($html,0,$sectionPos);$tail=substr($html,$sectionPos);
            $tail=preg_replace($gridPattern,$conditions,$tail,1)??$tail;
            $html=$head.$tail;
        }

        $compareAnchor='<p data-cms-editable>Os 7 minutos permaneceram iguais, mas passamos de 10 para 20 ml de Parodinal.';
        $compareLead='<p data-cms-editable>Foi exatamente o que fizemos nas duas chapas da aula.</p>';
        $pos=strpos($html,$compareAnchor);
        if($pos!==false&&strpos(substr($html,max(0,$pos-180),180),'Foi exatamente o que fizemos nas duas chapas da aula.')===false)$html=substr($html,0,$pos).$compareLead.substr($html,$pos);

        if($html!==$before){$doc['html']=$html;$updates[$column]=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$changed=true;}
    }
    if(!$changed)return;
    $now=gmdate('c');$revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $sets=[];$args=[];foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
    $sets[]='draft_revision=?';$args[]=$revision;$sets[]='published_revision=?';$args[]=$revision;$sets[]='draft_updated_at=?';$args[]=$now;$sets[]='published_at=?';$args[]=$now;$sets[]='updated_at=?';$args[]=$now;$args[]=(int)$page['id'];
    $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
};
