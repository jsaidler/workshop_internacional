<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $now=gmdate('c');
    $html=<<<'HTML'
<section class="hero hero--copy-only" data-cms-section="hero" data-cms-section-name="Início">
  <div class="hero-copy">
    <div class="hero-top">
      <p class="label" data-cms-editable>Oficina on-line e ao vivo</p>
      <h1 data-cms-editable><span>Pinhole</span><span>Lambe-Lambe</span></h1>
      <p class="hero-intro" data-cms-editable>Uma câmera fotográfica sem lente e um pequeno laboratório no mesmo equipamento, construídos com papel paraná, alumínio de lata e fita isolante.</p>
    </div>
    <div class="hero-bottom">
      <dl class="hero-facts">
        <div><dt>Formato</dt><dd data-cms-editable>1 encontro ao vivo</dd></div>
        <div><dt>Duração</dt><dd data-cms-editable>2 a 3 horas</dd></div>
        <div><dt>Material</dt><dd data-cms-editable>Projeto completo em PDF</dd></div>
        <div><dt>Construção</dt><dd data-cms-editable>Materiais simples</dd></div>
      </dl>
      <div class="cta-row"><a class="button button-primary button-emphasis" href="#interesse" data-cms-editable>Quero receber data e valor <span aria-hidden="true">↘</span></a><p class="microcopy" data-cms-editable>Receba as informações da primeira turma por e-mail.</p></div>
    </div>
  </div>
</section>

<section class="cms-support cms-support--single section-compact" data-cms-section="project" data-cms-section-name="A câmera">
  <div class="cms-support-inner">
    <div>
      <p class="section-label" data-cms-editable>01 / A câmera</p>
      <h2 data-cms-editable>Uma câmera que também abriga o laboratório.</h2>
      <p data-cms-editable>Uma câmera pinhole forma a imagem por uma pequena abertura, sem lente. Na Pinhole Lambe-Lambe, câmera e pequeno laboratório ficam reunidos no mesmo equipamento.</p>
      <p data-cms-editable>O projeto usa papel paraná, alumínio de lata, fita isolante e operações simples de corte e montagem para chegar a um equipamento fotográfico funcional.</p>
      <p class="technical-note" data-cms-editable>Projeto autoral de João Saidler.</p>
    </div>
  </div>
</section>

<section class="format" data-cms-section="offer" data-cms-section-name="A oficina">
  <div class="format-inner section-compact">
    <p class="section-label" data-cms-editable>02 / A oficina</p>
    <div class="format-heading"><h2 data-cms-editable>O projeto completo da câmera — e a construção demonstrada do começo ao fim.</h2><p data-cms-editable>Antes do encontro, você recebe um PDF com a lista de materiais e as peças para imprimir, colar no papel paraná e recortar.</p></div>
    <div class="format-grid cols-3">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Construção</h3><p data-cms-editable>Cada parte da câmera é mostrada e explicada até o equipamento completo.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Imagem e exposição</h3><p data-cms-editable>Princípio da pinhole, características da imagem e uma introdução à falha de reciprocidade.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Operação</h3><p data-cms-editable>Como preparar e usar a câmera e o pequeno laboratório integrado.</p></article>
    </div>
    <div class="section-cta"><a class="button button-primary" href="#interesse" data-cms-editable>Quero receber data e valor <span aria-hidden="true">↘</span></a></div>
  </div>
</section>

<section class="section about section-compact" data-cms-section="about" data-cms-section-name="João Saidler">
  <div class="about-copy">
    <p class="section-label" data-cms-editable>03 / Quem conduz</p>
    <h2 data-cms-editable>João Saidler</h2>
    <p class="about-lead" data-cms-editable>Fotógrafo e pesquisador independente em fotografia química desde 2018.</p>
    <p data-cms-editable>Parte dessa pesquisa consiste em projetar e construir as próprias ferramentas: câmeras de grande formato, obturadores, tanques e sistemas de processamento.</p>
    <p data-cms-editable>A Pinhole Lambe-Lambe nasce dessa prática aplicada a um projeto simples de construir e funcional como equipamento fotográfico.</p>
  </div>
  <figure class="media-figure about-image" data-cms-image-block><div class="media-area"><img src="/assets/media/joao-portrait.webp" alt="João Saidler com equipamento fotográfico" data-cms-image></div><figcaption class="media-caption" data-cms-editable>João Saidler · Fotógrafo e pesquisador independente</figcaption></figure>
