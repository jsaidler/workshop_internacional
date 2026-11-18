<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $html=<<<'HTML'
<section class="hero hero--copy-only" data-cms-section="hero" data-cms-section-name="Início">
  <div class="hero-copy">
    <div class="hero-top">
      <p class="label" data-cms-editable>Formação on-line · primeira turma em preparação</p>
      <h1 data-cms-editable><span>Fotografia experimental</span><span>em grande formato</span></h1>
      <p class="hero-intro" data-cms-editable>Uma formação de oito encontros para quem quer entrar no grande formato sem começar comprando uma câmera, objetiva, chassis e uma estrutura inteira de laboratório. Você constrói uma câmera-laboratório, aprende a produzir negativos em folha e usa esses negativos para chegar a positivos físicos por contato.</p>
    </div>
    <div class="hero-bottom">
      <dl class="hero-facts">
        <div><dt>Formato</dt><dd data-cms-editable>On-line e ao vivo</dd></div>
        <div><dt>Encontros</dt><dd data-cms-editable>8 encontros de 1h30</dd></div>
        <div><dt>Carga ao vivo</dt><dd data-cms-editable>12 horas</dd></div>
        <div><dt>Primeira turma</dt><dd data-cms-editable>R$ 1.290 no Pix</dd></div>
      </dl>
      <div class="cta-row">
        <a class="button button-primary" href="#interesse" data-cms-editable>Quero receber a abertura da turma <span aria-hidden="true">↘</span></a>
        <p class="microcopy" data-cms-editable>Sem pagamento agora. A data da primeira turma ainda será anunciada.</p>
      </div>
    </div>
  </div>
</section>

<section class="format" data-cms-section="diagnosis" data-cms-section-name="O ponto de partida">
  <div class="format-inner">
    <p class="section-label" data-cms-editable>01 / O ponto de partida</p>
    <div class="format-heading">
      <h2 data-cms-editable>Você quer experimentar grande formato — mas faz sentido comprar todo o sistema antes de descobrir como quer trabalhar?</h2>
      <p data-cms-editable>Grande formato costuma aparecer acompanhado de uma lista de compras: câmera, objetiva, chassis, acessórios e uma estrutura separada para processar o material. A proposta desta formação parte do caminho oposto: compreender o sistema construindo a ferramenta e usando cada etapa da imagem como parte do aprendizado.</p>
    </div>
    <div class="format-grid cols-3">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Quero entrar no grande formato</h3><p data-cms-editable>Mas não quero começar investindo uma fortuna em equipamento para só depois descobrir se esse caminho faz sentido para mim.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Quero entender a câmera</h3><p data-cms-editable>Não apenas aprender uma sequência de comandos em um equipamento pronto, mas compreender por que cada parte existe e o que ela faz.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Quero chegar a uma imagem física</h3><p data-cms-editable>Do material dentro da câmera ao negativo e, depois, a positivos produzidos por contato com processos que eu possa continuar explorando.</p></article>
    </div>
  </div>
</section>

<section class="cms-support cms-support--single" data-cms-section="camera-lab" data-cms-section-name="A câmera-laboratório">
  <div class="cms-support-inner">
    <div>
      <p class="section-label" data-cms-editable>02 / A ferramenta</p>
      <h2 data-cms-editable>Uma câmera que já nasce como laboratório.</h2>
      <p data-cms-editable>O espaço de manipulação e processamento não é um acessório acrescentado depois. Ele faz parte do projeto desde o primeiro desenho, junto da câmara escura, do plano do material e da abertura pinhole.</p>
      <p data-cms-editable>A abertura pinhole permite começar pelo grande formato sem depender de uma objetiva. Isso deixa expostos os fundamentos: distância entre abertura e plano sensível, campo de imagem, abertura efetiva, tempo de exposição e comportamento do material.</p>
      <p class="technical-note" data-cms-editable>Você recebe duas soluções construtivas e escolhe qual quer executar: uma baseada em materiais acessíveis e outra em madeira. A função fotográfica e a lógica de câmera-laboratório são as mesmas.</p>
    </div>
  </div>
</section>

