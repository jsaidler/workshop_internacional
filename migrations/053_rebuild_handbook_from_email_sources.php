<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;

    $html=<<<'HTML'
<section class="section" data-layout-background="surface" data-layout-space="xl" data-cms-section="caderno-capa" data-cms-section-name="Capa">
  <p class="section-label" data-cms-editable>Material do workshop · João Saidler</p>
  <div class="statement-grid">
    <div><h1 data-cms-editable>Positivo direto em filme de raio-X</h1></div>
    <div class="statement-copy">
      <p data-cms-editable>Exposição, energia, revelação e reversão reunidas em um material de referência construído a partir do conteúdo enviado aos participantes do workshop.</p>
      <p class="technical-note" data-cms-editable>O conteúdo foi reorganizado editorialmente por assunto e por aula. Foram retirados apenas saudações, datas, pedidos administrativos e recados circunstanciais da turma.</p>
    </div>
  </div>
</section>

<section class="format" aria-label="Índice do material" data-cms-section="caderno-indice" data-cms-section-name="Índice">
  <div class="format-inner">
    <div class="format-heading"><div><p class="section-label" data-cms-editable>Índice</p><h2 data-cms-editable>Três aulas, um único processo</h2></div><p data-cms-editable>O conteúdo é liberado por aula. As seções abaixo permanecem editáveis individualmente no CMS.</p></div>
    <div class="format-grid">
      <a class="format-card" href="#caderno-aula-1"><span class="number">01</span><h3 data-cms-editable>Filme e exposição</h3></a>
      <a class="format-card" href="#caderno-aula-2"><span class="number">02</span><h3 data-cms-editable>Processos químicos para positivos</h3></a>
      <a class="format-card" href="#caderno-aula-3"><span class="number">03</span><h3 data-cms-editable>Revisão de resultados</h3></a>
    </div>
  </div>
</section>

<section id="caderno-aula-1" class="format" data-cms-section="caderno-aula-1" data-cms-section-name="Aula 1 — Filme e exposição">
  <div class="format-inner"><div class="format-heading"><div><p class="section-label" data-cms-editable>Aula 01</p><h2 data-cms-editable>Filme e exposição</h2></div><p data-cms-editable>O filme de raio-X, a distribuição de energia na cena, a construção do positivo, a reciprocidade e o Índice de Exposição.</p></div></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-01-ponto-partida" data-cms-section-name="Aula 1 · Ponto de partida">
  <p class="section-label" data-cms-editable>Ponto de partida</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Uma receita pode ser um excelente ponto de partida. Dificilmente será a resposta para todas as fotografias.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>A ideia não é transformar isso numa bula. Boa parte do processo depende da relação entre exposição, revelação, temperatura, diluição e movimento.</p>
      <p data-cms-editable>Tempo, diluição, temperatura e movimento precisam ser considerados juntos. Um tempo de revelação sozinho diz muito pouco.</p>
    </div>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-02-filme" data-cms-section-name="Aula 1 · O filme de raio-X">
  <p class="section-label" data-cms-editable>O filme</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Fuji Super HR-U</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>O Fuji Super HR-U é um filme ortocromático.</p>
      <p data-cms-editable>Na prática isso significa que ele não registra todas as cores da mesma maneira que um filme preto e branco pancromático.</p>
      <p data-cms-editable>Os vermelhos tendem a aparecer mais escuros. Isso interfere bastante na pele, mas também em roupas, objetos e qualquer situação em que duas cores diferentes tenham luminosidades parecidas. Verdes e azuis ficam mais claros.</p>
    </div>
  </div>
  <figure data-private-media-slot="filme-ortocromatico" data-private-media-alt="Infográfico ilustrado: resposta ortocromática do Fuji Super HR-U, mostrando vermelhos mais escuros e verdes/azuis mais claros sem simular uma fotografia real."></figure>
  <div class="statement-grid">
    <div><h3 data-cms-editable>Dupla emulsão</h3></div>
    <div class="statement-copy">
      <p data-cms-editable>Outra característica importante é a dupla emulsão.</p>
      <p data-cms-editable>Existe emulsão fotossensível nos dois lados da base.</p>
      <p data-cms-editable>Isso traz uma desvantagem bastante evidente durante o processamento: quando o filme está molhado temos duas superfícies que podem ser riscadas. Além disso, é ideal que as duas faces sejam reveladas de forma idêntica, diferenças na revelação produzem manchas.</p>
      <p data-cms-editable>Evitem arrastar a chapa no fundo das bandejas, procurem manipulá-la pelas bordas e preferencialmente usem o suporte especial para o filme.</p>
      <p data-cms-editable>Por outro lado, essa dupla emulsão é muito interessante para o positivo direto.</p>
      <p data-cms-editable>Nas regiões que queremos pretas precisamos de bastante densidade para impedir a passagem da luz. Como existe emulsão dos dois lados, conseguimos formar áreas muito densas.</p>
      <p data-cms-editable>No outro extremo queremos remover o máximo possível dessa densidade e chegar à transparência da própria base.</p>
    </div>
  </div>
  <figure data-private-media-slot="dupla-emulsao-positivo" data-private-media-alt="Infográfico ilustrado: corte conceitual de uma chapa de raio-X com emulsão nos dois lados da base e a relação entre dupla emulsão, densidade máxima e transparência da base no positivo."></figure>
</section>

