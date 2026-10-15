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

must_global_ui(str_contains($uiCss,'.button.button-danger{color:var(--text)')&&str_contains($uiCss,'.button.button-danger:hover{color:#fff')&&!str_contains($uiCss,'.button-danger{color:#8b2727'),'global destructive button must keep accessible text colors in dark and light themes');

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
    // The notebook creation form has a shared renderer; check the actual
    // included component rather than requiring duplicated markup in the route.
    if(in_array($file,['login.php','senha.php'],true)){
        $source.=(string)file_get_contents($root.'/app/student_auth_view.php');
        must_global_ui(str_contains($source,$file==='login.php'?'student_auth_render_login(':'student_auth_render_password('),$file.' must share the canonical authentication renderer');
    }
    if($file==='perfil.php'){
        $source.=(string)file_get_contents($root.'/app/student_profile_view.php');
        must_global_ui(str_contains($source,'student_profile_render($profile,$error,$notice)'),'account must share the canonical profile renderer');
    }
    if($file==='teste.php'){
        $source.=(string)file_get_contents($root.'/app/student_record_workspace_view.php');
        must_global_ui(str_contains($source,'student_record_render_workspace(compact('),$file.' must share the canonical 3-part record workspace');
    }
    if($file==='caderno.php'){
        $source.=(string)file_get_contents($root.'/app/student_notebook_view.php');
        must_global_ui(str_contains($source,'student_record_create_dialog('),'notebook must include the canonical creation form');
        $source.=(string)file_get_contents($root.'/app/student_record_create_dialog.php');
    }
    foreach($needles as $needle)must_global_ui(str_contains($source,$needle),$file.' does not consume global primitive '.$needle);
}

$legacyTests=(string)file_get_contents($root.'/aluno/testes.php');
must_global_ui(str_contains($legacyTests,"header('Location: /aluno/caderno.php'"),'legacy tests route no longer redirects to the global notebook');

// Task-first presentation must consume the existing Design, never impose a new skin.
$taskCssFiles=['student-agenda.css','student-courses.css','student-laboratory.css','student-profile.css','student-caderno.css','student-lab-stock.css','student-processes.css'];
foreach($taskCssFiles as $name){
    $css=(string)file_get_contents($root.'/assets/'.$name);
    must_global_ui(!str_contains($css,'Georgia'),'task stylesheet imports an independent serif font: '.$name);
    must_global_ui(!str_contains($css,'var(--body,Arial'),'task stylesheet overrides the saved body font fallback: '.$name);
    must_global_ui(!preg_match('/--student-v2-(?:bg|surface|subtle|text|muted|line|accent|ink)\\b/',$css),'task stylesheet reintroduces a parallel palette: '.$name);
}
$taskShell=$studentCss;
must_global_ui(!is_file($root.'/assets/student-app-shell.css'),'mobile shell remains a second global CSS authority');
must_global_ui(!str_contains($taskShell,'Georgia'),'student shell brings back an independent brand font');
must_global_ui(!preg_match('/\\.student-app \\.student-shell\\{[^}]*--(?:bg|surface|surface-2|text|muted|line|line-strong|focus)\\s*:/',$taskShell),'task shell overwrites the CMS color tokens');
must_global_ui(!str_contains($taskShell,'.student-v2-cta{'),'task Home recreates the canonical button primitive');
$taskHome=(string)file_get_contents($root.'/app/student_home_view.php');
must_global_ui(str_contains($taskHome,'class="student-title"')&&str_contains($taskHome,'class="student-subtitle"'),'task Home is not using canonical display headings');
must_global_ui(str_contains($taskHome,'class="button button-primary student-v2-cta"')&&str_contains($taskHome,'class="button button-secondary student-v2-secondary-link"'),'task Home bypasses canonical button actions');
foreach(['student_agenda_view.php','student_courses_view.php','student_laboratory_view.php'] as $renderer){
    $src=(string)file_get_contents($root.'/app/'.$renderer);
    must_global_ui(str_contains($src,'class="student-title"'),'task heading bypasses the existing student-title primitive: '.$renderer);
}
$notebookCss=(string)file_get_contents($root.'/assets/student-caderno.css');
must_global_ui(!preg_match('/\\.student-v2-notebook-header h1\\.student-title\\{[^}]*font\\s*:/',$notebookCss),'Caderno heading bypasses inherited Design display font');
must_global_ui(!preg_match('/\\.student-app \\.student-record-header \\.student-title-record\\{[^}]*font\\s*:/',$notebookCss),'record heading overrides canonical display typography');
$stockCss=(string)file_get_contents($root.'/assets/student-lab-stock.css');
must_global_ui(!preg_match('/\\.student-task-editor \\.student-task-actions \\.button-primary\\{[^}]*?(?:background|color|font|border-radius)\\s*:/',$stockCss),'laboratory task action recreates canonical button visuals');
must_global_ui(!str_contains($stockCss,'--task-radius'),'laboratory task editor introduced independent component shape token');
$processCss=(string)file_get_contents($root.'/assets/student-processes.css');
must_global_ui(!preg_match('/\\.student-process-task-form \\.form-field\\{[^}]*font\\s*:/',$processCss),'process editor overrides canonical field labels');

$profileCss=(string)file_get_contents($root.'/assets/student-profile.css');
must_global_ui(!preg_match('/\\.student-profile-header \\.student-title-record\\{[^}]*font\\s*:/',$profileCss),'profile overrides its inherited canonical display heading');

echo "global-ui-consumption: ok\n";