<section class="format" data-cms-section="journey" data-cms-section-name="Do objeto ao positivo">
  <div class="format-inner">
    <p class="section-label" data-cms-editable>03 / O ciclo fotográfico</p>
    <div class="format-heading">
      <h2 data-cms-editable>O curso percorre o sistema inteiro, não apenas a construção da câmera.</h2>
      <p data-cms-editable>A câmera é o ponto de entrada. O objetivo é compreender como uma imagem em grande formato é produzida e transformada em um objeto positivo físico.</p>
    </div>
    <div class="format-grid">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Projetar e construir</h3><p data-cms-editable>Formação da imagem, plano sensível, distância, pinhole, vedação, acesso ao material e espaço de processamento entram no mesmo sistema.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Fotografar</h3><p data-cms-editable>Composição, carregamento, exposição, tempos longos e reciprocidade aparecem quando passam a ser necessários para operar a câmera que você construiu.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Produzir o negativo</h3><p data-cms-editable>Você aprende a processar e ler um negativo em folha, inclusive usando filme de raio-X como material acessível para experimentar grande formato.</p></article>
      <article class="format-card"><span class="number">04</span><h3 data-cms-editable>Produzir o positivo</h3><p data-cms-editable>O negativo passa a funcionar como matriz para cópias por contato em processos diferentes, permitindo comparar como o mesmo registro se transforma em objetos distintos.</p></article>
    </div>
  </div>
</section>

<section class="section process" data-cms-section="meetings" data-cms-section-name="Os oito encontros">
  <div class="process-copy cms-process-wide">
    <p class="section-label" data-cms-editable>04 / Os oito encontros</p>
    <h2 data-cms-editable>O intervalo entre as aulas faz parte do curso.</h2>
    <p data-cms-editable>Construir, fotografar, expor ao sol, processar, errar e voltar com o resultado exige tempo. Os encontros foram pensados para que a experiência aconteça entre uma aula e outra, e não para comprimir tudo em uma demonstração.</p>
    <div class="process-list">
      <article class="process-item"><span>01</span><h3 data-cms-editable>O sistema e o projeto</h3><p data-cms-editable>Câmera escura, plano sensível, formato, pinhole, distância, campo de imagem e leitura do projeto da câmera-laboratório.</p></article>
      <article class="process-item"><span>02</span><h3 data-cms-editable>Construção da câmera-laboratório</h3><p data-cms-editable>Estrutura, vedação, carregamento, área protegida de manipulação e soluções construtivas nas duas versões do projeto.</p></article>
      <article class="process-item"><span>03</span><h3 data-cms-editable>Fazer a câmera fotografar</h3><p data-cms-editable>Enquadramento, exposição, tempos longos, reciprocidade e planejamento das primeiras fotografias.</p></article>
      <article class="process-item"><span>04</span><h3 data-cms-editable>O negativo</h3><p data-cms-editable>Processamento, leitura do negativo e relação entre exposição, contraste, densidade e resultado.</p></article>
      <article class="process-item"><span>05</span><h3 data-cms-editable>Do negativo ao positivo</h3><p data-cms-editable>Cópia por contato, escala 1:1, densidade da matriz e o que muda quando a imagem passa para outro suporte fotossensível.</p></article>
      <article class="process-item"><span>06</span><h3 data-cms-editable>Cianotipia</h3><p data-cms-editable>Preparação, sensibilização, contato, exposição e processamento de uma cópia positiva a partir do negativo produzido no curso.</p></article>
      <article class="process-item"><span>07</span><h3 data-cms-editable>Imagem em clorofila</h3><p data-cms-editable>Impressão fotográfica diretamente em folhas de plantas, exposição prolongada e resposta do material vivo à densidade do negativo e à luz.</p></article>
      <article class="process-item"><span>08</span><h3 data-cms-editable>Resultados e continuidade</h3><p data-cms-editable>Comparação dos processos, revisão dos problemas encontrados, ajustes da câmera e definição de novos experimentos para continuar o trabalho de forma autônoma.</p></article>
    </div>
  </div>
</section>

