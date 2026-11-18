<?php
declare(strict_types=1);
function database_connection(string $path): PDO {
    return new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
}
function run_migrations(PDO $db): void {
    $db->exec('PRAGMA foreign_keys=ON; PRAGMA busy_timeout=5000; CREATE TABLE IF NOT EXISTS schema_migrations (version TEXT PRIMARY KEY, applied_at TEXT NOT NULL)');
    foreach(glob(dirname(__DIR__).'/migrations/*.php')?:[] as $file){
        $version=basename($file,'.php');
        $check=$db->prepare('SELECT 1 FROM schema_migrations WHERE version=?');$check->execute([$version]);
        if($check->fetchColumn()) continue;
        if($version==='001_initial'&&$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='admin_users'")->fetchColumn()){
            $db->prepare('INSERT INTO schema_migrations(version,applied_at) VALUES(?,?)')->execute([$version,gmdate('c')]);
            continue;
        }
        $migration=require $file;
        if(!is_callable($migration)) throw new RuntimeException("Invalid migration: $version");
        $migration($db);
        $insert=$db->prepare('INSERT INTO schema_migrations(version,applied_at) VALUES(?,?)');$insert->execute([$version,gmdate('c')]);
    }
}
function database_run_migrations_locked(PDO $db,string $storageRoot): void {
    $lockPath=$storageRoot.'/migrations.lock';
    $handle=@fopen($lockPath,'c+');
    if($handle===false)throw new RuntimeException('Cannot open migration lock.');
    try{
        if(!flock($handle,LOCK_EX))throw new RuntimeException('Cannot acquire migration lock.');
        run_migrations($db);
    }finally{
        @flock($handle,LOCK_UN);
        fclose($handle);
    }
}
function database(): PDO {
    static $db; if($db) return $db;
    $storage=dirname(__DIR__).'/storage';
    $path=$storage.'/database.sqlite';
    if(!is_file($path)) throw new RuntimeException('Missing storage/database.sqlite.');
    $db=database_connection($path);database_run_migrations_locked($db,$storage);return $db;
}
