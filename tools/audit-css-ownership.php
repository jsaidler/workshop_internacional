<?php
declare(strict_types=1);

require_once __DIR__.'/lib/css-ownership.php';

$root=dirname(__DIR__);
$strict=in_array('--strict',$argv,true);
$files=css_ownership_authored_files($root);
$totalRepeatedRules=0;
$totalPropertyCollisions=0;

foreach($files as $relative){
    $path=$root.'/'.$relative;
    $counts=css_ownership_counts((string)file_get_contents($path));
    $repeatedRules=array_filter($counts['rules'],static fn(int $count): bool=>$count>1);
    $propertyCollisions=array_filter($counts['properties'],static fn(int $count): bool=>$count>1);
    arsort($repeatedRules);arsort($propertyCollisions);
    $totalRepeatedRules+=count($repeatedRules);
    $totalPropertyCollisions+=count($propertyCollisions);
    if(!$repeatedRules&&!$propertyCollisions)continue;

    echo "\n[$relative]\n";
    echo ' repeated selector/context keys: '.count($repeatedRules)."\n";
    echo ' repeated property ownership keys: '.count($propertyCollisions)."\n";
    $shown=0;
    foreach($propertyCollisions as $key=>$count){
        echo str_pad((string)$count,3,' ',STR_PAD_LEFT).' × '.$key."\n";
        if(++$shown>=60){echo "... property collisions truncated ...\n";break;}
    }
}

echo "\nCSS ownership audit complete; files: ".count($files)."; repeated rules: {$totalRepeatedRules}; repeated property keys: {$totalPropertyCollisions}.\n";
if($strict&&$totalPropertyCollisions>0){
    fwrite(STDERR,"Strict CSS ownership failed: declarations still self-correct the same property for the same selector/context.\n");
    exit(1);
}
