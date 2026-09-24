<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;

    /*
     * Fonte editorial canônica:
     * - e-mail de 07/09/2026: "Receitas, materiais e algumas referências para trabalhar com filme de raio-X";
     * - e-mail de 18/09/2026: "Segundo Encontro: Processos químicos para positivos".
     *
     * A página abaixo preserva o conteúdo técnico integral desses envios e remove somente
     * cumprimentos, datas, pedidos de endereço/suporte, convites de publicação e demais
     * recados circunstanciais para a turma. A organização foi refeita editorialmente por aula
     * e assunto, sem converter o conteúdo em resumo.
     */
    $html=<<<'HTML'
<section class="section" data-layout-background="surface" data-layout-space="xl" data-cms-section="caderno-capa" data-cms-section-name="Capa">
  <p class="section-label" data-cms-editable>João Saidler · fotografia química</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Positivo direto em filme de raio-X</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Receitas, materiais, exposição, energia e processos químicos reunidos como material de referência do workshop.</p>
      <p class="technical-note" data-cms-editable>Conteúdo editorial reorganizado a partir dos materiais enviados aos participantes.</p>
    </div>
  </div>
</section>

<section class="format" aria-label="Aulas do material">
  <div class="format-inner">
    <div class="format-heading"><div><p class="section-label" data-cms-editable>Material do workshop</p><h2 data-cms-editable>Aulas</h2></div><p data-cms-editable>O conteúdo é liberado progressivamente por aula.</p></div>
    <div class="format-grid">
      <a class="format-card" href="#caderno-aula-1"><span class="number">01</span><h3 data-cms-editable>Filme e exposição</h3></a>
      <a class="format-card" href="#caderno-aula-2"><span class="number">02</span><h3 data-cms-editable>Processos químicos para positivos</h3></a>
      <a class="format-card" href="#caderno-aula-3"><span class="number">03</span><h3 data-cms-editable>Revisão de resultados</h3></a>
    </div>
  </div>
</section>

<section id="caderno-aula-1" class="format" data-cms-section="caderno-aula-1" data-cms-section-name="Aula 1 — Filme e exposição">
  <div class="format-inner"><div class="format-heading"><div><p class="section-label" data-cms-editable>Aula 01</p><h2 data-cms-editable>Filme e exposição</h2></div><p data-cms-editable>Características do filme, construção do positivo, energia, reciprocidade, receitas de referência e registro dos testes.</p></div></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a1-abertura" data-cms-section-name="Ponto de partida">
  <p class="section-label" data-cms-editable>Ponto de partida</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Uma receita é um ponto de partida</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>A ideia não é transformar isso numa bula. Principalmente porque boa parte do processo depende da relação entre exposição, revelação, temperatura, diluição e movimento. Uma receita pode ser um excelente ponto de partida; dificilmente será a resposta para todas as fotografias.</p>
      <p data-cms-editable>Antes de começar: alguns dos produtos abaixo são corrosivos, oxidantes ou bastante concentrados. Usem luvas, proteção para os olhos, recipientes próprios para os químicos e trabalhem em local ventilado.</p>
      <p class="technical-note" data-cms-editable>Nunca misturem amônia com água sanitária, hipoclorito ou qualquer produto clorado. TENTEM NÃO SE MATAR.</p>
    </div>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a1-filme" data-cms-section-name="Filme de raio-X">
  <p class="section-label" data-cms-editable>Filme de raio-X</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Fuji Super HR-U</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>O Fuji Super HR-U é um filme ortocromático.</p>
      <p data-cms-editable>Na prática isso significa que ele não registra todas as cores da mesma maneira que um filme preto e branco pancromático.</p>
      <p data-cms-editable>Os vermelhos tendem a aparecer mais escuros. Isso interfere bastante na pele, mas também em roupas, objetos e qualquer situação em que duas cores diferentes tenham luminosidades parecidas. Verdes e azuis ficam mais claros.</p>
      <p data-cms-editable>Outra característica importante é a dupla emulsão.</p>
      <p data-cms-editable>Existe emulsão fotossensível nos dois lados da base.</p>
      <p data-cms-editable>Isso traz uma desvantagem bastante evidente durante o processamento: quando o filme está molhado temos duas superfícies que podem ser riscadas. Além disso, é ideal que as duas faces sejam reveladas de forma idêntica, diferenças na revelação produzem manchas.</p>
      <p data-cms-editable>Evitem arrastar a chapa no fundo das bandejas, procurem manipulá-la pelas bordas e preferencialmente usem o suporte especial para o filme.</p>
      <p data-cms-editable>Por outro lado, essa dupla emulsão é muito interessante para o positivo direto.</p>
      <p data-cms-editable>Nas regiões que queremos pretas precisamos de bastante densidade para impedir a passagem da luz. Como existe emulsão dos dois lados, conseguimos formar áreas muito densas.</p>
      <p data-cms-editable>No outro extremo queremos remover o máximo possível dessa densidade e chegar à transparência da própria base.</p>
    </div>
  </div>
  <figure data-private-media-slot="filme-dupla-emulsao" data-private-media-alt="Infográfico ilustrado: ortocromatismo e dupla emulsão do Fuji Super HR-U"></figure>
</section>

