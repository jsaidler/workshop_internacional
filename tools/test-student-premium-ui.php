<?php
declare(strict_types=1);
function fail_student_premium_ui(string $message): never {fwrite(STDERR,"student-premium-ui: $message\n");exit(1);}
function must_student_premium_ui(bool $condition,string $message): void {if(!$condition)fail_student_premium_ui($message);}
$root=dirname(__DIR__);
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$css=(string)file_get_contents($root.'/assets/student-area.css');
$experienceCss=(string)file_get_contents($root.'/assets/student-experience.css');
$uiCss=(string)file_get_contents($root.'/assets/ui-core.css');
$uiJs=(string)file_get_contents($root.'/assets/ui-core.js');
$home=(string)file_get_contents($root.'/aluno/index.php');
$courses=(string)file_get_contents($root.'/aluno/cursos.php');
$bench=(string)file_get_contents($root.'/aluno/ferramentas.php');
$notebook=(string)file_get_contents($root.'/aluno/caderno.php');
$test=(string)file_get_contents($root.'/aluno/teste.php');
$legacyTests=(string)file_get_contents($root.'/aluno/testes.php');
$login=(string)file_get_contents($root.'/aluno/login.php');
$password=(string)file_get_contents($root.'/aluno/senha.php');
$profile=(string)file_get_contents($root.'/aluno/perfil.php');

must_student_premium_ui(!str_contains($shell,'data-theme="light"'),'student shell still hardcodes the light theme');
must_student_premium_ui(str_contains($shell,'data-theme-value="auto"')&&str_contains($shell,'data-theme-value="dark"'),'student shell does not expose canonical theme choices');
must_student_premium_ui(str_contains($shell,'/assets/ui-core.css')&&str_contains($shell,'/assets/ui-core.js'),'student shell does not consume global UI authority');
must_student_premium_ui(str_contains($shell,'/assets/student-experience.css')&&str_contains($shell,'/assets/student-experience.js'),'student shell does not load the experience layer');
foreach(['.student-field','.student-choice-','.student-check-field','.student-button','.student-error','.student-notice','.student-danger-button','--student-control-height'] as $forbidden)must_student_premium_ui(!str_contains($css,$forbidden),'student stylesheet still owns global primitive '.$forbidden);

foreach(['>Início</a>','>Curso</a>','>Caderno</a>'] as $destination)must_student_premium_ui(str_contains($shell,$destination),'student primary navigation is missing '.$destination);
must_student_premium_ui(str_contains($shell,'data-toolbox-open')&&str_contains($shell,'data-student-toolbox'),'small utilities are not exposed as a contextual toolbox');
must_student_premium_ui(str_contains($shell,'student-user-menu')&&str_contains($shell,'Gerenciar conta'),'account/theme/session controls are not grouped as settings');
must_student_premium_ui(str_contains($experienceCss,'.student-home-primary')&&str_contains($experienceCss,'.student-process-now')&&str_contains($experienceCss,'.student-bench-grid'),'experience stylesheet lost task hierarchy');

foreach(['.form-field','.choice-field','.check-field','.button-compact','.button-danger','.ui-alert'] as $global)must_student_premium_ui(str_contains($uiCss,$global),'global UI stylesheet is missing '.$global);
must_student_premium_ui(str_contains($uiCss,'appearance:none')&&str_contains($uiCss,'[aria-invalid=true]'),'global field states are incomplete');
must_student_premium_ui(str_contains($uiJs,"form[data-ui-validate]")&&str_contains($uiJs,'workshop-theme'),'global UI behavior does not own validation and theme');

must_student_premium_ui(str_contains($home,'student_experience_process_state')&&str_contains($home,'<h1 class="student-title">Início</h1>'),'student home is not a continuation surface');
must_student_premium_ui(!str_contains($home,'Escolha o que você veio fazer')&&!str_contains($home,'Abrir laboratório'),'student home regressed to a duplicate navigation catalog');
must_student_premium_ui(str_contains($courses,'elseif(count($enrollments)===1)$selectedEnrollment=$enrollments[0]'),'single enrollment still requires a course-selector click');
must_student_premium_ui(str_contains($courses,'student-course-dashboard')&&str_contains($courses,'Dúvidas e respostas'),'course workspace is not flattened around study tasks');
must_student_premium_ui(str_contains($bench,'data-exposure-tool')&&str_contains($bench,'data-quick-reciprocity')&&str_contains($bench,'data-lab-timer'),'workbench lost inline simple tools');
must_student_premium_ui(str_contains($bench,'<h2>Receitas</h2>')&&str_contains($bench,'Modo de preparo'),'recipes no longer keep quantities and preparation together');
foreach(['Continue de onde faz sentido','Ferramentas pequenas ficam aqui','sem entrar e sair de várias páginas','O histórico fica abaixo','Aqui o foco é somente a etapa atual','registre apenas os dados que usou'] as $internalCopy)must_student_premium_ui(!str_contains($home.$bench.$test,$internalCopy),'internal design commentary leaked into student UI: '.$internalCopy);

must_student_premium_ui(str_contains($notebook,'student-process-card')&&str_contains($notebook,'Mais ações'),'notebook does not subordinate administrative actions');
must_student_premium_ui(!str_contains($notebook,'student-process-compare'),'notebook reintroduced permanent compare checkboxes');
must_student_premium_ui(str_contains($notebook,'button button-danger button-compact')&&str_contains($notebook,'name="visibility"'),'secondary/destructive notebook controls are not explicit inside secondary actions');
must_student_premium_ui(str_contains($notebook,'data-ui-validate')&&str_contains($notebook,'data-record-create-dialog'),'new-record flow must be one-click and consume global validation');
must_student_premium_ui(str_contains($legacyTests,"header('Location: /aluno/caderno.php'"),'legacy tests route does not redirect to notebook');

must_student_premium_ui(str_contains($test,'student-process-now')&&str_contains($test,'student-process-history'),'processing does not prioritize now over history');
must_student_premium_ui(str_contains($test,'data-lab-timer'),'record workflow lost contextual timer');
must_student_premium_ui(str_contains($test,"if(\$stageKey==='dry')")&&str_contains($test,"\$view='review'"),'drying does not move directly to result');
must_student_premium_ui(str_contains($test,'Desfazer última etapa')&&!str_contains($test,'remover daqui'),'destructive process correction is still repeated on every step');
must_student_premium_ui(str_contains($test,'class="choice-field"')&&str_contains($test,'data-process-step-form'),'guided process does not consume global choice controls');
must_student_premium_ui(str_contains($test,'data-ui-validate')&&!str_contains($test,'data-student-validate'),'record workflow is not consuming global validation');
must_student_premium_ui(str_contains($experienceCss,'.student-workflow-panel .student-sticky-action{margin-top:28px;padding-top:22px}'),'primary form actions can collapse against fields');

foreach([$login,$password,$profile] as $surface){must_student_premium_ui(str_contains($surface,'data-ui-validate'),'an account form is not consuming global validation');must_student_premium_ui(!str_contains($surface,'data-student-validate'),'an account form still uses a local validation contract');}
must_student_premium_ui(str_contains($login,'class="form-field"')&&str_contains($login,'button button-primary'),'login does not consume global field/button primitives');
must_student_premium_ui(str_contains($password,'data-ui-match')&&str_contains($password,'class="check-field"'),'password screen does not consume global match/check primitives');
echo "student-premium-ui: ok\n";
