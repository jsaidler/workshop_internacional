<?php
declare(strict_types=1);
function fail_student_premium_ui(string $message): never {fwrite(STDERR,"student-premium-ui: $message\n");exit(1);}
function must_student_premium_ui(bool $condition,string $message): void {if(!$condition)fail_student_premium_ui($message);}
$root=dirname(__DIR__);
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$css=(string)file_get_contents($root.'/assets/student-area.css');
$experienceCss=(string)file_get_contents($root.'/assets/student-experience.css');
$academicCss=(string)file_get_contents($root.'/assets/student-academic.css');
$renderedCss=(string)file_get_contents($root.'/assets/student-rendered-fixes.css');
$uiCss=(string)file_get_contents($root.'/assets/ui-core.css');
$uiJs=(string)file_get_contents($root.'/assets/ui-core.js');
$home=(string)file_get_contents($root.'/aluno/index.php');
$courses=(string)file_get_contents($root.'/aluno/cursos.php');$coursesView=(string)file_get_contents($root.'/app/student_courses_view.php');
$bench=(string)file_get_contents($root.'/aluno/ferramentas.php').(string)file_get_contents($root.'/app/student_laboratory_view.php');
$notebook=(string)file_get_contents($root.'/aluno/caderno.php').(string)file_get_contents($root.'/app/student_notebook_view.php');
$test=(string)file_get_contents($root.'/aluno/teste.php');
$routePicker=(string)file_get_contents($root.'/aluno/registro-roteiro.php');
$processManager=(string)file_get_contents($root.'/aluno/processamentos.php');
$processRunner=(string)file_get_contents($root.'/aluno/processar.php');
$legacyTests=(string)file_get_contents($root.'/aluno/testes.php');
$login=(string)file_get_contents($root.'/aluno/login.php');
$password=(string)file_get_contents($root.'/aluno/senha.php');
$profile=(string)file_get_contents($root.'/aluno/perfil.php').(string)file_get_contents($root.'/app/student_profile_view.php');

must_student_premium_ui(!str_contains($shell,'data-theme="light"'),'student shell still hardcodes the light theme');
must_student_premium_ui(str_contains($shell,'data-theme-value="auto"')&&str_contains($shell,'data-theme-value="dark"'),'student shell does not expose canonical theme choices');
must_student_premium_ui(str_contains($shell,'/assets/ui-core.css')&&str_contains($shell,'/assets/ui-core.js'),'student shell does not consume global UI authority');
must_student_premium_ui(str_contains($shell,'/assets/student-experience.css')&&str_contains($shell,'/assets/student-experience.js'),'student shell does not load the experience layer');
must_student_premium_ui(str_contains($shell,'/assets/student-academic.css'),'student shell does not load the academic UX layer');
must_student_premium_ui(str_contains($shell,'/assets/student-rendered-fixes.css')&&str_contains($shell,'data-student-rendered-fixes'),'student shell does not load the rendered visual polish layer');
foreach(['.student-field','.student-choice-','.student-check-field','.student-button','.student-error','.student-notice','.student-danger-button','--student-control-height'] as $forbidden)must_student_premium_ui(!str_contains($css,$forbidden),'student stylesheet still owns global primitive '.$forbidden);

foreach(["'home'=>['label'=>'Início','href'=>'/aluno/']","'agenda'=>['label'=>'Agenda','href'=>'/aluno/agenda.php']","'courses'=>['label'=>'Cursos','href'=>'/aluno/cursos.php']","'notebook'=>['label'=>'Caderno','href'=>'/aluno/caderno.php']","'laboratory'=>['label'=>'Laboratório','href'=>'/aluno/ferramentas.php']"] as $destination)must_student_premium_ui(str_contains($shell,$destination),'student primary navigation is missing '.$destination);
must_student_premium_ui(str_contains($shell,'data-toolbox-open')&&str_contains($shell,'data-student-toolbox'),'small utilities are not exposed as a contextual toolbox');
must_student_premium_ui(str_contains($shell,'student-user-menu')&&str_contains($shell,'Gerenciar conta'),'account/theme/session controls are not grouped as settings');
must_student_premium_ui(str_contains($experienceCss,'.student-home-primary')&&str_contains($experienceCss,'.student-process-now')&&str_contains($experienceCss,'.student-bench-grid'),'experience stylesheet lost task hierarchy for unchanged student surfaces');
must_student_premium_ui(str_contains($academicCss,'.student-dashboard-focus')&&str_contains($academicCss,'.student-academic-material-list'),'academic stylesheet does not own Dashboard/Course study hierarchy');

