<?php
declare(strict_types=1);
function fail_student_test_workflow(string $message): never {fwrite(STDERR,"student-test-workflow: $message\n");exit(1);}
require_once dirname(__DIR__).'/app/student_enrollments.php';

$enrollments=[
    ['cohort_id'=>10,'cohort_uuid'=>'cohort-a','activity_id'=>100],
    ['cohort_id'=>11,'cohort_uuid'=>'cohort-a2','activity_id'=>100],
    ['cohort_id'=>20,'cohort_uuid'=>'cohort-b','activity_id'=>200],
];
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

$root=dirname(__DIR__);$test=(string)file_get_contents($root.'/aluno/teste.php');$shared=(string)file_get_contents($root.'/aluno/teste-compartilhado.php');$shell=(string)file_get_contents($root.'/app/student_shell.php');$reciprocity=(string)file_get_contents($root.'/assets/student-reciprocity.js');$workbench=(string)file_get_contents($root.'/app/student_workbench.php');
if(!str_contains($test,'name="action" value="save_exposure"')||!str_contains($test,'Salvar e continuar →'))fail_student_test_workflow('exposure no longer saves and advances to processing');
if(!str_contains($test,'data-process-step-form')||!str_contains($test,'name="action" value="add_step"')||!str_contains($test,'name="stage_key"')||!str_contains($test,'Adicionar etapa →'))fail_student_test_workflow('guided processing no longer advances one selected stage at a time');
if(!str_contains($test,'student_process_next_choices($steps)'))fail_student_test_workflow('guided workflow no longer derives the next choices from completed stages');
if(!str_contains($test,'data-developer-amount')||!str_contains($test,'data-water-amount')||!str_contains($test,'data-dilution-output'))fail_student_test_workflow('developer preparation no longer records measured developer and water amounts');
if(!str_contains($test,'data-saved-preparation')||!str_contains($test,'student_saved_preparations'))fail_student_test_workflow('guided workflow lost saved personal preparations');
if(!str_contains($test,'student_process_inventory_select_html')||!str_contains($workbench,'student_inventory'))fail_student_test_workflow('guided workflow lost inventory integration');
if(!str_contains($test,'view=exposure')||!str_contains($test,'view=process')||!str_contains($test,'view=review'))fail_student_test_workflow('record no longer exposes exposure, processing and result views');
if(!str_contains($test,'href="/aluno/caderno.php"'))fail_student_test_workflow('owned record no longer returns to the global process notebook');
if(!str_contains($shared,'student_enrollment_shared_test_navigation_context'))fail_student_test_workflow('shared record lost enrollment-aware sharing access');
if(!str_contains($test,'name="calculated_time"')||!str_contains($test,'name="reciprocity_time"'))fail_student_test_workflow('exposure no longer exposes the calculated and reciprocity time pair');
if(!str_contains($shell,'/assets/student-reciprocity.js'))fail_student_test_workflow('student shell does not load the client-side reciprocity calculator');
if(!str_contains($reciprocity,'const RECIPROCITY_EXPONENT=1.38542662'))fail_student_test_workflow('reciprocity research exponent changed or disappeared');
if(!str_contains($reciprocity,'input[name="calculated_time"]')||!str_contains($reciprocity,'input[name="reciprocity_time"]'))fail_student_test_workflow('reciprocity calculator is no longer bound to the exposure fields');

echo "student-test-workflow: ok\n";
