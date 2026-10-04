<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    if(!$tableExists('cms_pages'))return;

    $pageQuery=$db->prepare("SELECT * FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $pageQuery->execute();
    $page=$pageQuery->fetch(PDO::FETCH_ASSOC)?:null;
    if(!$page)return;

    $aula2=<<<'HTML'
<section class="section study-unit" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-aula2-proprios-testes" data-cms-section-name="Aula 2 · Agora é a vez de vocês">
  <p class="section-label" data-cms-editable>Agora é a vez de vocês</p>
  <div class="statement-copy">
    <p data-cms-editable>Depois de acompanhar o processo completo, o próximo passo é fazer seus próprios testes.</p>
    <p data-cms-editable>Entre esta aula e a próxima, fotografem, processem e guardem cada tentativa no Caderno da Área do Aluno. A ideia não é produzir uma coleção de fichas técnicas. É conseguir voltar a uma fotografia e saber o que realmente foi feito.</p>
  </div>
  <div class="process-list">
    <div class="process-item"><span>01</span><p data-cms-editable><strong>Exposição.</strong> Registrem as condições usadas na fotografia: filme, EI, abertura, tempo e as informações que forem importantes para aquela tentativa.</p></div>
    <div class="process-item"><span>02</span><p data-cms-editable><strong>Processamento.</strong> Um processo pode servir como referência, mas o registro deve acompanhar o que realmente aconteceu na bandeja. Se alguma coisa mudou durante o trabalho, registrem a mudança.</p></div>
    <div class="process-item"><span>03</span><p data-cms-editable><strong>Resultado.</strong> Fotografem ou digitalizem o positivo e anotem o que observaram. Não é preciso saber ainda por que o resultado aconteceu.</p></div>
  </div>
  <p class="technical-note study-summary" data-cms-editable>Registrem o que aconteceu, não o que deveria ter acontecido.</p>
  <figure class="media-figure" data-private-media-slot="aula2-registro" data-private-media-alt="Área do Aluno: um registro reúne exposição, processamento e resultado da mesma tentativa."></figure>
  <div class="statement-copy">
    <p data-cms-editable>Não é preciso chegar à terceira aula com uma fotografia “certa”. Precisamos chegar com resultados que consigamos observar, reconstruir e discutir.</p>
  </div>
</section>
HTML;

    $aula3=<<<'HTML'
<section class="section study-unit" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-aula3-area-aluno" data-cms-section-name="Aula 3 · Do resultado à próxima tentativa">
  <p class="section-label" data-cms-editable>Do resultado à próxima tentativa</p>
  <div class="statement-copy">
    <p data-cms-editable>Na aula passada a preocupação era fazer e registrar. Agora esses registros começam a ficar interessantes.</p>
    <p data-cms-editable>O Caderno reúne exposição, processamento e resultado no mesmo lugar. Isso permite voltar a uma tentativa depois que a memória já começou a colaborar menos do que gostaríamos.</p>
    <p data-cms-editable>O registro não serve apenas para repetir uma fotografia. Ele serve também para descobrir o que vale a pena mudar na próxima.</p>
  </div>
  <figure class="media-figure" data-private-media-slot="aula3-caderno" data-private-media-alt="Área do Aluno: Caderno de Processos com o histórico das experimentações."></figure>
</section>

<section class="section study-unit" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-aula3-avaliacao" data-cms-section-name="Aula 3 · Avaliação e retorno">
  <p class="section-label" data-cms-editable>Avaliação e retorno</p>
  <div class="statement-copy">
    <p data-cms-editable>Quando o resultado estiver registrado, a tentativa pode ser enviada para avaliação.</p>
    <p data-cms-editable>O retorno fica ligado à própria fotografia. Assim, quando estivermos discutindo densidade, exposição, agitação ou alguma etapa do processo, os dados que produziram aquela chapa continuam ali.</p>
    <p data-cms-editable>Se eu pedir uma revisão ou uma nova tentativa, vocês podem responder e reenviar pelo mesmo registro.</p>
  </div>
  <figure class="media-figure" data-private-media-slot="aula3-avaliacao" data-private-media-alt="Área do Aluno: resultado, observações e avaliação reunidos no mesmo registro."></figure>
</section>

<section class="section study-unit" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-aula3-comparacao" data-cms-section-name="Aula 3 · Comparar duas tentativas">
  <p class="section-label" data-cms-editable>Comparar duas tentativas</p>
  <div class="statement-copy">
    <p data-cms-editable>Quando existem duas tentativas, podemos colocá-las lado a lado e conferir o que foi registrado de forma diferente.</p>
    <p data-cms-editable>A comparação mostra as diferenças; não explica sozinha por que a imagem mudou.</p>
    <p data-cms-editable>Se o EI mudou ao mesmo tempo que o tempo de primeira revelação, por exemplo, as duas diferenças continuam existindo. O Caderno ajuda a enxergá-las; não escolhe uma causa por nós.</p>
  </div>
  <p class="technical-note study-summary" data-cms-editable>Uma diferença registrada não é, por si só, uma explicação.</p>
  <figure class="media-figure" data-private-media-slot="aula3-comparacao" data-private-media-alt="Área do Aluno: comparação descritiva de duas experimentações, seus resultados e a preparação da próxima tentativa."></figure>
</section>

<section class="section study-unit" data-layout-background="surface" data-layout-space="l" data-cms-section="caderno-aula3-continuar" data-cms-section-name="Aula 3 · Continuar a pesquisa">
  <p class="section-label" data-cms-editable>Continuar a pesquisa</p>
  <div class="statement-copy">
    <p data-cms-editable>Depois de observar ou comparar os resultados, uma tentativa pode servir como ponto de partida para a próxima.</p>
    <p data-cms-editable>Escolham o registro de origem e escrevam o que pretendem mudar. A nova tentativa começa a partir dessa intenção, mas processamento e resultado continuam vazios até que realmente aconteçam.</p>
    <p data-cms-editable>Isso é importante: a próxima fotografia herda um ponto de partida, não uma conclusão.</p>
  </div>
</section>
HTML;

    $changed=false;
    $updates=[];
    foreach(['draft_document_json','published_document_json'] as $column){
        $raw=(string)($page[$column]??'');
        if($raw==='')continue;
        $document=json_decode($raw,true);
        if(!is_array($document)||!is_string($document['html']??null))continue;
        $html=(string)$document['html'];
        $before=$html;

        if(!str_contains($html,'data-cms-section="caderno-aula2-proprios-testes"')){
            $html=preg_replace(
                '~(?=<section\b[^>]*data-cms-section=["\']caderno-aula-3["\'][^>]*>)~i',
                $aula2."\n",
                $html,
                1
            )??$html;
        }

        if(!str_contains($html,'data-cms-section="caderno-aula3-area-aluno"')){
            $html=preg_replace_callback(
                '~<section\b[^>]*data-cms-section=["\']caderno-aula-3["\'][^>]*>.*?</section>~is',
                static fn(array $match):string=>(string)$match[0]."\n".$aula3,
                $html,
                1
            )??$html;
        }

        if($html!==$before){
            $document['html']=$html;
            $updates[$column]=json_encode($document,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            $changed=true;
        }
    }

    if($changed){
        $now=gmdate('c');
        $revision=max((int)($page['draft_revision']??0),(int)($page['published_revision']??0))+1;
        $sets=[];$args=[];
        foreach($updates as $column=>$value){$sets[]=$column.'=?';$args[]=$value;}
        $sets[]='draft_revision=?';$args[]=$revision;
        $sets[]='published_revision=?';$args[]=$revision;
        $sets[]='draft_updated_at=?';$args[]=$now;
        $sets[]='published_at=?';$args[]=$now;
        $sets[]='updated_at=?';$args[]=$now;
        $args[]=(int)$page['id'];
        $db->prepare('UPDATE cms_pages SET '.implode(',',$sets).' WHERE id=?')->execute($args);
    }

    $now=gmdate('c');
    if($tableExists('course_material_pages')&&$tableExists('course_material_sections')&&$tableExists('course_lessons')){
        $courseQuery=$db->prepare('SELECT course_id FROM course_material_pages WHERE page_id=? ORDER BY course_id');
        $courseQuery->execute([(int)$page['id']]);
        $courseIds=array_map('intval',$courseQuery->fetchAll(PDO::FETCH_COLUMN));
        $sectionLessons=[
            'caderno-aula2-proprios-testes'=>'aula-2',
            'caderno-aula3-area-aluno'=>'aula-3',
            'caderno-aula3-avaliacao'=>'aula-3',
            'caderno-aula3-comparacao'=>'aula-3',
            'caderno-aula3-continuar'=>'aula-3',
        ];
        foreach($courseIds as $courseId){
            foreach($sectionLessons as $sectionKey=>$lessonKey){
                $lessonQuery=$db->prepare('SELECT id FROM course_lessons WHERE course_id=? AND lesson_key=? LIMIT 1');
                $lessonQuery->execute([$courseId,$lessonKey]);
                $lessonId=(int)($lessonQuery->fetchColumn()?:0);
                if($lessonId<1)continue;
                $map=$db->prepare('INSERT INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(?,?,?,?,?,?) ON CONFLICT(course_id,page_id,section_key) DO UPDATE SET lesson_id=excluded.lesson_id,updated_at=excluded.updated_at');
                $map->execute([$courseId,(int)$page['id'],$sectionKey,$lessonId,$now,$now]);
            }
        }
    }

    if(!$tableExists('media_assets')||!$tableExists('media_versions')||!$tableExists('course_page_media_slots'))return;

    $root=dirname(__DIR__);
    $sourceRoot=__DIR__.'/handbook-workflow-media';
    $mediaRoot=$root.'/uploads/media';
    if(!is_dir($mediaRoot)&&!mkdir($mediaRoot,0755,true)&&!is_dir($mediaRoot))throw new RuntimeException('handbook_workflow_media_root_unavailable');

    $media=[
        'aula2-registro'=>['aula2-registro.webp','Área do Aluno — Exposição, processamento e resultado'],
        'aula3-caderno'=>['aula3-caderno.webp','Área do Aluno — Caderno'],
        'aula3-avaliacao'=>['aula3-avaliacao.webp','Área do Aluno — Avaliação'],
        'aula3-comparacao'=>['aula3-comparacao.webp','Área do Aluno — Comparação e continuidade'],
    ];

    foreach($media as $slot=>[$filename,$title]){
        $chunks=glob($sourceRoot.'/'.$filename.'.*.b64')?:[];
        sort($chunks,SORT_STRING);
        if(!$chunks)throw new RuntimeException('handbook_workflow_media_source_missing:'.$filename);
        $encoded='';
        foreach($chunks as $chunk){
            $part=file_get_contents($chunk);
            if($part===false)throw new RuntimeException('handbook_workflow_media_chunk_failed:'.basename($chunk));
            $encoded.=trim($part);
        }
        $bytes=base64_decode($encoded,true);
        if($bytes===false||$bytes==='')throw new RuntimeException('handbook_workflow_media_decode_failed:'.$filename);
        $checksum=hash('sha256',$bytes);
        $assetUuid=md5('student-handbook-workflow:'.$slot);
        $versionUuid=md5($assetUuid.':'.$checksum);

        $find=$db->prepare('SELECT id,active_version_id FROM media_assets WHERE asset_uuid=? LIMIT 1');
        $find->execute([$assetUuid]);
        $asset=$find->fetch(PDO::FETCH_ASSOC)?:null;
        $assetId=(int)($asset['id']??0);

        if($assetId<1){
            $relative=$assetUuid.'/'.$versionUuid.'/original/file.webp';
            $destination=$mediaRoot.'/'.$relative;
            $directory=dirname($destination);
            if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))throw new RuntimeException('handbook_workflow_media_directory_failed');
            if(!is_file($destination)&&file_put_contents($destination,$bytes,LOCK_EX)===false)throw new RuntimeException('handbook_workflow_media_write_failed:'.$filename);
            $info=@getimagesize($destination);
            if(!is_array($info))throw new RuntimeException('handbook_workflow_media_image_invalid:'.$filename);
            $width=(int)$info[0];$height=(int)$info[1];$byteSize=(int)filesize($destination);

            $db->prepare('INSERT INTO media_assets(asset_uuid,kind,title,default_alt,original_name,mime_type,byte_size,width,height,checksum,processing_status,visibility,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,"ready","private",?,?)')
                ->execute([$assetUuid,'image',$title,'',$filename,'image/webp',$byteSize,$width,$height,$checksum,$now,$now]);
            $assetId=(int)$db->lastInsertId();
            $db->prepare('INSERT INTO media_versions(asset_id,version_uuid,original_path,mime_type,byte_size,width,height,checksum,processing_status,created_at) VALUES(?,?,?,?,?,?,?,?,"ready",?)')
                ->execute([$assetId,$versionUuid,$relative,'image/webp',$byteSize,$width,$height,$checksum,$now]);
            $versionId=(int)$db->lastInsertId();
            $db->prepare('UPDATE media_assets SET active_version_id=? WHERE id=?')->execute([$versionId,$assetId]);
        }else{
            $db->prepare("UPDATE media_assets SET visibility='private',title=?,updated_at=? WHERE id=?")->execute([$title,$now,$assetId]);
        }

        $assetDir=$mediaRoot.'/'.$assetUuid;
        if(!is_dir($assetDir)&&!mkdir($assetDir,0755,true)&&!is_dir($assetDir))throw new RuntimeException('handbook_workflow_media_asset_directory_failed');
        file_put_contents($assetDir.'/.htaccess',"Require all denied\n",LOCK_EX);

        $db->prepare('INSERT INTO course_page_media_slots(page_id,slot_key,media_asset_id,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(page_id,slot_key) DO UPDATE SET media_asset_id=excluded.media_asset_id,updated_at=excluded.updated_at')
            ->execute([(int)$page['id'],$slot,$assetId,$now,$now]);
    }
};
