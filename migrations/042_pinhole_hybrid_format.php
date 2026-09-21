<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    $replacements=[
        'Oficina on-line e ao vivo'=>'Oficina on-line · conteúdo em vídeo + encontro ao vivo',
        '<div><dt>Formato</dt><dd data-cms-editable>1 encontro ao vivo</dd></div>'=>'<div><dt>Formato</dt><dd data-cms-editable>Vídeo + encontro ao vivo</dd></div>',
        '<div><dt>Duração</dt><dd data-cms-editable>2 a 3 horas</dd></div>'=>'<div><dt>Encontro</dt><dd data-cms-editable>60 a 90 minutos</dd></div>',
        'O projeto completo da câmera — e a construção demonstrada do começo ao fim.'=>'O projeto completo. A construção em vídeo. E um encontro ao vivo comigo.',
        'Antes do encontro, você recebe um PDF com a lista de materiais e as peças para imprimir, colar no papel paraná e recortar.'=>'Você recebe o projeto em PDF e acompanha a construção completa em vídeo, no seu ritmo. Depois, participa de um encontro ao vivo de 60 a 90 minutos para discutir a câmera, a operação e as dúvidas que surgirem na montagem.',
        '<h3 data-cms-editable>Construção</h3><p data-cms-editable>Cada parte da câmera é mostrada e explicada até o equipamento completo.</p>'=>'<h3 data-cms-editable>Construção em vídeo</h3><p data-cms-editable>Cada parte da câmera é mostrada e explicada para você construir no seu ritmo.</p>',
        '<h3 data-cms-editable>Operação</h3><p data-cms-editable>Como preparar e usar a câmera e o pequeno laboratório integrado.</p>'=>'<h3 data-cms-editable>Encontro ao vivo</h3><p data-cms-editable>Um encontro de 60 a 90 minutos para discutir a operação, as dúvidas da montagem e a própria câmera.</p>',
        'Os princípios necessários para compreender a câmera são apresentados durante o encontro.'=>'Os princípios necessários para compreender a câmera são apresentados no conteúdo em vídeo e retomados no encontro ao vivo.',
        '<article class="format-card"><h3 data-cms-editable>A câmera é funcional?</h3><p data-cms-editable>Sim. O projeto foi concebido para produzir uma câmera pinhole com um pequeno laboratório integrado.</p></article>'=>'<article class="format-card"><h3 data-cms-editable>Como funciona o conteúdo em vídeo?</h3><p data-cms-editable>A construção completa da câmera é demonstrada em vídeo para você acompanhar e montar no seu ritmo.</p></article>',
        '<article class="format-card"><h3 data-cms-editable>O encontro fica gravado?</h3><p data-cms-editable>Não. O encontro acontece ao vivo e o projeto em PDF fica com você.</p></article>'=>'<article class="format-card"><h3 data-cms-editable>O encontro ao vivo fica gravado?</h3><p data-cms-editable>Não. A demonstração da construção já faz parte do conteúdo em vídeo; o encontro ao vivo é dedicado à conversa, à operação e às dúvidas.</p></article>',
        'O encontro é dedicado à construção, à formação da imagem e à operação do equipamento.'=>'O conteúdo é dedicado à construção, à formação da imagem e à operação do equipamento.',
        'Oficina on-line e ao vivo sobre a construção e a operação de uma Pinhole Lambe-Lambe autoral, com projeto completo em PDF.'=>'Oficina on-line para construir uma Pinhole Lambe-Lambe autoral, com projeto completo em PDF, construção em vídeo e encontro ao vivo com João Saidler.',
    ];

    $now=gmdate('c');
    $pages=$db->query("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='pinhole-lambe-lambe' AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    foreach($pages as $page){
        $draft=json_decode((string)$page['draft_document_json'],true);
        if(!is_array($draft))continue;
        $draftHtml=(string)($draft['html']??'');
        $draft['html']=str_replace(array_keys($replacements),array_values($replacements),$draftHtml);
        if(isset($draft['meta']['description']))$draft['meta']['description']='Oficina on-line para construir uma Pinhole Lambe-Lambe autoral, com projeto completo em PDF, construção em vídeo e encontro ao vivo com João Saidler.';
        $draftJson=json_encode($draft,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);

        $publishedOut=$page['published_document_json'];
        if(is_string($publishedOut)&&$publishedOut!==''){
            $published=json_decode($publishedOut,true);
            if(is_array($published)){
                $publishedHtml=(string)($published['html']??'');
                $published['html']=str_replace(array_keys($replacements),array_values($replacements),$publishedHtml);
                if(isset($published['meta']['description']))$published['meta']['description']='Oficina on-line para construir uma Pinhole Lambe-Lambe autoral, com projeto completo em PDF, construção em vídeo e encontro ao vivo com João Saidler.';
                $publishedOut=json_encode($published,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            }
        }

        $q=$db->prepare('UPDATE cms_pages SET draft_document_json=?,published_document_json=?,draft_revision=draft_revision+1,published_revision=CASE WHEN published_document_json IS NULL THEN published_revision ELSE COALESCE(published_revision,draft_revision)+1 END,draft_updated_at=?,published_at=CASE WHEN published_document_json IS NULL THEN published_at ELSE ? END,updated_at=? WHERE id=?');
        $q->execute([$draftJson,$publishedOut,$now,$now,$now,(int)$page['id']]);

        $hasSeo=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_page_seo'")->fetchColumn();
        if($hasSeo){
            $seo=$db->prepare('UPDATE cms_page_seo SET description=?,social_description=?,updated_at=? WHERE page_id=?');
            $seo->execute([
                'Oficina on-line para construir uma Pinhole Lambe-Lambe autoral, com projeto completo em PDF, construção em vídeo e encontro ao vivo com João Saidler.',
                'Projeto completo em PDF, construção em vídeo e encontro ao vivo com João Saidler para construir e compreender uma Pinhole Lambe-Lambe autoral.',
                $now,
                (int)$page['id'],
            ]);
        }
    }
};