<section class="section" data-layout-background="inverse" data-layout-space="l" data-cms-section="caderno-03-positivo-procurado" data-cms-section-name="Aula 1 · O positivo procurado">
  <p class="section-label" data-cms-editable>O positivo procurado</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Pretos absolutos. Altas luzes na transparência da base. E fotografia entre os dois extremos.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>O positivo que procuro pode ser resumido desta maneira:</p>
      <p data-cms-editable>pretos absolutos, densos o suficiente para bloquear a luz;</p>
      <p data-cms-editable>altas luzes chegando à transparência da base do filme.</p>
      <p data-cms-editable>E, naturalmente, alguma fotografia entre uma coisa e outra.</p>
      <p data-cms-editable>Um preto absoluto e uma transparência absoluta sem nada no meio podem produzir um excelente estêncil, mas não necessariamente a fotografia que estávamos procurando. E esse ponto é muito importante, você deve encontrar a fotografia que procurava.</p>
    </div>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-04-energia" data-cms-section-name="Aula 1 · Exposição e energia">
  <p class="section-label" data-cms-editable>Exposição e energia</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>A câmera fará uma única exposição para uma cena inteira que contém quantidades muito diferentes de luz.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Quando pensamos numa cena, é fácil falar dela como se existisse uma única quantidade de luz.</p>
      <p data-cms-editable>Não existe.</p>
      <p data-cms-editable>Um rosto iluminado, o lado sombreado desse mesmo rosto, o cabelo, uma roupa preta, uma parede clara e o fundo podem estar recebendo ou refletindo quantidades muito diferentes de luz.</p>
      <p data-cms-editable>Podemos usar EV para visualizar essas diferenças.</p>
    </div>
  </div>
  <div class="format-grid">
    <div class="format-card"><span class="number">−1 EV</span><h3 data-cms-editable>1/2 da luz</h3></div>
    <div class="format-card"><span class="number">−3 EV</span><h3 data-cms-editable>1/8 da luz</h3></div>
    <div class="format-card"><span class="number">−5 EV</span><h3 data-cms-editable>1/32 da luz</h3></div>
  </div>
  <div class="statement-copy">
    <p data-cms-editable>Cinco pontos de diferença significam que uma das regiões está fornecendo ao filme trinta e duas vezes menos luz que a outra.</p>
    <p data-cms-editable>A câmera, naturalmente, fará uma única exposição para tudo isso.</p>
    <p data-cms-editable>Essa diferença de energia é justamente uma das coisas que usamos para construir o positivo.</p>
    <p data-cms-editable>Durante a exposição, as regiões claras da cena entregam mais energia ao filme. Na primeira revelação, essas regiões produzem mais prata metálica. Essa prata será retirada durante o branqueamento e, no positivo final, poderá chegar à transparência.</p>
    <p data-cms-editable>As regiões escuras da cena entregam menos energia.</p>
    <p data-cms-editable>Menos prata é formada nelas durante a primeira revelação e sobra mais material para produzir densidade depois da inversão.</p>
    <p data-cms-editable>É assim que aquilo que era escuro na cena volta a ser escuro no positivo.</p>
  </div>
  <p class="technical-note" data-cms-editable>De maneira simplificada: mais energia durante a exposição → região mais transparente no positivo. Menos energia durante a exposição → região mais densa no positivo.</p>
  <figure data-private-media-slot="energia-positivo" data-private-media-alt="Infográfico ilustrado: uma única exposição atravessando uma cena com regiões em diferentes EV; mais energia gera mais prata na primeira revelação, mais material removido no branqueamento e mais transparência no positivo; menos energia resulta em mais densidade final."></figure>
  <div class="statement-copy">
    <p data-cms-editable>Mas existe um limite importante aí.</p>
    <p data-cms-editable>Não basta simplesmente fornecer cada vez menos energia para conseguir um preto cada vez melhor.</p>
    <p data-cms-editable>Se duas regiões escuras recebem energia insuficiente para formar diferenças registráveis, as duas podem terminar praticamente no mesmo preto.</p>
    <p data-cms-editable>A sombra continua densa, mas perdemos a informação que existia dentro dela.</p>
    <p data-cms-editable>É por isso que a exposição não serve apenas para “fazer aparecer a fotografia”.</p>
    <p data-cms-editable>Ela participa diretamente da construção do positivo que estamos procurando.</p>
    <p data-cms-editable>Se quero que uma alta luz chegue realmente à transparência, preciso fornecer energia suficiente para que ela seja trabalhada na primeira revelação e removida no branqueamento.</p>
    <p data-cms-editable>Se quero uma sombra densa, mas ainda com informação, ela precisa receber menos energia que as altas luzes — mas não energia nenhuma.</p>
    <p data-cms-editable>Existe uma diferença bastante grande entre um preto e um buraco.</p>
    <p data-cms-editable>Por isso, quando estou trabalhando com raio-X e preciso escolher entre uma pequena falta ou um pequeno excesso de exposição, normalmente prefiro garantir energia.</p>
    <p data-cms-editable>Não porque superexpor seja automaticamente melhor.</p>
    <p data-cms-editable>Mas porque uma exposição mais generosa ainda me deixa alguma coisa para administrar durante a primeira revelação.</p>
    <p data-cms-editable>Uma região importante que recebeu energia insuficiente já chegou ao laboratório com muito menos possibilidades.</p>
    <p data-cms-editable>A exposição e a revelação trabalham juntas, mas cada uma tem seu trabalho.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-05-reciprocidade" data-cms-section-name="Aula 1 · Falha de reciprocidade">
  <p class="section-label" data-cms-editable>Falha de reciprocidade</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Quantidade de luz e tempo deixam de ser perfeitamente intercambiáveis nas exposições longas.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>A falha de reciprocidade é outra questão.</p>
      <p data-cms-editable>Em condições ideais, quantidade de luz e tempo poderiam ser trocados diretamente.</p>
      <p data-cms-editable>Se temos metade da intensidade da luz, dobramos o tempo.</p>
      <p data-cms-editable>Se temos 1/4, multiplicamos o tempo por quatro.</p>
      <p data-cms-editable>Em teoria, deveríamos terminar com o mesmo resultado.</p>
      <p data-cms-editable>Na prática, o filme de raio-X com que trabalho deixa de obedecer perfeitamente a essa relação quando entramos nas exposições longas.</p>
      <p data-cms-editable>Quanto menor a intensidade da luz, mais tempo precisamos acrescentar além daquela conta simples.</p>
    </div>
  </div>
  <p class="technical-note" data-cms-editable>Para o filme que utilizo, trabalho com esta correção: tempo corrigido = tempo calculado elevado a 1,3854. O cálculo é feito em segundos.</p>
  <div class="format-grid">
    <div class="format-card"><span class="number">EV 16</span><h3 data-cms-editable>1 s → 1 s</h3></div>
    <div class="format-card"><span class="number">EV 13</span><h3 data-cms-editable>8 s → aproximadamente 18 s</h3></div>
    <div class="format-card"><span class="number">EV 10</span><h3 data-cms-editable>1 min → aproximadamente 5 min</h3></div>
    <div class="format-card"><span class="number">EV 7</span><h3 data-cms-editable>8 min 30 s → aproximadamente 1 h 34 min</h3></div>
    <div class="format-card"><span class="number">EV 4</span><h3 data-cms-editable>1 h 8 min → aproximadamente 28 h</h3></div>
  </div>
  <div class="statement-copy">
    <p data-cms-editable>Não vejo grande utilidade em decorar a fórmula. O gráfico deixa a situação muito mais clara.</p>
    <p data-cms-editable>O interessante não é decorar esses números.</p>
    <p data-cms-editable>É observar a velocidade com que eles começam a ficar absurdos.</p>
    <p data-cms-editable>Em EV 13, oito segundos viram dezoito. Tudo bem.</p>
    <p data-cms-editable>Em EV 10, um minuto vira cinco.</p>
    <p data-cms-editable>Em EV 8, quatro minutos viram mais de meia hora.</p>
    <p data-cms-editable>Em EV 6, os dezessete minutos inicialmente calculados passam de quatro horas.</p>
    <p data-cms-editable>A matemática continua funcionando perfeitamente. A fotografia é que começa a discordar.</p>
    <p data-cms-editable>Não existe um EV exato em que o filme simplesmente deixa de funcionar.</p>
    <p data-cms-editable>Existe um ponto em que fornecer a quantidade necessária de energia deixa de ser viável com aquela luz e aquela abertura.</p>
    <p data-cms-editable>Nesse momento, a solução pode ser aumentar a iluminação, abrir mais o diafragma ou aceitar que aquela situação simplesmente não cabe no equipamento que temos.</p>
  </div>
  <figure data-private-media-slot="reciprocidade-energia" data-private-media-alt="Infográfico ilustrado: separar claramente diferenças de energia dentro da cena da falha de reciprocidade; incluir a fórmula t corrigido = t calculado^1,3854 e os exemplos EV 16, 13, 10, 7 e 4 fornecidos no material."></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-06-energia-reciprocidade" data-cms-section-name="Aula 1 · Baixa energia e reciprocidade">
  <p class="section-label" data-cms-editable>Duas coisas diferentes, um mesmo problema de energia</p>
  <div class="statement-copy">
    <p data-cms-editable>A diferença de luminosidade entre os pontos da cena e a falha de reciprocidade não são a mesma coisa.</p>
    <p data-cms-editable>Uma sombra recebe menos energia simplesmente porque aquela região da cena fornece menos luz.</p>
    <p data-cms-editable>A falha de reciprocidade é uma característica da resposta do filme quando trabalhamos com pouca intensidade luminosa durante muito tempo.</p>
    <p data-cms-editable>Mas as duas coisas podem se encontrar justamente onde o filme já está recebendo pouca energia.</p>
    <p data-cms-editable>Uma área sombreada começa em desvantagem porque fornece menos luz.</p>
    <p data-cms-editable>Se toda a fotografia estiver sendo feita numa condição de pouca luz, essa mesma região pode entrar também numa faixa em que o filme aproveita essa energia com menor eficiência.</p>
    <p data-cms-editable>Isso reforça uma ideia que considero importante no trabalho com raio-X:</p>
    <p data-cms-editable>precisamos decidir quais partes da cena queremos efetivamente registrar e garantir energia suficiente para elas.</p>
    <p data-cms-editable>No positivo direto, essa decisão não serve apenas para garantir que alguma imagem apareça.</p>
    <p data-cms-editable>Serve para construir aquilo que estamos procurando:</p>
    <p data-cms-editable>sombras densas, mas ainda capazes de guardar informação;</p>
    <p data-cms-editable>altas luzes realmente transparentes;</p>
    <p data-cms-editable>e uma escala tonal utilizável entre os dois extremos.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-07-ei" data-cms-section-name="Aula 1 · Índice de Exposição">
  <p class="section-label" data-cms-editable>EI · Índice de Exposição</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>O filme não muda. A exposição muda.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Até agora usei algumas vezes ISO como referência, mas, no nosso caso, EI é o termo mais adequado.</p>
      <p data-cms-editable>O Fuji Super HR-U não foi produzido para fotografia com luz visível e não existe um ISO fotográfico fornecido pelo fabricante que possamos simplesmente adotar. Quando trabalho com EI 200 ou EI 400, estou escolhendo uma referência para dizer ao fotômetro quanto quero expor aquele filme.</p>
      <p data-cms-editable>O filme não muda. A exposição muda.</p>
      <p data-cms-editable>Passar de EI 200 para EI 400 retira uma parada de exposição. A segunda chapa recebeu metade da luz da primeira.</p>
      <p data-cms-editable>O Sistema de Zonas, de Ansel Adams, ajuda bastante a visualizar o que isso significa. Se uma determinada região estava colocada em Zona III com EI 200, usando EI 400 ela desce para Zona II. O que estava em II se aproxima de I. Toda a escala é deslocada uma zona para baixo.</p>
      <p data-cms-editable>Isso importa especialmente nas regiões que já recebem pouca energia, porque são elas as primeiras a perder separação.</p>
    </div>
  </div>
  <figure data-private-media-slot="ei-zonas" data-private-media-alt="Infográfico ilustrado: comparação EI 200 e EI 400 como deslocamento de uma zona para baixo no Sistema de Zonas, destacando que a segunda chapa recebe metade da luz e que os valores baixos são os primeiros a perder separação."></figure>
  <div class="statement-copy">
    <p data-cms-editable>Na primeira chapa algumas regiões de sombra avançaram durante a primeira revelação e ainda permaneceram distintas umas das outras. Na segunda, mesmo com o dobro de Parodinal, algumas dessas regiões avançaram menos e começaram a se agrupar.</p>
    <p data-cms-editable>Esse agrupamento não significa simplesmente uma fotografia mais escura. Significa que valores diferentes estão chegando praticamente à mesma densidade.</p>
    <p data-cms-editable>Para o positivo que estamos procurando, queremos uma Zona I densa, mas quero que II, III e as regiões acima dela continuem sendo diferentes. Se todas terminam no mesmo lugar, temos preto, mas perdemos a informação que deveria existir entre esses valores.</p>
    <p data-cms-editable>Ao passar para EI 400 retiramos energia justamente dessas regiões. Aumentar a atividade do revelador conseguiu modificar a revelação, mas não devolver a exposição que retiramos.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-08-luz-seguranca" data-cms-section-name="Aula 1 · Luz de segurança e registro de teste">
  <p class="section-label" data-cms-editable>Luz de segurança e registro</p>
  <div class="statement-copy">
    <p data-cms-editable>O filme de raio-X não possui um ISO fotográfico absoluto que possamos simplesmente tirar da caixa.</p>
    <p data-cms-editable>O número que usamos funciona melhor como um ISO de trabalho, estabelecido a partir dos nossos testes, do tipo de luz e do processamento.</p>
    <p data-cms-editable>Por isso, anotem o ISO que utilizaram mesmo que ele seja apenas nossa referência naquele momento.</p>
    <p data-cms-editable>O filme também permite trabalhar sob luz de segurança por ser ortocromático, mas isso não significa que qualquer lâmpada vermelha seja automaticamente segura.</p>
    <p data-cms-editable>A distância, a potência da lâmpada e o tempo que o filme permanece exposto a ela fazem diferença. Se houver dúvida, façam um teste de véu antes de sacrificar uma caixa inteira numa descoberta científica desnecessariamente cara.</p>
  </div>
