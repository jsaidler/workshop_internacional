<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    $hasForms=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_forms'")->fetchColumn();
    if(!$hasPages)return;

    $html=<<<'HTML'
<section class="hero" data-cms-section="hero" data-cms-section-name="Início">
  <div class="hero-copy">
    <div class="hero-top">
      <p class="label" data-cms-editable>Oficina on-line · duas versões · construção em vídeo + encontro ao vivo</p>
      <h1 data-cms-editable><span>Pinhole</span><span>Lambe-Lambe</span></h1>
      <p class="hero-intro" data-cms-editable>Uma câmera fotográfica sem lente e um pequeno laboratório no mesmo equipamento. Você escolhe como quer construí-la: com materiais alternativos ou em madeira.</p>
    </div>
    <div class="hero-bottom">
      <dl class="hero-facts">
        <div><dt>Formato</dt><dd data-cms-editable>Vídeos + encontro ao vivo</dd></div>
        <div><dt>Versões</dt><dd data-cms-editable>Alternativos ou madeira</dd></div>
        <div><dt>Cada versão</dt><dd data-cms-editable>R$ 98</dd></div>
        <div><dt>As duas</dt><dd data-cms-editable>R$ 168</dd></div>
      </dl>
      <div class="cta-row"><a class="button button-primary button-emphasis" href="#interesse" data-cms-editable>Quero escolher minha versão <span aria-hidden="true">↘</span></a><p class="microcopy" data-cms-editable>Escolha no formulário e receba a data da primeira turma por e-mail.</p></div>
    </div>
  </div>
  <figure class="hero-image" data-cms-image-block>
    <div class="cms-media-placeholder" data-cms-image-placeholder><span>Imagem ou vídeo das versões da Pinhole Lambe-Lambe</span></div>
    <figcaption><span data-cms-editable>Pinhole Lambe-Lambe · câmera-laboratório</span><span data-cms-editable>Projetos autorais de João Saidler</span></figcaption>
  </figure>
</section>

<section class="format" data-cms-section="versions" data-cms-section-name="Escolha a versão">
  <div class="format-inner section-compact">
    <p class="section-label" data-cms-editable>01 / Escolha o projeto</p>
    <div class="format-heading"><h2 data-cms-editable>A mesma câmera-laboratório. Dois caminhos de construção.</h2><p data-cms-editable>As duas versões partem da mesma ideia: uma pinhole funcional com pequeno laboratório integrado. O que muda é a solução construtiva. Você pode escolher uma delas ou levar as duas.</p></div>
    <div class="cms-project-preview"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Detalhe das duas versões da Pinhole Lambe-Lambe</span></div></div>
    <div class="format-grid cols-3">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Materiais alternativos · R$ 98</h3><p data-cms-editable>Container plástico, pasta de escritório e outros materiais simples de encontrar. Um projeto pensado para chegar a um equipamento funcional com soluções pouco óbvias, sem depender de marcenaria.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Madeira · R$ 98</h3><p data-cms-editable>Estrutura em madeira para quem prefere construir a câmera a partir de peças rígidas. Você pode cortar as peças por conta própria ou encomendar o corte nas medidas do projeto e fazer a montagem.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>As duas · R$ 168</h3><p data-cms-editable>Os dois projetos e os dois conteúdos de construção, com R$ 28 de economia em relação à compra separada.</p></article>
    </div>
    <p class="technical-note" data-cms-editable>Cada versão é uma oficina completa e pode ser feita separadamente.</p>
  </div>
</section>

