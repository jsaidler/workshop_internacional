<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    $hasForms=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_forms'")->fetchColumn();
    $hasSeo=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_page_seo'")->fetchColumn();
    if(!$hasPages||!$hasForms)return;

    $now=gmdate('c');

    $html=<<<'HTML'
<section class="hero" data-cms-section="hero" data-cms-section-name="Início">
  <div class="hero-copy">
    <div class="hero-top">
      <p class="label" data-cms-editable>Oficina on-line e ao vivo · Primeira turma em preparação</p>
      <h1 data-cms-editable><span>Pinhole</span><span>Lambe-Lambe</span><span>Câmera-laboratório</span></h1>
      <p class="hero-intro" data-cms-editable>Uma câmera. Um pequeno laboratório. Papel paraná, uma lata e fita isolante. Um projeto autoral para construir um equipamento fotográfico funcional praticamente como um trabalho de papelaria.</p>
    </div>
    <div class="hero-bottom">
      <dl class="hero-facts">
        <div><dt>Formato</dt><dd data-cms-editable>1 encontro ao vivo</dd></div>
        <div><dt>Duração</dt><dd data-cms-editable>2 a 3 horas</dd></div>
        <div><dt>Projeto</dt><dd data-cms-editable>PDF incluído</dd></div>
        <div><dt>Construção</dt><dd data-cms-editable>Sem ferramentas de oficina</dd></div>
      </dl>
      <div class="cta-row"><a class="button button-primary" href="#interesse" data-cms-editable>Quero saber quando abrir <span aria-hidden="true">↘</span></a><p class="microcopy" data-cms-editable>Sem pagamento agora. Data e valor serão informados antes da abertura da primeira turma.</p></div>
    </div>
  </div>
  <figure class="hero-image" data-cms-image-block>
    <div class="cms-media-placeholder" data-cms-image-placeholder><span>Imagem ou vídeo do protótipo entra aqui</span></div>
    <figcaption><span data-cms-editable>Pinhole Lambe-Lambe</span><span data-cms-editable>Protótipo em desenvolvimento</span></figcaption>
  </figure>
</section>

<section class="cms-support" data-cms-section="project" data-cms-section-name="O projeto">
  <div class="cms-support-inner">
    <div>
      <p class="section-label" data-cms-editable>01 / O projeto</p>
      <h2 data-cms-editable>Materiais simples. Soluções de projeto que não são.</h2>
      <p data-cms-editable>A proposta é transformar materiais quase banais em uma Pinhole Lambe-Lambe funcional: uma câmera que incorpora também um pequeno laboratório no próprio equipamento.</p>
      <p data-cms-editable>O valor do projeto não está em exigir uma oficina equipada. Está em resolver estrutura, vedação, mecanismos e operação com papel paraná, alumínio de lata, fita isolante e poucas outras peças acessíveis.</p>
      <p class="technical-note" data-cms-editable>O projeto foi desenvolvido especificamente para esta oficina e pensado para poder ser construído em um único dia.</p>
    </div>
    <div class="cms-media-placeholder" data-cms-image-placeholder><span>Imagem detalhada da câmera entra aqui</span></div>
  </div>
</section>

<section class="format" data-cms-section="offer" data-cms-section-name="O que você recebe">
  <div class="format-inner">
    <p class="section-label" data-cms-editable>02 / O que você recebe</p>
    <div class="format-heading"><h2 data-cms-editable>O projeto completo, a construção explicada e a operação da câmera.</h2><p data-cms-editable>O encontro foi desenhado para que você entenda como o objeto é feito e tenha depois um projeto utilizável para construir o seu.</p></div>
    <div class="format-grid">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Projeto em PDF</h3><p data-cms-editable>Antes do encontro, você recebe a lista de materiais e as peças para imprimir, colar no papel paraná e recortar.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Construção completa</h3><p data-cms-editable>Durante a oficina eu percorro todas as etapas usando modelos preparados em diferentes estágios, para mostrar cada solução sem depender do tempo de uma montagem completa ao vivo.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Como a câmera funciona</h3><p data-cms-editable>Princípio da pinhole, o que esperar da imagem, uma introdução à falha de reciprocidade e a operação da câmera com seu laboratório integrado.</p></article>
    </div>
    <p class="technical-note" data-cms-editable>A oficina é ao vivo e não será gravada. O projeto em PDF fica com você.</p>
  </div>
