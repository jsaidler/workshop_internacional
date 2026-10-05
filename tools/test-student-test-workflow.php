<?php
declare(strict_types=1);
function fail_student_test_workflow(string $message): never {fwrite(STDERR,"student-test-workflow: $message\n");exit(1);}
require_once dirname(__DIR__).'/app/student_enrollments.php';
$enrollments=[['cohort_id'=>10,'cohort_uuid'=>'cohort-a','activity_id'=>100],['cohort_id'=>11,'cohort_uuid'=>'cohort-a2','activity_id'=>100],['cohort_id'=>20,'cohort_uuid'=>'cohort-b','activity_id'=>200]];
$owned=['cohort_id'=>10,'activity_id'=>100];
if((student_enrollment_owned_test_navigation_context($enrollments,$owned,'cohort-a')['cohort_uuid']??'')!=='cohort-a')fail_student_test_workflow('legacy contextual record lost its exact enrollment context');
if(student_enrollment_owned_test_navigation_context($enrollments,$owned,'cohort-a2')!==null)fail_student_test_workflow('legacy contextual record accepted a different cohort context');
if((student_enrollment_owned_test_navigation_context($enrollments,$owned)['cohort_uuid']??'')!=='cohort-a')fail_student_test_workflow('legacy contextual record cannot recover its own active enrollment');
$sharedCohort=['cohort_id'=>10,'activity_id'=>100,'visibility'=>'cohort'];
if((student_enrollment_shared_test_navigation_context($enrollments,$sharedCohort,'cohort-a')['cohort_uuid']??'')!=='cohort-a')fail_student_test_workflow('cohort-shared record lost the matching cohort');
if(student_enrollment_shared_test_navigation_context($enrollments,$sharedCohort,'cohort-a2')!==null)fail_student_test_workflow('cohort-shared record accepted another cohort from the same course');
$sharedCourse=['cohort_id'=>10,'activity_id'=>100,'visibility'=>'course'];
if((student_enrollment_shared_test_navigation_context($enrollments,$sharedCourse,'cohort-a2')['cohort_uuid']??'')!=='cohort-a2')fail_student_test_workflow('course-shared record cannot return through another enrolled cohort of the same course');
if(student_enrollment_shared_test_navigation_context($enrollments,$sharedCourse,'cohort-b')!==null)fail_student_test_workflow('course-shared record accepted a context from another course');

$root=dirname(__DIR__);$test=(string)file_get_contents($root.'/aluno/teste.php');$routePicker=(string)file_get_contents($root.'/aluno/registro-roteiro.php');$runner=(string)file_get_contents($root.'/aluno/processar.php');$shared=(string)file_get_contents($root.'/aluno/teste-compartilhado.php');$shell=(string)file_get_contents($root.'/app/student_shell.php');$reciprocity=(string)file_get_contents($root.'/assets/student-reciprocity.js');$notebook=(string)file_get_contents($root.'/app/student_process_notebook.php');$free=(string)file_get_contents($root.'/app/student_process_notebook_free.php');
if(!str_contains($test,'name="action" value="save_exposure"')||!str_contains($test,'Salvar exposição')||str_contains($test,'Salvar exposição e continuar'))fail_student_test_workflow('exposure is no longer an independently saved section');
foreach(['id="exposicao"','id="processamento"','id="resultado"'] as $anchor)if(!str_contains($test,$anchor))fail_student_test_workflow('record no longer keeps all documentary sections accessible together');
if(str_contains($test,'$resultReady')||str_contains($test,'aria-disabled'))fail_student_test_workflow('record navigation is again gated by completion state');
if(!str_contains($test,"isset(\$_GET['view'])")||!str_contains($test,"'process'=>'processamento'")||!str_contains($test,"'review'=>'resultado'"))fail_student_test_workflow('legacy view links no longer canonicalize to record anchors');
if(!str_contains($test,'Associar roteiro')||!str_contains($test,'Adicionar etapa')||!str_contains($test,'Movimentar estoque'))fail_student_test_workflow('processing no longer exposes notebook actions independently');
if(!str_contains($test,'Marcar ✓')||!str_contains($test,'Desmarcar')||!str_contains($test,'Abrir timer'))fail_student_test_workflow('associated route no longer exposes arbitrary checks/timers');
foreach(['Vou revelar agora','Já revelei','Registrar manualmente','Continuar laboratório','Próxima etapa','intent=live'] as $forbidden)if(str_contains($test,$forbidden))fail_student_test_workflow('record reintroduced obsolete process workflow: '.$forbidden);
if(!str_contains($notebook,'student_process_notebook_set_completed')||!str_contains($notebook,"true,'timer'"))fail_student_test_workflow('timer/check notebook authority is missing');
if(!str_contains($free,'student_process_notebook_add_free_step')||!str_contains($free,'student_process_notebook_delete_free_step'))fail_student_test_workflow('free notebook steps are missing');
if(str_contains($free,'student_inventory_move'))fail_student_test_workflow('free notebook step editing still mutates stock implicitly');
if(!str_contains($routePicker,'student_process_replan_template')||!str_contains($routePicker,'student_process_replan_standard')||!str_contains($routePicker,'voltar ao registro'))fail_student_test_workflow('record route selection is not reversible and contextual');
if(!str_contains($routePicker,'não inicia processamento, não impõe ordem e não movimenta estoque'))fail_student_test_workflow('route association is not neutral');
foreach(['Estou nesta etapa','Ir para próxima etapa','Concluir processamento','Registrar o processamento realizado','$intent'] as $forbidden)if(str_contains($runner,$forbidden))fail_student_test_workflow('route tool still governs process progression: '.$forbidden);
if(!str_contains($runner,'Marcar como concluída')||!str_contains($runner,'Desmarcar etapa')||!str_contains($runner,'Editar dados da etapa'))fail_student_test_workflow('route step is not freely checkable/editable');
if(!str_contains($test,'href="/aluno/caderno.php"'))fail_student_test_workflow('owned record no longer returns to the global process notebook');
if(!str_contains($shared,'student_test_accessible_to_student')||!str_contains($shared,'href="/aluno/caderno.php"'))fail_student_test_workflow('shared record lost canonical sharing authorization or notebook return');
if(!str_contains($test,'name="calculated_time"')||!str_contains($test,'name="reciprocity_time"'))fail_student_test_workflow('exposure no longer exposes the calculated and reciprocity time pair');
if(!str_contains($shell,'/assets/student-reciprocity.js'))fail_student_test_workflow('student shell does not load the client-side reciprocity calculator');
if(!str_contains($reciprocity,'const RECIPROCITY_EXPONENT=1.38542662'))fail_student_test_workflow('reciprocity research exponent changed or disappeared');
if(!str_contains($reciprocity,'input[name="calculated_time"]')||!str_contains($reciprocity,'input[name="reciprocity_time"]'))fail_student_test_workflow('reciprocity calculator is no longer bound to the exposure fields');
echo "student-test-workflow: ok\n";