<section class="section" data-layout-background="inverse" data-layout-space="l" data-cms-section="a1-positivo" data-cms-section-name="O positivo que procuro">
  <p class="section-label" data-cms-editable>O positivo que procuro</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Pretos, transparência e fotografia entre os dois</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>O positivo que procuro pode ser resumido desta maneira:</p>
      <p data-cms-editable>pretos absolutos, densos o suficiente para bloquear a luz;</p>
      <p data-cms-editable>altas luzes chegando à transparência da base do filme.</p>
      <p data-cms-editable>E, naturalmente, alguma fotografia entre uma coisa e outra.</p>
      <p data-cms-editable>Um preto absoluto e uma transparência absoluta sem nada no meio podem produzir um excelente estêncil, mas não necessariamente a fotografia que estávamos procurando. E esse ponto é muito importante, você deve encontrar a fotografia que procurava.</p>
    </div>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a1-energia" data-cms-section-name="Exposição e energia">
  <p class="section-label" data-cms-editable>Exposição e energia</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Uma cena, muitas quantidades de luz</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Quando pensamos numa cena, é fácil falar dela como se existisse uma única quantidade de luz.</p>
      <p data-cms-editable>Não existe.</p>
      <p data-cms-editable>Um rosto iluminado, o lado sombreado desse mesmo rosto, o cabelo, uma roupa preta, uma parede clara e o fundo podem estar recebendo ou refletindo quantidades muito diferentes de luz.</p>
      <p data-cms-editable>Podemos usar EV para visualizar essas diferenças.</p>
      <p data-cms-editable>A cada EV abaixo temos metade da luz:</p>
      <p class="technical-note" data-cms-editable>1 EV abaixo = 1/2 da luz · 3 EV abaixo = 1/8 da luz · 5 EV abaixo = 1/32 da luz</p>
      <p data-cms-editable>Cinco pontos de diferença significam que uma das regiões está fornecendo ao filme trinta e duas vezes menos luz que a outra.</p>
      <p data-cms-editable>A câmera, naturalmente, fará uma única exposição para tudo isso.</p>
      <p data-cms-editable>Essa diferença de energia é justamente uma das coisas que usamos para construir o positivo.</p>
      <p data-cms-editable>Durante a exposição, as regiões claras da cena entregam mais energia ao filme. Na primeira revelação, essas regiões produzem mais prata metálica. Essa prata será retirada durante o branqueamento e, no positivo final, poderá chegar à transparência.</p>
      <p data-cms-editable>As regiões escuras da cena entregam menos energia.</p>
      <p data-cms-editable>Menos prata é formada nelas durante a primeira revelação e sobra mais material para produzir densidade depois da inversão.</p>
      <p data-cms-editable>É assim que aquilo que era escuro na cena volta a ser escuro no positivo.</p>
      <p class="technical-note" data-cms-editable>mais energia durante a exposição → região mais transparente no positivo · menos energia durante a exposição → região mais densa no positivo</p>
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
  </div>
  <figure data-private-media-slot="energia-cena" data-private-media-alt="Infográfico ilustrado: uma exposição, diferentes EVs e a relação entre energia, prata da primeira revelação, branqueamento e densidade ou transparência no positivo"></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a1-reciprocidade" data-cms-section-name="Falha de reciprocidade">
  <p class="section-label" data-cms-editable>Falha de reciprocidade</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Quando tempo e intensidade deixam de ser equivalentes</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>A falha de reciprocidade é outra questão.</p>
      <p data-cms-editable>Em condições ideais, quantidade de luz e tempo poderiam ser trocados diretamente.</p>
      <p data-cms-editable>Se temos metade da intensidade da luz, dobramos o tempo.</p>
      <p data-cms-editable>Se temos 1/4, multiplicamos o tempo por quatro.</p>
      <p data-cms-editable>Em teoria, deveríamos terminar com o mesmo resultado.</p>
      <p data-cms-editable>Na prática, o filme de raio-X com que trabalho deixa de obedecer perfeitamente a essa relação quando entramos nas exposições longas.</p>
      <p data-cms-editable>Quanto menor a intensidade da luz, mais tempo precisamos acrescentar além daquela conta simples.</p>
      <p data-cms-editable>Para o filme que utilizo, trabalho com esta correção:</p>
      <p class="technical-note" data-cms-editable>tempo corrigido = tempo calculado elevado a 1,3854 · o cálculo é feito em segundos</p>
      <p data-cms-editable>Não vejo grande utilidade em decorar a fórmula. O gráfico deixa a situação muito mais clara.</p>
      <p data-cms-editable>Usando uma pinhole f/256 como referência, podemos ver o tamanho da diferença:</p>
    </div>
  </div>
  <div class="process-list">
    <div class="process-item"><span>EV 16</span><p data-cms-editable>1 segundo → 1 segundo</p></div>
    <div class="process-item"><span>EV 13</span><p data-cms-editable>8 segundos → aproximadamente 18 segundos</p></div>
    <div class="process-item"><span>EV 10</span><p data-cms-editable>1 minuto → aproximadamente 5 minutos</p></div>
    <div class="process-item"><span>EV 7</span><p data-cms-editable>8 minutos e meio → aproximadamente 1 hora e 34 minutos</p></div>
    <div class="process-item"><span>EV 4</span><p data-cms-editable>1 hora e 8 minutos → aproximadamente 28 horas</p></div>
  </div>
  <div class="statement-copy">
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
  <figure data-private-media-slot="reciprocidade" data-private-media-alt="Infográfico ilustrado: baixa energia na cena versus falha de reciprocidade, incluindo a fórmula e os tempos reais registrados no material"></figure>
</section>