</section>

<section class="section about" data-cms-section="about" data-cms-section-name="João Saidler">
  <div class="about-copy">
    <p class="section-label" data-cms-editable>03 / Quem conduz</p>
    <h2 data-cms-editable>João Saidler</h2>
    <p class="about-lead" data-cms-editable>Há quase oito anos desenvolvo uma pesquisa em fotografia química na qual construir o equipamento faz parte do próprio trabalho fotográfico.</p>
    <p data-cms-editable>Nesse percurso, projetei e construí câmeras de grande formato, obturadores, tanques e outros sistemas quando a imagem pedia uma ferramenta que ainda não existia para o que eu queria fazer.</p>
    <p data-cms-editable>Esta Pinhole Lambe-Lambe parte da mesma lógica, mas com uma restrição deliberada: fazer muito com materiais e operações simples.</p>
    <p class="technical-note" data-cms-editable>Se depois você quiser continuar, o valor pago pela oficina poderá ser usado integralmente como crédito em um único curso elegível: Positivo Direto em Filme de Raio-X ou o futuro curso completo de Construção de Câmeras. As regras serão informadas na abertura da turma.</p>
  </div>
  <figure class="media-figure about-image" data-cms-image-block><div class="media-area"><img src="/assets/media/joao-portrait.webp" alt="Fotógrafo João Saidler" data-cms-image></div><figcaption class="media-caption" data-cms-editable>João Saidler · Fotógrafo e pesquisador independente</figcaption></figure>
</section>

<section class="format" data-cms-section="faq" data-cms-section-name="Perguntas frequentes">
  <div class="format-inner">
    <p class="section-label" data-cms-editable>04 / Perguntas frequentes</p>
    <div class="format-heading"><h2 data-cms-editable>O que você precisa saber antes de entrar na lista.</h2></div>
    <div class="format-grid">
      <article class="format-card"><h3 data-cms-editable>Preciso já ter uma câmera?</h3><p data-cms-editable>Não. A própria atividade gira em torno do projeto da Pinhole Lambe-Lambe.</p></article>
      <article class="format-card"><h3 data-cms-editable>Preciso entender fotografia?</h3><p data-cms-editable>Não é necessário domínio técnico avançado. Os princípios necessários para compreender o comportamento da câmera serão apresentados de forma introdutória.</p></article>
      <article class="format-card"><h3 data-cms-editable>Preciso de ferramentas de oficina?</h3><p data-cms-editable>Não. A construção foi pensada para operações de papelaria: imprimir, colar, cortar e montar materiais simples.</p></article>
      <article class="format-card"><h3 data-cms-editable>Vou construir uma câmera inteira durante o encontro?</h3><p data-cms-editable>Eu demonstrarei toda a construção com modelos preparados em diferentes estágios. O PDF do projeto fica com você para construir a sua câmera a partir dele.</p></article>
      <article class="format-card"><h3 data-cms-editable>A oficina será gravada?</h3><p data-cms-editable>Não. O encontro acontece ao vivo. O material permanente que você recebe é o projeto em PDF.</p></article>
      <article class="format-card"><h3 data-cms-editable>Vamos fotografar ou revelar?</h3><p data-cms-editable>Não. Esta oficina é dedicada à construção e à operação da câmera-laboratório.</p></article>
    </div>
  </div>
</section>

<section class="interest cms-form-section" id="interesse" data-cms-section="interest" data-cms-section-name="Lista de interesse">
  <div class="interest-inner">
    <div class="interest-intro"><p class="section-label" data-cms-editable>05 / Primeira turma</p><h2 data-cms-editable>Quer saber quando a primeira turma abrir?</h2><p data-cms-editable>Deixe seu contato. Quando protótipo, data e valor estiverem fechados, eu envio as informações da primeira turma.</p></div>
    <div data-cms-form-key="pinhole-interest"></div>
  </div>
