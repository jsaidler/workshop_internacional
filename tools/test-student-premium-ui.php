<?php
declare(strict_types=1);

function fail_student_premium_ui(string $message): never {fwrite(STDERR,"student-premium-ui: $message\n");exit(1);}
function must_student_premium_ui(bool $condition,string $message): void {if(!$condition)fail_student_premium_ui($message);}

$root=dirname(__DIR__);
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$css=(string)file_get_contents($root.'/assets/student-area.css');
$js=(string)file_get_contents($root.'/assets/student-area.js');
$tests=(string)file_get_contents($root.'/aluno/testes.php');
$test=(string)file_get_contents($root.'/aluno/teste.php');
$login=(string)file_get_contents($root.'/aluno/login.php');
$password=(string)file_get_contents($root.'/aluno/senha.php');
$profile=(string)file_get_contents($root.'/aluno/perfil.php');
$header=(string)file_get_contents($root.'/assets/cms-header.css');

must_student_premium_ui(!str_contains($shell,'data-theme="light"'),'student shell still hardcodes the light theme');
must_student_premium_ui(str_contains($shell,'data-theme-value="auto"')&&str_contains($shell,'data-theme-value="dark"'),'student shell does not expose the canonical theme choices');
must_student_premium_ui(str_contains($shell,'/template/page.js')&&str_contains($shell,'/assets/student-area.js'),'student shell does not load canonical theme behavior and student interaction behavior');

must_student_premium_ui(str_contains($css,'--student-control-height:54px'),'student controls do not lock the system button/control target size');
must_student_premium_ui(str_contains($css,'.student-button,.student-capture-button,.student-course-primary'),'student primary actions are not governed by one visual contract');
must_student_premium_ui(str_contains($css,'[aria-invalid=true]')&&str_contains($css,'.student-field-error'),'student field error states are missing');
must_student_premium_ui(str_contains($css,'.student-choice-option input:checked+span'),'segmented choices do not expose a selected state');
must_student_premium_ui(str_contains($css,'appearance:none')&&str_contains($css,'linear-gradient(45deg'),'native select chrome is not normalized');

must_student_premium_ui(str_contains($js,"form[data-student-validate]")&&str_contains($js,'form.noValidate=true'),'progressive form validation is not installed');
must_student_premium_ui(str_contains($js,"aria-invalid")&&str_contains($js,"aria-describedby"),'client validation does not expose accessible field state');
must_student_premium_ui(str_contains($js,'scrollIntoView')&&str_contains($js,'focus({preventScroll:true})'),'first invalid field is not brought back into context');
must_student_premium_ui(str_contains($js,"As senhas não conferem."),'password confirmation does not expose a specific inline error');

must_student_premium_ui(!str_contains($test,'datalist id="bleach-options"'),'bleach still uses the browser datalist popup');
must_student_premium_ui(str_contains($test,'student-choice-field')&&str_contains($test,'Solução peroxiacética')&&str_contains($test,'Cloreto férrico'),'bleach choices are not rendered as governed controls');
must_student_premium_ui(str_contains($test,'data-student-validate'),'test workflow forms do not opt into refined validation');

must_student_premium_ui(!str_contains($tests,'onchange="this.form.requestSubmit()"'),'test visibility still autosaves through an opaque select change');
must_student_premium_ui(str_contains($tests,'Salvar acesso')&&str_contains($tests,'name="visibility" value="course"'),'test sharing does not expose an explicit save action');
must_student_premium_ui(str_contains($tests,'data-student-validate'),'new-test form does not opt into refined validation');

foreach([$login,$password,$profile] as $surface)must_student_premium_ui(str_contains($surface,'data-student-validate'),'an account form is still using browser-only validation');
must_student_premium_ui(!str_contains($password,'style="font-size:50px"')&&!str_contains($password,'style="display:flex'),'password screen still carries one-off inline visual rules');
must_student_premium_ui(str_contains($header,'.cms-student-context nav a:first-child'),'protected material context does not distinguish the return-to-course action');

echo "student-premium-ui: ok\n";