foreach(['.form-field','.choice-field','.check-field','.button-compact','.button-danger','.ui-alert'] as $global)must_student_premium_ui(str_contains($uiCss,$global),'global UI stylesheet is missing '.$global);
must_student_premium_ui(str_contains($uiCss,'appearance:none')&&str_contains($uiCss,'[aria-invalid=true]'),'global field states are incomplete');
must_student_premium_ui(str_contains($uiJs,"form[data-ui-validate]")&&str_contains($uiJs,'workshop-theme'),'global UI behavior does not own validation and theme');

$homeView=(string)file_get_contents($root.'/app/student_home_view.php');
must_student_premium_ui(str_contains($home,'student_experience_dashboard_state')&&str_contains($homeView,'student-v2-home')&&str_contains($homeView,'data-home-meeting'),'student Home must consume operational state and feature the next authorized meeting');
must_student_premium_ui(str_contains($homeView,'student-v2-next')&&str_contains($homeView,'student-v2-section')&&str_contains($homeView,'student-v2-course')&&str_contains($homeView,'data-record-create-open')&&str_contains($homeView,'/aluno/agenda.php'),'student Home must preserve contextual work, courses, record creation and direct Agenda access');
$mobileAppCss=(string)file_get_contents($root.'/assets/student-app-shell.css');
must_student_premium_ui(str_contains($shell,"'agenda'=>['label'=>'Agenda','href'=>'/aluno/agenda.php']")&&str_contains($mobileAppCss,'grid-template-columns:repeat(5,minmax(0,1fr))'),'approved five-destination shell is not integrated');
must_student_premium_ui(str_contains($mobileAppCss,'.student-desktop-nav')&&str_contains($mobileAppCss,'.student-mobile-nav')&&str_contains($homeView,'student-v2-date-line'),'mobile-first shell/sidebar and factual date hierarchy are absent');
must_student_premium_ui(!str_contains($home,'Escolha o que você veio fazer')&&!str_contains($home,'Abrir laboratório'),'student home regressed to a duplicate navigation catalog');
must_student_premium_ui(str_contains($courses,'elseif(count($enrollments)===1)$selectedEnrollment=$enrollments[0]'),'single enrollment still requires a course-selector click');
must_student_premium_ui(str_contains($coursesView,'student-v2-course-pages')&&str_contains($coursesView,'Dúvidas e respostas')&&str_contains($courses,'course_material_page_release_summary'),'course workspace is not flattened around study tasks');
must_student_premium_ui(!str_contains($coursesView,'student-course-dashboard')&&!str_contains($courses,'$releasedCount'),'course workspace regressed to parallel dashboard/progress blocks');
must_student_premium_ui(str_contains($bench,'data-exposure-tool')&&str_contains($bench,'data-quick-reciprocity'),'workbench lost inline simple exposure tools');
must_student_premium_ui(!str_contains($bench,'data-lab-timer')&&str_contains($bench,'/aluno/processamentos.php'),'workbench did not replace the isolated timer with the process library');

// Process library remains reusable, but the Caderno does not declare a live/retroactive mode.
must_student_premium_ui(str_contains($processManager,'Associar ao registro'),'process library lost neutral record association');
must_student_premium_ui(str_contains($processRunner,'data-process-runner')&&str_contains($processRunner,'Marcar como concluída')&&str_contains($processRunner,'Desmarcar etapa'),'route tool must allow independent reversible step checks');
must_student_premium_ui(str_contains($processRunner,'Editar dados da etapa')&&str_contains($processRunner,'Movimentar estoque'),'route tool must expose editing and stock as independent notebook tools');
foreach(['Estou nesta etapa','Ir para próxima etapa','Concluir processamento','Interromper a etapa atual','Concluir esta etapa','Registrar o processamento realizado'] as $forbidden)must_student_premium_ui(!str_contains($processRunner,$forbidden),'route tool reintroduced workflow language: '.$forbidden);
must_student_premium_ui(str_contains($bench,'id="receitas" data-lab-calculator')&&str_contains($bench,'Modo de preparo'),'recipes no longer keep quantities and preparation together');
foreach(['Continue de onde faz sentido','Ferramentas pequenas ficam aqui','sem entrar e sair de várias páginas','O histórico fica abaixo','Aqui o foco é somente a etapa atual','registre apenas os dados que usou'] as $internalCopy)must_student_premium_ui(!str_contains($home.$bench.$test,$internalCopy),'internal design commentary leaked into student UI: '.$internalCopy);

