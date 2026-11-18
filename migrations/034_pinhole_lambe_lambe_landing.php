<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    $hasForms=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_forms'")->fetchColumn();
    $hasActivities=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='activities'")->fetchColumn();
    if(!$hasPages||!$hasForms||!$hasActivities)return;

    $hasSeo=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_page_seo'")->fetchColumn();
    $now=gmdate('c');
    $activities=$db->query('SELECT id FROM activities WHERE is_root=1 ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);

    $formSchema=[
        'version'=>1,
        'submitLabel'=>'Quero receber as informações',
        'successTitle'=>'Interesse registrado',
        'successMessage'=>'Vou te avisar quando a primeira turma estiver pronta para abrir.',
        'fields'=>[
            ['id'=>'name','type'=>'text','label'=>'Nome','required'=>true,'autocomplete'=>'name','width'=>'half'],
            ['id'=>'email','type'=>'email','label'=>'E-mail','required'=>true,'autocomplete'=>'email','width'=>'half'],
            ['id'=>'contact','type'=>'text','label'=>'Instagram ou WhatsApp (opcional)','required'=>false,'width'=>'full'],
            [
                'id'=>'main_interest',
                'type'=>'checkbox-group',
                'label'=>'O que mais te interessa nessa proposta? (opcional)',
                'required'=>false,
                'width'=>'full',
                'options'=>[
                    ['value'=>'build','label'=>'Construir a Pinhole Lambe-Lambe'],
                    ['value'=>'use','label'=>'Fotografar com a câmera depois'],
                    ['value'=>'camera-course','label'=>'Aprofundar a construção de câmeras'],
                    ['value'=>'positive-course','label'=>'Usar a câmera com positivo direto em filme de raio-X'],
                ],
            ],
            ['id'=>'consent','type'=>'consent','label'=>'Quero receber informações sobre a primeira turma desta oficina.','required'=>true,'width'=>'full'],
        ],
    ];
    $formJson=json_encode($formSchema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

    $html=<<<'HTML'
<section class="hero" data-cms-section="hero" data-cms-section-name="Início">
  <div class="hero-copy">
    <div class="hero-top">
      <p class="label" data-cms-editable>Nova oficina · Primeira turma em preparação</p>
      <h1 data-cms-editable><span>Pinhole</span><span>Lambe-Lambe</span><span>Câmera-laboratório</span></h1>
      <p class="hero-intro" data-cms-editable>Uma oficina on-line e ao vivo para acompanhar a construção de uma câmera-laboratório autoral com papel paraná, alumínio de lata, fita isolante e soluções simples de montagem.</p>
    </div>
    <div class="hero-bottom">
      <dl class="hero-facts">
        <div><dt>Formato</dt><dd data-cms-editable>1 encontro ao vivo</dd></div>
        <div><dt>Duração</dt><dd data-cms-editable>2 a 3 horas</dd></div>
        <div><dt>Construção</dt><dd data-cms-editable>Sem ferramentas de oficina</dd></div>
        <div><dt>Projeto</dt><dd data-cms-editable>PDF incluído</dd></div>
      </dl>
      <div class="cta-row"><a class="button button-primary" href="#interesse" data-cms-editable>Quero saber da primeira turma <span aria-hidden="true">↘</span></a><p class="microcopy" data-cms-editable>Sem pagamento agora. A primeira turma ainda está sendo preparada.</p></div>
    </div>
  </div>
  <figure class="hero-image" data-cms-image-block>
    <div class="cms-media-placeholder" data-cms-image-placeholder><span>Imagem ou vídeo do protótipo entra aqui</span></div>
    <figcaption><span data-cms-editable>Projeto Pinhole Lambe-Lambe</span><span data-cms-editable>Protótipo em desenvolvimento</span></figcaption>
  </figure>
</section>

<section class="section" data-cms-section="proposal" data-cms-section-name="A proposta">
  <p class="section-label" data-cms-editable>01 / A proposta</p>
  <div class="statement-grid">
    <h2 data-cms-editable>A simplicidade está nos materiais. O projeto é que faz o trabalho.</h2>
    <div class="statement-copy">
      <p data-cms-editable>Fazer uma pinhole básica é simples e há milhares de projetos disponíveis. A proposta aqui é outra: usar materiais quase banais para construir uma pequena câmera-laboratório com uma lógica de operação mais interessante.</p>
      <p data-cms-editable>O projeto foi pensado para ser montado praticamente como um trabalho de papelaria. Papel paraná, alumínio de lata e fita isolante resolvem estrutura, mecanismos e vedação sem depender de marcenaria, serralheria ou ferramentas de oficina.</p>
      <p class="technical-note" data-cms-editable>A câmera foi concebida para poder ser construída em um único dia. Durante o encontro, cada pessoa decide se monta junto ou se acompanha a demonstração e constrói depois.</p>
    </div>
  </div>
</section>

<section class="cms-support" data-cms-section="object" data-cms-section-name="A câmera">
  <div class="cms-support-inner">
    <div>
      <p class="section-label" data-cms-editable>02 / O que vamos construir</p>
      <h2 data-cms-editable>Uma Pinhole Lambe-Lambe funcional, não uma experiência escolar de câmera escura.</h2>
      <p data-cms-editable>O interesse da oficina está nas soluções usadas para transformar materiais simples em um equipamento capaz de trabalhar como câmera e pequeno laboratório integrado.</p>
      <p class="technical-note" data-cms-editable>A imagem e o vídeo do protótipo serão adicionados aqui quando o projeto físico estiver finalizado.</p>
    </div>
    <div class="cms-media-placeholder" data-cms-image-placeholder><span>Imagem detalhada da câmera entra aqui</span></div>
  </div>
</section>

<section class="format" data-cms-section="method" data-cms-section-name="Como funciona">
  <div class="format-inner">
    <p class="section-label" data-cms-editable>03 / Como funciona</p>
    <div class="format-heading"><h2 data-cms-editable>Você pode construir junto ou usar a aula como guia para montar depois.</h2><p data-cms-editable>O encontro não depende de todos trabalharem exatamente no mesmo ritmo.</p></div>
    <div class="format-grid">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Antes do encontro</h3><p data-cms-editable>Você recebe um PDF com a lista de materiais e o projeto para imprimir. As peças são coladas no papel paraná e recortadas seguindo os moldes.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Etapa por etapa</h3><p data-cms-editable>Eu mostro os detalhes de cada fase da construção e uso modelos já preparados em diferentes estágios para avançarmos sem depender do tempo de corte e montagem.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>No seu ritmo</h3><p data-cms-editable>Quem quiser pode acompanhar construindo. Quem preferir pode observar, tirar dúvidas e usar o projeto para montar a câmera depois.</p></article>
      <article class="format-card"><span class="number">04</span><h3 data-cms-editable>Operação</h3><p data-cms-editable>Depois da construção, eu apresento como a câmera e o laboratório integrado são usados na prática, sem realizar fotografia ou revelação durante a oficina.</p></article>
    </div>
  </div>
</section>

<section class="section process" data-cms-section="content" data-cms-section-name="O encontro">
  <div class="process-copy cms-process-wide">
    <p class="section-label" data-cms-editable>04 / O encontro</p>
    <h2 data-cms-editable>Construção primeiro. A teoria entra apenas onde ajuda a entender e operar a câmera.</h2>
    <div class="process-list">
      <article class="process-item"><span>01</span><h3 data-cms-editable>Princípio da pinhole</h3><p data-cms-editable>Uma introdução curta à formação da imagem sem lente e às decisões básicas que definem esse tipo de câmera.</p></article>
      <article class="process-item"><span>02</span><h3 data-cms-editable>O que esperar da imagem</h3><p data-cms-editable>Comportamento da câmera, tempos longos e uma introdução prática à falha de reciprocidade.</p></article>
      <article class="process-item"><span>03</span><h3 data-cms-editable>Construção</h3><p data-cms-editable>Estrutura, vedação, montagem e soluções funcionais do projeto mostradas em detalhes.</p></article>
      <article class="process-item"><span>04</span><h3 data-cms-editable>Câmera + laboratório</h3><p data-cms-editable>Como carregar, operar e usar a lógica lambe-lambe integrada ao equipamento construído.</p></article>
    </div>
  </div>
</section>

<section class="apparatus" data-cms-section="project" data-cms-section-name="Projeto em PDF">
  <div class="apparatus-copy">
    <p class="section-label" data-cms-editable>05 / O projeto</p>
    <h2 data-cms-editable>O PDF é o projeto da câmera, não um resumo da aula.</h2>
    <p data-cms-editable>Ele reúne a lista de materiais e as peças para imprimir, colar e recortar. A ideia é que o arquivo continue útil depois do encontro: você poderá voltar ao desenho para reconstruir, reparar ou adaptar a câmera.</p>
    <p class="technical-note" data-cms-editable>Os materiais principais são papel paraná, lata de refrigerante e fita isolante. Ímãs poderão aparecer no projeto final se ajudarem a resolver alguma função de maneira simples.</p>
  </div>
  <div class="apparatus-media"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Prévia do PDF do projeto entra aqui</span></div></div>
</section>

<section class="cms-proof" data-cms-section="scope" data-cms-section-name="Escopo">
  <div class="cms-proof-inner">
    <p class="section-label" data-cms-editable>06 / O que acontece — e o que não acontece</p>
    <div class="cms-proof-grid">
      <h2 data-cms-editable>A oficina termina com a compreensão da construção e da operação da câmera.</h2>
      <div><p data-cms-editable>Eu não vou fotografar nem revelar durante este encontro. Isso exigiria deslocar o foco para exposição, material sensível, química e processamento — assuntos que pertencem a outra etapa.</p><p data-cms-editable>Aqui o objetivo é mostrar integralmente como a Pinhole Lambe-Lambe é construída, explicar o necessário para entender seu comportamento e deixar claro como câmera e laboratório são operados.</p><p class="technical-note" data-cms-editable>Para participar da oficina, portanto, você não precisa comprar filme, químicos ou montar um laboratório antes do encontro.</p></div>
    </div>
  </div>
</section>

<section class="format" data-cms-section="audience" data-cms-section-name="Para quem é">
  <div class="format-inner">
    <p class="section-label" data-cms-editable>07 / Para quem é</p>
    <div class="format-heading"><h2 data-cms-editable>Uma porta de entrada pela construção de um objeto fotográfico de verdade.</h2><p data-cms-editable>Não é preciso já possuir uma câmera nem uma oficina equipada.</p></div>
    <div class="format-grid">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Fotografia pinhole</h3><p data-cms-editable>Para quem quer explorar a formação da imagem por um caminho mais elaborado do que a pinhole escolar convencional.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Construção de câmeras</h3><p data-cms-editable>Para quem se interessa pela inteligência das soluções e por transformar materiais simples em equipamento fotográfico funcional.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Fotografia química</h3><p data-cms-editable>Para quem quer chegar à fotografia analógica por uma câmera que já nasce pensada para trabalhar junto de um pequeno laboratório.</p></article>
      <article class="format-card"><span class="number">04</span><h3 data-cms-editable>Sem estrutura de oficina</h3><p data-cms-editable>Para quem quer construir sem depender de madeira, metal, máquinas ou ferramentas especializadas.</p></article>
    </div>
  </div>
</section>

<section class="section" data-cms-section="continuity" data-cms-section-name="Depois da oficina">
  <p class="section-label" data-cms-editable>08 / Depois da oficina</p>
  <div class="statement-grid">
    <h2 data-cms-editable>A oficina funciona sozinha. Se você quiser continuar, há dois caminhos.</h2>
    <div class="statement-copy">
      <p data-cms-editable><strong>Positivo Direto em Filme de Raio-X.</strong> Para quem quiser usar a câmera como ponto de partida para trabalhar a imagem e o processo químico.</p>
      <p data-cms-editable><strong>Construção de Câmeras.</strong> Para quem se interessar principalmente pelo projeto, mecanismos e desenvolvimento de equipamentos e quiser aprofundar isso no curso completo, quando ele estiver disponível.</p>
      <p class="technical-note" data-cms-editable>O valor pago nesta oficina poderá ser aproveitado integralmente como crédito em um desses dois cursos. Regras e prazo serão informados quando a primeira turma abrir.</p>
    </div>
  </div>
</section>

<section class="section about" data-cms-section="about" data-cms-section-name="João Saidler">
  <div class="about-copy">
    <p class="section-label" data-cms-editable>09 / Quem conduz</p>
    <h2 data-cms-editable>João Saidler</h2>
    <p class="about-lead" data-cms-editable>Fotógrafo e pesquisador independente de fotografia analógica e química.</p>
    <p data-cms-editable>Além do trabalho com positivo direto em filme de raio-X, projeto e construo câmeras, obturadores, tanques e outros sistemas quando a fotografia exige uma ferramenta que ainda não existe para o que quero fazer.</p>
    <p data-cms-editable>Esta oficina nasce dessa prática, mas deliberadamente reduz a infraestrutura: o desafio é chegar a uma câmera-laboratório interessante usando materiais acessíveis e soluções simples.</p>
  </div>
  <figure class="media-figure about-image" data-cms-image-block><div class="media-area"><img src="/assets/media/joao-portrait.webp" alt="Fotógrafo João Saidler" data-cms-image></div><figcaption class="media-caption" data-cms-editable>João Saidler · Fotógrafo e pesquisador independente</figcaption></figure>
</section>

<section class="format" data-cms-section="faq" data-cms-section-name="Perguntas frequentes">
  <div class="format-inner">
    <p class="section-label" data-cms-editable>10 / Perguntas frequentes</p>
    <div class="format-heading"><h2 data-cms-editable>O que vale saber antes de entrar na lista.</h2></div>
    <div class="format-grid">
      <article class="format-card"><h3 data-cms-editable>Preciso montar a câmera durante a aula?</h3><p data-cms-editable>Não. Você pode montar junto ou acompanhar a demonstração e usar o PDF para construir depois, no seu ritmo.</p></article>
      <article class="format-card"><h3 data-cms-editable>Preciso entender fotografia?</h3><p data-cms-editable>Não é necessário domínio técnico avançado. Os princípios necessários para compreender e operar a câmera serão apresentados de forma introdutória.</p></article>
      <article class="format-card"><h3 data-cms-editable>Preciso de ferramentas?</h3><p data-cms-editable>Não de ferramentas de oficina. A construção foi pensada como trabalho de papelaria, com impressão, colagem, corte e montagem de materiais simples.</p></article>
      <article class="format-card"><h3 data-cms-editable>Vamos fotografar ou revelar?</h3><p data-cms-editable>Não. O encontro é dedicado à construção, ao funcionamento e à operação da câmera-laboratório.</p></article>
      <article class="format-card"><h3 data-cms-editable>O projeto está incluído?</h3><p data-cms-editable>Sim. Você recebe o PDF com lista de materiais e as peças do projeto para imprimir, colar e cortar.</p></article>
      <article class="format-card"><h3 data-cms-editable>Como funciona o crédito?</h3><p data-cms-editable>O valor integral pago pela oficina poderá ser usado em um dos cursos elegíveis: Positivo Direto em Filme de Raio-X ou o futuro curso completo de Construção de Câmeras. As regras serão apresentadas na abertura da turma.</p></article>
    </div>
  </div>
</section>

<section class="interest cms-form-section" id="interesse" data-cms-section="interest" data-cms-section-name="Lista de interesse">
  <div class="interest-inner">
    <div class="interest-intro"><p class="section-label" data-cms-editable>11 / Primeira turma</p><h2 data-cms-editable>Quer saber quando a primeira turma abrir?</h2><p data-cms-editable>Deixe seu contato. Quando o protótipo estiver finalizado e data, valor e regras da primeira turma estiverem fechados, eu envio as informações.</p></div>
    <div data-cms-form-key="pinhole-interest"></div>
  </div>
</section>
HTML;

    $pageDocument=[
        'version'=>2,
        'theme'=>'auto',
        'meta'=>[
            'title'=>'Oficina Pinhole Lambe-Lambe — João Saidler',
            'description'=>'Oficina on-line e ao vivo para acompanhar a construção de uma Pinhole Lambe-Lambe autoral com projeto em PDF, materiais simples e operação de câmera-laboratório.',
        ],
        'html'=>$html,
    ];
    $pageJson=json_encode($pageDocument,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

    foreach($activities as $activityIdRaw){
        $activityId=(int)$activityIdRaw;

        $formCheck=$db->prepare('SELECT id FROM cms_forms WHERE activity_id=? AND locale=? AND form_key=? LIMIT 1');
        $formCheck->execute([$activityId,'pt-BR','pinhole-interest']);
        if(!$formCheck->fetchColumn()){
            $insertForm=$db->prepare('INSERT INTO cms_forms(form_uuid,activity_id,locale,form_key,title,status,draft_schema_json,published_schema_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at) VALUES(?,?,?,?,?,"active",?,?,1,1,?,?,?,?)');
            $insertForm->execute([
                bin2hex(random_bytes(16)),
                $activityId,
                'pt-BR',
                'pinhole-interest',
                'Lista de interesse — Pinhole Lambe-Lambe',
                $formJson,
                $formJson,
                $now,
                $now,
                $now,
                $now,
            ]);
        }

        $pageCheck=$db->prepare('SELECT id FROM cms_pages WHERE activity_id=? AND locale=? AND slug=? LIMIT 1');
        $pageCheck->execute([$activityId,'pt-BR','pinhole-lambe-lambe']);
        $pageId=(int)($pageCheck->fetchColumn()?:0);
        if($pageId<1){
            $orderQuery=$db->prepare('SELECT COALESCE(MAX(sort_order),0)+1 FROM cms_pages WHERE activity_id=? AND locale=?');
            $orderQuery->execute([$activityId,'pt-BR']);
            $sortOrder=(int)$orderQuery->fetchColumn();
            $insertPage=$db->prepare('INSERT INTO cms_pages(page_uuid,activity_id,locale,slug,title,nav_title,status,is_home,show_in_nav,sort_order,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at) VALUES(?,?,?,?,?,?,"active",0,1,?,?,?,1,1,?,?,?,?)');
            $insertPage->execute([
                bin2hex(random_bytes(16)),
                $activityId,
                'pt-BR',
                'pinhole-lambe-lambe',
                'Oficina Pinhole Lambe-Lambe',
                'Pinhole Lambe-Lambe',
                $sortOrder,
                $pageJson,
                $pageJson,
                $now,
                $now,
                $now,
                $now,
            ]);
            $pageId=(int)$db->lastInsertId();
        }

        if($hasSeo&&$pageId>0){
            $seo=$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,social_image,canonical_url,robots,updated_at) VALUES(?,?,?,?,?,?,?,?,?) ON CONFLICT(page_id) DO NOTHING');
            $seo->execute([
                $pageId,
                'Oficina Pinhole Lambe-Lambe | João Saidler',
                'Oficina on-line e ao vivo para acompanhar a construção de uma Pinhole Lambe-Lambe autoral com projeto em PDF e materiais simples.',
                'Oficina Pinhole Lambe-Lambe — João Saidler',
                'Uma câmera-laboratório autoral construída praticamente como um trabalho de papelaria. Primeira turma em preparação.',
                '',
                '',
                'index,follow',
                $now,
            ]);
        }
    }
};
