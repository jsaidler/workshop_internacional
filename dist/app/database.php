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
        $migration=require $file;
        if(!is_callable($migration)) throw new RuntimeException("Invalid migration: $version");
        $migration($db);
        $insert=$db->prepare('INSERT INTO schema_migrations(version,applied_at) VALUES(?,?)');$insert->execute([$version,gmdate('c')]);
    }
}
function database(): PDO {
    static $db; if($db) return $db;
    $path=dirname(__DIR__).'/storage/database.sqlite';
    if(!is_file($path)) throw new RuntimeException('Missing storage/database.sqlite.');
    $db=database_connection($path);run_migrations($db);return $db;
}
