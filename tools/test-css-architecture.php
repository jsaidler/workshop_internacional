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
if($important)$failures[]="CSS autoral ainda contém !important:\n - ".implode("\n - ",$important);

$canonical=[
    'assets/ui-core.css',
    'assets/admin-system.css',
    'assets/admin-shell-responsive.css',
    'assets/admin-media.css',
    'assets/student-area.css',
    'assets/cms-core.css',
    'assets/cms-editorial.css',
    'editor/editor-system.css',
];
foreach($canonical as $path){
    if(!is_file($root.'/'.$path))$failures[]='Autoridade CSS canônica ausente: '.$path;
}

$retired=[
    'admin/admin.css','admin/cms-admin.css','admin/pro-admin.css',
    'assets/admin-ux-v2.css','assets/admin-ux-v3.css','assets/admin-data-ux.css','assets/admin-form-ux.css','assets/experience-ux.css',
    'assets/admin-media-maintenance.css','assets/admin-media-task.css',
    'assets/cms.css','assets/cms-v3.css','assets/cms-ui-refinements.css',
    'editor/cms-editor.css','editor/cms-pro-editor.css','editor/cms-ux-v2.css','editor/cms-ux-v3.css','editor/task-centric.css',
];
foreach($retired as $path){
    if(is_file($root.'/'.$path))$failures[]='Camada histórica aposentada voltou ao repositório: '.$path;
}

$adminShell=(string)file_get_contents($root.'/app/admin_shell.php');
if(!preg_match('~<link[^>]+admin-system\.css~i',$adminShell))$failures[]='admin_shell.php deve carregar admin-system.css como autoridade global.';
if(!preg_match('~<link[^>]+admin-shell-responsive\.css~i',$adminShell))$failures[]='admin_shell.php deve carregar a autoridade responsiva do shell administrativo.';
foreach(['admin-ux-v2.css','admin-ux-v3.css','admin-data-ux.css','admin-form-ux.css','experience-ux.css','admin-media.css'] as $legacy){
    if(str_contains($adminShell,$legacy))$failures[]="admin_shell.php carrega CSS que não pertence ao shell global: {$legacy}";
}
if(preg_match('/<style\b/i',$adminShell))$failures[]='admin_shell.php contém CSS inline; estilos estruturais pertencem ao sistema CSS canônico.';

$mediaPage=(string)file_get_contents($root.'/admin/media.php');
if(!preg_match('~<link[^>]+admin-media\.css~i',$mediaPage))$failures[]='admin/media.php deve ser o consumidor explícito de admin-media.css.';

$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
if(str_contains($studentShell,'experience-ux.css'))$failures[]='student_shell.php depende da camada corretiva experience-ux.css.';
if(!preg_match('~<link[^>]+student-area\.css~i',$studentShell))$failures[]='student_shell.php deve consumir student-area.css como autoridade da superfície.';
if(!preg_match('~<link[^>]+ui-core\.css~i',$studentShell))$failures[]='student_shell.php deve consumir as primitivas de ui-core.css.';

$renderer=(string)file_get_contents($root.'/app/cms_renderer.php');
foreach(['cms.css','cms-v3.css','cms-ui-refinements.css','cms-system-choice-controls'] as $legacy){
    if(str_contains($renderer,$legacy))$failures[]="cms_renderer.php ainda referencia camada/patch aposentado: {$legacy}";
}
if(!str_contains($renderer,"'/assets/cms-core.css'"))$failures[]='cms_renderer.php deve importar cms-core.css dentro de cms-system.';
if(!str_contains($renderer,"'/assets/cms-editorial.css'"))$failures[]='cms_renderer.php deve importar cms-editorial.css dentro de cms-system.';
if(!str_contains($renderer,'id="cms-custom-css"'))$failures[]='cms_renderer.php deve preservar a camada editorial final #cms-custom-css.';

$editorIndex=(string)file_get_contents($root.'/editor/index.html');
if(substr_count($editorIndex,'/editor/editor-system.css')!==1)$failures[]='editor/index.html deve carregar exatamente um editor-system.css.';
foreach(['cms-editor.css','cms-pro-editor.css','cms-ux-v2.css','cms-ux-v3.css','task-centric.css'] as $legacy){
    if(str_contains($editorIndex,$legacy))$failures[]="editor/index.html ainda carrega camada histórica: {$legacy}";
}

// Regressions and browser fixtures are part of the architecture contract too.
// They must exercise the same canonical CSS authorities used in production.
$retiredBasenames=array_values(array_unique(array_map('basename',$retired)));
$toolsIterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/tools',FilesystemIterator::SKIP_DOTS));
foreach($toolsIterator as $testPath){
    if(!$testPath->isFile())continue;
    $relative=str_replace('\\','/',substr($testPath->getPathname(),strlen($root)+1));
    if($relative==='tools/test-css-architecture.php')continue;
    if(!in_array(strtolower($testPath->getExtension()),['php','html','js','cjs','mjs'],true))continue;
    $source=(string)file_get_contents($testPath->getPathname());
    foreach($retiredBasenames as $basename){
        if(str_contains($source,$basename))$failures[]="Teste/fixture ainda referencia autoridade CSS aposentada: {$relative} -> {$basename}";
    }
}

$cssRoots=[$root.'/assets',$root.'/admin',$root.'/editor',$root.'/template'];
foreach($cssRoots as $dir){
    if(!is_dir($dir))continue;
    foreach(new DirectoryIterator($dir) as $file){
        if(!$file->isFile()||strtolower($file->getExtension())!=='css')continue;
        $name=strtolower($file->getFilename());
        if(preg_match('/(?:^|[-_])(v[2-9][0-9]*|fix|hotfix|override|refinements?)(?:[-_.]|$)/',$name)){
            $failures[]='Folha CSS com semântica de correção cronológica proibida: '.$file->getPathname();
        }
    }
}

// The ownership gates themselves are architectural invariants. A future CI edit
// must not silently remove the checks and reopen the same cascade debt.
$ci=(string)file_get_contents($root.'/.github/workflows/ci.yml');
foreach([
    'php tools/audit-css-ownership.php --strict',
    'php tools/consolidate-css-ownership.php --check',
    'php tools/consolidate-css-selectors.php --check',
] as $gate){
    if(!str_contains($ci,$gate))$failures[]='CI deixou de executar gate CSS obrigatório: '.$gate;
}

if($failures){
    fwrite(STDERR,"CSS architecture regression failed:\n\n".implode("\n\n",array_unique($failures))."\n");
    exit(1);
}

echo "CSS architecture regression passed.\n";
