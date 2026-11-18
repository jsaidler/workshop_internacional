<?php
declare(strict_types=1);

const UPDATE_REPO_RAW='https://raw.githubusercontent.com/jsaidler/workshop_internacional/production-dist/';
const UPDATE_MAX_FILE_BYTES=16777216;
const UPDATE_BACKUP_RETENTION=8;

function update_app_root(): string { return dirname(__DIR__); }
function update_storage_root(): string { return update_app_root().'/storage/updates'; }
function update_database_path(): string { return update_app_root().'/storage/database.sqlite'; }
function update_lock_path(): string { return update_storage_root().'/update.lock'; }
function update_persistent_path(string $relative): bool {
    $relative=str_replace('\\','/',$relative);
    return $relative==='config/local.php'||$relative==='config/install.php'||$relative==='storage/database.sqlite'||$relative==='storage/installed.lock'||str_starts_with($relative,'storage/logs/')||str_starts_with($relative,'storage/updates/')||str_starts_with($relative,'uploads/');
}
function update_safe_relative(string $relative): string {
    $relative=ltrim(str_replace('\\','/',$relative),'/');
    if($relative===''||str_contains($relative,'..')||str_contains($relative,"\0")||preg_match('~^[a-z]+:~i',$relative))throw new RuntimeException('unsafe_update_path');
    return $relative;
}
function update_cache_token(): string {
    try{return bin2hex(random_bytes(8));}catch(Throwable){return str_replace('.','',sprintf('%.6f',microtime(true)));}
}
function update_remote_url(string $relative,?string $token=null): string {
    $relative=update_safe_relative($relative);$token=$token??update_cache_token();
    return UPDATE_REPO_RAW.str_replace('%2F','/',rawurlencode($relative)).'?v='.rawurlencode($token);
}
function update_http_get(string $url,int $maxBytes=UPDATE_MAX_FILE_BYTES): string {
    $headers="Cache-Control: no-cache\r\nPragma: no-cache\r\nAccept: application/octet-stream, application/json;q=0.9, */*;q=0.8\r\n";
    $context=stream_context_create(['http'=>['timeout'=>20,'follow_location'=>1,'user_agent'=>'WorkshopCMS-Updater/1.3','header'=>$headers]]);
    $data=@file_get_contents($url,false,$context,0,$maxBytes+1);
    if($data===false){
        if(function_exists('curl_init')){
            $ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>20,CURLOPT_USERAGENT=>'WorkshopCMS-Updater/1.3',CURLOPT_MAXFILESIZE=>$maxBytes,CURLOPT_HTTPHEADER=>['Cache-Control: no-cache','Pragma: no-cache','Accept: application/octet-stream, application/json;q=0.9, */*;q=0.8']]);$data=curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$error=curl_error($ch);curl_close($ch);if(!is_string($data)||$code<200||$code>=300)throw new RuntimeException('update_download_failed'.($error?': '.$error:''));
        }else throw new RuntimeException('update_download_failed');
    }
    if(strlen($data)>$maxBytes)throw new RuntimeException('update_file_too_large');
    return $data;
}
function update_remote_text(string $file,int $maxBytes=4194304,?string $token=null): string {
    return update_http_get(update_remote_url($file,$token),$maxBytes);
}
function update_remote_json(string $file,?string $token=null): array {
    $data=json_decode(update_remote_text($file,4194304,$token),true);
    if(!is_array($data))throw new RuntimeException('invalid_update_manifest');
    return $data;
}
function update_local_info(): array {
    $file=update_app_root().'/deploy-info.json';if(!is_file($file))return ['sourceSha'=>'unknown','generatedAt'=>null,'files'=>null];$data=json_decode((string)file_get_contents($file),true);return is_array($data)?$data:['sourceSha'=>'unknown','generatedAt'=>null,'files'=>null];
}
function update_remote_info(): array { return update_remote_json('deploy-info.json',update_cache_token()); }
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
function update_lock_acquire(?string $path=null) {
    $path=$path??update_lock_path();update_mkdir(dirname($path));$handle=@fopen($path,'c+');if($handle===false)throw new RuntimeException('update_lock_unavailable');if(!@flock($handle,LOCK_EX|LOCK_NB)){fclose($handle);throw new RuntimeException('update_already_running');}@ftruncate($handle,0);@fwrite($handle,(string)getmypid().' '.gmdate('c')."\n");@fflush($handle);return $handle;
}
function update_lock_release($handle): void {if(is_resource($handle)){@flock($handle,LOCK_UN);@fclose($handle);}}
function update_copy_atomic(string $source,string $destination): void {
    update_mkdir(dirname($destination));$tmp=$destination.'.update-'.bin2hex(random_bytes(4));if(!copy($source,$tmp)){@unlink($tmp);throw new RuntimeException('update_write_failed');}if(is_file($destination)&&!is_writable($destination)){@unlink($tmp);throw new RuntimeException('update_target_not_writable');}if(!@rename($tmp,$destination)){@unlink($tmp);throw new RuntimeException('update_write_failed');}
}
function update_write_atomic(string $destination,string $data): void {
    update_mkdir(dirname($destination));$tmp=$destination.'.update-'.bin2hex(random_bytes(4));if(file_put_contents($tmp,$data,LOCK_EX)===false){@unlink($tmp);throw new RuntimeException('update_write_failed');}if(is_file($destination)&&!is_writable($destination)){@unlink($tmp);throw new RuntimeException('update_target_not_writable');}if(!@rename($tmp,$destination)){@unlink($tmp);throw new RuntimeException('update_write_failed');}
}
function update_backup_database(string $backupDir,?string $databasePath=null): ?string {
    $databasePath=$databasePath??update_database_path();if(!is_file($databasePath))return null;update_mkdir($backupDir);$destination=$backupDir.'/database.sqlite';if(is_file($destination)&&!@unlink($destination))throw new RuntimeException('update_backup_failed');
    try{
        $db=new PDO('sqlite:'.$databasePath,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$db->exec('PRAGMA busy_timeout=5000');$quoted=$db->quote($destination);if($quoted===false)throw new RuntimeException('update_backup_failed');$db->exec('VACUUM INTO '.$quoted);$db=null;
    }catch(Throwable $error){@unlink($destination);throw new RuntimeException('update_database_backup_failed: '.$error->getMessage(),0,$error);}
    if(!is_file($destination)||(int)filesize($destination)<=0)throw new RuntimeException('update_database_backup_failed');return $destination;
}
function update_backup_file(string $root,string $backup,string $relative): void {
    $source=$root.'/'.$relative;if(!is_file($source))return;$destination=$backup.'/'.$relative;update_mkdir(dirname($destination));if(!copy($source,$destination))throw new RuntimeException('update_backup_failed');
}
function update_restore_files(string $root,string $backup,array $touched,array $originallyExisted): void {
    foreach($touched as $relative){$target=$root.'/'.$relative;$hadFile=!empty($originallyExisted[$relative]);$saved=$backup.'/'.$relative;if($hadFile&&is_file($saved)){try{update_copy_atomic($saved,$target);}catch(Throwable){}}elseif(!$hadFile&&is_file($target)){@unlink($target);}}
}
function update_remove_tree(string $dir): void {
    if(!is_dir($dir))return;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $entry){$path=$entry->getPathname();if($entry->isLink()||$entry->isFile()){@unlink($path);}elseif($entry->isDir()){@rmdir($path);}}@rmdir($dir);
}
function update_backup_history(int $limit=8): array {
    $root=update_storage_root();if(!is_dir($root))return [];$dirs=glob($root.'/backup-*',GLOB_ONLYDIR)?:[];rsort($dirs,SORT_STRING);$items=[];
    foreach(array_slice($dirs,0,max(1,$limit)) as $dir){$meta=[];$file=$dir.'/update.json';if(is_file($file)){$decoded=json_decode((string)file_get_contents($file),true);if(is_array($decoded))$meta=$decoded;}$items[]=['path'=>$dir,'name'=>basename($dir),'sourceSha'=>(string)($meta['sourceSha']??''),'installedAt'=>$meta['installedAt']??null,'changed'=>is_array($meta['changed']??null)?$meta['changed']:[],'removed'=>is_array($meta['removed']??null)?$meta['removed']:[],'database'=>is_file($dir.'/database.sqlite')];}
    return $items;
}
function update_prune_backups(int $keep=UPDATE_BACKUP_RETENTION): int {
    $root=update_storage_root();if(!is_dir($root))return 0;$dirs=glob($root.'/backup-*',GLOB_ONLYDIR)?:[];rsort($dirs,SORT_STRING);$removed=0;foreach(array_slice($dirs,max(1,$keep)) as $dir){update_remove_tree($dir);if(!is_dir($dir))$removed++;}return $removed;
}
function update_apply_unlocked(): array {
    $manifestToken=update_cache_token();$remoteManifest=update_remote_text('deploy-manifest.json',4194304,$manifestToken);$manifestJson=json_decode($remoteManifest,true);if(!is_array($manifestJson))throw new RuntimeException('invalid_update_manifest');$manifest=update_manifest_validate($manifestJson);$sourceSha=$manifest['sourceSha'];if($sourceSha==='unknown')throw new RuntimeException('invalid_update_manifest');
    $root=update_app_root();$storage=update_storage_root();update_mkdir($storage);$stamp=gmdate('Ymd-His').'-'.substr(preg_replace('/[^a-f0-9]/i','',$sourceSha),0,12);$stage=$storage.'/stage-'.$stamp;$backup=$storage.'/backup-'.$stamp;update_mkdir($stage);update_mkdir($backup);
    $changed=[];$removed=[];$old=update_local_manifest_files();$touched=[];$originallyExisted=[];$databaseBackup=null;
    try{
        foreach($manifest['files'] as $relative=>$meta){$target=$root.'/'.$relative;$current=is_file($target)?strtolower((string)hash_file('sha256',$target)):'';if($current===$meta['sha256'])continue;$data=update_remote_text($relative,max(1024,$meta['bytes']+1024),$sourceSha);if(strtolower(hash('sha256',$data))!==$meta['sha256'])throw new RuntimeException('update_checksum_mismatch: '.$relative);$stageFile=$stage.'/'.$relative;update_mkdir(dirname($stageFile));if(file_put_contents($stageFile,$data,LOCK_EX)===false)throw new RuntimeException('update_storage_unavailable');$changed[]=$relative;}
        foreach($old as $relative=>$meta)if(!isset($manifest['files'][$relative])&&!update_persistent_path($relative)&&is_file($root.'/'.$relative))$removed[]=$relative;
        $remoteInfo=update_remote_text('deploy-info.json',1048576,$sourceSha);$infoJson=json_decode($remoteInfo,true);if(!is_array($infoJson)||(string)($infoJson['sourceSha']??'unknown')!==$sourceSha)throw new RuntimeException('update_channel_changed');
        $touched=array_values(array_unique(array_merge($changed,$removed,['deploy-manifest.json','deploy-info.json'])));foreach($touched as $relative){$originallyExisted[$relative]=is_file($root.'/'.$relative);if($originallyExisted[$relative])update_backup_file($root,$backup,$relative);}
        $databaseBackup=update_backup_database($backup);
        foreach($changed as $relative)update_copy_atomic($stage.'/'.$relative,$root.'/'.$relative);
        foreach($removed as $relative)if(is_file($root.'/'.$relative)&&!@unlink($root.'/'.$relative))throw new RuntimeException('update_remove_failed');
        update_write_atomic($root.'/deploy-manifest.json',$remoteManifest);update_write_atomic($root.'/deploy-info.json',$remoteInfo);
        $record=['sourceSha'=>$sourceSha,'installedAt'=>gmdate('c'),'changed'=>$changed,'removed'=>$removed,'databaseBackup'=>$databaseBackup?basename($databaseBackup):null];update_write_atomic($backup.'/update.json',json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n");
        return ['sourceSha'=>$sourceSha,'changed'=>$changed,'removed'=>$removed,'backup'=>$backup,'databaseBackup'=>$databaseBackup];
    }catch(Throwable $error){
        update_restore_files($root,$backup,$touched,$originallyExisted);throw $error;
    }finally{
        if(is_dir($stage))update_remove_tree($stage);
    }
}
function update_apply(): array {
    $lock=update_lock_acquire();try{$result=update_apply_unlocked();$result['prunedBackups']=update_prune_backups();return $result;}finally{update_lock_release($lock);}
}