must_student_premium_ui(str_contains($notebook,'student-process-card')&&str_contains($notebook,'Mais ações'),'notebook does not subordinate administrative actions');
must_student_premium_ui(!str_contains($notebook,'student-process-compare')&&!str_contains($notebook,'student-record-progress'),'notebook reintroduced permanent comparison or staged progress controls');
must_student_premium_ui(str_contains($notebook,'button button-danger button-compact')&&str_contains($notebook,'name="visibility"'),'secondary/destructive notebook controls are not explicit inside secondary actions');
$createForm=(string)file_get_contents($root.'/app/student_record_create_dialog.php');
must_student_premium_ui(str_contains($notebook,'student_record_create_dialog(')&&str_contains($notebook,'data-record-create-open')&&str_contains($createForm,'data-ui-validate')&&str_contains($createForm,'data-record-create-dialog'),'new-record must use shared one-click form with global validation');
must_student_premium_ui(str_contains($createForm,'data-record-create-error')&&str_contains($createForm,'data-record-create-alert'),'new record creation errors must stay inside the dialog');
must_student_premium_ui(str_contains($legacyTests,"header('Location: /aluno/caderno.php'"),'legacy tests route does not redirect to notebook');

foreach(['id="exposicao"','id="processamento"','id="resultado"'] as $anchor)must_student_premium_ui(str_contains($test,$anchor),'record lost always-available documentary section '.$anchor);
must_student_premium_ui(!str_contains($test,"\$view='review'")&&!str_contains($test,'$resultReady'),'record navigation is coupled again to process progression');
foreach(['Vou revelar agora','Já revelei','Continuar laboratório','Registrar manualmente','intent=live','Próxima etapa'] as $forbidden)must_student_premium_ui(!str_contains($test,$forbidden),'record reintroduced workflow choice: '.$forbidden);
must_student_premium_ui(str_contains($test,'Marcar ✓')&&str_contains($test,'edit_plan_step=')&&str_contains($test,"?'Timer':'Abrir etapa'")&&str_contains($test,'Adicionar etapa ao roteiro')&&str_contains($test,'Movimentar estoque'),'record does not expose notebook advantages directly');
must_student_premium_ui(str_contains($routePicker,'student_process_replan_template')&&str_contains($routePicker,'student_process_replan_standard'),'record does not use the canonical route picker');
must_student_premium_ui(str_contains($routePicker,'não inicia processamento, não impõe ordem e não movimenta estoque'),'route picker semantics are not neutral');
must_student_premium_ui(str_contains($test,'data-ui-validate')&&!str_contains($test,'data-student-validate'),'record is not consuming global validation');
must_student_premium_ui(str_contains($renderedCss,'.student-sticky-action{margin-top:30px;padding-top:22px')&&str_contains($renderedCss,'.student-form-grid{gap:28px 20px}'),'primary form actions can collapse against fields');
must_student_premium_ui(str_contains($renderedCss,'.student-create-dialog .student-actions{justify-content:flex-end;gap:12px;margin-top:10px;padding-top:22px'),'new-record action still collapses against preceding fields');

foreach([$login,$password,$profile] as $surface){must_student_premium_ui(str_contains($surface,'data-ui-validate'),'an account form is not consuming global validation');must_student_premium_ui(!str_contains($surface,'data-student-validate'),'an account form still uses a local validation contract');}
must_student_premium_ui(str_contains($login,'class="form-field"')&&str_contains($login,'button button-primary'),'login does not consume global field/button primitives');
must_student_premium_ui(str_contains($password,'data-ui-match')&&str_contains($password,'class="check-field"'),'password screen does not consume global match/check primitives');
echo "student-premium-ui: ok\n";