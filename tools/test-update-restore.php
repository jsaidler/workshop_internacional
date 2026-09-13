<?php
declare(strict_types=1);
function fail_restore(string $message): never {fwrite(STDERR,"test-update-restore: $message\n");exit(1);}function expect_restore(bool $value,string $message): void {if(!$value)fail_restore($message);}
require __DIR__.'/../app/update_restore.php';
$bad=false;try{update_restore_backup_name('../backup-test');}catch(RuntimeException){$bad=true;}expect_restore($bad,'path traversal backup name accepted');expect_restore(update_restore_backup_name('backup-20260913-000000-abcdef123456')==='backup-20260913-000000-abcdef123456','valid backup name rejected');
$tmp=sys_get_temp_dir().'/restore-meta-'.bin2hex(random_bytes(5));mkdir($tmp,0777,true);
try{
    $meta=['sourceSha'=>str_repeat('a',40),'installedAt'=>'2026-09-13T00:00:00Z','changed'=>['assets/public.js','uploads/keep.jpg'],'removed'=>['old.php'],'databaseBackup'=>'database.sqlite'];file_put_contents($tmp.'/update.json',json_encode($meta));$parsed=update_restore_metadata($tmp);expect_restore(in_array('assets/public.js',$parsed['changed'],true),'normal changed file missing');expect_restore(!in_array('uploads/keep.jpg',$parsed['changed'],true),'persistent upload entered restore set');$paths=update_restore_paths($parsed);expect_restore(in_array('old.php',$paths,true)&&in_array('deploy-info.json',$paths,true)&&in_array('deploy-manifest.json',$paths,true),'restore control paths incomplete');
    $dbPath=$tmp.'/database.sqlite';$db=new PDO('sqlite:'.$dbPath,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);$db->exec('CREATE TABLE t(id INTEGER PRIMARY KEY)');$db=null;expect_restore(update_restore_database_valid($dbPath),'valid SQLite backup rejected');file_put_contents($tmp.'/not-db.sqlite','not a database');expect_restore(!update_restore_database_valid($tmp.'/not-db.sqlite'),'invalid SQLite backup accepted');
}finally{if(is_dir($tmp)){$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $entry){$entry->isDir()?rmdir($entry->getPathname()):unlink($entry->getPathname());}rmdir($tmp);}}
$admin=(string)file_get_contents(__DIR__.'/../admin/system.php');expect_restore(str_contains($admin,"value=\"rollback\"")&&str_contains($admin,'update_restore($backupName,false)'),'admin rollback is not wired as code-only restore');
echo "Updater restore tests passed\n";