</section>
HTML;

    $document=[
        'version'=>2,
        'theme'=>'auto',
        'meta'=>[
            'title'=>'Oficina Pinhole Lambe-Lambe — João Saidler',
            'description'=>'Oficina on-line e ao vivo para conhecer a construção e a operação de uma Pinhole Lambe-Lambe autoral, com projeto completo em PDF e materiais simples.',
        ],
        'html'=>$html,
    ];
    $pageJson=json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

    $formSchema=[
        'version'=>1,
        'submitLabel'=>'Quero receber as informações',
        'successTitle'=>'Interesse registrado',
        'successMessage'=>'Vou te avisar quando a primeira turma estiver pronta para abrir.',
        'fields'=>[
            ['id'=>'name','type'=>'text','label'=>'Nome','required'=>true,'autocomplete'=>'name','width'=>'half'],
            ['id'=>'email','type'=>'email','label'=>'E-mail','required'=>true,'autocomplete'=>'email','width'=>'half'],
            ['id'=>'contact','type'=>'text','label'=>'Instagram ou WhatsApp (opcional)','required'=>false,'width'=>'full'],
            ['id'=>'consent','type'=>'consent','label'=>'Quero receber informações sobre a primeira turma desta oficina.','required'=>true,'width'=>'full'],
        ],
    ];
    $formJson=json_encode($formSchema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

    $pages=$db->query("SELECT id FROM cms_pages WHERE locale='pt-BR' AND slug='pinhole-lambe-lambe' AND status!='archived'")->fetchAll(PDO::FETCH_COLUMN);
    foreach($pages as $pageIdRaw){
        $pageId=(int)$pageIdRaw;
        $q=$db->prepare('UPDATE cms_pages SET title=?,nav_title=?,draft_document_json=?,published_document_json=?,draft_revision=draft_revision+1,published_revision=COALESCE(published_revision,draft_revision)+1,draft_updated_at=?,published_at=?,updated_at=? WHERE id=?');
        $q->execute(['Oficina Pinhole Lambe-Lambe','Pinhole Lambe-Lambe',$pageJson,$pageJson,$now,$now,$now,$pageId]);
        if($hasSeo){
            $seo=$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,social_image,canonical_url,robots,updated_at) VALUES(?,?,?,?,?,?,?,?,?) ON CONFLICT(page_id) DO UPDATE SET title=excluded.title,description=excluded.description,social_title=excluded.social_title,social_description=excluded.social_description,robots=excluded.robots,updated_at=excluded.updated_at');
            $seo->execute([
                $pageId,
                'Oficina Pinhole Lambe-Lambe | João Saidler',
                'Oficina on-line e ao vivo sobre a construção e a operação de uma Pinhole Lambe-Lambe autoral, com projeto completo em PDF e materiais simples.',
                'Oficina Pinhole Lambe-Lambe — João Saidler',
                'Uma câmera e um pequeno laboratório construídos a partir de materiais simples. Primeira turma em preparação.',
                '',
                '',
                'index,follow',
                $now,
            ]);
        }
    }

    $forms=$db->query("SELECT id FROM cms_forms WHERE locale='pt-BR' AND form_key='pinhole-interest' AND status!='archived'")->fetchAll(PDO::FETCH_COLUMN);
    foreach($forms as $formIdRaw){
        $formId=(int)$formIdRaw;
        $q=$db->prepare('UPDATE cms_forms SET draft_schema_json=?,published_schema_json=?,draft_revision=draft_revision+1,published_revision=COALESCE(published_revision,draft_revision)+1,draft_updated_at=?,published_at=?,updated_at=? WHERE id=?');
        $q->execute([$formJson,$formJson,$now,$now,$now,$formId]);
    }
};
