<?php
declare(strict_types=1);

function fail_student_premium_ui(string $message): never {fwrite(STDERR,"student-premium-ui: $message\n");exit(1);}
function must_student_premium_ui(bool $condition,string $message): void {if(!$condition)fail_student_premium_ui($message);}

$root=dirname(__DIR__);
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$css=(string)file_get_contents($root.'/assets/student-area.css');
$workbenchCss=(string)file_get_contents($root.'/assets/student-workbench.css');
$uiCss=(string)file_get_contents($root.'/assets/ui-core.css');
$uiJs=(string)file_get_contents($root.'/assets/ui-core.js');
$home=(string)file_get_contents($root.'/aluno/index.php');
$courses=(string)file_get_contents($root.'/aluno/cursos.php');
$lab=(string)file_get_contents($root.'/aluno/ferramentas.php');
$notebook=(string)file_get_contents($root.'/aluno/caderno.php');
$test=(string)file_get_contents($root.'/aluno/teste.php');
$legacyTests=(string)file_get_contents($root.'/aluno/testes.php');
$login=(string)file_get_contents($root.'/aluno/login.php');
$password=(string)file_get_contents($root.'/aluno/senha.php');
$profile=(string)file_get_contents($root.'/aluno/perfil.php');
$renderer=(string)file_get_contents($root.'/app/cms_renderer.php');
$header=(string)file_get_contents($root.'/assets/cms-header.css');

must_student_premium_ui(!str_contains($shell,'data-theme="light"'),'student shell still hardcodes the light theme');
must_student_premium_ui(str_contains($shell,'data-theme-value="auto"')&&str_contains($shell,'data-theme-value="dark"'),'student shell does not expose the canonical theme choices');
must_student_premium_ui(str_contains($shell,'/assets/ui-core.css')&&str_contains($shell,'/assets/ui-core.js'),'student shell does not consume the global UI authority');
must_student_premium_ui(!str_contains($shell,'/assets/student-area.js'),'student shell still loads obsolete student-area.js behavior');
must_student_premium_ui(!is_file($root.'/assets/student-area.js'),'obsolete student-area.js still exists');
foreach(['.student-field','.student-choice-','.student-check-field','.student-button','.student-error','.student-notice','.student-danger-button','--student-control-height'] as $forbidden)must_student_premium_ui(!str_contains($css,$forbidden),'student stylesheet still owns global primitive '.$forbidden);

foreach(['>Início</a>','>Cursos</a>','>Caderno</a>','>Laboratório</a>'] as $destination)must_student_premium_ui(str_contains($shell,$destination),'student navigation is missing '.$destination);
must_student_premium_ui(!str_contains($shell,'>Meus cursos</a>')&&!str_contains($shell,'>Ferramentas</a>'),'obsolete student navigation labels returned');
must_student_premium_ui(str_contains($shell,'student-account-link')&&str_contains($shell,'>Conta</a>'),'account is not available outside the primary task navigation');
must_student_premium_ui(str_contains($css,'grid-template-columns:repeat(4,1fr)'),'mobile global navigation is not sized for the four task destinations');
must_student_premium_ui(str_contains($css,'.student-home-grid')&&str_contains($css,'.student-course-list')&&str_contains($css,'.student-lab-row'),'student stylesheet lost the canonical home/course/lab composition');
must_student_premium_ui(str_contains($css,'.student-desktop-nav a[aria-current=page]::after'),'desktop student navigation lost the restrained active marker');
must_student_premium_ui(str_contains($workbenchCss,'.student-process-row'),'student workbench lost notebook hierarchy');
must_student_premium_ui(!str_contains($workbenchCss,'transform:translateY(-2px)'),'student tools regressed to promotional hover cards');