<section class="cms-proof" data-cms-section="positive-processes" data-cms-section-name="Processos de positivação">
  <div class="cms-proof-inner">
    <p class="section-label" data-cms-editable>05 / Processos de positivação</p>
    <div class="cms-proof-grid">
      <h2 data-cms-editable>O objetivo não é colecionar técnicas. É entender o negativo como matriz.</h2>
      <div>
        <p data-cms-editable>Na cianotipia, o contato permite acompanhar de forma relativamente previsível a passagem do negativo para outro suporte. Na impressão em clorofila, a mesma lógica encontra um material vivo, tempos muito mais longos e uma resposta menos controlável.</p>
        <p data-cms-editable>Colocar os dois processos lado a lado torna visível uma ideia central da fotografia química: a imagem final não depende apenas do que aconteceu dentro da câmera. O suporte e o processo de positivação também participam do resultado.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" data-cms-section="construction" data-cms-section-name="Duas construções">
  <p class="section-label" data-cms-editable>06 / Duas construções</p>
  <div class="statement-grid">
    <h2 data-cms-editable>O mesmo sistema pode ser resolvido com recursos diferentes.</h2>
    <div class="statement-copy">
      <p data-cms-editable>Os dois projetos pertencem ao curso. Você escolhe qual construir e pode consultar o outro para compreender soluções diferentes para o mesmo problema fotográfico.</p>
      <p data-cms-editable><strong>Materiais acessíveis.</strong> Uma solução pensada para quem quer começar sem estrutura de oficina, usando materiais simples e ferramentas comuns.</p>
      <p data-cms-editable><strong>Madeira.</strong> Uma construção rígida e permanente para quem possui ferramentas ou prefere encomendar as peças já cortadas e fazer a montagem.</p>
    </div>
  </div>
</section>

<section class="cms-proof" data-cms-section="offer" data-cms-section-name="Formato e valor">
  <div class="cms-proof-inner">
    <p class="section-label" data-cms-editable>07 / Formato e valor</p>
    <div class="cms-proof-grid">
      <h2 data-cms-editable>Oito encontros ao vivo para construir, testar e voltar com resultados.</h2>
      <div>
        <p data-cms-editable>São 12 horas de encontro ao vivo, distribuídas em oito aulas de aproximadamente 1h30. O curso inclui os projetos construtivos, material de consulta e acompanhamento dos resultados produzidos entre os encontros.</p>
        <dl class="recognition">
          <div><dt data-cms-editable>Primeira turma</dt><dd data-cms-editable>R$ 1.290 no Pix</dd></div>
          <div><dt data-cms-editable>Valor regular</dt><dd data-cms-editable>R$ 1.490</dd></div>
          <div><dt data-cms-editable>Formato</dt><dd data-cms-editable>On-line e ao vivo</dd></div>
          <div><dt data-cms-editable>Carga ao vivo</dt><dd data-cms-editable>12 horas</dd></div>
        </dl>
        <div class="section-cta"><a class="button button-primary" href="#interesse" data-cms-editable>Quero receber a abertura da turma <span aria-hidden="true">↘</span></a></div>
      </div>
    </div>
  </div>
</section>

<section class="section about section-compact" data-cms-section="about" data-cms-section-name="João Saidler">
  <div class="about-copy">
    <p class="section-label" data-cms-editable>08 / Quem conduz</p>
    <h2 data-cms-editable>João Saidler</h2>
    <p class="about-lead" data-cms-editable>Fotógrafo, pesquisador independente e construtor de equipamentos fotográficos desde 2018.</p>
    <p data-cms-editable>Meu trabalho em grande formato foi desenvolvido junto das ferramentas que uso. Ao longo da pesquisa projetei e construí câmeras, obturadores, diafragmas, tanques e sistemas de processamento para resolver problemas concretos das imagens e dos processos que eu queria realizar.</p>
    <p data-cms-editable>A formação parte dessa experiência, mas não para ensinar a copiar uma câmera minha. A proposta é usar uma câmera-laboratório simples como maneira de tornar visível o sistema fotográfico e dar ao aluno autonomia para continuar experimentando depois do curso.</p>
    <dl class="recognition">
      <div><dt data-cms-editable>2022</dt><dd data-cms-editable>Siena Creative Photo Awards — Commended</dd></div>
      <div><dt data-cms-editable>2023</dt><dd data-cms-editable>ArtLimited Awards — Runner-up, Portraiture</dd></div>
      <div><dt data-cms-editable>2025</dt><dd data-cms-editable>Siena Creative Photo Awards — Highly Commended, People</dd></div>
      <div><dt data-cms-editable>Publicações</dt><dd data-cms-editable>The Black &amp; White Book — GOD Publishing · STRKNG Editors' Selection</dd></div>
    </dl>
  </div>
  <figure class="media-figure about-image" data-cms-image-block><div class="media-area"><img src="/assets/media/joao-portrait.webp" alt="João Saidler com equipamento fotográfico" data-cms-image></div><figcaption class="media-caption" data-cms-editable>João Saidler · Fotógrafo e pesquisador independente</figcaption></figure>
