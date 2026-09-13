<?php
declare(strict_types=1);
function fail_health(string $message): never {fwrite(STDERR,"test-system-health: $message\n");exit(1);}function expect_health(bool $ok,string $message):void{if(!$ok)fail_health($message);}
$root=sys_get_temp_dir().'/workshop-health-'.bin2hex(random_bytes(5));mkdir($root.'/storage/updates',0777,true);mkdir($root.'/uploads',0777,true);define('APP_ROOT',$root);
$db=new PDO('sqlite:'.$root.'/storage/database.sqlite');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->exec('CREATE TABLE probe(id INTEGER PRIMARY KEY,value TEXT)');$db->exec("INSERT INTO probe(value) VALUES('ok')");$db=null;
require __DIR__.'/../app/system_health.php';
$health=system_health_collect();expect_health(isset($health['items'])&&is_array($health['items']),'health items missing');
$by=[];foreach($health['items'] as $item)$by[$item['id']]=$item;
expect_health(($by['php']['status']??'')==='ok','PHP 8.2+ should be accepted');expect_health(($by['database']['status']??'')==='ok','healthy writable SQLite database not accepted');expect_health(($by['uploads']['status']??'')==='ok','writable uploads not accepted');expect_health(($by['storage']['status']??'')==='ok','writable storage not accepted');expect_health(isset($by['ext-pdo_sqlite'],$by['ext-dom'],$by['ext-mbstring']),'required extension diagnostics missing');expect_health(isset($by['mail'],$by['disk'],$by['backups']),'operational diagnostics missing');
file_put_contents($root.'/storage/not-a-database.sqlite','not sqlite');$bad=system_health_database($root.'/storage/not-a-database.sqlite');expect_health($bad['status']==='error','invalid SQLite database was accepted');
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);foreach($it as $file){$file->isDir()?rmdir($file->getPathname()):unlink($file->getPathname());}rmdir($root);
echo "System health diagnostics tests passed\n";