foreach(['.form-field','.choice-field','.check-field','.button-compact','.button-danger','.ui-alert'] as $global)must_student_premium_ui(str_contains($uiCss,$global),'global UI stylesheet is missing '.$global);
must_student_premium_ui(str_contains($uiCss,'appearance:none')&&str_contains($uiCss,'[aria-invalid=true]'),'global field states are incomplete');
must_student_premium_ui(str_contains($uiJs,"form[data-ui-validate]")&&str_contains($uiJs,'workshop-theme'),'global UI behavior does not own validation and theme');
must_student_premium_ui(str_contains($uiJs,'aria-invalid')&&str_contains($uiJs,'aria-describedby')&&str_contains($uiJs,'scrollIntoView'),'global validation does not expose accessible persistent errors');
must_student_premium_ui(str_contains($uiJs,'As senhas não conferem.')&&str_contains($uiJs,'dataset.uiMatch'),'global validation does not own password matching');

must_student_premium_ui(str_contains($home,'Escolha o que você veio fazer')&&str_contains($home,'Abrir cursos')&&str_contains($home,'Abrir Caderno')&&str_contains($home,'Abrir laboratório'),'student home does not orient the user by task');
must_student_premium_ui(str_contains($courses,'student_enrollment_dashboard_context')&&str_contains($courses,'student_course_context_header'),'courses surface lost course selection/context');
must_student_premium_ui(!str_contains($courses,'>Progresso<')&&str_contains($courses,'aulas disponíveis para esta turma'),'course overview must describe availability rather than false progress');
must_student_premium_ui(str_contains($courses,'Material e acompanhamento')&&str_contains($courses,'Abrir dúvidas'),'course actions are not explicit');
must_student_premium_ui(str_contains($lab,'Instrumentos de trabalho')&&str_contains($lab,'Química e estoque')&&str_contains($lab,'Preparos salvos'),'laboratory surface lost its work-oriented organization');

must_student_premium_ui(!str_contains($test,'datalist id="bleach-options"'),'guided process regressed to browser datalist');
must_student_premium_ui(str_contains($test,'class="choice-field"')&&str_contains($test,'data-process-step-form')&&str_contains($test,'name="stage_key"'),'guided process does not consume the global choice control');
must_student_premium_ui(str_contains($test,'data-ui-validate')&&!str_contains($test,'data-student-validate'),'record workflow is not consuming global validation');
must_student_premium_ui(str_contains($test,'button button-primary'),'record workflow is not consuming global buttons');
must_student_premium_ui(str_contains($notebook,'Caderno de Processos')&&str_contains($notebook,'Novo registro'),'global notebook surface is missing');
must_student_premium_ui(str_contains($notebook,'button button-danger button-compact')&&str_contains($notebook,'name="visibility"'),'notebook secondary/destructive actions are not explicit controls');
must_student_premium_ui(str_contains($notebook,'data-ui-validate')&&!str_contains($notebook,'data-student-validate'),'new-record form is not consuming global validation');
must_student_premium_ui(str_contains($legacyTests,"header('Location: /aluno/caderno.php'"),'legacy tests route does not redirect to the notebook');

foreach([$login,$password,$profile] as $surface){must_student_premium_ui(str_contains($surface,'data-ui-validate'),'an account form is not consuming global validation');must_student_premium_ui(!str_contains($surface,'data-student-validate'),'an account form still uses a local validation contract');}
must_student_premium_ui(str_contains($login,'class="form-field"')&&str_contains($login,'button button-primary'),'login does not consume global field/button primitives');
must_student_premium_ui(str_contains($password,'data-ui-match')&&str_contains($password,'class="check-field"'),'password screen does not consume global match/check primitives');
must_student_premium_ui(!str_contains($password,'style="font-size:50px"')&&!str_contains($password,'style="display:flex'),'password screen still carries one-off inline visual rules');
must_student_premium_ui(str_contains($renderer,'$studentAccessLabel=$materialContext?$contextBackLabel:$studentAreaLabel')&&str_contains($renderer,'/aluno/caderno.php')&&str_contains($renderer,'/aluno/duvidas.php?cohort='),'protected material does not expose the current student-area destinations');
must_student_premium_ui(!str_contains($renderer,'data-cms-student-context')&&!str_contains($header,'.cms-student-context'),'protected material still owns a second context bar');

echo "student-premium-ui: ok\n";
