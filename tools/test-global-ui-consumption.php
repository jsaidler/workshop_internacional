<?php
declare(strict_types=1);

function fail_global_ui(string $message): never {fwrite(STDERR,"global-ui-consumption: $message\n");exit(1);}
function must_global_ui(bool $condition,string $message): void {if(!$condition)fail_global_ui($message);}

$root=dirname(__DIR__);
$studentCss=(string)file_get_contents($root.'/assets/student-area.css');
$uiCss=(string)file_get_contents($root.'/assets/ui-core.css');
$uiJs=(string)file_get_contents($root.'/assets/ui-core.js');
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$pageJs=(string)file_get_contents($root.'/template/page.js');
$publicJs=(string)file_get_contents($root.'/assets/public.js');

must_global_ui(is_file($root.'/assets/ui-core.css')&&is_file($root.'/assets/ui-core.js'),'global UI authority files are missing');
must_global_ui(!is_file($root.'/assets/student-area.js'),'student area still owns a local JavaScript UI runtime');
must_global_ui(str_contains($shell,'/assets/ui-core.css')&&str_contains($shell,'/assets/ui-core.js'),'student shell does not consume global UI assets');

$forbiddenCss=['.student-field','.student-choice-','.student-check-field','.student-button','.student-error','.student-notice','.student-danger-button','--student-control-height'];
foreach($forbiddenCss as $needle)must_global_ui(!str_contains($studentCss,$needle),'student stylesheet recreates global primitive '.$needle);
foreach(['.student-step-nav','.student-workflow-panel','.student-home-grid','.student-course-list','.student-mobile-nav'] as $layout)must_global_ui(str_contains($studentCss,$layout),'student stylesheet lost application composition '.$layout);

foreach(['.form-field','.form-field-error','.choice-field','.choice-option','.check-field','.ui-alert','.button-danger'] as $primitive)must_global_ui(str_contains($uiCss,$primitive),'global stylesheet missing '.$primitive);
foreach(['workshop-theme','form[data-ui-validate]','dataset.uiMatch','aria-describedby','scrollIntoView'] as $behavior)must_global_ui(str_contains($uiJs,$behavior),'global UI runtime missing '.$behavior);

must_global_ui(str_contains($pageJs,"import(`/assets/ui-core.js")&&!str_contains($pageJs,"localStorage.setItem('workshop-theme'"),'template runtime does not delegate theme to ui-core');
must_global_ui(str_contains($publicJs,"import(`/assets/ui-core.js")&&!str_contains($publicJs,'function applyTheme(')&&!str_contains($publicJs,"localStorage.setItem('workshop-theme'"),'public runtime does not delegate theme to ui-core');

$forbiddenClasses=['student-field','student-choice-field','student-choice-row','student-choice-option','student-check-field','student-button','student-button-secondary','student-button-compact','student-error','student-notice','student-danger-button'];
foreach(glob($root.'/aluno/*.php')?:[] as $path){
    $source=(string)file_get_contents($path);
    preg_match_all('/class="([^"]*)"/',$source,$classMatches);
    $classTokens=[];
    foreach($classMatches[1]??[] as $classList){foreach(preg_split('/\s+/',trim((string)$classList))?:[] as $token)if($token!=='')$classTokens[$token]=true;}
    foreach($forbiddenClasses as $className)must_global_ui(!isset($classTokens[$className]),basename($path).' still consumes local primitive '.$className);
    must_global_ui(!str_contains($source,'data-student-validate'),basename($path).' still consumes local primitive data-student-validate');
}

$requiredConsumers=[
    'login.php'=>['form-field','button button-primary','data-ui-validate'],
    'perfil.php'=>['form-field','button button-primary','data-ui-validate'],
    'senha.php'=>['form-field','check-field','data-ui-match','data-ui-validate'],
    'caderno.php'=>['form-field','choice-field','button button-primary','data-ui-validate'],
    'teste.php'=>['form-field','button button-primary','data-ui-validate'],
];
foreach($requiredConsumers as $file=>$needles){
    $source=(string)file_get_contents($root.'/aluno/'.$file);
    foreach($needles as $needle)must_global_ui(str_contains($source,$needle),$file.' does not consume global primitive '.$needle);
}

$legacyTests=(string)file_get_contents($root.'/aluno/testes.php');
must_global_ui(str_contains($legacyTests,"header('Location: /aluno/caderno.php'"),'legacy tests route no longer redirects to the global notebook');

echo "global-ui-consumption: ok\n";
