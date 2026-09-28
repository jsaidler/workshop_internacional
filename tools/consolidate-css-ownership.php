<?php
declare(strict_types=1);

require_once __DIR__.'/lib/css-ownership.php';

$root=dirname(__DIR__);
$check=in_array('--check',$argv,true);
$files=css_ownership_authored_files($root);
$totalRemoved=0;$changed=[];

foreach($files as $relative){
    $path=$root.'/'.$relative;
    $css=(string)file_get_contents($path);
    $occurrences=[];
    foreach(css_ownership_scan($css) as $rule){
        $ruleKey=$rule['context'].' || '.$rule['selector'];
        foreach($rule['declarations'] as $declaration){
            $key=$ruleKey.' || '.$declaration['property'];
            $occurrences[$key][]=$declaration;
        }
    }

    $removals=[];
    foreach($occurrences as $items){
        $count=count($items);
        if($count<2)continue;
        for($i=0;$i<$count-1;$i++)$removals[]=$items[$i];
    }
    if(!$removals)continue;

    usort($removals,static fn(array $a,array $b): int=>$b['start']<=>$a['start']);
    $nextStart=strlen($css)+1;$removedForFile=0;
    foreach($removals as $removal){
        if($removal['end']>$nextStart)continue;
        $css=substr($css,0,$removal['start']).substr($css,$removal['end']);
        $nextStart=$removal['start'];
        $removedForFile++;$totalRemoved++;
    }
    if($removedForFile===0)continue;
    $changed[]=$relative;
    echo $relative.': obsolete declarations '.$removedForFile."\n";
    if(!$check)file_put_contents($path,$css);
}

echo "Consolidation complete; obsolete declarations: {$totalRemoved}; files: ".count($changed)." of ".count($files).".\n";
if($check&&$changed)exit(1);