</section>

<section id="caderno-aula-2" class="format" data-cms-section="caderno-aula-2" data-cms-section-name="Aula 2 — Processos químicos para positivos">
  <div class="format-inner"><div class="format-heading"><div><p class="section-label" data-cms-editable>Aula 02</p><h2 data-cms-editable>Processos químicos para positivos</h2></div><p data-cms-editable>O que a exposição registra, como o revelador transforma essa informação e como as rotas de branqueamento conduzem ao positivo.</p></div></div>
</section>

<section class="section" data-layout-background="inverse" data-layout-space="l" data-cms-section="caderno-09-seguranca" data-cms-section-name="Aula 2 · Segurança química">
  <p class="section-label" data-cms-editable>Antes de começar</p>
  <div class="statement-copy">
    <p data-cms-editable>Alguns dos produtos abaixo são corrosivos, oxidantes ou bastante concentrados. Usem luvas, proteção para os olhos, recipientes próprios para os químicos e trabalhem em local ventilado.</p>
    <p data-cms-editable>Nunca misturem amônia com água sanitária, hipoclorito ou qualquer produto clorado.</p>
    <p data-cms-editable>TENTEM NÃO SE MATAR.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-10-imagem-latente" data-cms-section-name="Aula 2 · O que estamos revelando">
  <p class="section-label" data-cms-editable>O que estamos revelando</p>
  <div class="statement-copy">
    <p data-cms-editable>Os haletos de prata são cristais formados por prata (Ag) e haletos: cloretos (Cl), brometos (Br) e iodetos (I), organizados numa estrutura estável.</p>
    <p data-cms-editable>Quando a luz atinge esses cristais, sua energia provoca uma alteração nessa estrutura. Essa alteração forma a imagem latente: a fotografia já está registrada no filme, mas ainda não conseguimos enxergá-la.</p>
    <p data-cms-editable>É aí que entra o revelador.</p>
    <p data-cms-editable>O revelador é uma solução redutora. Ele fornece elétrons aos íons de prata presentes nos cristais que foram alterados pela exposição:</p>
  </div>
  <p class="technical-note" data-cms-editable>Ag⁺ + elétron → Ag⁰</p>
  <div class="statement-copy">
    <p data-cms-editable>Ag⁰ é prata metálica.</p>
    <p data-cms-editable>À medida que a revelação avança, cada vez mais prata é reduzida dentro desses cristais. Esse acúmulo de prata metálica forma os grãos que passamos a enxergar no filme e são a quantidade e a distribuição desses grãos que constroem a imagem.</p>
    <p data-cms-editable>Quanto mais prata metálica se forma numa determinada região, maior a densidade que ela alcança durante a primeira revelação.</p>
    <p data-cms-editable>Quando usei na aula a ideia de que o revelador “liberta” a prata, estava simplificando justamente essa transformação. A luz altera o cristal e o torna revelável; o revelador fornece o elétron que transforma Ag⁺ em prata metálica. A partir daí a reação avança como uma avalanche até formar o grão visível.</p>
    <p data-cms-editable>Isso também explica por que aumentar a atividade do revelador tem um limite. Se uma região recebeu pouca energia, existem menos cristais em condição de responder à revelação. Podemos aumentar tempo, concentração ou temperatura, mas em algum momento já não existe informação suficiente registrada ali para que o revelador a transforme em separação tonal.</p>
    <p data-cms-editable>É daí que vem a velha máxima de Ansel Adams:</p>
  </div>
  <p class="technical-note" data-cms-editable>Exponha para as sombras e revele para as luzes.</p>
  <div class="statement-copy">
    <p data-cms-editable>Seguiremos isso cegamente? Não. Mas ela resume uma relação importante: aquilo que não foi registrado durante a exposição não poderá ser criado depois pelo revelador.</p>
  </div>
  <figure data-private-media-slot="imagem-latente-prata" data-private-media-alt="Infográfico ilustrado: haleto de prata → alteração pela luz → imagem latente → Ag⁺ recebe elétron → Ag⁰ → formação de prata metálica visível. Não usar microscopia simulada nem química além do que está descrito no texto."></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-11-revelador" data-cms-section-name="Aula 2 · O revelador">
  <p class="section-label" data-cms-editable>O revelador</p>
  <div class="statement-copy">
    <p data-cms-editable>Em uma definição prática, um revelador é uma solução formada por três componentes básicos: um agente revelador, um alcalinizante e um meio aquoso.</p>
    <p data-cms-editable>O agente revelador é o redutor, aquele que fornece os elétrons. O alcalinizante cria o ambiente químico necessário para que essa reação aconteça e a água funciona como o meio onde essas substâncias circulam e entram em contato com a emulsão.</p>
    <p data-cms-editable>A partir daí podemos acrescentar outros componentes. Alguns reveladores usam conservantes; outros combinam mais de um agente revelador. É justamente aí que Parodinal e Caffenol começam a se comportar de maneiras diferentes.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-12-parodinal" data-cms-section-name="Aula 2 · Parodinal">
  <p class="section-label" data-cms-editable>Parodinal · química e receita</p>
  <div class="statement-copy">
    <p data-cms-editable>No Parodinal, a soda cáustica cria o meio alcalino e participa da transformação do paracetamol em p-aminofenol, que é o agente revelador. É o p-aminofenol que trabalha como redutor e fornece os elétrons durante a revelação, enquanto o sulfito de sódio ajuda a preservar a solução contra a oxidação.</p>
    <p data-cms-editable>Quando aumentamos a concentração de Parodinal estamos, portanto, colocando mais agente revelador disponível em contato com a emulsão.</p>
    <p data-cms-editable>Na aula eu usei o exemplo de dois grupos tentando se encontrar no meio de uma multidão. Para a revelação acontecer, as moléculas do revelador precisam encontrar os pontos onde podem agir dentro da gelatina. Quanto maior o número de moléculas disponíveis, maior a frequência desses encontros e maior a atividade da revelação.</p>
    <p data-cms-editable>Foi exatamente o que fizemos na segunda chapa ao passar de 10 para 20 ml de Parodinal.</p>
    <p data-cms-editable>Mas aumentar o número de reveladores no meio da multidão não cria pessoas do outro grupo que não estavam lá. A concentração aumenta a atividade química; ela não aumenta a exposição que o filme recebeu.</p>
  </div>
  <div class="statement-grid">
    <div><h3 data-cms-editable>Concentrado</h3></div>
    <div class="statement-copy">
      <p data-cms-editable>250 ml de água destilada ou desmineralizada</p>
      <p data-cms-editable>15 g de paracetamol</p>
      <p data-cms-editable>50 g de sulfito de sódio</p>
      <p data-cms-editable>20 g de hidróxido de sódio — soda cáustica perolizada P.A.</p>
    </div>
  </div>
  <div class="process-list">
    <div class="process-item"><span>01</span><p data-cms-editable>Ferver a água e desligar quando atingir a ebulição.</p></div>
    <div class="process-item"><span>02</span><p data-cms-editable>Esperar a temperatura baixar para aproximadamente 60 °C.</p></div>
    <div class="process-item"><span>03</span><p data-cms-editable>Adicionar, nesta ordem: paracetamol, sulfito de sódio e soda cáustica, aos poucos.</p></div>
    <div class="process-item"><span>04</span><p data-cms-editable>Guardar a solução em recipiente fechado durante 3 dias, agitando uma vez por dia.</p></div>
    <div class="process-item"><span>05</span><p data-cms-editable>Depois desse período, filtrar e armazenar preferencialmente em vidro âmbar bem fechado.</p></div>
  </div>
  <div class="statement-copy">
    <p data-cms-editable>No caso do paracetamol, considerem a quantidade de princípio ativo indicada na embalagem.</p>
    <p data-cms-editable>Por exemplo: 20 comprimidos de 750 mg = 15 g de paracetamol.</p>
    <p data-cms-editable>Eu utilizo soda cáustica perolizada P.A.. As sodas comuns vendidas em mercado podem conter bastante umidade e isso interfere na quantidade real de hidróxido de sódio que estamos pesando.</p>
    <p data-cms-editable>Mesmo a soda P.A. deve ser mantida muito bem fechada. Ela absorve umidade do ar com facilidade.</p>
    <h3 data-cms-editable>O Parodinal é um concentrado</h3>
    <p data-cms-editable>Depois desses três dias, o que temos no frasco não é o revelador pronto para colocar na bandeja.</p>
    <p data-cms-editable>O concentrado é diluído em água antes do uso.</p>
    <p data-cms-editable>Uma forma simples de escrever isso é:</p>
  </div>
  <p class="technical-note" data-cms-editable>15/550 = 15 ml de Parodinal e água até completar 550 ml. Não são 15 ml de Parodinal mais 550 ml de água. O volume final é 550 ml.</p>
  <div class="format-grid">
    <div class="format-card"><span class="number">15 ml</span><h3 data-cms-editable>água até 550 ml</h3></div>
    <div class="format-card"><span class="number">11 ml</span><h3 data-cms-editable>água até 550 ml</h3></div>
    <div class="format-card"><span class="number">6 ml</span><h3 data-cms-editable>água até 550 ml</h3></div>
    <div class="format-card"><span class="number">5 ml</span><h3 data-cms-editable>água até 550 ml</h3></div>
  </div>
  <div class="statement-copy">
    <p data-cms-editable>A diluição é uma ferramenta de controle.</p>
    <p data-cms-editable>Diminuir a quantidade de concentrado reduz a atividade do revelador e permite prolongar o processo. Aumentá-la faz o contrário.</p>
    <p data-cms-editable>Isso interessa particularmente no positivo direto porque não estamos apenas esperando a imagem “aparecer”. Estamos tentando decidir até onde a primeira revelação vai trabalhar a imagem.</p>
    <p data-cms-editable>Temperatura e movimento fazem parte da mesma equação.</p>
    <p data-cms-editable>Um revelador mais concentrado, mais quente ou mais movimentado tende a trabalhar mais rapidamente.</p>
    <p data-cms-editable>Um revelador mais diluído, mais frio ou menos movimentado tende a trabalhar mais devagar.</p>
    <p data-cms-editable>Por isso um tempo de revelação sozinho diz muito pouco. Tempo, diluição, temperatura e movimento precisam ser considerados juntos.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-13-caffenol" data-cms-section-name="Aula 2 · Brewed Caffenol">
  <p class="section-label" data-cms-editable>Brewed Caffenol · química e receita</p>
  <div class="statement-copy">
    <p data-cms-editable>O Caffenol trabalha de outra maneira porque combina dois agentes reveladores.</p>
    <p data-cms-editable>De um lado temos os compostos fenólicos, presentes em quantidade significativa em muitos vegetais e capazes de atuar como agentes reveladores. Do outro temos o ascorbato, derivado da vitamina C, que trabalha mais rapidamente e também se esgota com maior facilidade.</p>
    <p data-cms-editable>O café não é escolhido porque possua alguma propriedade exclusiva. A escolha é muito mais prática: é uma matéria-prima vegetal distribuída em pó, fácil de encontrar, medir e preparar sempre de maneira semelhante. Isso nos permite repetir o processo com muito mais facilidade do que se dependêssemos de uma matéria-prima vegetal menos padronizada.</p>
    <p data-cms-editable>A força do Caffenol vem da combinação dos dois agentes. Os compostos fenólicos trabalham de maneira mais lenta e estável; o ascorbato reage mais rapidamente. Em conjunto, um favorece a ação do outro e obtemos uma atividade que não depende apenas de um único revelador.</p>
    <p data-cms-editable>A alcalinidade vem do carbonato de sódio, que também transforma o ácido ascórbico em ascorbato, colocando a vitamina C na forma que nos interessa para a revelação.</p>
    <p data-cms-editable>Essa composição também explica uma diferença prática importante em relação ao Parodinal. O Parodinal é um concentrado estável que diluímos no momento do uso. O Caffenol é preparado diretamente na concentração de trabalho e não possui um conservante como o sulfito de sódio.</p>
    <p data-cms-editable>Depois de preparado, seus componentes começam a se oxidar e o ascorbato se esgota rapidamente. Por isso fazemos o Caffenol no momento da revelação e trabalhamos com ele fresco.</p>
  </div>
  <div class="statement-grid">
    <div><h3 data-cms-editable>Para aproximadamente 1 litro</h3></div>
    <div class="statement-copy">
      <p data-cms-editable>37 g de café torrado e moído extra-forte</p>
      <p data-cms-editable>54 g de carbonato de sódio — soda barrilha</p>
      <p data-cms-editable>20 g de ácido ascórbico — vitamina C</p>
      <p data-cms-editable>água para completar 1 litro</p>
    </div>
  </div>
  <div class="statement-copy"><p data-cms-editable>Eu costumo usar café extra-forte comum de supermercado. A receita foi desenvolvida usando café torrado e moído, e não café solúvel.</p></div>
  <div class="process-list">
    <div class="process-item"><span>01</span><p data-cms-editable>Ferver 500 ml de água.</p></div>
    <div class="process-item"><span>02</span><p data-cms-editable>Adicionar o café e manter a fervura durante aproximadamente 5 minutos.</p></div>
    <div class="process-item"><span>03</span><p data-cms-editable>Desligar e deixar descansar por mais 10 minutos.</p></div>
    <div class="process-item"><span>04</span><p data-cms-editable>Em outra parte da água, dissolver primeiro a soda barrilha e depois a vitamina C.</p></div>
    <div class="process-item"><span>05</span><p data-cms-editable>Misturar as duas partes filtrando o café.</p></div>
    <div class="process-item"><span>06</span><p data-cms-editable>Eu uso primeiro um coador de voil e depois um coador de tecido, procurando deixar a maior quantidade possível de borra no primeiro recipiente.</p></div>
    <div class="process-item"><span>07</span><p data-cms-editable>Completar com água até chegar a 1 litro.</p></div>
  </div>
  <div class="statement-copy">
    <p data-cms-editable>Parodinal e Caffenol são reveladores diferentes e não devem produzir necessariamente o mesmo resultado.</p>
    <p data-cms-editable>Além da fórmula, temperatura, concentração, tempo e movimentação alteram a maneira como cada um trabalha o filme.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-14-primeira-revelacao" data-cms-section-name="Aula 2 · Depois da primeira revelação">
  <p class="section-label" data-cms-editable>De volta à luz</p>
  <div class="statement-copy">
    <p data-cms-editable>Terminada a revelação, o filme é lavado com água e pode continuar o processamento em ambiente iluminado.</p>
  </div>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Positivo? Negativo? Depende do que fica.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Depois da primeira revelação o filme tem uma composição mista. Nas áreas que receberam mais luz, parte dos haletos foi reduzida e transformada em prata metálica. Nas sombras profundas ainda predominam os haletos que não foram revelados. Entre esses dois extremos encontramos diferentes proporções das duas coisas.</p>
      <p data-cms-editable>A partir daqui, ter um negativo ou um positivo depende basicamente de decidir o que fica e o que sai.</p>
      <p data-cms-editable>Para produzir um negativo queremos manter a prata metálica formada na revelação e retirar os haletos que sobraram. É isso que fazemos com o fixador, normalmente à base de tiossulfato de sódio: ele dissolve os haletos não revelados e deixa a prata metálica que forma a imagem.</p>
      <p data-cms-editable>Para produzir um positivo fazemos o contrário. Queremos retirar a prata formada na primeira revelação e preservar os haletos que sobraram, porque são eles que vão formar a imagem positiva na segunda revelação.</p>
      <p data-cms-editable>É para remover essa primeira prata que usamos o branqueador.</p>
    </div>
  </div>
  <figure data-private-media-slot="negativo-positivo" data-private-media-alt="Infográfico ilustrado: depois da primeira revelação, mostrar prata metálica e haletos remanescentes; no negativo fica a prata e saem os haletos; no positivo sai a prata da primeira revelação e ficam os haletos para a segunda revelação."></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-15-peracetica" data-cms-section-name="Aula 2 · Solução peracética">
  <p class="section-label" data-cms-editable>Branqueamento · solução peracética</p>
  <div class="statement-grid">
    <div><h3 data-cms-editable>Uma das soluções que utilizo</h3></div>
    <div class="statement-copy">
      <p data-cms-editable>650 ml de água</p>
      <p data-cms-editable>150 ml de vinagre de álcool</p>
      <p data-cms-editable>200 ml de peróxido de hidrogênio a 50% — água oxigenada a 50%</p>
    </div>
  </div>
  <p class="technical-note" data-cms-editable>Misturar nesta ordem: água → vinagre → peróxido de hidrogênio.</p>
  <div class="statement-copy">
    <p data-cms-editable>Depois de preparada, deixo a solução descansar entre 2 e 7 dias antes do uso.</p>
    <p data-cms-editable>Como referência, tenho utilizado aproximadamente 2 minutos de branqueamento, mas a velocidade da reação varia bastante conforme os produtos utilizados e a temperatura.</p>
    <p data-cms-editable>A água oxigenada a 50% é muito diferente da água oxigenada comum de farmácia. É um produto bastante concentrado e deve ser manuseado com cuidado.</p>
    <p data-cms-editable>Na solução peracética, a prata metálica é oxidada. Durante a reação o peróxido também libera oxigênio e forma aquelas bolhas que vimos aparecer sobre a emulsão.</p>
    <p data-cms-editable>Essas bolhas podem danificar a gelatina e por isso é interessante começar o banho com mergulhos do filme na solução, utilizar tanques verticais ou manter a agitação constante. Esta solução não deve ser utilizada em filmes de base orgânica ou no branqueamento de papéis fotográficos.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-16-fecl3-amonia" data-cms-section-name="Aula 2 · Cloreto férrico e amônia">
  <p class="section-label" data-cms-editable>Branqueamento · cloreto férrico + amônia</p>
  <div class="statement-copy">
    <p data-cms-editable>O cloreto férrico é normalmente encontrado em pó em lojas de componentes eletrônicos.</p>
    <p data-cms-editable>Ele é usado para corroer o cobre de placas de circuito impresso e pode aparecer com nomes como: cloreto férrico, percloreto de ferro ou corrosivo para PCI.</p>
    <p data-cms-editable>Com o produto em pó que utilizo, preparo na proporção: 1 parte de cloreto férrico para 4 partes de água.</p>
    <p data-cms-editable>Por exemplo: 100 g de cloreto férrico e 400 ml de água.</p>
    <p data-cms-editable>Colocar primeiro a água e depois acrescentar o pó aos poucos, misturando até dissolver.</p>
    <p data-cms-editable>A dissolução aquece a solução.</p>
    <p data-cms-editable>Se encontrarem cloreto férrico já vendido líquido, não usem automaticamente essa proporção. Nesse caso já existe água na solução e a concentração pode ser diferente.</p>
    <p data-cms-editable>Também recomendo separar utensílios próprios para esse banho. O cloreto férrico parece ter uma vocação particular para encontrar justamente a roupa que você não queria manchar.</p>
    <h3 data-cms-editable>Amônia</h3>
    <p data-cms-editable>Uso a amônia também na proporção: 1 parte de amônia para 4 partes de água.</p>
    <p data-cms-editable>Por exemplo: 100 ml de amônia e 400 ml de água.</p>
    <p data-cms-editable>Ela pode aparecer como: amônia, amônia líquida, amoníaco ou hidróxido de amônio.</p>
    <p data-cms-editable>Procurem uma solução simples, sem perfume, detergente ou outros produtos misturados.</p>
    <p data-cms-editable>As concentrações dos produtos comerciais variam bastante. Depois de encontrar um produto que funcione, é interessante continuar usando o mesmo durante uma sequência de testes.</p>
    <p data-cms-editable>E reforçando: amônia nunca deve ser misturada com água sanitária, hipoclorito ou produtos clorados.</p>
    <h3 data-cms-editable>O que acontece no branqueamento</h3>
    <p data-cms-editable>Com o cloreto férrico acontece outra coisa. Esse é um branqueamento rehalogênico: em vez de simplesmente retirar a prata metálica, ele a transforma novamente em um haleto.</p>
    <p data-cms-editable>No nosso caso, o cloreto férrico oxida a prata e, na presença do cloreto da própria solução, forma cloreto de prata — AgCl.</p>
  </div>
  <p class="technical-note" data-cms-editable>prata metálica → cloreto de prata</p>
  <div class="statement-copy">
    <p data-cms-editable>Isso resolve uma parte do problema, mas cria outra.</p>
    <p data-cms-editable>O cloreto de prata continua sensível à luz. Se parássemos nesse ponto e colocássemos novamente o filme no revelador, esse cloreto também seria reduzido a prata metálica. Em vez de eliminar a imagem negativa, estaríamos colocando todo esse material novamente em condição de escurecer e a chapa tenderia a ficar preta.</p>
    <p data-cms-editable>Precisamos, portanto, retirar o cloreto de prata antes de seguir.</p>
    <p data-cms-editable>É aí que entra a amônia.</p>
    <p data-cms-editable>A amônia torna essa prata solúvel e permite que o cloreto formado pelo banho anterior seja retirado da emulsão.</p>
    <p data-cms-editable>O cloreto férrico e a amônia, portanto, são dois processos independentes. O primeiro transforma a prata metálica em cloreto de prata; o segundo resolve o produto criado pelo primeiro e permite sua remoção.</p>
    <p data-cms-editable>Depois disso permanecem na emulsão os haletos que não participaram da primeira revelação. São eles que interessam agora.</p>
  </div>
  <figure data-private-media-slot="branqueamentos-rotas" data-private-media-alt="Infográfico ilustrado: duas rotas distintas de branqueamento. Rota peracética: oxidação da prata metálica. Rota FeCl3: prata metálica → AgCl; etapa separada de amônia solubiliza/remove o AgCl. As duas convergem para os haletos remanescentes antes da segunda revelação."></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-17-segunda-revelacao" data-cms-section-name="Aula 2 · Segunda revelação">
  <p class="section-label" data-cms-editable>Segunda revelação</p>
  <div class="statement-copy">
    <p data-cms-editable>Depois do branqueamento voltamos ao revelador, mas agora com uma intenção diferente.</p>
    <p data-cms-editable>Na primeira revelação queremos transformar em prata apenas os haletos afetados pela exposição da câmera. Na segunda queremos revelar todo o material restante.</p>
    <p data-cms-editable>É essa segunda massa de prata que forma a imagem positiva.</p>
    <p data-cms-editable>Seguindo o processo padrão, deixamos a segunda revelação avançar até o fim porque não temos motivo para manter haletos sensíveis dentro da imagem final.</p>
    <p data-cms-editable>Isso não impede que alguém interrompa essa etapa como decisão criativa. Nesse caso, porém, qualquer haleto que permanecer sem revelação continuará sensível à luz e precisará ser retirado posteriormente com um fixador.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-18-parametros" data-cms-section-name="Aula 2 · Quatro parâmetros da revelação">
  <p class="section-label" data-cms-editable>Os quatro parâmetros da revelação</p>
  <div class="statement-copy">
    <p data-cms-editable>Trabalhamos com quatro parâmetros que interferem diretamente na revelação: concentração, temperatura, tempo e agitação.</p>
    <p data-cms-editable>Eles atuam no mesmo processo, mas não produzem exatamente o mesmo efeito.</p>
    <p data-cms-editable>A concentração altera a quantidade de agente revelador disponível, como já foi explicado anteriormente. Na prática, quanto maior a concentração, mais rapidamente a revelação avança; diluir o revelador reduz essa atividade e torna o processo mais lento. Por que eu gostaria de reduzir a velocidade? Revelações muito rápidas podem causar manchas pelo contato irregular com as emulsões. A vazão do tanque pode ser lenta ou a capacidade grande demais e pode demorar vários segundos para encher e esvaziar. Uma boa prática é que o tempo útil de revelação fique superior a 3 minutos.</p>
    <p data-cms-editable>O tempo determina até onde permitimos que a reação avance antes de interrompê-la. Quanto mais prolongamos a revelação, mais grãos podem continuar sendo formados nos cristais que ainda respondem ao revelador, aumentando a densidade dessas regiões. Interromper antes limita esse avanço. É o mecanismo mais simples para controlar o contraste geral da imagem.</p>
    <p data-cms-editable>A agitação mantém o revelador em movimento, renovando constantemente a solução junto à emulsão. Quando deixamos de agitar, o revelador começa a se esgotar justamente onde está trabalhando com maior intensidade.</p>
    <p data-cms-editable>As regiões mais expostas consomem o revelador mais rapidamente e passam a sofrer uma exaustão localizada. Sua revelação desacelera enquanto regiões menos expostas continuam avançando. Com isso conseguimos alterar a relação entre os valores tonais e produzir um efeito compensador no contraste. Portanto, deixar de agitar não é apenas outra maneira de “revelar menos”. Estamos fazendo regiões diferentes da imagem avançarem em velocidades diferentes.</p>
    <p data-cms-editable>A temperatura, por outro lado, não é uma escala linear. Sua variação é de ordem exponencial, como as aberturas do diafragma. Então a diferença entre um grau para mais ou para menos tem efeitos muito mais acentuados que os demais parâmetros.</p>
    <p data-cms-editable>Temperatura é sinônimo de energia: aumentar acelera o movimento das partículas e automaticamente a velocidade do processo; reduzir tem o efeito inverso até um limite em que se torna tão lento que praticamente cessa. Na prática, isso significa que uma pequena variação de temperatura pode alterar bastante o ponto alcançado pela revelação mesmo quando mantemos concentração, tempo e agitação iguais. No caso de emulsões à base de gelatina, é importante lembrar que uma temperatura muito alta pode amolecer ou dissolver a gelatina e ocasionar descolamentos e outros defeitos nas emulsões.</p>
    <p data-cms-editable>Precisamos ter cuidado quando falamos em expansão ou contração pelo Sistema de Zonas ao ajustar os diversos parâmetros da primeira revelação.</p>
    <p data-cms-editable>Se quero observar o efeito do tempo, preciso manter concentração, temperatura e agitação padronizadas. Se altero dois ou três desses parâmetros ao mesmo tempo, já não estou apenas prolongando a mesma revelação; estou criando outra condição de processamento do filme.</p>
  </div>
  <figure data-private-media-slot="parametros-revelacao" data-private-media-alt="Infográfico ilustrado: quatro parâmetros da primeira revelação — concentração, tempo, agitação e temperatura — mostrando que atuam no mesmo processo por mecanismos diferentes e não são controles equivalentes."></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-19-duas-chapas" data-cms-section-name="Aula 2 · Duas condições comparadas">
  <p class="section-label" data-cms-editable>Duas condições comparadas</p>
  <div class="format-grid">
    <div class="format-card"><span class="number">EI 200</span><h3 data-cms-editable>10 ml de Parodinal + 550 ml de água · 7 min · 26 °C · agitação leve</h3></div>
    <div class="format-card"><span class="number">EI 400</span><h3 data-cms-editable>20 ml de Parodinal + 550 ml de água · 7 min · 26 °C · agitação leve</h3></div>
  </div>
  <p class="technical-note" data-cms-editable>Registro preservado literalmente do segundo e-mail: aqui aparece “10 ml de Parodinal + 550 ml de água” e “20 ml de Parodinal + 550 ml de água”. Isso diverge da notação 15/550 do primeiro e-mail, que define água até completar 550 ml. A divergência não foi corrigida silenciosamente.</p>
  <div class="statement-copy">
    <p data-cms-editable>Ao retirar uma parada de exposição, dobramos a concentração do revelador. Os resultados ficaram próximos, mas não iguais, e a principal diferença apareceu justamente nas regiões que receberam menos luz.</p>
    <p data-cms-editable>Os 7 minutos permaneceram iguais, mas passamos de 10 para 20 ml de Parodinal. Portanto, não foram simplesmente duas exposições diferentes submetidas ao mesmo desenvolvimento. Também alteramos a atividade do revelador com um objetivo: encontrar uma proporcionalidade do efeito da diluição para compensar a diminuição de energia que poderia afetar as altas luzes da imagem sem a necessidade de estender o tempo de revelação que causaria efeitos adicionais no resultado.</p>
    <p data-cms-editable>Lembrem-se: as áreas de transparência são tão importantes em um positivo quanto a densidade máxima das sombras.</p>
    <h3 data-cms-editable>E os 7 minutos?</h3>
    <p data-cms-editable>O comportamento da primeira chapa sugere que 7 minutos já representem uma revelação estendida para 10 ml de Parodinal + 550 ml de água, a 26 °C e com a agitação que usamos.</p>
    <p data-cms-editable>Minha hipótese neste momento é que o nosso desenvolvimento normal esteja mais próximo de 5 minutos.</p>
    <p data-cms-editable>Ainda preciso confirmar isso, mas estabelecer esse ponto é importante porque expansão e contração só fazem sentido quando existe um normal a partir do qual podemos comparar.</p>
    <p data-cms-editable>Se os cinco minutos se confirmarem como esse normal, sete minutos representam aproximadamente 40% a mais de tempo de revelação.</p>
    <p data-cms-editable>Isso não significa que todos os valores da imagem avancem 40%. A resposta não é linear e cada região parte de uma quantidade diferente de exposição. Significa apenas que deixamos o revelador atuar durante mais tempo e precisamos observar onde esse tempo adicional realmente produziu separação e onde já havia pouco a ganhar.</p>
    <p data-cms-editable>É exatamente aqui que a lógica do Sistema de Zonas volta a ser útil. A exposição determina principalmente onde posicionamos os valores baixos; a revelação nos permite trabalhar a distribuição dos valores que tiveram exposição suficiente para continuar respondendo.</p>
    <p data-cms-editable>No positivo direto a imagem ainda passará pela inversão, mas essa relação não desaparece. Se diferentes regiões de sombra receberam energia insuficiente para permanecer separadas na primeira revelação, não será a segunda etapa do processo que inventará essa separação.</p>
    <p data-cms-editable>Ao mesmo tempo, simplesmente aumentar a exposição também não resolve tudo. O que estamos procurando é um positivo em que as sombras cheguem à densidade necessária, próximas de Z1, as altas luzes possam alcançar a transparência da base e exista informação tonal útil entre esses dois extremos.</p>
    <h3 data-cms-editable>O que as duas fotografias nos disseram</h3>
    <p data-cms-editable>Na primeira fotografia usamos EI 200 e 10 ml de Parodinal. Na segunda retiramos uma parada de exposição e dobramos a concentração do revelador.</p>
    <p data-cms-editable>A revelação mais ativa conseguiu compensar parte dessa mudança, mas não reproduziu nas regiões mais baixas a mesma separação que encontramos na primeira chapa.</p>
    <p data-cms-editable>Isso não torna EI 400 certo ou errado.</p>
    <p data-cms-editable>O EI não é um número escondido dentro do filme que precisamos descobrir. É uma decisão sobre quanto queremos expô-lo e, consequentemente, sobre onde queremos posicionar a escala que a cena nos oferece.</p>
  </div>
  <div class="statement-grid">
    <div><h3 data-cms-editable>Possível condição de referência</h3></div>
    <div class="statement-copy">
      <p data-cms-editable>EI 200</p>
      <p data-cms-editable>10 ml de Parodinal + 550 ml de água</p>
      <p data-cms-editable>26 °C</p>
      <p data-cms-editable>5 minutos</p>
      <p data-cms-editable>agitação leve</p>
      <p data-cms-editable>Não como uma receita definitiva, mas como um possível desenvolvimento normal.</p>
      <p data-cms-editable>A partir dele podemos alterar conscientemente uma coisa de cada vez e observar a consequência: aumentar ou diminuir exposição, prolongar o tempo, mudar concentração, temperatura ou agitação.</p>
    </div>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-20-materiais" data-cms-section-name="Aula 2 · Onde encontrar os materiais">
  <p class="section-label" data-cms-editable>Onde encontrar os materiais</p>
  <div class="format-grid">
    <div class="format-card"><h3 data-cms-editable>Água destilada ou desmineralizada</h3><p data-cms-editable>Lojas de materiais automotivos. Procurem água desmineralizada sem aditivos, não fluido pronto para radiador.</p></div>
    <div class="format-card"><h3 data-cms-editable>Paracetamol</h3><p data-cms-editable>Farmácia. Dê preferência para os comprimidos não encerados.</p></div>
    <div class="format-card"><h3 data-cms-editable>Sulfito de sódio</h3><p data-cms-editable>Fornecedores de produtos químicos no Mercado Livre.</p></div>
    <div class="format-card"><h3 data-cms-editable>Hidróxido de sódio / soda cáustica perolizada P.A.</h3><p data-cms-editable>Fornecedores de produtos químicos no Mercado Livre.</p></div>
    <div class="format-card"><h3 data-cms-editable>Carbonato de sódio / soda barrilha</h3><p data-cms-editable>Lojas de materiais para piscina.</p></div>
    <div class="format-card"><h3 data-cms-editable>Ácido ascórbico / vitamina C</h3><p data-cms-editable>Farmácias, lojas de suplementos ou fornecedores de produtos químicos no Mercado Livre.</p></div>
    <div class="format-card"><h3 data-cms-editable>Peróxido de hidrogênio a 50% / água oxigenada a 50%</h3><p data-cms-editable>Lojas de materiais para piscina.</p></div>
    <div class="format-card"><h3 data-cms-editable>Cloreto férrico / percloreto de ferro / corrosivo para PCI</h3><p data-cms-editable>Lojas de componentes eletrônicos.</p></div>
    <div class="format-card"><h3 data-cms-editable>Amônia / amoníaco</h3><p data-cms-editable>Lojas de insumos para cabeleireiros e também lojas de artigos religiosos para Umbanda e Candomblé.</p></div>
    <div class="format-card"><h3 data-cms-editable>Café torrado e moído extra-forte</h3><p data-cms-editable>Supermercado. Quanto menos fuligem, melhor. Um Pilão ou Pelé resolvem bem.</p></div>
    <div class="format-card"><h3 data-cms-editable>Vinagre de álcool</h3><p data-cms-editable>Supermercado.</p></div>
    <div class="format-card"><h3 data-cms-editable>Filme de raio-X</h3><p data-cms-editable>Distribuidores e lojas de materiais radiológicos. O filme que utilizo é o Fuji Super HR-U. Eu costumo comprar na ClinRio, mas o 13x18cm já não é tão simples de encotrar.</p></div>
  </div>