<section class="section" data-layout-background="inverse" data-layout-space="l" data-cms-section="a1-energia-reciprocidade" data-cms-section-name="Baixa energia e reciprocidade">
  <p class="section-label" data-cms-editable>Duas coisas diferentes, um mesmo problema de energia</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Baixa energia não é falha de reciprocidade</h2></div>
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
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a1-parodinal-receita" data-cms-section-name="Receita de Parodinal">
  <p class="section-label" data-cms-editable>Receita · Parodinal</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Concentrado de Parodinal</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Esta receita produz o concentrado de Parodinal:</p>
      <p data-cms-editable>250 ml de água destilada ou desmineralizada</p>
      <p data-cms-editable>15 g de paracetamol</p>
      <p data-cms-editable>50 g de sulfito de sódio</p>
      <p data-cms-editable>20 g de hidróxido de sódio — soda cáustica perolizada P.A.</p>
    </div>
  </div>
  <div class="process-list">
    <div class="process-item"><span>01</span><p data-cms-editable>Ferver a água e desligar quando atingir a ebulição.</p></div>
    <div class="process-item"><span>02</span><p data-cms-editable>Esperar a temperatura baixar para aproximadamente 60 °C.</p></div>
    <div class="process-item"><span>03</span><p data-cms-editable>Adicionar, nesta ordem: paracetamol; sulfito de sódio; soda cáustica, aos poucos.</p></div>
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
    <p class="technical-note" data-cms-editable>15/550 = 15 ml de Parodinal e água até completar 550 ml. Não são 15 ml de Parodinal mais 550 ml de água. O volume final é 550 ml.</p>
    <p data-cms-editable>Alguns exemplos de diluições que já utilizei no trabalho com raio-X:</p>
    <p data-cms-editable>15 ml de Parodinal + água até 550 ml</p>
    <p data-cms-editable>11 ml de Parodinal + água até 550 ml</p>
    <p data-cms-editable>6 ml de Parodinal + água até 550 ml</p>
    <p data-cms-editable>5 ml de Parodinal + água até 550 ml</p>
    <p data-cms-editable>A diluição é uma ferramenta de controle.</p>
    <p data-cms-editable>Diminuir a quantidade de concentrado reduz a atividade do revelador e permite prolongar o processo. Aumentá-la faz o contrário.</p>
    <p data-cms-editable>Isso interessa particularmente no positivo direto porque não estamos apenas esperando a imagem “aparecer”. Estamos tentando decidir até onde a primeira revelação vai trabalhar a imagem.</p>
    <p data-cms-editable>Temperatura e movimento fazem parte da mesma equação.</p>
    <p data-cms-editable>Um revelador mais concentrado, mais quente ou mais movimentado tende a trabalhar mais rapidamente.</p>
    <p data-cms-editable>Um revelador mais diluído, mais frio ou menos movimentado tende a trabalhar mais devagar.</p>
    <p data-cms-editable>Por isso um tempo de revelação sozinho diz muito pouco. Tempo, diluição, temperatura e movimento precisam ser considerados juntos.</p>
  </div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a1-caffenol-receita" data-cms-section-name="Receita de Brewed Caffenol">
  <p class="section-label" data-cms-editable>Receita · Brewed Caffenol</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Para aproximadamente 1 litro</h2></div><div class="statement-copy"><p data-cms-editable>37 g de café torrado e moído extra-forte</p><p data-cms-editable>54 g de carbonato de sódio — soda barrilha</p><p data-cms-editable>20 g de ácido ascórbico — vitamina C</p><p data-cms-editable>água para completar 1 litro</p></div></div>
  <div class="statement-copy">
    <p data-cms-editable>Eu costumo usar café extra-forte comum de supermercado. A receita foi desenvolvida usando café torrado e moído, e não café solúvel.</p>
  </div>
  <div class="process-list">
    <div class="process-item"><span>01</span><p data-cms-editable>Ferver 500 ml de água.</p></div>
    <div class="process-item"><span>02</span><p data-cms-editable>Adicionar o café e manter a fervura durante aproximadamente 5 minutos.</p></div>
    <div class="process-item"><span>03</span><p data-cms-editable>Desligar e deixar descansar por mais 10 minutos.</p></div>
    <div class="process-item"><span>04</span><p data-cms-editable>Em outra parte da água, dissolver primeiro a soda barrilha e depois a vitamina C.</p></div>
    <div class="process-item"><span>05</span><p data-cms-editable>Misturar as duas partes filtrando o café.</p></div>
    <div class="process-item"><span>06</span><p data-cms-editable>Eu uso primeiro um coador de voil e depois um coador de tecido, procurando deixar a maior quantidade possível de borra no primeiro recipiente.</p></div>
    <div class="process-item"><span>07</span><p data-cms-editable>Completar com água até chegar a 1 litro.</p></div>
  </div>
  <div class="statement-copy"><p data-cms-editable>Parodinal e Caffenol são reveladores diferentes e não devem produzir necessariamente o mesmo resultado.</p><p data-cms-editable>Além da fórmula, temperatura, concentração, tempo e movimentação alteram a maneira como cada um trabalha o filme.</p></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a1-branqueadores-receitas" data-cms-section-name="Receitas de branqueadores e amônia">
  <p class="section-label" data-cms-editable>Receitas · branqueamento</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Solução peracética</h2></div><div class="statement-copy"><p data-cms-editable>Uma das soluções que utilizo para o branqueamento é preparada com:</p><p data-cms-editable>650 ml de água</p><p data-cms-editable>150 ml de vinagre de álcool</p><p data-cms-editable>200 ml de peróxido de hidrogênio a 50% — água oxigenada a 50%</p><p data-cms-editable>Misturar nesta ordem: água → vinagre → peróxido de hidrogênio</p><p data-cms-editable>Depois de preparada, deixo a solução descansar entre 2 e 7 dias antes do uso.</p><p data-cms-editable>Como referência, tenho utilizado aproximadamente 2 minutos de branqueamento, mas a velocidade da reação varia bastante conforme os produtos utilizados e a temperatura.</p><p data-cms-editable>A água oxigenada a 50% é muito diferente da água oxigenada comum de farmácia. É um produto bastante concentrado e deve ser manuseado com cuidado.</p></div></div>
  <div class="statement-grid"><div><h2 data-cms-editable>Cloreto férrico</h2></div><div class="statement-copy"><p data-cms-editable>O cloreto férrico é normalmente encontrado em pó em lojas de componentes eletrônicos.</p><p data-cms-editable>Ele é usado para corroer o cobre de placas de circuito impresso e pode aparecer com nomes como: cloreto férrico; percloreto de ferro; corrosivo para PCI.</p><p data-cms-editable>Com o produto em pó que utilizo, preparo na proporção: 1 parte de cloreto férrico para 4 partes de água.</p><p data-cms-editable>Por exemplo: 100 g de cloreto férrico; 400 ml de água.</p><p data-cms-editable>Colocar primeiro a água e depois acrescentar o pó aos poucos, misturando até dissolver.</p><p data-cms-editable>A dissolução aquece a solução.</p><p data-cms-editable>Se encontrarem cloreto férrico já vendido líquido, não usem automaticamente essa proporção. Nesse caso já existe água na solução e a concentração pode ser diferente.</p><p data-cms-editable>Também recomendo separar utensílios próprios para esse banho. O cloreto férrico parece ter uma vocação particular para encontrar justamente a roupa que você não queria manchar.</p></div></div>
  <div class="statement-grid"><div><h2 data-cms-editable>Amônia</h2></div><div class="statement-copy"><p data-cms-editable>Uso a amônia também na proporção: 1 parte de amônia para 4 partes de água.</p><p data-cms-editable>Por exemplo: 100 ml de amônia; 400 ml de água.</p><p data-cms-editable>Ela pode aparecer como: amônia; amônia líquida; amoníaco; hidróxido de amônio.</p><p data-cms-editable>Procurem uma solução simples, sem perfume, detergente ou outros produtos misturados.</p><p data-cms-editable>As concentrações dos produtos comerciais variam bastante. Depois de encontrar um produto que funcione, é interessante continuar usando o mesmo durante uma sequência de testes.</p><p class="technical-note" data-cms-editable>E reforçando: amônia nunca deve ser misturada com água sanitária, hipoclorito ou produtos clorados.</p></div></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a1-materiais" data-cms-section-name="Onde encontrar os materiais">
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

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a1-testes" data-cms-section-name="Dicas para os testes">
  <p class="section-label" data-cms-editable>Algumas dicas para os testes</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Registre o que mudou</h2></div><div class="statement-copy"><p data-cms-editable>O filme de raio-X não possui um ISO fotográfico absoluto que possamos simplesmente tirar da caixa.</p><p data-cms-editable>O número que usamos funciona melhor como um ISO de trabalho, estabelecido a partir dos nossos testes, do tipo de luz e do processamento.</p><p data-cms-editable>Por isso, anotem o ISO que utilizaram mesmo que ele seja apenas nossa referência naquele momento.</p><p data-cms-editable>O filme também permite trabalhar sob luz de segurança por ser ortocromático, mas isso não significa que qualquer lâmpada vermelha seja automaticamente segura.</p><p data-cms-editable>A distância, a potência da lâmpada e o tempo que o filme permanece exposto a ela fazem diferença. Se houver dúvida, façam um teste de véu antes de sacrificar uma caixa inteira numa descoberta científica desnecessariamente cara.</p><p data-cms-editable>E anotem os testes.</p><p data-cms-editable>Não precisa virar um relatório da NASA. Mas tentem registrar pelo menos:</p></div></div>
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
  <div class="statement-copy"><p data-cms-editable>Um teste ruim, mas bem anotado, continua sendo um teste e pode nos dizer exatamente onde mexer.</p><p data-cms-editable>Uma fotografia que ficou ótima e ninguém sabe exatamente por quê é mais difícil de repetir — embora seja, admito, uma forma bastante tradicional de fazer fotografia.</p></div>
