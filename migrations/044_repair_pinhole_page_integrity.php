<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    // Canonical repair for the page after the partial 042/043 transformations.
    // This deliberately rebuilds the document once instead of trying another
    // phrase/regex patch over already-mutated HTML.
    $html=<<<'HTML'
<section class="hero" data-cms-section="hero" data-cms-section-name="Início">
  <div class="hero-copy">
    <div class="hero-top">
      <p class="label" data-cms-editable>Oficina on-line · construção em vídeo + encontro ao vivo</p>
      <h1 data-cms-editable><span>Pinhole</span><span>Lambe-Lambe</span></h1>
      <p class="hero-intro" data-cms-editable>Uma câmera fotográfica sem lente e um pequeno laboratório no mesmo equipamento, construídos com papel paraná, alumínio de lata e fita isolante.</p>
    </div>
    <div class="hero-bottom">
      <dl class="hero-facts">
        <div><dt>Formato</dt><dd data-cms-editable>Vídeos + encontro ao vivo</dd></div>
        <div><dt>Encontro</dt><dd data-cms-editable>60 a 90 minutos</dd></div>
        <div><dt>Projeto</dt><dd data-cms-editable>PDF completo da câmera</dd></div>
        <div><dt>Construção</dt><dd data-cms-editable>No seu ritmo</dd></div>
      </dl>
      <div class="cta-row"><a class="button button-primary button-emphasis" href="#interesse" data-cms-editable>Quero receber data e valor <span aria-hidden="true">↘</span></a><p class="microcopy" data-cms-editable>Receba as informações da primeira turma por e-mail.</p></div>
    </div>
  </div>
  <figure class="hero-image" data-cms-image-block>
    <div class="cms-media-placeholder" data-cms-image-placeholder><span>Imagem ou vídeo da Pinhole Lambe-Lambe</span></div>
    <figcaption><span data-cms-editable>Pinhole Lambe-Lambe · câmera-laboratório</span><span data-cms-editable>Projeto autoral de João Saidler</span></figcaption>
  </figure>
</section>

<section class="cms-support section-compact" data-cms-section="project" data-cms-section-name="A câmera">
  <div class="cms-support-inner">
    <div>
      <p class="section-label" data-cms-editable>01 / A câmera</p>
      <h2 data-cms-editable>Uma câmera que também abriga o laboratório.</h2>
      <p data-cms-editable>Uma câmera pinhole forma a imagem por uma pequena abertura, sem lente. Na Pinhole Lambe-Lambe, câmera e pequeno laboratório ficam reunidos no mesmo equipamento.</p>
      <p data-cms-editable>O projeto usa papel paraná, alumínio de lata, fita isolante e operações simples de corte e montagem para chegar a um equipamento fotográfico funcional.</p>
      <p class="technical-note" data-cms-editable>Projeto autoral de João Saidler.</p>
    </div>
    <div class="cms-media-placeholder" data-cms-image-placeholder><span>Detalhe da Pinhole Lambe-Lambe</span></div>
  </div>
</section>

<section class="format" data-cms-section="offer" data-cms-section-name="A oficina">
  <div class="format-inner section-compact">
    <p class="section-label" data-cms-editable>02 / A oficina</p>
    <div class="format-heading"><h2 data-cms-editable>O projeto completo. A construção em vídeo. E um encontro ao vivo comigo.</h2><p data-cms-editable>Você recebe o projeto em PDF e a demonstração completa da construção em vídeo para montar a câmera no seu ritmo. Depois, participa de um encontro ao vivo de 60 a 90 minutos para tirar dúvidas e compreender a operação do equipamento.</p></div>
    <div class="cms-project-preview"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Prévia do projeto em PDF</span></div></div>
    <div class="format-grid cols-3">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Projeto em PDF</h3><p data-cms-editable>Lista de materiais e peças prontas para imprimir, colar no papel paraná e recortar.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Construção em vídeo</h3><p data-cms-editable>A montagem completa é demonstrada etapa por etapa para você avançar, pausar e retomar no seu ritmo.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Encontro ao vivo</h3><p data-cms-editable>60 a 90 minutos para dúvidas de montagem, operação da câmera e conversa direta sobre o projeto.</p></article>
    </div>
    <div class="section-cta"><a class="button button-primary" href="#interesse" data-cms-editable>Quero receber data e valor <span aria-hidden="true">↘</span></a></div>
  </div>
</section>

<section class="section about section-compact" data-cms-section="about" data-cms-section-name="João Saidler">
  <div class="about-copy">
    <p class="section-label" data-cms-editable>03 / Quem conduz</p>
    <h2 data-cms-editable>João Saidler</h2>
    <p class="about-lead" data-cms-editable>Fotógrafo, pesquisador e construtor de câmeras desde 2018.</p>
    <p data-cms-editable>Meu trabalho fotográfico e a construção dos equipamentos fazem parte da mesma pesquisa. Todo esse trabalho foi produzido com câmeras que projetei e construí em casa, inclusive as imagens que receberam premiações e publicações internacionais.</p>
    <dl class="recognition">
      <div><dt data-cms-editable>2022</dt><dd data-cms-editable>Siena Creative Photo Awards — Commended</dd></div>
      <div><dt data-cms-editable>2023</dt><dd data-cms-editable>ArtLimited Awards — Runner-up, Portraiture</dd></div>
      <div><dt data-cms-editable>2025</dt><dd data-cms-editable>Siena Creative Photo Awards — Highly Commended, People</dd></div>
      <div><dt data-cms-editable>Publicações</dt><dd data-cms-editable>The Black &amp; White Book — GOD Publishing · STRKNG Editors' Selection #88 e #89</dd></div>
    </dl>
    <p data-cms-editable>Além de projetar e construir as câmeras que uso no meu próprio trabalho, fabrico para venda a NINA, uma câmera autoral de grande formato. A Pinhole Lambe-Lambe nasce dessa mesma prática: desenvolver equipamentos fotográficos funcionais a partir das necessidades do processo.</p>
  </div>
  <figure class="media-figure about-image" data-cms-image-block><div class="media-area"><img src="/assets/media/joao-portrait.webp" alt="João Saidler com equipamento fotográfico" data-cms-image></div><figcaption class="media-caption" data-cms-editable>João Saidler · Fotógrafo, pesquisador e construtor de câmeras</figcaption></figure>
