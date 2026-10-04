<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tableExists=static fn(string $name): bool=>(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name))->fetchColumn();
    foreach(['cms_pages','media_assets','media_versions','course_page_media_slots'] as $table)if(!$tableExists($table))return;

    $q=$db->prepare("SELECT id FROM cms_pages WHERE locale='pt-BR' AND slug='caderno-positivo-direto' AND status!='archived' LIMIT 1");
    $q->execute();$pageId=(int)($q->fetchColumn()?:0);if($pageId<1)return;

    $assets=[
        'aula3-caderno'=>['notebook.webp','Aula 3 — Caderno','Tela do Caderno de Processos mostrando os registros do aluno, seus estados e a ação necessária para continuar cada tentativa.'],
        'aula3-exposicao'=>['exposure.webp','Aula 3 — Exposição','Tela de Exposição de um registro do Caderno, com filme, EI, diafragma, tempo calculado e tempo com reciprocidade.'],
        'aula3-processamentos'=>['process-library.webp','Aula 3 — Processamentos','Biblioteca de Processamentos da área do aluno, com roteiros pessoais e padrões do workshop disponíveis para uso ou cópia.'],
        'aula3-processamento-realizado'=>['recording-partial.webp','Aula 3 — Processamento realizado','Tela de registro de processamento parcialmente realizado, mostrando etapas já registradas e a continuação do histórico real da chapa.'],
        'aula3-avaliacao'=>['result-reviewed.webp','Aula 3 — Avaliação','Tela de Resultado com avaliação concluída e retorno do professor associado ao registro da tentativa.'],
        'aula3-comparacao'=>['compare-records.webp','Aula 3 — Comparação','Tela de comparação entre dois registros, mostrando diferenças técnicas registradas e os resultados observados lado a lado.'],
        'aula3-continuidade'=>['research-derived.webp','Aula 3 — Continuidade da pesquisa','Registro criado como continuação de uma tentativa anterior, exibindo a origem e a intenção declarada para a próxima variação.'],
        'aula3-ferramentas'=>['tools.webp','Aula 3 — Ferramentas','Bancada de Ferramentas da área do aluno, reunindo recursos de exposição, processamento, receitas e organização do laboratório.'],
    ];

    $root=dirname(__DIR__);$seedRoot=__DIR__.'/assets/aula3';$mediaRoot=$root.'/uploads/media';
    if(!is_dir($mediaRoot)&&!mkdir($mediaRoot,0755,true)&&!is_dir($mediaRoot))throw new RuntimeException('aula3_media_root_unavailable');
    $now=gmdate('c');

    foreach($assets as $slot=>$spec){
        [$filename,$title,$alt]=$spec;$seed=$seedRoot.'/'.$filename.'.b64';if(!is_file($seed))continue;
        $encoded=preg_replace('/\s+/','',(string)file_get_contents($seed));$bytes=base64_decode((string)$encoded,true);if($bytes===false||$bytes==='')throw new RuntimeException('aula3_media_seed_invalid:'.$filename);
        $checksum=hash('sha256',$bytes);$assetUuid=md5('course:aula3:'.$slot);$versionUuid=md5('course:aula3:'.$slot.':'.$checksum);$mime='image/webp';$size=strlen($bytes);
        $info=@getimagesizefromstring($bytes);$width=is_array($info)?(int)$info[0]:null;$height=is_array($info)?(int)$info[1]:null;

        $find=$db->prepare('SELECT id,active_version_id,checksum FROM media_assets WHERE asset_uuid=?');$find->execute([$assetUuid]);$asset=$find->fetch(PDO::FETCH_ASSOC)?:null;
        if(!$asset){
            $db->prepare('INSERT INTO media_assets(asset_uuid,kind,title,default_alt,original_name,mime_type,byte_size,width,height,checksum,processing_status,visibility,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,\'ready\',\'private\',?,?)')
                ->execute([$assetUuid,'image',$title,$alt,$filename,$mime,$size,$width,$height,$checksum,$now,$now]);
            $assetId=(int)$db->lastInsertId();
        }else{
            $assetId=(int)$asset['id'];
            $db->prepare("UPDATE media_assets SET title=?,default_alt=?,original_name=?,mime_type=?,byte_size=?,width=?,height=?,checksum=?,processing_status='ready',visibility='private',updated_at=? WHERE id=?")
                ->execute([$title,$alt,$filename,$mime,$size,$width,$height,$checksum,$now,$assetId]);
        }

        $relative=$assetUuid.'/'.$versionUuid.'/original/file.webp';$destination=$mediaRoot.'/'.$relative;$directory=dirname($destination);
        if(!is_dir($directory)&&!mkdir($directory,0755,true)&&!is_dir($directory))throw new RuntimeException('aula3_media_storage_unavailable:'.$filename);
        if(!is_file($destination)||hash_file('sha256',$destination)!==$checksum){if(file_put_contents($destination,$bytes,LOCK_EX)===false)throw new RuntimeException('aula3_media_write_failed:'.$filename);}
        $denyRoot=$mediaRoot.'/'.$assetUuid;if(!is_dir($denyRoot)&&!mkdir($denyRoot,0755,true)&&!is_dir($denyRoot))throw new RuntimeException('aula3_media_storage_unavailable:'.$filename);
        if(file_put_contents($denyRoot.'/.htaccess',"Require all denied\n",LOCK_EX)===false)throw new RuntimeException('aula3_media_deny_failed:'.$filename);

        $versionQ=$db->prepare('SELECT id FROM media_versions WHERE version_uuid=?');$versionQ->execute([$versionUuid]);$versionId=(int)($versionQ->fetchColumn()?:0);
        if($versionId<1){
            $db->prepare("INSERT INTO media_versions(asset_id,version_uuid,original_path,mime_type,byte_size,width,height,checksum,processing_status,created_at) VALUES(?,?,?,?,?,?,?,?, 'ready',?)")
                ->execute([$assetId,$versionUuid,$relative,$mime,$size,$width,$height,$checksum,$now]);
            $versionId=(int)$db->lastInsertId();
        }
        $db->prepare('UPDATE media_assets SET active_version_id=? WHERE id=?')->execute([$versionId,$assetId]);
        $db->prepare('INSERT INTO course_page_media_slots(page_id,slot_key,media_asset_id,created_at,updated_at) VALUES(?,?,?,?,?) ON CONFLICT(page_id,slot_key) DO UPDATE SET media_asset_id=excluded.media_asset_id,updated_at=excluded.updated_at')
            ->execute([$pageId,$slot,$assetId,$now,$now]);
    }
};
