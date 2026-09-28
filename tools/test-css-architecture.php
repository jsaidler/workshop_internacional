<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$failures=[];
$important=[];

$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){
    if(!$file->isFile()||strtolower($file->getExtension())!=='css')continue;
    $path=str_replace('\\','/',substr($file->getPathname(),strlen($root)+1));
    if(str_starts_with($path,'dist/')||str_starts_with($path,'node_modules/'))continue;
    $css=(string)file_get_contents($file->getPathname());
    if(str_contains($css,'!important')){
        $lines=preg_split('/\R/',$css)?:[];
        foreach($lines as $index=>$line){
            if(str_contains($line,'!important'))$important[]=$path.':'.($index+1).' '.trim($line);
        }
    }
}

if($important){
    $failures[]="CSS autoral ainda contém !important:\n - ".implode("\n - ",$important);
}

$adminShell=(string)file_get_contents($root.'/app/admin_shell.php');
foreach(['admin-ux-v2.css','admin-ux-v3.css','admin-form-ux.css','experience-ux.css'] as $legacy){
    if(str_contains($adminShell,$legacy))$failures[]="admin_shell.php ainda carrega camada corretiva histórica: {$legacy}";
}
if(preg_match('/<style\b/i',$adminShell))$failures[]='admin_shell.php ainda contém CSS inline; estilos estruturais devem pertencer ao sistema CSS canônico.';

$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
if(str_contains($studentShell,'experience-ux.css'))$failures[]='student_shell.php ainda depende da camada corretiva experience-ux.css.';

if($failures){
    fwrite(STDERR,"CSS architecture regression failed:\n\n".implode("\n\n",$failures)."\n");
    exit(1);
}

echo "CSS architecture regression passed.\n";
