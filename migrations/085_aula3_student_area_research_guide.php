<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    if(!$tableExists('cms_pages')||!$tableExists('course_page_sections'))return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;
    $pageId=(int)$page['id'];

    $lessonQ=$db->prepare("SELECT lesson_id FROM course_page_sections WHERE page_id=? AND section_key='caderno-aula-3' LIMIT 1");
    $lessonQ->execute([$pageId]);
    $lessonId=(int)($lessonQ->fetchColumn()?:0);
    if($lessonId<1&&$tableExists('course_material_sections')){
        $lessonQ=$db->prepare("SELECT lesson_id FROM course_material_sections WHERE page_id=? AND section_key='caderno-aula-3' LIMIT 1");
        $lessonQ->execute([$pageId]);
        $lessonId=(int)($lessonQ->fetchColumn()?:0);
    }
    if($lessonId<1)return;

    $availability=' data-cms-availability="lesson" data-cms-lesson-id="'.$lessonId.'"';
    $aula3=<<<HTML
<section id="caderno-aula-3" class="format" data-cms-section="caderno-aula-3" data-cms-section-name="Aula 3 — Área do aluno: revisão, avaliação e continuidade da pesquisa"$availability>
  <div class="format-inner">
    <div class="format-heading">
      <div><p class="section-label" data-cms-editable>Aula 03</p><h2 data-cms-editable>Área do aluno: revisão, avaliação e continuidade da pesquisa</h2></div>
      <p data-cms-editable>A terceira aula fecha o primeiro ciclo do workshop e abre o seguinte: transformar cada tentativa em informação útil para a próxima. A área do aluno reúne o Caderno, a avaliação e as ferramentas de apoio para que exposição, processamento e resultado possam ser recuperados e comparados depois.</p>
    </div>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-21-fluxo-pesquisa" data-cms-section-name="Aula 3 · Da prática ao conhecimento"$availability>
  <p class="section-label" data-cms-editable>Da prática ao conhecimento</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>O registro só vale a pena quando ajuda a entender o que aconteceu e a escolher conscientemente o que mudar depois.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Mais do que decidir se uma fotografia “deu certo”, quero conseguir voltar a uma chapa e localizar o que foi exposto, o que realmente aconteceu no processamento e o que apareceu no resultado.</p>
      <p data-cms-editable>Um resultado ruim, mas bem anotado, continua sendo informação. Uma fotografia que ficou ótima e ninguém sabe exatamente por quê é muito mais difícil de repetir.</p>
      <p data-cms-editable>Na área do aluno, o ciclo fica explícito: exposição → processamento → resultado → avaliação → comparação → próxima variação.</p>
    </div>
  </div>
  <figure data-private-media-slot="aula3-caderno" data-private-media-alt="Tela do Caderno de Processos mostrando os registros do aluno, seus estados e a ação necessária para continuar cada tentativa."></figure>
  <p class="technical-note" data-cms-editable>O Caderno é o registro do experimento. As ferramentas fazem cálculos, guardam referências e organizam o laboratório, mas não transformam uma intenção, um cálculo ou um roteiro associado em fato realizado.</p>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-22-exposicao" data-cms-section-name="Aula 3 · Registrar a exposição"$availability>
  <p class="section-label" data-cms-editable>Quando você acabou de fotografar</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Registre a exposição que foi feita, não apenas a conta que levou até ela.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Abra um registro no Caderno e anote os dados que serão necessários para compreender ou repetir a tentativa: filme e lote, EI usado como referência, diafragma, tempo medido ou calculado, correção de reciprocidade quando houver, condição de luz e a diferença entre as regiões claras e escuras que você quis preservar.</p>
      <p data-cms-editable>Os cálculos de reciprocidade e de exposição equivalente servem para chegar a uma referência. O dado experimental passa a existir quando a exposição efetivamente realizada é registrada no Caderno.</p>
    </div>
  </div>
  <figure data-private-media-slot="aula3-exposicao" data-private-media-alt="Tela de Exposição de um registro do Caderno, com filme, EI, diafragma, tempo calculado e tempo com reciprocidade."></figure>
