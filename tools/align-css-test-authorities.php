<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$replacements=[
    'tools/test-handbook-email-integrity.php'=>[
        "assets/cms-ui-refinements.css"=>"assets/cms-editorial.css",
    ],
    'tools/test-pages-admin-ux.php'=>[
        "admin/cms-admin.css"=>"assets/admin-system.css",
    ],
    'tools/test-pinhole-lambe-lambe-page.php'=>[
        "cms-ui-refinements.css"=>"cms-editorial.css",
    ],
    'tools/test-registration-google-form-parity.php'=>[
        "assets/cms-ui-refinements.css"=>"assets/cms-editorial.css",
    ],
    'tools/test-student-material-system.php'=>[
        "assets/cms.css"=>"assets/cms-core.css",
        "/assets/admin-data-ux.css"=>"/assets/admin-system.css",
    ],
];

foreach($replacements as $relative=>$map){
    $path=$root.'/'.$relative;
    $source=(string)file_get_contents($path);
    $updated=str_replace(array_keys($map),array_values($map),$source,$count);
    if($count===0)throw new RuntimeException('No replacement applied in '.$relative);
    if(file_put_contents($path,$updated)===false)throw new RuntimeException('Cannot write '.$relative);
}

echo "Remaining CSS authority assertions aligned.\n";