</section>

<section id="caderno-aula-2" class="format" data-cms-section="caderno-aula-2" data-cms-section-name="Aula 2 — Processos químicos para positivos">
  <div class="format-inner"><div class="format-heading"><div><p class="section-label" data-cms-editable>Aula 02</p><h2 data-cms-editable>Processos químicos para positivos</h2></div><p data-cms-editable>Duas condições de exposição, imagem latente, reveladores, branqueamento, segunda revelação e parâmetros de controle.</p></div></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a2-duas-chapas" data-cms-section-name="Duas condições de teste">
  <p class="section-label" data-cms-editable>Duas condições</p>
  <div class="statement-grid"><div><h2 data-cms-editable>EI 200 × EI 400</h2></div><div class="statement-copy"><p data-cms-editable>Foram feitas duas fotografias em condições diferentes e o processo completo até o positivo.</p><p data-cms-editable>A primeira foi exposta em EI 200 e revelada com 10 ml de Parodinal + 550 ml de água, durante 7 minutos, a 26 °C e com agitação leve.</p><p data-cms-editable>Na segunda passamos para EI 400 e 20 ml de Parodinal + 550 ml de água, mantendo os mesmos 7 minutos, 26 °C e a mesma agitação.</p><p data-cms-editable>Ao retirar uma parada de exposição, dobramos a concentração do revelador. Os resultados ficaram próximos, mas não iguais, e a principal diferença apareceu justamente nas regiões que receberam menos luz.</p><p class="technical-note" data-cms-editable>Registro preservado literalmente: este segundo material diz “10 ml/20 ml de Parodinal + 550 ml de água”; o primeiro material define a notação 15/550 como volume final de 550 ml. Os dois registros não são reconciliados silenciosamente.</p></div></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a2-ei" data-cms-section-name="Índice de Exposição">
  <p class="section-label" data-cms-editable>EI · Índice de Exposição</p>
  <div class="statement-grid"><div><h2 data-cms-editable>O filme não muda. A exposição muda.</h2></div><div class="statement-copy"><p data-cms-editable>Até agora usei algumas vezes ISO como referência, mas, no nosso caso, EI é o termo mais adequado.</p><p data-cms-editable>O Fuji Super HR-U não foi produzido para fotografia com luz visível e não existe um ISO fotográfico fornecido pelo fabricante que possamos simplesmente adotar. Quando trabalho com EI 200 ou EI 400, estou escolhendo uma referência para dizer ao fotômetro quanto quero expor aquele filme.</p><p data-cms-editable>O filme não muda. A exposição muda.</p><p data-cms-editable>Passar de EI 200 para EI 400 retira uma parada de exposição. A segunda chapa recebeu metade da luz da primeira.</p><p data-cms-editable>O Sistema de Zonas, de Ansel Adams, ajuda bastante a visualizar o que isso significa. Se uma determinada região estava colocada em Zona III com EI 200, usando EI 400 ela desce para Zona II. O que estava em II se aproxima de I. Toda a escala é deslocada uma zona para baixo.</p><p data-cms-editable>Isso importa especialmente nas regiões que já recebem pouca energia, porque são elas as primeiras a perder separação.</p><p data-cms-editable>Foi justamente o que observamos.</p><p data-cms-editable>Na primeira chapa algumas regiões de sombra avançaram durante a primeira revelação e ainda permaneceram distintas umas das outras. Na segunda, mesmo com o dobro de Parodinal, algumas dessas regiões avançaram menos e começaram a se agrupar.</p><p data-cms-editable>Esse agrupamento não significa simplesmente uma fotografia mais escura. Significa que valores diferentes estão chegando praticamente à mesma densidade.</p><p data-cms-editable>Para o positivo que estamos procurando, queremos uma Zona I densa, mas quero que II, III e as regiões acima dela continuem sendo diferentes. Se todas terminam no mesmo lugar, temos preto, mas perdemos a informação que deveria existir entre esses valores.</p><p data-cms-editable>Ao passar para EI 400 retiramos energia justamente dessas regiões. Aumentar a atividade do revelador conseguiu modificar a revelação, mas não devolver a exposição que retiramos.</p><p data-cms-editable>Para entender por que isso acontece, precisamos voltar um pouco e olhar o que estamos realmente revelando.</p></div></div>
  <figure data-private-media-slot="ei-zonas" data-private-media-alt="Infográfico ilustrado: deslocamento de uma zona ao passar de EI 200 para EI 400 e perda de separação nas regiões de menor energia"></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a2-imagem-latente" data-cms-section-name="O que estamos revelando">
  <p class="section-label" data-cms-editable>O que estamos revelando</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Da imagem latente à prata metálica</h2></div><div class="statement-copy"><p data-cms-editable>Os haletos de prata são cristais formados por prata (Ag) e haletos: cloretos (Cl), brometos (Br) e iodetos (I), organizados numa estrutura estável.</p><p data-cms-editable>Quando a luz atinge esses cristais, sua energia provoca uma alteração nessa estrutura. Essa alteração forma a imagem latente: a fotografia já está registrada no filme, mas ainda não conseguimos enxergá-la.</p><p data-cms-editable>É aí que entra o revelador.</p><p data-cms-editable>O revelador é uma solução redutora. Ele fornece elétrons aos íons de prata presentes nos cristais que foram alterados pela exposição:</p><p class="technical-note" data-cms-editable>Ag⁺ + elétron → Ag⁰ · Ag⁰ é prata metálica.</p><p data-cms-editable>À medida que a revelação avança, cada vez mais prata é reduzida dentro desses cristais. Esse acúmulo de prata metálica forma os grãos que passamos a enxergar no filme e são a quantidade e a distribuição desses grãos que constroem a imagem.</p><p data-cms-editable>Quanto mais prata metálica se forma numa determinada região, maior a densidade que ela alcança durante a primeira revelação.</p><p data-cms-editable>Quando uso a ideia de que o revelador “liberta” a prata, estou simplificando justamente essa transformação. A luz altera o cristal e o torna revelável; o revelador fornece o elétron que transforma Ag⁺ em prata metálica. A partir daí a reação avança como uma avalanche até formar o grão visível.</p><p data-cms-editable>Isso também explica por que aumentar a atividade do revelador tem um limite. Se uma região recebeu pouca energia, existem menos cristais em condição de responder à revelação. Podemos aumentar tempo, concentração ou temperatura, mas em algum momento já não existe informação suficiente registrada ali para que o revelador a transforme em separação tonal.</p><p data-cms-editable>É daí que vem a velha máxima de Ansel Adams:</p><p class="technical-note" data-cms-editable>Exponha para as sombras e revele para as luzes.</p><p data-cms-editable>Seguiremos isso cegamente? Não. Mas ela resume uma relação importante: aquilo que não foi registrado durante a exposição não poderá ser criado depois pelo revelador.</p><p data-cms-editable>Agora podemos olhar para os químicos com mais clareza.</p></div></div>
  <figure data-private-media-slot="imagem-latente" data-private-media-alt="Infográfico ilustrado: haleto de prata, energia da luz, imagem latente, redução Ag⁺ + elétron → Ag⁰ e formação de prata metálica"></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a2-revelador" data-cms-section-name="O revelador">
  <p class="section-label" data-cms-editable>O revelador</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Redutor, alcalinizante e água</h2></div><div class="statement-copy"><p data-cms-editable>Em uma definição prática, um revelador é uma solução formada por três componentes básicos: um agente revelador, um alcalinizante e um meio aquoso.</p><p data-cms-editable>O agente revelador é o redutor, aquele que fornece os elétrons. O alcalinizante cria o ambiente químico necessário para que essa reação aconteça e a água funciona como o meio onde essas substâncias circulam e entram em contato com a emulsão.</p><p data-cms-editable>A partir daí podemos acrescentar outros componentes. Alguns reveladores usam conservantes; outros combinam mais de um agente revelador. É justamente aí que Parodinal e Caffenol começam a se comportar de maneiras diferentes.</p></div></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a2-parodinal-quimica" data-cms-section-name="Parodinal — química">
  <p class="section-label" data-cms-editable>Parodinal</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Mais concentração aumenta a atividade, não a exposição</h2></div><div class="statement-copy"><p data-cms-editable>No Parodinal, a soda cáustica cria o meio alcalino e participa da transformação do paracetamol em p-aminofenol, que é o agente revelador. É o p-aminofenol que trabalha como redutor e fornece os elétrons durante a revelação, enquanto o sulfito de sódio ajuda a preservar a solução contra a oxidação.</p><p data-cms-editable>Quando aumentamos a concentração de Parodinal estamos, portanto, colocando mais agente revelador disponível em contato com a emulsão.</p><p data-cms-editable>Uso o exemplo de dois grupos tentando se encontrar no meio de uma multidão. Para a revelação acontecer, as moléculas do revelador precisam encontrar os pontos onde podem agir dentro da gelatina. Quanto maior o número de moléculas disponíveis, maior a frequência desses encontros e maior a atividade da revelação.</p><p data-cms-editable>Foi exatamente o que fizemos na segunda chapa ao passar de 10 para 20 ml de Parodinal.</p><p data-cms-editable>Mas aumentar o número de reveladores no meio da multidão não cria pessoas do outro grupo que não estavam lá. A concentração aumenta a atividade química; ela não aumenta a exposição que o filme recebeu.</p></div></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a2-caffenol-quimica" data-cms-section-name="Caffenol — química">
  <p class="section-label" data-cms-editable>Caffenol</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Dois agentes reveladores</h2></div><div class="statement-copy"><p data-cms-editable>O Caffenol trabalha de outra maneira porque combina dois agentes reveladores.</p><p data-cms-editable>De um lado temos os compostos fenólicos, presentes em quantidade significativa em muitos vegetais e capazes de atuar como agentes reveladores. Do outro temos o ascorbato, derivado da vitamina C, que trabalha mais rapidamente e também se esgota com maior facilidade.</p><p data-cms-editable>O café não é escolhido porque possua alguma propriedade exclusiva. A escolha é muito mais prática: é uma matéria-prima vegetal distribuída em pó, fácil de encontrar, medir e preparar sempre de maneira semelhante. Isso nos permite repetir o processo com muito mais facilidade do que se dependêssemos de uma matéria-prima vegetal menos padronizada.</p><p data-cms-editable>A força do Caffenol vem da combinação dos dois agentes. Os compostos fenólicos trabalham de maneira mais lenta e estável; o ascorbato reage mais rapidamente. Em conjunto, um favorece a ação do outro e obtemos uma atividade que não depende apenas de um único revelador.</p><p data-cms-editable>A alcalinidade vem do carbonato de sódio, que também transforma o ácido ascórbico em ascorbato, colocando a vitamina C na forma que nos interessa para a revelação.</p><p data-cms-editable>Essa composição também explica uma diferença prática importante em relação ao Parodinal. O Parodinal é um concentrado estável que diluímos no momento do uso. O Caffenol é preparado diretamente na concentração de trabalho e não possui um conservante como o sulfito de sódio.</p><p data-cms-editable>Depois de preparado, seus componentes começam a se oxidar e o ascorbato se esgota rapidamente. Por isso fazemos o Caffenol no momento da revelação e trabalhamos com ele fresco.</p></div></div>
