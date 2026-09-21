<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $heroLabel='<p class="label" data-cms-editable>Oficina on-line · construção em vídeo + encontro ao vivo</p>';
    $heroFacts=<<<'HTML'
<dl class="hero-facts">
        <div><dt>Formato</dt><dd data-cms-editable>Vídeos + encontro ao vivo</dd></div>
        <div><dt>Encontro</dt><dd data-cms-editable>60 a 90 minutos</dd></div>
        <div><dt>Projeto</dt><dd data-cms-editable>PDF completo da câmera</dd></div>
        <div><dt>Construção</dt><dd data-cms-editable>No seu ritmo</dd></div>
      </dl>
HTML;
    $offerHeading=<<<'HTML'
<div class="format-heading"><h2 data-cms-editable>O projeto completo. A construção em vídeo. E um encontro ao vivo comigo.</h2><p data-cms-editable>Você recebe o projeto em PDF e a demonstração completa da construção em vídeo para montar a câmera no seu ritmo. Depois, participa de um encontro ao vivo de 60 a 90 minutos para tirar dúvidas e compreender a operação do equipamento.</p></div>
HTML;
    $offerGrid=<<<'HTML'
<div class="format-grid cols-3">
      <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Projeto em PDF</h3><p data-cms-editable>Lista de materiais e peças prontas para imprimir, colar no papel paraná e recortar.</p></article>
      <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Construção em vídeo</h3><p data-cms-editable>A montagem completa é demonstrada etapa por etapa para você avançar, pausar e retomar no seu ritmo.</p></article>
      <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Encontro ao vivo</h3><p data-cms-editable>60 a 90 minutos para dúvidas de montagem, operação da câmera e conversa direta sobre o projeto.</p></article>
    </div>
HTML;
    $faqGrid=<<<'HTML'
<div class="format-grid cols-3">
      <article class="format-card"><h3 data-cms-editable>Preciso já ter uma câmera?</h3><p data-cms-editable>Não. A oficina parte do próprio projeto da Pinhole Lambe-Lambe.</p></article>
      <article class="format-card"><h3 data-cms-editable>Preciso entender fotografia?</h3><p data-cms-editable>Não. O conteúdo apresenta o necessário para compreender a pinhole, montar a câmera e operar o equipamento.</p></article>
      <article class="format-card"><h3 data-cms-editable>Que materiais vou usar?</h3><p data-cms-editable>Papel paraná, alumínio de lata, fita isolante e itens comuns de papelaria. A lista completa acompanha o projeto.</p></article>
      <article class="format-card"><h3 data-cms-editable>A câmera é funcional?</h3><p data-cms-editable>Sim. O projeto foi concebido para produzir uma câmera pinhole com um pequeno laboratório integrado.</p></article>
      <article class="format-card"><h3 data-cms-editable>O que fica gravado?</h3><p data-cms-editable>A construção da câmera faz parte do conteúdo em vídeo. O encontro com João acontece ao vivo e não é gravado.</p></article>
      <article class="format-card"><h3 data-cms-editable>A oficina inclui fotografia e revelação?</h3><p data-cms-editable>Não. Fotografia e revelação não fazem parte da oficina; o conteúdo vai até a construção e a operação da câmera-laboratório.</p></article>
    </div>
HTML;

    $replaceSection=static function(string $html,string $sectionKey,callable $mutator): string {
        $pattern='~<section\b[^>]*data-cms-section="'.preg_quote($sectionKey,'~').'"[^>]*>.*?</section>~s';
        $out=preg_replace_callback($pattern,static function(array $match) use($mutator): string {
            return $mutator($match[0]);
        },$html,1);
        return is_string($out)?$out:$html;
    };

    $normalizeHtml=static function(string $html) use($replaceSection,$heroLabel,$heroFacts,$offerHeading,$offerGrid,$faqGrid): string {
        $html=$replaceSection($html,'hero',static function(string $section) use($heroLabel,$heroFacts): string {
            $section=preg_replace('~<p class="label"[^>]*>.*?</p>~s',$heroLabel,$section,1)??$section;
            $section=preg_replace('~<dl class="hero-facts">.*?</dl>~s',$heroFacts,$section,1)??$section;
            return $section;
        });
        $html=$replaceSection($html,'offer',static function(string $section) use($offerHeading,$offerGrid): string {
            $section=preg_replace('~<div class="format-heading">.*?</div>~s',$offerHeading,$section,1)??$section;
            $section=preg_replace('~<div class="format-grid cols-3">.*?</div>~s',$offerGrid,$section,1)??$section;
            return $section;
        });
        $html=$replaceSection($html,'faq',static function(string $section) use($faqGrid): string {
            $section=preg_replace('~<div class="format-grid cols-3">.*?</div>~s',$faqGrid,$section,1)??$section;
            return $section;
        });
        return $html;
    };

    $description='Oficina on-line para construir uma Pinhole Lambe-Lambe autoral: projeto completo em PDF, construção em vídeo no seu ritmo e encontro ao vivo de 60 a 90 minutos com João Saidler.';
    $socialDescription='Projeto completo em PDF, construção em vídeo e encontro ao vivo de 60 a 90 minutos com João Saidler para construir e compreender uma Pinhole Lambe-Lambe autoral.';
    $now=gmdate('c');

    $pages=$db->query("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='pinhole-lambe-lambe' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    foreach($pages as $page){
        $draft=json_decode((string)$page['draft_document_json'],true);
        if(!is_array($draft))continue;
        $draftBefore=(string)($draft['html']??'');
        $draft['html']=$normalizeHtml($draftBefore);
        $draft['meta']['description']=$description;
        $draftChanged=$draft['html']!==$draftBefore || (($draft['meta']['description']??'')!==$description);
        $draftJson=json_encode($draft,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

        $publishedChanged=false;
        $publishedOut=$page['published_document_json'];
        if(is_string($publishedOut)&&$publishedOut!==''){
            $published=json_decode($publishedOut,true);
            if(is_array($published)){
                $publishedBefore=(string)($published['html']??'');
                $published['html']=$normalizeHtml($publishedBefore);
                $oldDescription=(string)($published['meta']['description']??'');
                $published['meta']['description']=$description;
                $publishedChanged=$published['html']!==$publishedBefore || $oldDescription!==$description;
                $publishedOut=json_encode($published,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            }
        }

        if($draftChanged||$publishedChanged){
            $q=$db->prepare('UPDATE cms_pages SET draft_document_json=?,published_document_json=?,draft_revision=draft_revision+1,published_revision=CASE WHEN ? THEN COALESCE(published_revision,draft_revision)+1 ELSE published_revision END,draft_updated_at=?,published_at=CASE WHEN ? THEN ? ELSE published_at END,updated_at=? WHERE id=?');
            $q->execute([$draftJson,$publishedOut,$publishedChanged?1:0,$now,$publishedChanged?1:0,$now,$now,(int)$page['id']]);
        }

        $hasSeo=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_page_seo'")->fetchColumn();
        if($hasSeo){
            $seo=$db->prepare('UPDATE cms_page_seo SET description=?,social_description=?,updated_at=? WHERE page_id=?');
            $seo->execute([$description,$socialDescription,$now,(int)$page['id']]);
        }
    }
};
