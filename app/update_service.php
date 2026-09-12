<?php
declare(strict_types=1);

const UPDATE_REPO_RAW='https://raw.githubusercontent.com/jsaidler/workshop_internacional/production-dist/';
const UPDATE_MAX_FILE_BYTES=16777216;

function update_app_root(): string { return dirname(__DIR__); }
function update_storage_root(): string { return update_app_root().'/storage/updates'; }
function update_persistent_path(string $relative): bool {
    $relative=str_replace('\\','/',$relative);
    return $relative==='config/local.php'||$relative==='config/install.php'||$relative==='storage/database.sqlite'||$relative==='storage/installed.lock'||str_starts_with($relative,'storage/logs/')||str_starts_with($relative,'storage/updates/')||str_starts_with($relative,'uploads/');
}
function update_safe_relative(string $relative): string {
    $relative=ltrim(str_replace('\\','/',$relative),'/');
    if($relative===''||str_contains($relative,'..')||str_contains($relative,"\0")||preg_match('~^[a-z]+:~i',$relative))throw new RuntimeException('unsafe_update_path');
    return $relative;
}
function update_http_get(string $url,int $maxBytes=UPDATE_MAX_FILE_BYTES): string {
    $context=stream_context_create(['http'=>['timeout'=>20,'follow_location'=>1,'user_agent'=>'WorkshopCMS-Updater/1.0'],'https'=>['timeout'=>20]]);
    $data=@file_get_contents($url,false,$context,0,$maxBytes+1);
    if($data===false){
        if(function_exists('curl_init')){
            $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>20,CURLOPT_USERAGENT=>'WorkshopCMS-Updater/1.0',CURLOPT_MAXFILESIZE=>$maxBytes]);$data=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);if(!is_string($data)||$code<200||$code>=300)throw new RuntimeException('update_download_failed'.($error?': '.$error:''));
        }else throw new RuntimeException('update_download_failed');
    }
    if(strlen($data)>$maxBytes)throw new RuntimeException('update_file_too_large');
    return $data;
}
function update_remote_json(string $file): array {
    $data=json_decode(update_http_get(UPDATE_REPO_RAW.rawurlencode($file),4194304),true);
    if(!is_array($data))throw new RuntimeException('invalid_update_manifest');
    return $data;
}
function update_local_info(): array {
    $file=update_app_root().'/deploy-info.json';if(!is_file($file))return ['sourceSha'=>'unknown','generatedAt'=>null,'files'=>null];$data=json_decode((string)file_get_contents($file),true);return is_array($data)?$data:['sourceSha'=>'unknown','generatedAt'=>null,'files'=>null];
}
function update_remote_info(): array { return update_remote_json('deploy-info.json'); }
function update_status(): array {
    $local=update_local_info();$remote=update_remote_info();$localSha=(string)($local['sourceSha']??'unknown');$remoteSha=(string)($remote['sourceSha']??'unknown');return ['local'=>$local,'remote'=>$remote,'available'=>$remoteSha!=='unknown'&&$remoteSha!==$localSha];
}
function update_manifest_validate(array $manifest): array {
    if((int)($manifest['schema']??0)!==1||!is_array($manifest['files']??null))throw new RuntimeException('invalid_update_manifest');
    $files=[];
    foreach($manifest['files'] as $relative=>$meta){$relative=update_safe_relative((string)$relative);if(update_persistent_path($relative))continue;if(!is_array($meta)||!preg_match('/^[a-f0-9]{64}$/i',(string)($meta['sha256']??'')))throw new RuntimeException('invalid_update_manifest');$bytes=(int)($meta['bytes']??0);if($bytes<0||$bytes>UPDATE_MAX_FILE_BYTES)throw new RuntimeException('invalid_update_manifest');$files[$relative]=['sha256'=>strtolower((string)$meta['sha256']),'bytes'=>$bytes];}
    ksort($files);return ['sourceSha'=>(string)($manifest['sourceSha']??'unknown'),'generatedAt'=>$manifest['generatedAt']??null,'files'=>$files];
}
function update_local_manifest_files(): array {
    $file=update_app_root().'/deploy-manifest.json';if(!is_file($file))return [];$data=json_decode((string)file_get_contents($file),true);if(!is_array($data))return [];try{return update_manifest_validate($data)['files'];}catch(Throwable){return [];}
}
function update_mkdir(string $dir): void {if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new RuntimeException('update_storage_unavailable');}
function update_copy_atomic(string $source,string $destination): void {
    update_mkdir(dirname($destination));$tmp=$destination.'.update-'.bin2hex(random_bytes(4));if(!copy($source,$tmp)){@unlink($tmp);throw new RuntimeException('update_write_failed');}if(is_file($destination)&&!is_writable($destination)){@unlink($tmp);throw new RuntimeException('update_target_not_writable');}if(!@rename($tmp,$destination)){@unlink($tmp);throw new RuntimeException('update_write_failed');}
}
function update_apply(): array {
    $manifest=update_manifest_validate(update_remote_json('deploy-manifest.json'));$sourceSha=$manifest['sourceSha'];if($sourceSha==='unknown')throw new RuntimeException('invalid_update_manifest');
    $root=update_app_root();$storage=update_storage_root();update_mkdir($storage);$stamp=gmdate('Ymd-His').'-'.substr(preg_replace('/[^a-f0-9]/i','',$sourceSha),0,12);$stage=$storage.'/stage-'.$stamp;$backup=$storage.'/backup-'.$stamp;update_mkdir($stage);update_mkdir($backup);
    $changed=[];$removed=[];$old=update_local_manifest_files();
    try{
        foreach($manifest['files'] as $relative=>$meta){$target=$root.'/'.$relative;$current=is_file($target)?strtolower((string)hash_file('sha256',$target)):'';if($current===$meta['sha256'])continue;$data=update_http_get(UPDATE_REPO_RAW.str_replace('%2F','/',rawurlencode($relative)),max(1024,$meta['bytes']+1024));if(strtolower(hash('sha256',$data))!==$meta['sha256'])throw new RuntimeException('update_checksum_mismatch: '.$relative);$stageFile=$stage.'/'.$relative;update_mkdir(dirname($stageFile));if(file_put_contents($stageFile,$data,LOCK_EX)===false)throw new RuntimeException('update_storage_unavailable');$changed[]=$relative;}
        foreach($old as $relative=>$meta)if(!isset($manifest['files'][$relative])&&!update_persistent_path($relative)&&is_file($root.'/'.$relative))$removed[]=$relative;
        foreach(array_unique(array_merge($changed,$removed)) as $relative){$target=$root.'/'.$relative;if(!is_file($target))continue;$backupFile=$backup.'/'.$relative;update_mkdir(dirname($backupFile));if(!copy($target,$backupFile))throw new RuntimeException('update_backup_failed');}
        foreach($changed as $relative)update_copy_atomic($stage.'/'.$relative,$root.'/'.$relative);
        foreach($removed as $relative)if(is_file($root.'/'.$relative)&&!@unlink($root.'/'.$relative))throw new RuntimeException('update_remove_failed');
        $remoteManifest=update_http_get(UPDATE_REPO_RAW.'deploy-manifest.json',4194304);$remoteInfo=update_http_get(UPDATE_REPO_RAW.'deploy-info.json',1048576);if(file_put_contents($root.'/deploy-manifest.json',$remoteManifest,LOCK_EX)===false||file_put_contents($root.'/deploy-info.json',$remoteInfo,LOCK_EX)===false)throw new RuntimeException('update_write_failed');
        file_put_contents($backup.'/update.json',json_encode(['sourceSha'=>$sourceSha,'installedAt'=>gmdate('c'),'changed'=>$changed,'removed'=>$removed],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        return ['sourceSha'=>$sourceSha,'changed'=>$changed,'removed'=>$removed,'backup'=>$backup];
    }catch(Throwable $error){
        if(is_dir($backup)){
            $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($backup,FilesystemIterator::SKIP_DOTS));foreach($it as $file){if(!$file->isFile()||$file->getFilename()==='update.json')continue;$relative=str_replace('\\','/',substr($file->getPathname(),strlen($backup)+1));try{update_copy_atomic($file->getPathname(),$root.'/'.$relative);}catch(Throwable){}}
        }
        throw $error;
    }finally{
        if(is_dir($stage))media_remove_tree($stage);
    }
}