</section>

<section class="section" data-layout-background="inverse" data-layout-space="l" data-cms-section="a2-negativo-positivo" data-cms-section-name="Positivo ou negativo">
  <p class="section-label" data-cms-editable>De volta à luz</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Positivo? Negativo? Depende do que fica</h2></div><div class="statement-copy"><p data-cms-editable>Terminada a revelação, o filme é lavado com água e pode continuar o processamento em ambiente iluminado.</p><p data-cms-editable>Depois da primeira revelação o filme tem uma composição mista. Nas áreas que receberam mais luz, parte dos haletos foi reduzida e transformada em prata metálica. Nas sombras profundas ainda predominam os haletos que não foram revelados. Entre esses dois extremos encontramos diferentes proporções das duas coisas.</p><p data-cms-editable>A partir daqui, ter um negativo ou um positivo depende basicamente de decidir o que fica e o que sai.</p><p data-cms-editable>Para produzir um negativo queremos manter a prata metálica formada na revelação e retirar os haletos que sobraram. É isso que fazemos com o fixador, normalmente à base de tiossulfato de sódio: ele dissolve os haletos não revelados e deixa a prata metálica que forma a imagem.</p><p data-cms-editable>Para produzir um positivo fazemos o contrário. Queremos retirar a prata formada na primeira revelação e preservar os haletos que sobraram, porque são eles que vão formar a imagem positiva na segunda revelação.</p><p data-cms-editable>É para remover essa primeira prata que usamos o branqueador.</p></div></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a2-branqueamento" data-cms-section-name="Branqueamento">
  <p class="section-label" data-cms-editable>O branqueamento</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Duas rotas para retirar a primeira prata</h2></div><div class="statement-copy"><p data-cms-editable>Há dois branqueadores diferentes que chegam ao mesmo objetivo por caminhos distintos.</p><p data-cms-editable>Na solução peracética, a prata metálica é oxidada. Durante a reação o peróxido também libera oxigênio e forma bolhas sobre a emulsão.</p><p data-cms-editable>Essas bolhas podem danificar a gelatina e por isso é interessante começar o banho com mergulhos do filme na solução, utilizar tanques verticais ou manter a agitação constante. Esta solução não deve ser utilizada em filmes de base orgânica ou no branqueamento de papéis fotográficos.</p><p data-cms-editable>Com o cloreto férrico acontece outra coisa. Esse é um branqueamento rehalogênico: em vez de simplesmente retirar a prata metálica, ele a transforma novamente em um haleto.</p><p data-cms-editable>No nosso caso, o cloreto férrico oxida a prata e, na presença do cloreto da própria solução, forma cloreto de prata — AgCl.</p><p class="technical-note" data-cms-editable>prata metálica → cloreto de prata</p><p data-cms-editable>Isso resolve uma parte do problema, mas cria outra.</p><p data-cms-editable>O cloreto de prata continua sensível à luz. Se parássemos nesse ponto e colocássemos novamente o filme no revelador, esse cloreto também seria reduzido a prata metálica. Em vez de eliminar a imagem negativa, estaríamos colocando todo esse material novamente em condição de escurecer e a chapa tenderia a ficar preta.</p><p data-cms-editable>Precisamos, portanto, retirar o cloreto de prata antes de seguir.</p><p data-cms-editable>É aí que entra a amônia.</p><p data-cms-editable>A amônia torna essa prata solúvel e permite que o cloreto formado pelo banho anterior seja retirado da emulsão.</p><p data-cms-editable>O cloreto férrico e a amônia, portanto, são dois processos independentes. O primeiro transforma a prata metálica em cloreto de prata; o segundo resolve o produto criado pelo primeiro e permite sua remoção.</p><p data-cms-editable>Depois disso permanecem na emulsão os haletos que não participaram da primeira revelação. São eles que interessam agora.</p></div></div>
  <figure data-private-media-slot="fluxo-positivo" data-private-media-alt="Infográfico ilustrado: fluxo do positivo direto com as duas rotas de branqueamento — peracética por oxidação e FeCl3 formando AgCl seguido de limpeza separada com amônia — antes da segunda revelação"></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a2-segunda-revelacao" data-cms-section-name="Segunda revelação">
  <p class="section-label" data-cms-editable>Segunda revelação</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Revelar todo o material restante</h2></div><div class="statement-copy"><p data-cms-editable>Depois do branqueamento voltamos ao revelador, mas agora com uma intenção diferente.</p><p data-cms-editable>Na primeira revelação queremos transformar em prata apenas os haletos afetados pela exposição da câmera. Na segunda queremos revelar todo o material restante.</p><p data-cms-editable>É essa segunda massa de prata que forma a imagem positiva.</p><p data-cms-editable>Seguindo o processo padrão, deixamos a segunda revelação avançar até o fim porque não temos motivo para manter haletos sensíveis dentro da imagem final.</p><p data-cms-editable>Isso não impede que alguém interrompa essa etapa como decisão criativa. Nesse caso, porém, qualquer haleto que permanecer sem revelação continuará sensível à luz e precisará ser retirado posteriormente com um fixador.</p></div></div>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a2-parametros" data-cms-section-name="Quatro parâmetros da revelação">
  <p class="section-label" data-cms-editable>Conclusão · quatro parâmetros da revelação</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Concentração, temperatura, tempo e agitação</h2></div><div class="statement-copy"><p data-cms-editable>Trabalhamos com quatro parâmetros que interferem diretamente na revelação: concentração, temperatura, tempo e agitação.</p><p data-cms-editable>Eles atuam no mesmo processo, mas não produzem exatamente o mesmo efeito.</p><p data-cms-editable>A concentração altera a quantidade de agente revelador disponível, como já foi explicado anteriormente. Na prática, quanto maior a concentração, mais rapidamente a revelação avança; diluir o revelador reduz essa atividade e torna o processo mais lento. Por que eu gostaria de reduzir a velocidade? Revelações muito rápidas podem causar manchas pelo contato irregular com as emulsões. A vazão do tanque pode ser lenta ou a capacidade grande demais e pode demorar vários segundos para encher e esvaziar. Uma boa prática é que o tempo útil de revelação fique superior a 3 minutos.</p><p data-cms-editable>O tempo determina até onde permitimos que a reação avance antes de interrompê-la. Quanto mais prolongamos a revelação, mais grãos podem continuar sendo formados nos cristais que ainda respondem ao revelador, aumentando a densidade dessas regiões. Interromper antes limita esse avanço. É o mecanismo mais simples para controlar o contraste geral da imagem.</p><p data-cms-editable>A agitação mantém o revelador em movimento, renovando constantemente a solução junto à emulsão. Quando deixamos de agitar, o revelador começa a se esgotar justamente onde está trabalhando com maior intensidade.</p><p data-cms-editable>As regiões mais expostas consomem o revelador mais rapidamente e passam a sofrer uma exaustão localizada. Sua revelação desacelera enquanto regiões menos expostas continuam avançando. Com isso conseguimos alterar a relação entre os valores tonais e produzir um efeito compensador no contraste. Portanto, deixar de agitar não é apenas outra maneira de “revelar menos”. Estamos fazendo regiões diferentes da imagem avançarem em velocidades diferentes.</p><p data-cms-editable>A temperatura, por outro lado, não é uma escala linear. Sua variação é de ordem exponencial, como as aberturas do diafragma. Então a diferença entre um grau para mais ou para menos tem efeitos muito mais acentuados que os demais parâmetros.</p><p data-cms-editable>Temperatura é sinônimo de energia: aumentar acelera o movimento das partículas e automaticamente a velocidade do processo; reduzir tem o efeito inverso até um limite em que se torna tão lento que praticamente cessa. Na prática, isso significa que uma pequena variação de temperatura pode alterar bastante o ponto alcançado pela revelação mesmo quando mantemos concentração, tempo e agitação iguais. No caso de emulsões à base de gelatina, é importante lembrar que uma temperatura muito alta pode amolecer ou dissolver a gelatina e ocasionar descolamentos e outros defeitos nas emulsões.</p><p data-cms-editable>Precisamos ter cuidado quando falamos em expansão ou contração pelo Sistema de Zonas ao ajustar os diversos parâmetros da primeira revelação.</p><p data-cms-editable>Se quero observar o efeito do tempo, preciso manter concentração, temperatura e agitação padronizadas. Se altero dois ou três desses parâmetros ao mesmo tempo, já não estou apenas prolongando a mesma revelação; estou criando outra condição de processamento do filme.</p><p data-cms-editable>Foi exatamente o que ocorreu nas duas chapas.</p></div></div>
  <figure data-private-media-slot="parametros-revelacao" data-private-media-alt="Infográfico ilustrado: como concentração, tempo, agitação e temperatura atuam de maneiras diferentes sobre a primeira revelação"></figure>