</section>

<section class="format" data-cms-section="faq" data-cms-section-name="Perguntas frequentes">
  <div class="format-inner section-compact">
    <p class="section-label" data-cms-editable>04 / Perguntas frequentes</p>
    <div class="format-grid cols-3">
      <article class="format-card"><h3 data-cms-editable>Preciso já ter uma câmera?</h3><p data-cms-editable>Não. A oficina parte do próprio projeto da Pinhole Lambe-Lambe.</p></article>
      <article class="format-card"><h3 data-cms-editable>Preciso entender fotografia?</h3><p data-cms-editable>Não. O conteúdo apresenta o necessário para compreender a pinhole, montar a câmera e operar o equipamento.</p></article>
      <article class="format-card"><h3 data-cms-editable>Que materiais vou usar?</h3><p data-cms-editable>Papel paraná, alumínio de lata, fita isolante e itens comuns de papelaria. A lista completa acompanha o projeto.</p></article>
      <article class="format-card"><h3 data-cms-editable>A câmera é funcional?</h3><p data-cms-editable>Sim. O projeto foi concebido para produzir uma câmera pinhole com um pequeno laboratório integrado.</p></article>
      <article class="format-card"><h3 data-cms-editable>O que fica gravado?</h3><p data-cms-editable>A construção da câmera faz parte do conteúdo em vídeo. O encontro com João acontece ao vivo e não é gravado.</p></article>
      <article class="format-card"><h3 data-cms-editable>A oficina inclui fotografia e revelação?</h3><p data-cms-editable>Não. Fotografia e revelação não fazem parte da oficina; o conteúdo vai até a construção e a operação da câmera-laboratório.</p></article>
    </div>
  </div>
</section>

<section class="interest cms-form-section" id="interesse" data-cms-section="interest" data-cms-section-name="Lista de interesse">
  <div class="interest-inner section-compact">
    <div class="interest-intro"><p class="section-label" data-cms-editable>05 / Primeira turma</p><h2 data-cms-editable>Receba a data e o valor.</h2><p data-cms-editable>Cadastre seu nome e e-mail e eu envio as informações de inscrição quando a turma abrir.</p></div>
    <div data-cms-form-key="pinhole-interest"></div>
  </div>
</section>
HTML;

    $description='Oficina on-line para construir uma Pinhole Lambe-Lambe autoral: projeto completo em PDF, construção em vídeo no seu ritmo e encontro ao vivo de 60 a 90 minutos com João Saidler.';
    $socialDescription='Projeto completo em PDF, construção em vídeo e encontro ao vivo de 60 a 90 minutos com João Saidler para construir e compreender uma Pinhole Lambe-Lambe autoral.';
    $now=gmdate('c');

    $pages=$db->query("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='pinhole-lambe-lambe' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    foreach($pages as $page){
        $draft=json_decode((string)$page['draft_document_json'],true);
        if(!is_array($draft))$draft=['version'=>2,'theme'=>'auto','meta'=>[]];
        $draft['html']=$html;
        $draft['meta']['title']='Oficina Pinhole Lambe-Lambe — João Saidler';
        $draft['meta']['description']=$description;
        $draftJson=json_encode($draft,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

        $publishedOut=$page['published_document_json'];
        $publishedChanged=false;
        if(is_string($publishedOut)&&$publishedOut!==''){
            $published=json_decode($publishedOut,true);
            if(!is_array($published))$published=['version'=>2,'theme'=>'auto','meta'=>[]];
            $published['html']=$html;
            $published['meta']['title']='Oficina Pinhole Lambe-Lambe — João Saidler';
            $published['meta']['description']=$description;
            $publishedOut=json_encode($published,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $publishedChanged=true;
        }

        $q=$db->prepare('UPDATE cms_pages SET draft_document_json=?,published_document_json=?,draft_revision=draft_revision+1,published_revision=CASE WHEN ? THEN COALESCE(published_revision,draft_revision)+1 ELSE published_revision END,draft_updated_at=?,published_at=CASE WHEN ? THEN ? ELSE published_at END,updated_at=? WHERE id=?');
        $q->execute([$draftJson,$publishedOut,$publishedChanged?1:0,$now,$publishedChanged?1:0,$now,$now,(int)$page['id']]);

        $hasSeo=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_page_seo'")->fetchColumn();
        if($hasSeo){
            $seo=$db->prepare('UPDATE cms_page_seo SET description=?,social_description=?,updated_at=? WHERE page_id=?');
            $seo->execute([$description,$socialDescription,$now,(int)$page['id']]);
        }
    }
};
