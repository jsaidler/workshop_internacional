<?php
declare(strict_types=1);

require_once __DIR__.'/update_service.php';

function update_restore_backup_name(string $name): string {
    $name=trim($name);
    if(!preg_match('/^backup-[A-Za-z0-9._-]+$/',$name))throw new RuntimeException('invalid_backup');
    return $name;
}

function update_restore_backup_dir(string $name): string {
    $name=update_restore_backup_name($name);
    $root=update_storage_root();
    $dir=$root.'/'.$name;
    $realRoot=realpath($root);$realDir=realpath($dir);
    if($realRoot===false||$realDir===false||!is_dir($realDir)||!str_starts_with($realDir,$realRoot.DIRECTORY_SEPARATOR))throw new RuntimeException('backup_not_found');
    return $realDir;
}

function update_restore_metadata(string $backupDir): array {
    $file=$backupDir.'/update.json';
    if(!is_file($file))throw new RuntimeException('backup_metadata_missing');
    $meta=json_decode((string)file_get_contents($file),true);
    if(!is_array($meta))throw new RuntimeException('backup_metadata_invalid');
    $normalise=function(mixed $items): array {
        if(!is_array($items))return [];$out=[];
        foreach($items as $item){$relative=update_safe_relative((string)$item);if(update_persistent_path($relative))continue;$out[$relative]=true;}
        return array_keys($out);
    };
    return [
        'sourceSha'=>(string)($meta['sourceSha']??''),
        'installedAt'=>$meta['installedAt']??null,
        'changed'=>$normalise($meta['changed']??[]),
        'removed'=>$normalise($meta['removed']??[]),
        'databaseBackup'=>(string)($meta['databaseBackup']??''),
        'restoreOf'=>(string)($meta['restoreOf']??''),
    ];
}

function update_restore_paths(array $meta): array {
    $paths=array_values(array_unique(array_merge($meta['changed']??[],$meta['removed']??[],['deploy-manifest.json','deploy-info.json'])));
    $out=[];foreach($paths as $relative){$relative=update_safe_relative((string)$relative);if(!update_persistent_path($relative))$out[]=$relative;}
    sort($out);return $out;
}

function update_restore_database_valid(string $path): bool {
    if(!is_file($path)||(int)filesize($path)<=0)return false;
    try{$db=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$check=$db->query('PRAGMA quick_check')->fetchColumn();$db=null;return $check==='ok';}catch(Throwable){return false;}
}

function update_restore_snapshot(array $paths,bool $withDatabase,string $restoreOf): string {
    $root=update_app_root();$storage=update_storage_root();update_mkdir($storage);
    $stamp=gmdate('Ymd-His').'-'.bin2hex(random_bytes(3));$dir=$storage.'/backup-restore-'.$stamp;update_mkdir($dir);
    $existing=[];
    foreach($paths as $relative){$relative=update_safe_relative((string)$relative);if(update_persistent_path($relative))continue;if(is_file($root.'/'.$relative)){update_backup_file($root,$dir,$relative);$existing[]=$relative;}}
    $databaseBackup=$withDatabase?update_backup_database($dir):null;
    $record=[
        'sourceSha'=>(string)(update_local_info()['sourceSha']??'unknown'),
        'installedAt'=>gmdate('c'),
        'changed'=>$paths,
        'removed'=>[],
        'databaseBackup'=>$databaseBackup?basename($databaseBackup):null,
        'restoreOf'=>$restoreOf,
        'snapshotExisting'=>$existing,
    ];
    update_write_atomic($dir.'/update.json',json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n");
    return $dir;
}

function update_restore_unlocked(string $backupName,bool $restoreDatabase=false): array {
    $backup=update_restore_backup_dir($backupName);$meta=update_restore_metadata($backup);$paths=update_restore_paths($meta);$root=update_app_root();
    if(!$paths)throw new RuntimeException('backup_has_no_files');
    $databaseSource=$backup.'/database.sqlite';
    if($restoreDatabase&&!update_restore_database_valid($databaseSource))throw new RuntimeException('backup_database_invalid');
    $safety=update_restore_snapshot($paths,$restoreDatabase,$backupName);
    $restored=[];$deleted=[];
    try{
        foreach($paths as $relative){$source=$backup.'/'.$relative;$target=$root.'/'.$relative;if(is_file($source)){update_copy_atomic($source,$target);$restored[]=$relative;}elseif(is_file($target)){if(!@unlink($target))throw new RuntimeException('restore_remove_failed: '.$relative);$deleted[]=$relative;}}
        if($restoreDatabase)update_copy_atomic($databaseSource,update_database_path());
        return ['backup'=>$backupName,'restored'=>$restored,'deleted'=>$deleted,'databaseRestored'=>$restoreDatabase,'safetyBackup'=>basename($safety)];
    }catch(Throwable $error){
        try{
            foreach($paths as $relative){$source=$safety.'/'.$relative;$target=$root.'/'.$relative;if(is_file($source))update_copy_atomic($source,$target);elseif(is_file($target))@unlink($target);}
            if($restoreDatabase&&is_file($safety.'/database.sqlite'))update_copy_atomic($safety.'/database.sqlite',update_database_path());
        }catch(Throwable){}
        throw $error;
    }
}

function update_restore(string $backupName,bool $restoreDatabase=false): array {
    $lock=update_lock_acquire();
    try{$result=update_restore_unlocked($backupName,$restoreDatabase);$result['prunedBackups']=update_prune_backups();return $result;}
    finally{update_lock_release($lock);}
}