</section>

<section id="caderno-aula-3" class="format" data-cms-section="caderno-aula-3" data-cms-section-name="Aula 3 — Revisão de resultados">
  <div class="format-inner"><div class="format-heading"><div><p class="section-label" data-cms-editable>Aula 03</p><h2 data-cms-editable>Revisão de resultados</h2></div><p data-cms-editable>Os próprios e-mails já definem como observar uma chapa: preservar separação entre valores, registrar as condições e alterar conscientemente uma variável de cada vez.</p></div></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-21-leitura-resultados" data-cms-section-name="Aula 3 · Como ler os resultados">
  <p class="section-label" data-cms-editable>Como ler os resultados</p>
  <div class="statement-copy">
    <p data-cms-editable>Mais do que decidir se uma fotografia “deu certo”, quero conseguir olhar para cada chapa e localizar onde a escala começou a mudar e qual decisão levou a essa mudança.</p>
    <p data-cms-editable>Um teste ruim, mas bem anotado, continua sendo um teste e pode nos dizer exatamente onde mexer.</p>
    <p data-cms-editable>Uma fotografia que ficou ótima e ninguém sabe exatamente por quê é mais difícil de repetir — embora seja, admito, uma forma bastante tradicional de fazer fotografia.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-22-registro" data-cms-section-name="Aula 3 · Registro dos testes">
  <p class="section-label" data-cms-editable>Registro dos testes</p>
  <div class="statement-copy"><p data-cms-editable>Não precisa virar um relatório da NASA. Mas tentem registrar pelo menos:</p></div>
  <div class="process-list">
    <div class="process-item"><span>01</span><p data-cms-editable>filme e lote</p></div>
    <div class="process-item"><span>02</span><p data-cms-editable>ISO usado como referência</p></div>
    <div class="process-item"><span>03</span><p data-cms-editable>diafragma</p></div>
    <div class="process-item"><span>04</span><p data-cms-editable>tempo inicialmente calculado</p></div>
    <div class="process-item"><span>05</span><p data-cms-editable>tempo corrigido pela reciprocidade, quando necessário</p></div>
    <div class="process-item"><span>06</span><p data-cms-editable>condição da luz</p></div>
    <div class="process-item"><span>07</span><p data-cms-editable>diferença entre as regiões claras e as sombras que querem preservar</p></div>
    <div class="process-item"><span>08</span><p data-cms-editable>revelador</p></div>
    <div class="process-item"><span>09</span><p data-cms-editable>diluição</p></div>
    <div class="process-item"><span>10</span><p data-cms-editable>temperatura</p></div>
    <div class="process-item"><span>11</span><p data-cms-editable>tempo de revelação</p></div>
    <div class="process-item"><span>12</span><p data-cms-editable>movimentação</p></div>
  </div>
