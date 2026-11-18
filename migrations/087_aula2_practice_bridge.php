<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    if(!$tableExists('cms_pages')||!$tableExists('course_page_sections'))return;

    $q=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();
    $page=$q->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;
    $pageId=(int)$page['id'];

    $lessonQ=$db->prepare("SELECT lesson_id FROM course_page_sections WHERE page_id=? AND section_key='caderno-aula-2' LIMIT 1");
    $lessonQ->execute([$pageId]);
    $lessonId=(int)($lessonQ->fetchColumn()?:0);
    if($lessonId<1&&$tableExists('course_material_sections')){
        $lessonQ=$db->prepare("SELECT lesson_id FROM course_material_sections WHERE page_id=? AND section_key='caderno-aula-2' LIMIT 1");
        $lessonQ->execute([$pageId]);
        $lessonId=(int)($lessonQ->fetchColumn()?:0);
    }
    if($lessonId<1)return;

    $availability=' data-cms-availability="lesson" data-cms-lesson-id="'.$lessonId.'"';
    $bridge=<<<HTML
<section class="section" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-20a-agora-e-a-vez" data-cms-section-name="Aula 2 · Agora é a vez de vocês"$availability>
  <p class="section-label" data-cms-editable>Agora é a vez de vocês</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Depois de acompanhar o processo completo, o próximo passo é fazer as próprias fotografias e produzir os próprios resultados.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Entre o segundo e o terceiro encontro, quero que vocês repitam o processo com as próprias chapas. O Caderno da área do aluno acompanha esse percurso em três momentos: exposição, processamento e resultado.</p>
      <p data-cms-editable>Não precisa transformar cada tentativa num relatório. O que precisa ficar registrado é informação suficiente para que, depois, possamos reconstruir o que aconteceu e discutir o resultado.</p>
    </div>
  </div>
  <div class="format-grid">
    <article class="format-card"><span class="number">01</span><h3 data-cms-editable>Exposição</h3><p data-cms-editable>Registre as condições realmente usadas na fotografia: filme, EI, diafragma, tempo e as demais informações que forem importantes para aquela tentativa.</p></article>
    <article class="format-card"><span class="number">02</span><h3 data-cms-editable>Processamento</h3><p data-cms-editable>Use os processos do workshop como ponto de partida, mas anote o que realmente acontecer no laboratório. Se mudar tempo, temperatura, movimentação ou uma etapa, é essa mudança que deve ficar no registro.</p></article>
    <article class="format-card"><span class="number">03</span><h3 data-cms-editable>Resultado</h3><p data-cms-editable>Anexe uma imagem da chapa e escreva as observações que fizerem sentido naquele momento. Não é necessário chegar imediatamente a uma explicação.</p></article>
  </div>
  <p class="technical-note" data-cms-editable>Registre o que aconteceu, não o que deveria ter acontecido.</p>
  <figure data-private-media-slot="aula2-caderno" data-private-media-alt="Tela do Caderno de Processos mostrando os registros do aluno e a continuidade entre exposição, processamento e resultado."></figure>
</section>

<section class="section" data-layout-background="inverse" data-layout-space="l" data-cms-section="caderno-20b-antes-terceiro-encontro" data-cms-section-name="Aula 2 · Antes do terceiro encontro"$availability>
  <p class="section-label" data-cms-editable>Antes do terceiro encontro</p>
  <div class="statement-grid">
    <div><h2 data-cms-editable>Não é preciso chegar com uma fotografia “certa”. Precisamos chegar com resultados que possamos observar, reconstruir e discutir.</h2></div>
    <div class="statement-copy">
      <p data-cms-editable>Façam suas próprias fotografias, processem as chapas e preservem cada tentativa no Caderno. Incluam a imagem final e as observações que vocês gostariam de encontrar se fossem abrir o registro novamente algumas semanas depois.</p>
      <p data-cms-editable>No terceiro encontro, esses registros serão o nosso ponto de partida para olhar os resultados, discutir problemas, comparar tentativas e decidir conscientemente o que vale a pena testar em seguida.</p>
    </div>
  </div>
  <div class="process-list">
    <div class="process-item"><span>01</span><p data-cms-editable>façam suas próprias fotografias</p></div>
    <div class="process-item"><span>02</span><p data-cms-editable>processem as chapas</p></div>
    <div class="process-item"><span>03</span><p data-cms-editable>registrem cada tentativa no Caderno</p></div>
    <div class="process-item"><span>04</span><p data-cms-editable>incluam a imagem final e suas observações</p></div>
  </div>
</section>
HTML;

    $transform=static function(?string $json) use($bridge): ?string {
        if($json===null||trim($json)==='')return $json;
        $doc=json_decode($json,true);
        if(!is_array($doc)||!isset($doc['html'])||!is_string($doc['html']))return $json;
        $html=$doc['html'];
        if(str_contains($html,'data-cms-section="caderno-20a-agora-e-a-vez"'))return $json;
        $marker='<section id="caderno-aula-3"';
        $position=strpos($html,$marker);
        if($position===false)return $json;
        $doc['html']=substr($html,0,$position).$bridge."\n\n".substr($html,$position);
        return json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    };

    $draft=$transform($page['draft_document_json']??null);
    $published=$transform($page['published_document_json']??null);
    if($draft===($page['draft_document_json']??null)&&$published===($page['published_document_json']??null))return;

    $now=gmdate('c');
    $revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
    $db->beginTransaction();
    try{
        $db->prepare('UPDATE cms_pages SET draft_document_json=?,published_document_json=?,draft_revision=?,published_revision=?,draft_updated_at=?,published_at=?,updated_at=? WHERE id=?')
            ->execute([$draft,$published,$revision,$revision,$now,$now,$now,$pageId]);

        $sectionKeys=['caderno-20a-agora-e-a-vez','caderno-20b-antes-terceiro-encontro'];
        $ins=$db->prepare('INSERT OR REPLACE INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?)');
        foreach($sectionKeys as $sectionKey)$ins->execute([$pageId,$sectionKey,$lessonId,$now,$now]);

        if($tableExists('course_material_sections')){
            $courseIds=[];
            $courseQ=$db->prepare('SELECT DISTINCT course_id FROM course_material_sections WHERE page_id=? AND lesson_id=?');
            $courseQ->execute([$pageId,$lessonId]);
            foreach($courseQ->fetchAll(PDO::FETCH_COLUMN) as $courseId)$courseIds[]=(int)$courseId;
            $insCourse=$db->prepare('INSERT OR REPLACE INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?,?)');
            foreach($courseIds as $courseId)foreach($sectionKeys as $sectionKey)$insCourse->execute([$courseId,$pageId,$sectionKey,$lessonId,$now,$now]);
        }

        if($tableExists('course_page_media_slots')){
            $slotQ=$db->prepare("SELECT media_asset_id FROM course_page_media_slots WHERE page_id=? AND slot_key='aula3-caderno' LIMIT 1");
            $slotQ->execute([$pageId]);
            $assetId=(int)($slotQ->fetchColumn()?:0);
            if($assetId>0){
                $db->prepare('INSERT INTO course_page_media_slots(page_id,slot_key,media_asset_id,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(page_id,slot_key) DO UPDATE SET media_asset_id=excluded.media_asset_id,updated_at=excluded.updated_at')
                    ->execute([$pageId,'aula2-caderno',$assetId,$now,$now]);
            }
        }
        $db->commit();
    }catch(Throwable $e){
        if($db->inTransaction())$db->rollBack();
        throw $e;
    }
};