</section>

<section class="section" data-layout-background="inverse" data-layout-space="l" data-cms-section="caderno-23-processamento" data-cms-section-name="Aula 3 · Roteiro não é execução"$availability>
  <p class="section-label" data-cms-editable>Quando o processamento começa — ou já aconteceu</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Roteiro associado e processamento realizado são duas coisas diferentes.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Em Processamentos você pode guardar sequências que usa no laboratório, partir dos padrões do workshop e ajustar uma cópia para a sua prática. Associar um roteiro ao registro organiza o plano; não afirma que as etapas foram executadas.</p>
      <p data-cms-editable>Durante ou depois do laboratório, registre o que realmente aconteceu: revelador, diluição, temperatura, tempo, movimentação, banhos utilizados e qualquer alteração feita no caminho. Se interrompeu, encurtou, repetiu ou mudou uma etapa, é isso que precisa ficar no histórico.</p>
      <p data-cms-editable>O registro pode ser feito ao vivo, retroativamente ou retomado depois. O importante é não preencher o passado com aquilo que estava apenas previsto.</p>
    </div>
  </div>
  <figure data-private-media-slot="aula3-processamentos" data-private-media-alt="Biblioteca de Processamentos da área do aluno, com roteiros pessoais e padrões do workshop disponíveis para uso ou cópia."></figure>
  <figure data-private-media-slot="aula3-processamento-realizado" data-private-media-alt="Tela de registro de processamento parcialmente realizado, mostrando etapas já registradas e a continuação do histórico real da chapa."></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-24-resultado-avaliacao" data-cms-section-name="Aula 3 · Resultado e avaliação"$availability>
  <p class="section-label" data-cms-editable>Quando a chapa está pronta</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>O resultado precisa permanecer ligado às condições que o produziram.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Anexe uma imagem do resultado e escreva observações que serão úteis quando você voltar ao registro: onde houve separação de valores, onde a densidade fechou, até onde as altas luzes chegaram, manchas, irregularidades ou qualquer comportamento que mereça ser comparado depois.</p>
      <p data-cms-editable>Nos registros do curso, o resultado pode ser enviado para avaliação. Meu retorno fica associado à própria tentativa. Se eu pedir uma revisão, você pode complementar o registro e enviá-lo novamente; quando a avaliação for concluída, o histórico continua disponível junto da chapa.</p>
    </div>
  </div>
  <figure data-private-media-slot="aula3-avaliacao" data-private-media-alt="Tela de Resultado com avaliação concluída e retorno do professor associado ao registro da tentativa."></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-25-comparacao" data-cms-section-name="Aula 3 · Comparar duas tentativas"$availability>
  <p class="section-label" data-cms-editable>Quando você já tem mais de uma tentativa</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Comparar serve para enxergar diferenças registradas. Não para inventar uma causa que o experimento não demonstrou.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Escolha dois registros no Caderno. A comparação coloca lado a lado exposição, processamento e resultado e destaca o que mudou, o que permaneceu igual e o que não foi registrado.</p>
      <p data-cms-editable>Isso não transforma correlação em explicação. Se tempo, temperatura e agitação mudaram ao mesmo tempo, por exemplo, a comparação mostra as três diferenças; ela não escolhe qual delas “causou” o resultado.</p>
      <p data-cms-editable>Quanto mais conscientemente você varia uma condição de cada vez, mais útil se torna o histórico acumulado.</p>
    </div>
  </div>
  <figure data-private-media-slot="aula3-comparacao" data-private-media-alt="Tela de comparação entre dois registros, mostrando diferenças técnicas registradas e os resultados observados lado a lado."></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-26-continuar" data-cms-section-name="Aula 3 · Criar a próxima variação"$availability>
  <p class="section-label" data-cms-editable>Quando você sabe o que quer testar em seguida</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Use uma tentativa anterior como ponto de partida, mas declare o que pretende investigar antes de produzir o próximo resultado.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Depois da comparação, você pode criar a próxima variação a partir de um dos registros. O novo registro mantém a origem da pesquisa e os dados iniciais de exposição como ponto de partida, mas não copia um processamento que ainda não aconteceu nem um resultado que ainda não existe.</p>
      <p data-cms-editable>Escreva a intenção da nova tentativa de forma concreta. “Manter EI 400 e voltar a primeira revelação para 7 minutos” é mais útil do que “tentar melhorar”. Na próxima comparação, essa intenção ajuda a lembrar o que você estava tentando descobrir.</p>
    </div>
  </div>
  <figure data-private-media-slot="aula3-continuidade" data-private-media-alt="Registro criado como continuação de uma tentativa anterior, exibindo a origem e a intenção declarada para a próxima variação."></figure>
