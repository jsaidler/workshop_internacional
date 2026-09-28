<?php
declare(strict_types=1);

require_once __DIR__.'/lib/css-ownership.php';

$root=dirname(__DIR__);
$check=in_array('--check',$argv,true);
$files=[
    'assets/ui-core.css',
    'assets/admin-system.css',
    'assets/admin-media.css',
    'assets/student-area.css',
    'assets/cms-core.css',
    'assets/cms-editorial.css',
    'editor/editor-system.css',
];

$totalRemoved=0;$changed=[];
foreach($files as $relative){
    $path=$root.'/'.$relative;
    if(!is_file($path))throw new RuntimeException('CSS authority missing: '.$relative);
    $css=(string)file_get_contents($path);
    $rules=css_ownership_scan($css);
    $occurrences=[];
    foreach($rules as $rule){
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
    if(!$removals){echo $relative.": already canonical\n";continue;}

    usort($removals,static fn(array $a,array $b): int=>$b['start']<=>$a['start']);
    $nextStart=strlen($css)+1;
    foreach($removals as $removal){
        // Defensive overlap guard: only independent declaration ranges are removed.
        if($removal['end']>$nextStart)continue;
        $css=substr($css,0,$removal['start']).substr($css,$removal['end']);
        $nextStart=$removal['start'];
        $totalRemoved++;
    }
    $changed[]=$relative;
    echo $relative.': obsolete declarations '.count($removals)."\n";
    if(!$check)file_put_contents($path,$css);
}

echo "Consolidation complete; obsolete declarations: {$totalRemoved}; files: ".count($changed).".\n";
if($check&&$changed)exit(1);
