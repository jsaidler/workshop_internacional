<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$js=(string)file_get_contents($root.'/editor/cms-component-editor.js');
$layoutJs=(string)file_get_contents($root.'/editor/cms-layout-properties.js');
$css=(string)file_get_contents($root.'/assets/cms-pro.css');
$layoutCss=(string)file_get_contents($root.'/assets/cms-content-layout.css');
$publicJs=(string)file_get_contents($root.'/assets/public.js');
$editor=(string)file_get_contents($root.'/editor/index.html');
$deploy=(string)file_get_contents($root.'/.github/workflows/deploy.yml');

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
foreach([
    'cmsBoxWidth',
    'cmsTabletPadding',
    'cmsMobilePadding',
    'cmsTabletOrder',
    'cmsMobileOrder',
    'cmsHideDesktop',
    'cmsHideTablet',
    'cmsHideMobile',
] as $needle){
    if(!str_contains($layoutJs,$needle)){
        fwrite(STDERR,"Missing layout-property editor contract: {$needle}\n");
        exit(1);
    }
}
foreach([
    '@media (min-width:1025px)',
    '@media (min-width:721px) and (max-width:1024px)',
    '@media (max-width:720px)',
    '[data-cms-box-width="narrow"]',
    '[data-cms-tablet-padding="m"]',
    '[data-cms-mobile-order="1"]',
    '[data-cms-hide-mobile="1"]',
] as $needle){
    if(!str_contains($layoutCss,$needle)){
        fwrite(STDERR,"Missing first-class responsive layout CSS: {$needle}\n");
        exit(1);
    }
}
if(!str_contains($editor,'/editor/cms-component-editor.js')||!str_contains($editor,'/editor/cms-layout-properties.js')||!str_contains($editor,'/editor/cms-layout-properties.css')){
    fwrite(STDERR,"Editor does not load the structural property layer\n");
    exit(1);
}
if(!str_contains($publicJs,'@import url("/assets/cms-content-layout.css") layer(cms-system);')){
    fwrite(STDERR,"Public renderer does not load content layout CSS inside cms-system\n");
    exit(1);
}
if(str_contains($css,'[data-cms-columns="2"][data-cms-ratio="30-70"]')===false){
    fwrite(STDERR,"Desktop ratio contract missing\n");
    exit(1);
}
if(str_contains($deploy,'DEPLOY_FTP_')||str_contains($deploy,'lftp')||str_contains($deploy,'Deploy dist to hosting')){
    fwrite(STDERR,"Deployment workflow must publish only production-dist for the admin updater\n");
    exit(1);
}

echo "Content structure editor contract OK\n";
