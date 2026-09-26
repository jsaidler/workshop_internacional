<?php
declare(strict_types=1);
$root=dirname(__DIR__);$fixture=sys_get_temp_dir().'/safe-dist-'.bin2hex(random_bytes(4));
function cpdir(string $a,string $b):void{mkdir($b,0777,true);foreach(scandir($a)?:[] as $n){if($n==='.'||$n==='..'||$n==='dist'||$n==='.build'||$n==='.git')continue;$s="$a/$n";$d="$b/$n";is_dir($s)?cpdir($s,$d):copy($s,$d);}}
function must(bool $v,string $m):void{if(!$v)throw new RuntimeException($m);}
function run_build(string $php,string $build,string $fixture): int {$out=[];exec("$php $build --root=".escapeshellarg($fixture).' 2>&1',$out,$rc);return $rc;}
try{
    cpdir($root,$fixture);$php=escapeshellarg(PHP_BINARY);$build=escapeshellarg($fixture.'/tools/build-dist.php');
    must(run_build($php,$build,$fixture)===0,'initial build');
    $data=['config/local.php'=>'config','storage/database.sqlite'=>'database','storage/installed.lock'=>'lock','uploads/media/teste.jpg'=>'upload','logs/runtime.log'=>'log'];
    foreach($data as $p=>$v){$f="$fixture/dist/$p";@mkdir(dirname($f),0777,true);file_put_contents($f,$v);}
    file_put_contents("$fixture/dist/obsolete.php",'obsolete');$m=json_decode(file_get_contents("$fixture/.build/dist-managed-files.json"),true);$m[]='obsolete.php';file_put_contents("$fixture/.build/dist-managed-files.json",json_encode($m));
    must(run_build($php,$build,$fixture)===0,'rebuild');
    foreach($data as $p=>$v)must(file_get_contents("$fixture/dist/$p")===$v,"preserve $p");
    must(!file_exists("$fixture/dist/obsolete.php"),'remove obsolete');

    rename("$fixture/media-stream.php","$fixture/media-stream.bad");$before=hash_file('sha256',"$fixture/dist/media-stream.php");
    must(run_build($php,$build,$fixture)!==0,'invalid source must fail');
    must(hash_file('sha256',"$fixture/dist/media-stream.php")===$before,'dist intact on source failure');
    rename("$fixture/media-stream.bad","$fixture/media-stream.php");

    $migration=glob("$fixture/migrations/020_*.php")[0]??null;must(is_string($migration),'fixture migration 020 exists');$migrationBackup=$migration.'.bak';rename($migration,$migrationBackup);
    must(run_build($php,$build,$fixture)!==0,'migration gap must fail');
    must(hash_file('sha256',"$fixture/dist/media-stream.php")===$before,'dist intact on migration gap');
    rename($migrationBackup,$migration);

    $first=glob("$fixture/migrations/020_*.php")[0]??null;must(is_string($first),'fixture migration source exists');copy($first,"$fixture/migrations/020_duplicate.php");
    must(run_build($php,$build,$fixture)!==0,'duplicate migration number must fail');
    unlink("$fixture/migrations/020_duplicate.php");
    must(run_build($php,$build,$fixture)===0,'build recovers after migration validation fixtures');
    echo "preservation and migration sequence tests passed\n";
}finally{if(is_dir($fixture)){exec('rmdir /s /q '.escapeshellarg($fixture));}}
