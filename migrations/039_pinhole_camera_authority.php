<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $about=<<<'HTML'
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
    <p data-cms-editable>A construção também passou do uso pessoal para um produto: hoje fabrico para venda a NINA, uma câmera autoral de grande formato. A Pinhole Lambe-Lambe nasce da mesma prática de projetar equipamentos funcionais e resolver mecanismos com recursos acessíveis.</p>
  </div>
  <figure class="media-figure about-image" data-cms-image-block><div class="media-area"><img src="/assets/media/joao-portrait.webp" alt="João Saidler com equipamento fotográfico" data-cms-image></div><figcaption class="media-caption" data-cms-editable>João Saidler · Fotógrafo, pesquisador e construtor de câmeras</figcaption></figure>
</section>
HTML;

    $now=gmdate('c');
    $pages=$db->query("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='pinhole-lambe-lambe' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    foreach($pages as $page){
        $draft=json_decode((string)$page['draft_document_json'],true);
        if(!is_array($draft))continue;
        $draftHtml=(string)($draft['html']??'');
        $draftHtml=preg_replace('~<section class="section about section-compact" data-cms-section="about".*?</section>~s',$about,$draftHtml,1,$draftCount)??$draftHtml;
        if(($draftCount??0)!==1)continue;
        $draft['html']=$draftHtml;
        $draftJson=json_encode($draft,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

        $publishedJson=$page['published_document_json'];
        $publishedOut=$publishedJson;
        if(is_string($publishedJson)&&$publishedJson!==''){
            $published=json_decode($publishedJson,true);
            if(is_array($published)){
                $publishedHtml=(string)($published['html']??'');
                $publishedHtml=preg_replace('~<section class="section about section-compact" data-cms-section="about".*?</section>~s',$about,$publishedHtml,1,$publishedCount)??$publishedHtml;
                if(($publishedCount??0)===1){
                    $published['html']=$publishedHtml;
                    $publishedOut=json_encode($published,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
                }
            }
        }

        $q=$db->prepare('UPDATE cms_pages SET draft_document_json=?,published_document_json=?,draft_revision=draft_revision+1,published_revision=CASE WHEN published_document_json IS NULL THEN published_revision ELSE COALESCE(published_revision,draft_revision)+1 END,draft_updated_at=?,published_at=CASE WHEN published_document_json IS NULL THEN published_at ELSE ? END,updated_at=? WHERE id=?');
        $q->execute([$draftJson,$publishedOut,$now,$now,$now,(int)$page['id']]);
    }
};
