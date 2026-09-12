<?php
declare(strict_types=1);

function fail_update_test(string $message): never {fwrite(STDERR,"test-update-service: $message\n");exit(1);}
function expect_update(bool $value,string $message): void {if(!$value)fail_update_test($message);}
function remove_update_test_tree(string $dir): void {if(!is_dir($dir))return;$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $item){$item->isDir()?rmdir($item->getPathname()):unlink($item->getPathname());}rmdir($dir);}

require __DIR__.'/../app/update_service.php';

$unsafe=false;try{update_safe_relative('../config/local.php');}catch(RuntimeException){$unsafe=true;}expect_update($unsafe,'unsafe relative path accepted');
expect_update(update_persistent_path('storage/database.sqlite'),'database is not protected');
expect_update(update_persistent_path('uploads/photo.jpg'),'uploads are not protected');
expect_update(!update_persistent_path('assets/public.js'),'normal application file marked persistent');

$manifest=update_manifest_validate(['schema'=>1,'sourceSha'=>str_repeat('a',40),'files'=>[
    'assets/public.js'=>['sha256'=>str_repeat('b',64),'bytes'=>120],
    'storage/database.sqlite'=>['sha256'=>str_repeat('c',64),'bytes'=>500],
]]);
expect_update(isset($manifest['files']['assets/public.js']),'deployable manifest entry lost');
expect_update(!isset($manifest['files']['storage/database.sqlite']),'persistent manifest entry was not filtered');

$tmp=sys_get_temp_dir().'/workshop-update-test-'.bin2hex(random_bytes(5));mkdir($tmp,0777,true);
try{
    $lockPath=$tmp.'/locks/update.lock';$first=update_lock_acquire($lockPath);expect_update(is_resource($first),'update lock was not acquired');$locked=false;try{$second=update_lock_acquire($lockPath);update_lock_release($second);}catch(RuntimeException $error){$locked=$error->getMessage()==='update_already_running';}expect_update($locked,'second updater was not rejected while lock was held');update_lock_release($first);$after=update_lock_acquire($lockPath);expect_update(is_resource($after),'update lock was not reusable after release');update_lock_release($after);

    $dbPath=$tmp.'/database.sqlite';$pdo=new PDO('sqlite:'.$dbPath,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$pdo->exec('CREATE TABLE sample(id INTEGER PRIMARY KEY,value TEXT NOT NULL)');$pdo->prepare('INSERT INTO sample(value) VALUES(?)')->execute(['positivo direto']);$pdo=null;
    $backupDir=$tmp.'/backup-db';$backupPath=update_backup_database($backupDir,$dbPath);expect_update(is_string($backupPath)&&is_file($backupPath),'database backup was not created');$copy=new PDO('sqlite:'.$backupPath);expect_update($copy->query('SELECT value FROM sample WHERE id=1')->fetchColumn()==='positivo direto','database backup is not readable or consistent');$copy=null;

    $root=$tmp.'/root';$backup=$tmp.'/rollback';mkdir($root,0777,true);file_put_contents($root.'/old.txt','before');update_backup_file($root,$backup,'old.txt');file_put_contents($root.'/old.txt','after');file_put_contents($root.'/new.txt','new');update_restore_files($root,$backup,['old.txt','new.txt'],['old.txt'=>true,'new.txt'=>false]);expect_update(file_get_contents($root.'/old.txt')==='before','existing file was not restored');expect_update(!is_file($root.'/new.txt'),'new file survived rollback');

    $atomic=$tmp.'/atomic.txt';update_write_atomic($atomic,'one');update_write_atomic($atomic,'two');expect_update(file_get_contents($atomic)==='two','atomic writer did not replace content');
}finally{remove_update_test_tree($tmp);}

echo "Updater safety tests passed\n";