</section>

<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="a2-sete-minutos" data-cms-section-name="Hipótese dos sete minutos">
  <p class="section-label" data-cms-editable>E os 7 minutos?</p>
  <div class="statement-grid"><div><h2 data-cms-editable>Uma hipótese de desenvolvimento normal</h2></div><div class="statement-copy"><p data-cms-editable>O comportamento da primeira chapa sugere que 7 minutos já representem uma revelação estendida para 10 ml de Parodinal + 550 ml de água, a 26 °C e com a agitação que usamos.</p><p data-cms-editable>Minha hipótese neste momento é que o nosso desenvolvimento normal esteja mais próximo de 5 minutos.</p><p data-cms-editable>Ainda preciso confirmar isso, mas estabelecer esse ponto é importante porque expansão e contração só fazem sentido quando existe um normal a partir do qual podemos comparar.</p><p data-cms-editable>Se os cinco minutos se confirmarem como esse normal, sete minutos representam aproximadamente 40% a mais de tempo de revelação.</p><p data-cms-editable>Isso não significa que todos os valores da imagem avancem 40%. A resposta não é linear e cada região parte de uma quantidade diferente de exposição. Significa apenas que deixamos o revelador atuar durante mais tempo e precisamos observar onde esse tempo adicional realmente produziu separação e onde já havia pouco a ganhar.</p><p data-cms-editable>É exatamente aqui que a lógica do Sistema de Zonas volta a ser útil. A exposição determina principalmente onde posicionamos os valores baixos; a revelação nos permite trabalhar a distribuição dos valores que tiveram exposição suficiente para continuar respondendo.</p><p data-cms-editable>No positivo direto a imagem ainda passará pela inversão, mas essa relação não desaparece. Se diferentes regiões de sombra receberam energia insuficiente para permanecer separadas na primeira revelação, não será a segunda etapa do processo que inventará essa separação.</p><p data-cms-editable>Ao mesmo tempo, simplesmente aumentar a exposição também não resolve tudo. O que estamos procurando é um positivo em que as sombras cheguem à densidade necessária, próximas de Z1, as altas luzes possam alcançar a transparência da base e exista informação tonal útil entre esses dois extremos.</p></div></div>