</section>

<section class="format" data-cms-section="faq" data-cms-section-name="Perguntas frequentes">
  <div class="format-inner section-compact">
    <p class="section-label" data-cms-editable>04 / Perguntas frequentes</p>
    <div class="format-grid cols-3">
      <article class="format-card"><h3 data-cms-editable>Preciso já ter uma câmera?</h3><p data-cms-editable>Não. A oficina parte do próprio projeto da Pinhole Lambe-Lambe.</p></article>
      <article class="format-card"><h3 data-cms-editable>Preciso entender fotografia?</h3><p data-cms-editable>Não. Os princípios necessários para compreender a câmera são apresentados durante o encontro.</p></article>
      <article class="format-card"><h3 data-cms-editable>Que materiais vou usar?</h3><p data-cms-editable>Papel paraná, alumínio de lata, fita isolante e itens comuns de papelaria. A lista completa acompanha o projeto.</p></article>
      <article class="format-card"><h3 data-cms-editable>A câmera é funcional?</h3><p data-cms-editable>Sim. O projeto foi concebido para produzir uma câmera pinhole com um pequeno laboratório integrado.</p></article>
      <article class="format-card"><h3 data-cms-editable>O encontro fica gravado?</h3><p data-cms-editable>Não. O encontro acontece ao vivo e o projeto em PDF fica com você.</p></article>
      <article class="format-card"><h3 data-cms-editable>A oficina inclui fotografia e revelação?</h3><p data-cms-editable>Não. O encontro é dedicado à construção, à formação da imagem e à operação do equipamento.</p></article>
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

    $document=[
        'version'=>2,
        'theme'=>'auto',
        'meta'=>[
            'title'=>'Oficina Pinhole Lambe-Lambe — João Saidler',
            'description'=>'Oficina on-line e ao vivo sobre a construção e a operação de uma Pinhole Lambe-Lambe autoral, com projeto completo em PDF.',
        ],
        'html'=>$html,
    ];
    $pageJson=json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

    $pages=$db->query("SELECT id FROM cms_pages WHERE locale='pt-BR' AND slug='pinhole-lambe-lambe' AND status!='archived'")->fetchAll(PDO::FETCH_COLUMN);
    foreach($pages as $pageIdRaw){
        $pageId=(int)$pageIdRaw;
        $q=$db->prepare('UPDATE cms_pages SET title=?,nav_title=?,draft_document_json=?,published_document_json=?,draft_revision=draft_revision+1,published_revision=COALESCE(published_revision,draft_revision)+1,draft_updated_at=?,published_at=?,updated_at=? WHERE id=?');
        $q->execute(['Oficina Pinhole Lambe-Lambe','Pinhole Lambe-Lambe',$pageJson,$pageJson,$now,$now,$now,$pageId]);
    }

    $hasSiteSettings=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_site_settings'")->fetchColumn();
    $hasActivities=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='activities'")->fetchColumn();
    if(!$hasSiteSettings||!$hasActivities)return;

    $activityIds=$db->query('SELECT id FROM activities WHERE is_root=1 ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    foreach($activityIds as $activityIdRaw){
        $activityId=(int)$activityIdRaw;
        foreach(['pt-BR','en'] as $locale){
            $q=$db->prepare('SELECT settings_json FROM cms_site_settings WHERE activity_id=? AND locale=?');
            $q->execute([$activityId,$locale]);
            $raw=$q->fetchColumn();
            $settings=is_string($raw)&&$raw!==''?json_decode($raw,true):[];
            if(!is_array($settings))$settings=[];
            $settings['siteName']=$locale==='pt-BR'?'João Saidler — Oficinas de fotografia':'João Saidler — Photography workshops';
            $settings['wordmark']='João Saidler';
            if(!isset($settings['footer'])||!is_array($settings['footer']))$settings['footer']=[];
            $settings['footer']['line2']=$locale==='pt-BR'?'Fotografia experimental · processos e construção de câmeras':'Experimental photography · processes and camera building';
            $json=json_encode($settings,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $save=$db->prepare('INSERT INTO cms_site_settings(activity_id,locale,settings_json,updated_at) VALUES(?,?,?,?) ON CONFLICT(activity_id,locale) DO UPDATE SET settings_json=excluded.settings_json,updated_at=excluded.updated_at');
            $save->execute([$activityId,$locale,$json,$now]);
        }
    }
};
