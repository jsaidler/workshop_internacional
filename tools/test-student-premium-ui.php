<?php
declare(strict_types=1);

function fail_student_premium_ui(string $message): never {fwrite(STDERR,"student-premium-ui: $message\n");exit(1);}
function must_student_premium_ui(bool $condition,string $message): void {if(!$condition)fail_student_premium_ui($message);}

$root=dirname(__DIR__);
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$css=(string)file_get_contents($root.'/assets/student-area.css');
$shellCss=(string)file_get_contents($root.'/assets/student-shell.css');
$uiCss=(string)file_get_contents($root.'/assets/ui-core.css');
$uiJs=(string)file_get_contents($root.'/assets/ui-core.js');
$tests=(string)file_get_contents($root.'/aluno/testes.php');
$test=(string)file_get_contents($root.'/aluno/teste.php');
$login=(string)file_get_contents($root.'/aluno/login.php');
$password=(string)file_get_contents($root.'/aluno/senha.php');
$profile=(string)file_get_contents($root.'/aluno/perfil.php');
$renderer=(string)file_get_contents($root.'/app/cms_renderer.php');
$header=(string)file_get_contents($root.'/assets/cms-header.css');

must_student_premium_ui(!str_contains($shell,'data-theme="light"'),'student shell still hardcodes the light theme');
must_student_premium_ui(str_contains($shell,'data-theme-value="auto"')&&str_contains($shell,'data-theme-value="dark"'),'student shell does not expose the canonical theme choices');
must_student_premium_ui(str_contains($shell,'/assets/ui-core.css')&&str_contains($shell,'/assets/ui-core.js'),'student shell does not consume the global UI authority');
must_student_premium_ui(str_contains($shell,'/assets/student-shell.css'),'student shell does not load its shared composition layer');
must_student_premium_ui(!str_contains($shell,'/assets/student-area.js'),'student shell still loads student-specific interaction behavior');
must_student_premium_ui(!is_file($root.'/assets/student-area.js'),'student-specific validation implementation still exists');

foreach(['.student-field','.student-choice-','.student-check-field','.student-button','.student-error','.student-notice','.student-danger-button','--student-control-height'] as $forbidden){
    must_student_premium_ui(!str_contains($css,$forbidden),'student stylesheet still owns global primitive '.$forbidden);
}
must_student_premium_ui(str_contains($css,'.student-step-nav')&&str_contains($css,'.student-workflow-panel')&&str_contains($css,'.student-course-card'),'student stylesheet lost application-specific composition');
must_student_premium_ui(str_contains($shellCss,'.student-topbar')&&str_contains($shellCss,'.student-course-context'),'shared student shell lost global bar or course context composition');

foreach(['.form-field','.choice-field','.check-field','.button-compact','.button-danger','.ui-alert'] as $global){
    must_student_premium_ui(str_contains($uiCss,$global),'global UI stylesheet is missing '.$global);
}
must_student_premium_ui(str_contains($uiCss,'appearance:none')&&str_contains($uiCss,'[aria-invalid=true]'),'global field states are incomplete');
must_student_premium_ui(str_contains($uiJs,"form[data-ui-validate]")&&str_contains($uiJs,'workshop-theme'),'global UI behavior does not own validation and theme');
must_student_premium_ui(str_contains($uiJs,'aria-invalid')&&str_contains($uiJs,'aria-describedby')&&str_contains($uiJs,'scrollIntoView'),'global validation does not expose accessible persistent errors');
must_student_premium_ui(str_contains($uiJs,'As senhas não conferem.')&&str_contains($uiJs,'dataset.uiMatch'),'global validation does not own password matching');

must_student_premium_ui(!str_contains($test,'datalist id="bleach-options"'),'bleach still uses the browser datalist popup');
must_student_premium_ui(str_contains($test,'class="choice-field"')&&str_contains($test,'Solução peroxiacética')&&str_contains($test,'Cloreto férrico'),'bleach does not consume the global choice control');
must_student_premium_ui(str_contains($test,'data-ui-validate')&&!str_contains($test,'data-student-validate'),'test workflow is not consuming global validation');
must_student_premium_ui(str_contains($test,'button button-primary'),'test workflow is not consuming global buttons');

must_student_premium_ui(!str_contains($tests,'onchange="this.form.requestSubmit()"'),'test visibility still autosaves through an opaque select change');
must_student_premium_ui(str_contains($tests,'Salvar acesso')&&str_contains($tests,'name="visibility" value="course"'),'test sharing does not expose an explicit save action');
must_student_premium_ui(str_contains($tests,'data-ui-validate')&&!str_contains($tests,'data-student-validate'),'new-test form is not consuming global validation');
must_student_premium_ui(str_contains($tests,'choice-field')&&str_contains($tests,'button button-secondary button-compact'),'tests workspace is not consuming global choice/button primitives');

foreach([$login,$password,$profile] as $surface){
    must_student_premium_ui(str_contains($surface,'data-ui-validate'),'an account form is not consuming global validation');
    must_student_premium_ui(!str_contains($surface,'data-student-validate'),'an account form still uses a local validation contract');
}
must_student_premium_ui(str_contains($login,'class="form-field"')&&str_contains($login,'button button-primary'),'login does not consume global field/button primitives');
must_student_premium_ui(str_contains($password,'data-ui-match')&&str_contains($password,'class="check-field"'),'password screen does not consume global match/check primitives');
must_student_premium_ui(!str_contains($password,'style="font-size:50px"')&&!str_contains($password,'style="display:flex'),'password screen still carries one-off inline visual rules');
must_student_premium_ui(str_contains($renderer,'student_shell_topbar_markup')&&str_contains($renderer,'student_course_context_markup'),'protected material does not reuse canonical student shell/context');
must_student_premium_ui(str_contains($renderer,'if($materialContext&&$currentStudent)')&&str_contains($renderer,'data-cms-public-header'),'protected material does not make public and student topbars mutually exclusive');
must_student_premium_ui(!str_contains($renderer,'data-cms-student-context')&&!str_contains($header,'.cms-student-context'),'protected material still owns a second legacy context bar');

echo "student-premium-ui: ok\n";