</section>

<section class="format" data-cms-section="caderno-27-ferramentas" data-cms-section-name="Aula 3 · Ferramentas de apoio"$availability>
  <div class="format-inner">
    <div class="format-heading">
      <div><p class="section-label" data-cms-editable>Ferramentas</p><h2 data-cms-editable>Use a bancada para calcular e organizar. Use o Caderno para dizer o que aconteceu.</h2></div>
      <p data-cms-editable>O botão Ferramentas dá acesso rápido aos cálculos e às referências de laboratório. Elas reduzem trabalho repetitivo, mas não substituem a leitura da cena, a escolha da exposição nem a interpretação do resultado.</p>
    </div>
    <div class="format-grid">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Reciprocidade</h3><p data-cms-editable><strong>Use quando:</strong> a exposição entra em tempos em que a relação simples entre intensidade e duração deixa de funcionar. <strong>A ferramenta:</strong> calcula o tempo corrigido com a curva liberada pelo curso. <strong>Você decide:</strong> qual EI, qual região da cena está orientando a exposição e que densidades quer preservar.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Exposição equivalente</h3><p data-cms-editable><strong>Use quando:</strong> quiser transpor uma referência para outro diafragma, EI ou compensação em EV. <strong>A ferramenta:</strong> refaz a relação entre esses valores e apresenta o tempo equivalente. <strong>Você decide:</strong> por que está fazendo a alteração e se ela faz sentido para a fotografia.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Processamentos</h3><p data-cms-editable><strong>Use quando:</strong> quiser salvar uma sequência de laboratório ou partir de um padrão do workshop. <strong>A ferramenta:</strong> organiza etapas, tempos, temperatura, agitação e referências de preparo. <strong>Você decide e registra:</strong> o que foi realmente executado.</p></article>
      <article class="format-card"><span class="number">04</span><h3 data-cms-editable>Receitas e preparo</h3><p data-cms-editable><strong>Use quando:</strong> precisar preparar um volume diferente das soluções apresentadas no curso. <strong>A ferramenta:</strong> dimensiona as quantidades da receita liberada. <strong>Você continua responsável:</strong> pelo preparo, identificação, conservação e uso seguro da solução.</p></article>
      <article class="format-card"><span class="number">05</span><h3 data-cms-editable>Inventário</h3><p data-cms-editable><strong>Use quando:</strong> quiser saber o que existe no laboratório e registrar consumo. <strong>A ferramenta:</strong> acompanha quantidades, lotes, datas e movimentações. <strong>Ela não conclui:</strong> que uma solução ainda está adequada para produzir determinado resultado.</p></article>
      <article class="format-card"><span class="number">06</span><h3 data-cms-editable>Predefinições e calibração</h3><p data-cms-editable><strong>Use quando:</strong> uma combinação de revelador, diluição, tempo, temperatura ou movimentação virou uma referência recorrente da sua prática. <strong>A ferramenta:</strong> guarda essa referência para reutilização e comparação. <strong>Ela não transforma:</strong> uma referência pessoal em receita universal.</p></article>
    </div>
  </div>
  <figure data-private-media-slot="aula3-ferramentas" data-private-media-alt="Bancada de Ferramentas da área do aluno, reunindo recursos de exposição, processamento, receitas e organização do laboratório."></figure>
</section>

