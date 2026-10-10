<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$hasPages)return;

    if(function_exists('cms_pages_seed')){
        $activityIds=$db->query('SELECT id FROM activities')->fetchAll(PDO::FETCH_COLUMN);
        foreach($activityIds as $activityId)cms_pages_seed($db,(int)$activityId);
    }

    $hasLegacy=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='content_documents'")->fetchColumn();
    $hasMedia=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='media_assets'")->fetchColumn();
    $normalize=static function(string $src): string {
        $src=trim($src);
        if(str_starts_with($src,'../assets/'))return '/assets/'.substr($src,10);
        if(str_starts_with($src,'assets/'))return '/'.$src;
        return $src;
    };
    $escape=static fn(string $value): string=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');

    $processMedia=static function(int $activityId) use($db,$hasLegacy,$hasMedia,$normalize): array {
        $value=[];
        if($hasLegacy){
            $q=$db->prepare('SELECT published_json FROM content_documents WHERE activity_id=? LIMIT 1');
            $q->execute([$activityId]);$json=$q->fetchColumn();
            if(is_string($json)&&$json!==''){
                $legacy=json_decode($json,true);
                if(is_array($legacy)&&is_array($legacy['videos']['process-video']??null))$value=$legacy['videos']['process-video'];
            }
        }
        $assetId=(int)($value['mediaAssetId']??0);
        $src=$normalize((string)($value['src']??''));
        $poster=$normalize((string)($value['poster']??''));
        if($assetId<1&&$hasMedia){
            $q=$db->query("SELECT id FROM media_assets WHERE kind='video' AND archived_at IS NULL AND (LOWER(title) LIKE '%catedral%' OR LOWER(original_name) LIKE '%catedral%' OR LOWER(title) LIKE '%cathedral%' OR LOWER(original_name) LIKE '%cathedral%') ORDER BY updated_at DESC,id DESC LIMIT 1");
            $assetId=(int)($q->fetchColumn()?:0);
        }
        return ['assetId'=>$assetId,'src'=>$src,'poster'=>$poster];
    };

    $videoMarkup=static function(array $media,string $locale) use($escape): string {
        $caption=$locale==='pt-BR'?'Catedral · Petrópolis · Da exposição ao positivo':'Catedral · Petrópolis · From exposure to positive';
        if((int)$media['assetId']>0){
            $video='<video controls muted playsinline preload="metadata" data-cms-process-video data-media-asset-id="'.(int)$media['assetId'].'" data-media-version-mode="latest"></video>';
        }elseif((string)$media['src']!==''){
            $poster=(string)$media['poster']!==''?' poster="'.$escape((string)$media['poster']).'"':'';
            $video='<video controls muted playsinline preload="metadata" data-cms-process-video src="'.$escape((string)$media['src']).'"'.$poster.'></video>';
        }else{
            $label=$locale==='pt-BR'?'Exposição · Revelação · Positivo final':'Exposure · Development · Final positive';
            $number=$locale==='pt-BR'?'02 / Processo':'02 / Process';
            $video='<div class="reel-placeholder" data-cms-process-video-placeholder><span class="slot-number">'.$number.'</span><span class="slot-title">Catedral<br>Petrópolis</span><span class="slot-note">'.$label.'</span></div>';
        }
        return '<figure class="media-figure vertical-frame process-media" data-cms-process-video-slot><div class="media-area">'.$video.'</div><figcaption class="media-caption frame-data" data-cms-editable>'.$caption.'</figcaption></figure>';
    };

    $processSection=static function(array $media,string $locale) use($videoMarkup): string {
        if($locale==='pt-BR')return '<section class="section process" data-cms-section="process" data-cms-section-name="Processo">'.$videoMarkup($media,$locale).'<div class="process-copy"><p class="section-label" data-cms-editable>02 / Da exposição ao positivo</p><h2 data-cms-editable>O vídeo acompanha a transformação do filme até o positivo final.</h2><p data-cms-editable>Da exposição ao resultado final, o mesmo filme passa pela primeira revelação, reversão, reexposição e segunda revelação. No workshop, essa cadeia é acompanhada ao vivo e cada resultado é lido em função das decisões tomadas antes e durante o processamento.</p><div class="process-list"><article class="process-item"><span>01</span><h3 data-cms-editable>Exposição</h3><p data-cms-editable>Medição da luz, índice de exposição, distribuição tonal e limites do filme de raios X.</p></article><article class="process-item"><span>02</span><h3 data-cms-editable>Primeira revelação</h3><p data-cms-editable>Tempo, temperatura, agitação, revelador e diluição como variáveis que modificam o positivo.</p></article><article class="process-item"><span>03</span><h3 data-cms-editable>Reversão</h3><p data-cms-editable>Branqueamento, limpeza, reexposição e segunda revelação acompanhados como partes da mesma cadeia.</p></article><article class="process-item"><span>04</span><h3 data-cms-editable>Leitura do resultado</h3><p data-cms-editable>Sombras, meios-tons, altas luzes e falhas do processo usados para orientar a decisão seguinte.</p></article></div></div></section>';
        return '<section class="section process" data-cms-section="process" data-cms-section-name="Process">'.$videoMarkup($media,$locale).'<div class="process-copy"><p class="section-label" data-cms-editable>02 / The process in full</p><h2 data-cms-editable>The same sheet is followed from exposure to the final positive.</h2><p data-cms-editable>First development, bleaching, clearing, re-exposure and second development are not treated as isolated recipe steps. They are read together with exposure and the final tonal result.</p><div class="process-list"><article class="process-item"><span>01</span><h3 data-cms-editable>Exposure</h3><p data-cms-editable>Metering, working EI, tonal placement, reciprocity and the particular limits of X-ray film.</p></article><article class="process-item"><span>02</span><h3 data-cms-editable>First development</h3><p data-cms-editable>Time, temperature, agitation, developer and dilution as variables that change the final positive.</p></article><article class="process-item"><span>03</span><h3 data-cms-editable>Reversal</h3><p data-cms-editable>Bleaching, clearing, re-exposure and second development treated as one connected chain.</p></article><article class="process-item"><span>04</span><h3 data-cms-editable>Reading the result</h3><p data-cms-editable>Shadows, midtones, highlights and process defects used to decide what the next test should change.</p></article></div></div></section>';
    };

    $setInnerHtml=static function(DOMDocument $dom,DOMElement $target,string $html): void {
        while($target->firstChild)$target->removeChild($target->firstChild);
        $source=new DOMDocument('1.0','UTF-8');$previous=libxml_use_internal_errors(true);
        $source->loadHTML('<?xml encoding="utf-8" ?><div id="fragment-root">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
        $wrapper=$source->getElementById('fragment-root');if($wrapper)foreach(iterator_to_array($wrapper->childNodes) as $child)$target->appendChild($dom->importNode($child,true));
        libxml_clear_errors();libxml_use_internal_errors($previous);
    };
    $replaceSection=static function(DOMDocument $dom,DOMXPath $xp,string $key,string $html): ?DOMElement {
        $old=$xp->query('//*[@data-cms-section="'.$key.'"]')->item(0);
        $source=new DOMDocument('1.0','UTF-8');$previous=libxml_use_internal_errors(true);
        $source->loadHTML('<?xml encoding="utf-8" ?><div id="section-fragment">'.$html.'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);
        $wrapper=$source->getElementById('section-fragment');$node=$wrapper?iterator_to_array($wrapper->childNodes)[0]??null:null;
        $imported=$node?$dom->importNode($node,true):null;
        if($imported instanceof DOMElement){
            if($old&&$old->parentNode)$old->parentNode->replaceChild($imported,$old);
            else{$root=$dom->getElementById('editorial-root');$root?->appendChild($imported);}
        }
        libxml_clear_errors();libxml_use_internal_errors($previous);return $imported instanceof DOMElement?$imported:null;
    };
    $section=static fn(DOMXPath $xp,string $key): ?DOMElement=>($xp->query('//*[@data-cms-section="'.$key.'"]')->item(0) instanceof DOMElement)?$xp->query('//*[@data-cms-section="'.$key.'"]')->item(0):null;
    $setLabel=static function(DOMXPath $xp,string $key,string $text) use($section): void {$s=$section($xp,$key);if(!$s)return;$n=(new DOMXPath($s->ownerDocument))->query('.//*[contains(concat(" ",normalize-space(@class)," ")," section-label ")]',$s)->item(0);if($n)$n->nodeValue=$text;};
    $insertAfter=static function(DOMNode $anchor,DOMNode $node): void {$parent=$anchor->parentNode;if(!$parent)return;$next=$anchor->nextSibling;$next?$parent->insertBefore($node,$next):$parent->appendChild($node);};

    $rows=$db->query("SELECT id,activity_id,locale,draft_document_json,published_document_json,draft_revision,published_revision FROM cms_pages WHERE is_home=1 AND status!='archived'")->fetchAll(PDO::FETCH_ASSOC);
    $now=gmdate('c');$mediaByActivity=[];
    foreach($rows as $row){
        $activityId=(int)$row['activity_id'];$locale=(string)$row['locale'];
        if(!isset($mediaByActivity[$activityId]))$mediaByActivity[$activityId]=$processMedia($activityId);
        $updates=[];
        foreach(['draft_document_json','published_document_json'] as $column){
            $json=$row[$column]??null;if(!is_string($json)||$json==='')continue;$document=json_decode($json,true);if(!is_array($document)||!is_string($document['html']??null))continue;
            $previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');$dom->loadHTML('<?xml encoding="utf-8" ?><div id="editorial-root">'.$document['html'].'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$xp=new DOMXPath($dom);

            $replaceSection($dom,$xp,'process',$processSection($mediaByActivity[$activityId],$locale));$xp=new DOMXPath($dom);
            $about=$section($xp,'about');if($about){$copy=$xp->query('.//*[contains(concat(" ",normalize-space(@class)," ")," about-copy ")]',$about)->item(0);if($copy instanceof DOMElement){
                if($locale==='pt-BR')$setInnerHtml($dom,$copy,'<p class="section-label" data-cms-editable>07 / O fotógrafo</p><h2 data-cms-editable>João Saidler</h2><p class="about-lead" data-cms-editable>Fotógrafo e pesquisador independente. Há quase oito anos desenvolve uma pesquisa em fotografia química que reúne construção de equipamentos, filme de raios X e processos de revelação.</p><p data-cms-editable>Nesse percurso, projetou e construiu câmeras de grande formato, obturadores, tanques e outros sistemas necessários ao trabalho, enquanto desenvolvia o processo que hoje utiliza para produzir positivos diretos no próprio filme de raios X.</p><dl class="recognition"><div><dt>Reconhecimento internacional</dt><dd>Duas distinções no Siena Creative Photo Awards, incluindo Highly Commended na categoria People em 2025.</dd></div><div><dt>Publicações e prêmios</dt><dd>Seleções e destaques recorrentes na STRKNG e reconhecimento no ArtLimited Portraiture Awards.</dd></div></dl>');
                else $setInnerHtml($dom,$copy,'<p class="section-label" data-cms-editable>05 / Research and photographer</p><h2 data-cms-editable>João Saidler</h2><p class="about-lead" data-cms-editable>For nearly eight years, photographer and independent researcher João Saidler has developed a chemical-photography practice that joins apparatus design, X-ray film and reversal processing.</p><p data-cms-editable>He designs and builds the equipment required by the work — large-format cameras, shutters, tanks and processing systems — while developing the direct-positive process itself. The workshop grows out of that ongoing research rather than a single fixed formula.</p><dl class="recognition"><div><dt>International recognition</dt><dd>Two distinctions at the Siena Creative Photo Awards, including Highly Commended in the People category in 2025.</dd></div><div><dt>Editorial presence and awards</dt><dd>Repeated selections and features by STRKNG and recognition in the ArtLimited Portraiture Awards.</dd></div></dl>');
            }}
            $camera=$section($xp,'camera');if($camera){$copy=$xp->query('.//*[contains(concat(" ",normalize-space(@class)," ")," apparatus-copy ")]',$camera)->item(0);if($copy instanceof DOMElement){
                if($locale==='pt-BR')$setInnerHtml($dom,$copy,'<p class="section-label" data-cms-editable>04 / Equipamento</p><h2 data-cms-editable>A câmera faz parte do processo — mas você trabalha com o equipamento que tiver.</h2><p data-cms-editable>A NINA é uma câmera artesanal de grande formato criada para trabalhar com positivo direto em filme de raios X. Ela aparece no workshop como uma das ferramentas desenvolvidas para a pesquisa, não como equipamento que o participante precise reproduzir.</p><p class="technical-note" data-cms-editable>É possível trabalhar com 35 mm, médio formato ou grande formato. O foco do workshop é compreender o processo e suas variáveis.</p>');
                else $setInnerHtml($dom,$copy,'<p class="section-label" data-cms-editable>03 / Apparatus</p><h2 data-cms-editable>The equipment was developed as part of the photographic research.</h2><p data-cms-editable>The NINA is one of the large-format cameras I designed and built for work with direct-positive X-ray film. Cameras, shutters, tanks and processing systems are developed when the process requires them; the apparatus is a means to control the image, not the subject of the workshop.</p><p class="technical-note" data-cms-editable>Participants can work with 35 mm, medium format or large format. Reproducing my equipment is not a requirement.</p>');
            }}

            $xp=new DOMXPath($dom);$workshop=$section($xp,'workshop');
            if($locale==='pt-BR'){
                $setLabel($xp,'first-cohort','03 / Primeira turma');$setLabel($xp,'support','05 / Ferramenta incluída');$setLabel($xp,'format','06 / Formato');$setLabel($xp,'registration','08 / Nova turma');
                if($workshop){$anchor=$workshop;foreach(['process','first-cohort','camera','support','format','about','registration'] as $key){$node=$section($xp,$key);if($node){$insertAfter($anchor,$node);$anchor=$node;}}}
            }else{
                $hero=$section($xp,'hero');if($hero){$intro=$xp->query('.//*[contains(concat(" ",normalize-space(@class)," ")," hero-intro ")]',$hero)->item(0);if($intro)$intro->nodeValue='A fully live online workshop on direct-positive X-ray film, built from nearly eight years of independent research into cameras, chemistry and reversal processing.';}
                $support=$section($xp,'support');if($support&&$support->parentNode)$support->parentNode->removeChild($support);
                $setLabel($xp,'format','04 / Format');$setLabel($xp,'first-cohort','06 / Already tested live');$setLabel($xp,'interest','07 / First English-language cohort');
                if($workshop){$anchor=$workshop;foreach(['process','camera','format','about','first-cohort','interest'] as $key){$node=$section($xp,$key);if($node){$insertAfter($anchor,$node);$anchor=$node;}}}
            }

            $root=$dom->getElementById('editorial-root');$out='';if($root)foreach(iterator_to_array($root->childNodes) as $child)$out.=$dom->saveHTML($child);libxml_clear_errors();libxml_use_internal_errors($previous);
            if($out===''||$out===$document['html'])continue;$document['html']=$out;$updates[$column]=json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        }
        if(!$updates)continue;$sets=[];$args=[];
        if(isset($updates['draft_document_json'])){$sets[]='draft_document_json=?';$args[]=$updates['draft_document_json'];$sets[]='draft_revision=?';$args[]=(int)$row['draft_revision']+1;$sets[]='draft_updated_at=?';$args[]=$now;}
        if(isset($updates['published_document_json'])){$sets[]='published_document_json=?';$args[]=$updates['published_document_json'];$sets[]='published_revision=?';$args[]=max(1,(int)($row['published_revision']??0)+1);$sets[]='published_at=?';$args[]=$now;}
        $sets[]='updated_at=?';$args[]=$now;$args[]=(int)$row['id'];$db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
    }
};
