<?php
declare(strict_types=1);

function fail_manifest(string $message): never { fwrite(STDERR,"deploy-manifest: $message\n"); exit(1); }
$root=realpath($argv[1]??dirname(__DIR__).'/dist');
if($root===false||!is_dir($root))fail_manifest('dist directory not found');
$sourceSha=trim((string)(getenv('SOURCE_SHA')?:''));
if($sourceSha===''){
    $repo=dirname(__DIR__);
    $out=[];$rc=0;exec('git -C '.escapeshellarg($repo).' rev-parse HEAD',$out,$rc);
    if($rc===0&&isset($out[0]))$sourceSha=trim($out[0]);
}
if($sourceSha==='')$sourceSha='unknown';
$files=[];
$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
foreach($it as $file){
    if(!$file->isFile())continue;
    $relative=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
    if(in_array($relative,['deploy-manifest.json','deploy-info.json'],true))continue;
    $files[$relative]=['sha256'=>hash_file('sha256',$file->getPathname()),'bytes'=>$file->getSize()];
}
ksort($files);
$info=['sourceSha'=>$sourceSha,'generatedAt'=>gmdate('c'),'schema'=>1,'files'=>count($files)];
file_put_contents($root.'/deploy-info.json',json_encode($info,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
$manifest=['schema'=>1,'sourceSha'=>$sourceSha,'generatedAt'=>$info['generatedAt'],'files'=>$files];
file_put_contents($root.'/deploy-manifest.json',json_encode($manifest,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
echo "Deploy manifest written for {$info['files']} files at {$sourceSha}\n";