</section>

<section class="section" data-layout-background="inverse" data-layout-space="l" data-cms-section="a2-resultados" data-cms-section-name="O que as duas fotografias disseram">
  <p class="section-label" data-cms-editable>O que as duas fotografias nos disseram</p>
  <div class="statement-grid"><div><h2 data-cms-editable>EI é uma decisão de exposição</h2></div><div class="statement-copy"><p data-cms-editable>Na primeira fotografia usamos EI 200 e 10 ml de Parodinal. Na segunda retiramos uma parada de exposição e dobramos a concentração do revelador.</p><p data-cms-editable>A revelação mais ativa conseguiu compensar parte dessa mudança, mas não reproduziu nas regiões mais baixas a mesma separação que encontramos na primeira chapa.</p><p data-cms-editable>Isso não torna EI 400 certo ou errado.</p><p data-cms-editable>O EI não é um número escondido dentro do filme que precisamos descobrir. É uma decisão sobre quanto queremos expô-lo e, consequentemente, sobre onde queremos posicionar a escala que a cena nos oferece.</p><p data-cms-editable>Para os próximos testes, considero mais útil estabelecer primeiro uma condição de referência:</p><p class="technical-note" data-cms-editable>EI 200 · 10 ml de Parodinal + 550 ml de água · 26 °C · 5 minutos · agitação leve</p><p data-cms-editable>Não como uma receita definitiva, mas como um possível desenvolvimento normal.</p><p data-cms-editable>A partir dele podemos alterar conscientemente uma coisa de cada vez e observar a consequência: aumentar ou diminuir exposição, prolongar o tempo, mudar concentração, temperatura ou agitação.</p></div></div>