</section>
HTML;

    $document=[
        'version'=>2,
        'theme'=>'auto',
        'meta'=>[
            'title'=>'Positivo direto em filme de raio-X',
            'description'=>'Material técnico do workshop: filme de raio-X, exposição, reciprocidade, reveladores, branqueamento e leitura dos resultados.'
        ],
        'html'=>$html,
    ];
    $encoded=json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $now=gmdate('c');
    $revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $db->prepare("UPDATE cms_pages SET title=?,nav_title=?,access_level='enrolled',show_in_nav=0,draft_document_json=?,published_document_json=?,draft_revision=?,published_revision=?,draft_updated_at=?,published_at=?,updated_at=? WHERE id=?")
       ->execute(['Positivo direto em filme de raio-X','Positivo direto em filme de raio-X',$encoded,$encoded,$revision,$revision,$now,$now,$now,(int)$page['id']]);

    $hasLessons=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='course_lessons'")->fetchColumn();
    $hasSections=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='course_page_sections'")->fetchColumn();
    if(!$hasLessons||!$hasSections)return;

    $lessons=[];
    $lq=$db->prepare('SELECT id,lesson_key FROM course_lessons WHERE activity_id=?');
    $lq->execute([(int)$page['activity_id']]);
    foreach($lq->fetchAll(PDO::FETCH_ASSOC) as $row)$lessons[(string)$row['lesson_key']]=(int)$row['id'];
    if(!isset($lessons['aula-1'],$lessons['aula-2'],$lessons['aula-3']))return;

    $map=[
        'aula-1'=>['caderno-aula-1','caderno-01-ponto-partida','caderno-02-filme','caderno-03-positivo-procurado','caderno-04-energia','caderno-05-reciprocidade','caderno-06-energia-reciprocidade','caderno-07-ei','caderno-08-luz-seguranca'],
        'aula-2'=>['caderno-aula-2','caderno-09-seguranca','caderno-10-imagem-latente','caderno-11-revelador','caderno-12-parodinal','caderno-13-caffenol','caderno-14-primeira-revelacao','caderno-15-peracetica','caderno-16-fecl3-amonia','caderno-17-segunda-revelacao','caderno-18-parametros','caderno-19-duas-chapas','caderno-20-materiais'],
        'aula-3'=>['caderno-aula-3','caderno-21-leitura-resultados','caderno-22-registro'],
    ];
    $db->prepare('DELETE FROM course_page_sections WHERE page_id=?')->execute([(int)$page['id']]);
    $ins=$db->prepare('INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?)');
    foreach($map as $lessonKey=>$sections){
        foreach($sections as $sectionKey)$ins->execute([(int)$page['id'],$sectionKey,$lessons[$lessonKey],$now,$now]);
    }
};