<section class="format" data-cms-section="offer" data-cms-section-name="O que você recebe">
  <div class="format-inner section-compact">
    <p class="section-label" data-cms-editable>02 / O que você recebe</p>
    <div class="format-heading"><h2 data-cms-editable>Em qualquer versão: projeto completo, construção em vídeo e encontro ao vivo comigo.</h2><p data-cms-editable>Você recebe o PDF da versão escolhida e a demonstração correspondente em vídeo para montar no seu ritmo. Depois, participa de um encontro ao vivo de 60 a 90 minutos para tirar dúvidas e compreender a operação do equipamento. No combo, recebe os dois projetos e os dois conteúdos de construção.</p></div>
    <div class="cms-project-preview"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Prévia dos projetos em PDF</span></div></div>
    <div class="format-grid cols-3">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Projeto em PDF</h3><p data-cms-editable>Lista de materiais, medidas e peças da versão escolhida. Na versão em madeira, o projeto permite cortar ou encomendar as peças já nas medidas corretas.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Construção em vídeo</h3><p data-cms-editable>A montagem da versão escolhida é demonstrada etapa por etapa para você avançar, pausar e retomar no seu ritmo.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Encontro ao vivo</h3><p data-cms-editable>60 a 90 minutos para dúvidas de montagem, operação da câmera e conversa direta sobre o projeto.</p></article>
    </div>
    <p class="technical-note" data-cms-editable>Se depois você se inscrever no Workshop Positivo Direto em Filme de Raio-X, R$ 98 do valor pago aqui viram crédito na inscrição. No combo, o crédito máximo também é R$ 98.</p>
    <div class="section-cta"><a class="button button-primary" href="#interesse" data-cms-editable>Quero escolher minha versão <span aria-hidden="true">↘</span></a></div>
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
      <article class="format-card"><h3 data-cms-editable>Preciso já ter uma câmera ou entender fotografia?</h3><p data-cms-editable>Não. A oficina começa pela construção da própria câmera e apresenta o necessário para compreender e operar o equipamento.</p></article>
      <article class="format-card"><h3 data-cms-editable>A câmera é funcional?</h3><p data-cms-editable>Sim. As duas versões foram concebidas para produzir uma câmera pinhole com um pequeno laboratório integrado.</p></article>
      <article class="format-card"><h3 data-cms-editable>Qual versão escolher?</h3><p data-cms-editable>Materiais alternativos explora soluções com objetos simples do cotidiano. Madeira parte de uma estrutura rígida que pode ser cortada por você ou encomendada pronta para montagem. A função fotográfica é a mesma.</p></article>
      <article class="format-card"><h3 data-cms-editable>Preciso ter ferramentas para a versão em madeira?</h3><p data-cms-editable>Não necessariamente. Se você não tiver estrutura para cortar a madeira, pode encomendar as peças já cortadas nas medidas do projeto e fazer a montagem.</p></article>
      <article class="format-card"><h3 data-cms-editable>O que fica gravado?</h3><p data-cms-editable>A construção da versão escolhida faz parte do conteúdo em vídeo; no combo, você recebe os dois conteúdos. O encontro com João acontece ao vivo e não é gravado.</p></article>
      <article class="format-card"><h3 data-cms-editable>A oficina inclui fotografia e revelação?</h3><p data-cms-editable>Não. Fotografia e revelação não fazem parte da oficina; o conteúdo vai até a construção e a operação da câmera-laboratório.</p></article>
    </div>
  </div>
</section>

<section class="interest cms-form-section" id="interesse" data-cms-section="interest" data-cms-section-name="Lista de interesse">
  <div class="interest-inner section-compact">
    <div class="interest-intro"><p class="section-label" data-cms-editable>05 / Primeira turma</p><h2 data-cms-editable>Escolha sua versão e receba a data.</h2><p data-cms-editable>Cadastre seu contato, indique a opção que mais te interessa e eu envio as informações quando a primeira turma abrir.</p></div>
    <div data-cms-form-key="pinhole-interest"></div>
  </div>
</section>
HTML;

    $description='Oficina Pinhole Lambe-Lambe em duas versões: materiais alternativos ou madeira. Cada versão custa R$ 98; as duas, R$ 168. Projeto em PDF, construção em vídeo e encontro ao vivo com João Saidler.';
    $socialDescription='Escolha a Pinhole Lambe-Lambe em materiais alternativos, madeira ou as duas. Projeto completo, construção em vídeo, encontro ao vivo e crédito de até R$ 98 no Workshop Positivo Direto.';
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

    if($hasForms){
        $formSchema=[
            'version'=>1,
            'submitLabel'=>'Quero receber a data',
            'successTitle'=>'Interesse registrado',
            'successMessage'=>'Vou te avisar quando a primeira turma abrir.',
            'fields'=>[
                ['id'=>'name','type'=>'text','label'=>'Nome','required'=>true,'autocomplete'=>'name','width'=>'half'],
                ['id'=>'email','type'=>'email','label'=>'E-mail','required'=>true,'autocomplete'=>'email','width'=>'half'],
                ['id'=>'contact','type'=>'text','label'=>'Instagram ou WhatsApp (opcional)','required'=>false,'width'=>'full'],
                [
                    'id'=>'product_version',
                    'type'=>'radio',
                    'label'=>'Qual opção mais te interessa?',
                    'required'=>true,
                    'width'=>'full',
                    'options'=>[
                        ['value'=>'alternative','label'=>'Materiais alternativos — R$ 98'],
                        ['value'=>'wood','label'=>'Madeira — R$ 98'],
                        ['value'=>'both','label'=>'As duas — R$ 168'],
                    ],
                ],
                ['id'=>'consent','type'=>'consent','label'=>'Quero receber informações sobre a primeira turma da Oficina Pinhole Lambe-Lambe.','required'=>true,'width'=>'full'],
            ],
        ];
        $formJson=json_encode($formSchema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $forms=$db->query("SELECT * FROM cms_forms WHERE locale='pt-BR' AND form_key='pinhole-interest' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
        foreach($forms as $form){
            $publishedChanged=is_string($form['published_schema_json']??null)&&$form['published_schema_json']!=='';
            $publishedOut=$publishedChanged?$formJson:$form['published_schema_json'];
            $q=$db->prepare('UPDATE cms_forms SET draft_schema_json=?,published_schema_json=?,draft_revision=draft_revision+1,published_revision=CASE WHEN ? THEN COALESCE(published_revision,draft_revision)+1 ELSE published_revision END,draft_updated_at=?,published_at=CASE WHEN ? THEN ? ELSE published_at END,updated_at=? WHERE id=?');
            $q->execute([$formJson,$publishedOut,$publishedChanged?1:0,$now,$publishedChanged?1:0,$now,$now,(int)$form['id']]);
        }
    }
};