</section>

<section id="caderno-aula-3" class="format" data-cms-section="caderno-aula-3" data-cms-section-name="Aula 3 — Revisão de resultados">
  <div class="format-inner"><div class="format-heading"><div><p class="section-label" data-cms-editable>Aula 03</p><h2 data-cms-editable>Revisão de resultados</h2></div><p data-cms-editable>O conteúdo adicional desta aula só entra quando existir uma fonte editorial real.</p></div></div>
</section>
HTML;

    $doc=['version'=>2,'theme'=>'auto','meta'=>[
        'title'=>'Positivo direto em filme de raio-X',
        'description'=>'Material técnico do workshop sobre filme de raio-X, exposição, revelação e produção de positivos diretos.'
    ],'html'=>$html];
    $encoded=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $now=gmdate('c');
    $revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $db->prepare("UPDATE cms_pages SET title=?,nav_title=?,draft_document_json=?,published_document_json=?,draft_revision=?,published_revision=?,draft_updated_at=?,published_at=?,updated_at=?,access_level='enrolled',show_in_nav=0 WHERE id=?")
        ->execute(['Positivo direto em filme de raio-X','Positivo direto em filme de raio-X',$encoded,$encoded,$revision,$revision,$now,$now,$now,(int)$page['id']]);

    $hasMap=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='course_page_sections'")->fetchColumn();
    $hasLessons=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='course_lessons'")->fetchColumn();
    if(!$hasMap||!$hasLessons)return;

    $lessonQ=$db->prepare('SELECT id,lesson_key FROM course_lessons WHERE activity_id=?');
    $lessonQ->execute([(int)$page['activity_id']]);$lessons=[];
    foreach($lessonQ->fetchAll(PDO::FETCH_ASSOC) as $row)$lessons[(string)$row['lesson_key']]=(int)$row['id'];

    $lesson1Sections=['caderno-aula-1','a1-abertura','a1-filme','a1-positivo','a1-energia','a1-reciprocidade','a1-energia-reciprocidade','a1-parodinal-receita','a1-caffenol-receita','a1-branqueadores-receitas','a1-materiais','a1-testes'];
    $lesson2Sections=['caderno-aula-2','a2-duas-chapas','a2-ei','a2-imagem-latente','a2-revelador','a2-parodinal-quimica','a2-caffenol-quimica','a2-negativo-positivo','a2-branqueamento','a2-segunda-revelacao','a2-parametros','a2-sete-minutos','a2-resultados'];
    $lesson3Sections=['caderno-aula-3'];

    $db->prepare('DELETE FROM course_page_sections WHERE page_id=?')->execute([(int)$page['id']]);
    $map=$db->prepare('INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?)');
    foreach(['aula-1'=>$lesson1Sections,'aula-2'=>$lesson2Sections,'aula-3'=>$lesson3Sections] as $lessonKey=>$sections){
        if(!isset($lessons[$lessonKey]))continue;
        foreach($sections as $sectionKey)$map->execute([(int)$page['id'],$sectionKey,$lessons[$lessonKey],$now,$now]);
    }
};