<section class="section" data-layout-background="inverse" data-layout-space="l" data-cms-section="caderno-28-rotina" data-cms-section-name="Aula 3 · Depois de cada sessão"$availability>
  <p class="section-label" data-cms-editable>Depois de cada sessão</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>O objetivo não é preencher campos. É chegar à próxima fotografia sabendo mais do que você sabia antes da anterior.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Antes de encerrar uma tentativa, confira se o Caderno contém a exposição realmente feita, o processamento realmente executado, a imagem do resultado e as observações que você gostaria de encontrar se abrisse esse registro daqui a seis meses.</p>
      <p data-cms-editable>Quando houver dúvida, envie para avaliação. Quando houver duas tentativas relacionadas, compare. Quando surgir uma hipótese para o passo seguinte, crie a próxima variação e escreva a intenção antes de começar.</p>
    </div>
  </div>
</section>
HTML;

    $transform=static function(?string $json) use($aula3): ?string {
        if($json===null||trim($json)==='')return $json;
        $doc=json_decode($json,true);
        if(!is_array($doc)||!isset($doc['html'])||!is_string($doc['html']))return $json;
        $html=$doc['html'];
        $start=strpos($html,'<section id="caderno-aula-3"');
        if($start===false)return $json;
        $html=substr($html,0,$start).$aula3;
        $html=str_replace(
            '<a class="format-card" href="#caderno-aula-3"><span class="number">03</span><h3 data-cms-editable>Revisão de resultados</h3></a>',
            '<a class="format-card" href="#caderno-aula-3"><span class="number">03</span><h3 data-cms-editable>Área do aluno e continuidade</h3></a>',
            $html
        );
        $doc['html']=$html;
        return json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    };

    $draft=$transform($page['draft_document_json']??null);
    $published=$transform($page['published_document_json']??null);
    if($draft===($page['draft_document_json']??null)&&$published===($page['published_document_json']??null))return;

    $now=gmdate('c');
    $revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $db->beginTransaction();
    try{
        $db->prepare('UPDATE cms_pages SET draft_document_json=?,published_document_json=?,draft_revision=?,published_revision=?,draft_updated_at=?,published_at=?,updated_at=? WHERE id=?')
            ->execute([$draft,$published,$revision,$revision,$now,$now,$now,$pageId]);

        $sectionKeys=[
            'caderno-aula-3',
            'caderno-21-fluxo-pesquisa',
            'caderno-22-exposicao',
            'caderno-23-processamento',
            'caderno-24-resultado-avaliacao',
            'caderno-25-comparacao',
            'caderno-26-continuar',
            'caderno-27-ferramentas',
            'caderno-28-rotina',
        ];
        $oldKeys=['caderno-aula-3','caderno-21-leitura-resultados','caderno-22-registro'];
        $placeholders=implode(',',array_fill(0,count($oldKeys),'?'));
        $args=array_merge([$pageId],$oldKeys);
        $db->prepare("DELETE FROM course_page_sections WHERE page_id=? AND section_key IN ($placeholders)")->execute($args);
        $ins=$db->prepare('INSERT OR REPLACE INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?)');
        foreach($sectionKeys as $sectionKey)$ins->execute([$pageId,$sectionKey,$lessonId,$now,$now]);

        if($tableExists('course_material_sections')){
            $courseIds=[];
            $courseQ=$db->prepare('SELECT DISTINCT course_id FROM course_material_sections WHERE page_id=? AND lesson_id=?');
            $courseQ->execute([$pageId,$lessonId]);
            foreach($courseQ->fetchAll(PDO::FETCH_COLUMN) as $courseId)$courseIds[]=(int)$courseId;
            $db->prepare("DELETE FROM course_material_sections WHERE page_id=? AND section_key IN ($placeholders)")->execute($args);
            $insCourse=$db->prepare('INSERT OR REPLACE INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?,?)');
            foreach($courseIds as $courseId)foreach($sectionKeys as $sectionKey)$insCourse->execute([$courseId,$pageId,$sectionKey,$lessonId,$now,$now]);
        }
        $db->commit();
    }catch(Throwable $e){
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
};
