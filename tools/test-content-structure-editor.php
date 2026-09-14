<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$js=(string)file_get_contents($root.'/editor/cms-component-editor.js');
$css=(string)file_get_contents($root.'/assets/cms-pro.css');
$editor=(string)file_get_contents($root.'/editor/index.html');

$needles=[
    "columns-2",
    "data-cms-column",
    "cmsContainer='columns'",
    "cms-structure-tree",
    "cmsTabletColumns",
    "cmsMobileColumns",
    "ondragstart",
    "data-add-component",
];
foreach($needles as $needle){
    if(!str_contains($js,$needle)){
        fwrite(STDERR,"Missing structural editor contract: {$needle}\n");
        exit(1);
    }
}
foreach([
    '.cms-container-columns',
    '[data-cms-tablet-columns="2"]',
    '[data-cms-mobile-columns="2"]',
    '.cms-column',
    '.cms-structure-selected',
] as $needle){
    if(!str_contains($css,$needle)){
        fwrite(STDERR,"Missing structural CSS contract: {$needle}\n");
        exit(1);
    }
}
if(!str_contains($editor,'/editor/cms-component-editor.js')){
    fwrite(STDERR,"Editor does not load cms-component-editor.js\n");
    exit(1);
}
if(str_contains($css,'[data-cms-columns="2"][data-cms-ratio="30-70"]')===false){
    fwrite(STDERR,"Desktop ratio contract missing\n");
    exit(1);
}

echo "Content structure editor contract OK\n";