</section>

<section class="format" data-cms-section="faq" data-cms-section-name="Perguntas frequentes">
  <div class="format-inner section-compact">
    <p class="section-label" data-cms-editable>09 / Perguntas frequentes</p>
    <div class="format-grid cols-3">
      <article class="format-card"><h3 data-cms-editable>Preciso já ter uma câmera de grande formato?</h3><p data-cms-editable>Não. A proposta é justamente começar construindo a câmera que será usada durante a formação.</p></article>
      <article class="format-card"><h3 data-cms-editable>Preciso dominar fotografia analógica?</h3><p data-cms-editable>Não. O curso introduz os princípios necessários à medida que eles passam a ser necessários para construir, expor e processar.</p></article>
      <article class="format-card"><h3 data-cms-editable>Preciso ter ferramentas de oficina?</h3><p data-cms-editable>Não para a versão de materiais acessíveis. Na versão em madeira, quem não quiser fazer os cortes pode encomendar as peças nas medidas do projeto e realizar a montagem.</p></article>
      <article class="format-card"><h3 data-cms-editable>O curso termina no negativo?</h3><p data-cms-editable>Não. O negativo é usado como matriz para produzir positivos físicos por contato, incluindo cianotipia e impressão em clorofila sobre folhas.</p></article>
      <article class="format-card"><h3 data-cms-editable>A câmera é apenas um exercício didático?</h3><p data-cms-editable>Não. Ela é um sistema fotográfico funcional, pensado desde o início como câmera-laboratório para continuar sendo usado depois do curso.</p></article>
      <article class="format-card"><h3 data-cms-editable>Quando será a primeira turma?</h3><p data-cms-editable>A data ainda será anunciada. A lista abaixo é usada para avisar primeiro quem já demonstrou interesse.</p></article>
    </div>
  </div>
</section>

<section class="interest cms-form-section" id="interesse" data-cms-section="interest" data-cms-section-name="Lista de interesse">
  <div class="interest-inner section-compact">
    <div class="interest-intro">
      <p class="section-label" data-cms-editable>10 / Primeira turma</p>
      <h2 data-cms-editable>Se esse é o tipo de entrada no grande formato que você procura, deixe seu contato.</h2>
      <p data-cms-editable>Quero entender também o que hoje está impedindo você de começar. Isso ajuda a organizar a primeira turma sem presumir que todo mundo chega com o mesmo problema.</p>
    </div>
    <div data-cms-form-key="pinhole-interest"></div>
  </div>
</section>
HTML;

    $now=gmdate('c');
    $pages=$db->query("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='pinhole-lambe-lambe' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    foreach($pages as $page){
        $draft=json_decode((string)$page['draft_document_json'],true);
        if(!is_array($draft))continue;
        $draftHtml=(string)($draft['html']??'');
        if(!str_contains($draftHtml,'Fotografia experimental')||!str_contains($draftHtml,'data-cms-section="camera-lab"'))continue;

        $draft['html']=$html;
        $draftJson=json_encode($draft,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

        $publishedOut=$page['published_document_json'];
        $publishedChanged=false;
        if(is_string($publishedOut)&&$publishedOut!==''){
            $published=json_decode($publishedOut,true);
            if(is_array($published)){
                $publishedHtml=(string)($published['html']??'');
                if(str_contains($publishedHtml,'Fotografia experimental')&&str_contains($publishedHtml,'data-cms-section="camera-lab"')){
                    $published['html']=$html;
                    $publishedOut=json_encode($published,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
                    $publishedChanged=true;
                }
            }
        }

        $q=$db->prepare('UPDATE cms_pages SET draft_document_json=?,published_document_json=?,draft_revision=draft_revision+1,published_revision=CASE WHEN ? THEN COALESCE(published_revision,draft_revision)+1 ELSE published_revision END,draft_updated_at=?,published_at=CASE WHEN ? THEN ? ELSE published_at END,updated_at=? WHERE id=?');
        $q->execute([$draftJson,$publishedOut,$publishedChanged?1:0,$now,$publishedChanged?1:0,$now,$now,(int)$page['id']]);
    }
};
