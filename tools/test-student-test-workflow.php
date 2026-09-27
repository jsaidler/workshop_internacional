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
if((student_enrollment_owned_test_navigation_context($enrollments,$owned,'cohort-a')['cohort_uuid']??'')!=='cohort-a')fail_student_test_workflow('owned test lost its exact enrollment context');
if(student_enrollment_owned_test_navigation_context($enrollments,$owned,'cohort-a2')!==null)fail_student_test_workflow('owned test accepted a different cohort context');
if((student_enrollment_owned_test_navigation_context($enrollments,$owned)['cohort_uuid']??'')!=='cohort-a')fail_student_test_workflow('owned test cannot recover its own active enrollment without an explicit context');

$sharedCohort=['cohort_id'=>10,'activity_id'=>100,'visibility'=>'cohort'];
if((student_enrollment_shared_test_navigation_context($enrollments,$sharedCohort,'cohort-a')['cohort_uuid']??'')!=='cohort-a')fail_student_test_workflow('cohort-shared test lost the matching cohort');
if(student_enrollment_shared_test_navigation_context($enrollments,$sharedCohort,'cohort-a2')!==null)fail_student_test_workflow('cohort-shared test accepted another cohort from the same course');

$sharedCourse=['cohort_id'=>10,'activity_id'=>100,'visibility'=>'course'];
if((student_enrollment_shared_test_navigation_context($enrollments,$sharedCourse,'cohort-a2')['cohort_uuid']??'')!=='cohort-a2')fail_student_test_workflow('course-shared test cannot return through another enrolled cohort of the same course');
if(student_enrollment_shared_test_navigation_context($enrollments,$sharedCourse,'cohort-b')!==null)fail_student_test_workflow('course-shared test accepted a context from another course');
if(student_enrollment_shared_test_navigation_context($enrollments,$sharedCourse)!==null)fail_student_test_workflow('ambiguous course-shared test invented a cohort without an explicit context');
if((student_enrollment_shared_test_navigation_context([$enrollments[0]],$sharedCourse)['cohort_uuid']??'')!=='cohort-a')fail_student_test_workflow('single eligible course enrollment did not resolve naturally');

$root=dirname(__DIR__);$test=(string)file_get_contents($root.'/aluno/teste.php');$shared=(string)file_get_contents($root.'/aluno/teste-compartilhado.php');$delete=(string)file_get_contents($root.'/aluno/excluir-teste.php');
if(!str_contains($test,'Salvar exposição e continuar →'))fail_student_test_workflow('exposure no longer advances by saving');
if(!str_contains($test,'id="student-development-form"')||!str_contains($test,'enctype="multipart/form-data"')||!str_contains($test,'name="action" value="save_development"')||!str_contains($test,'Salvar revelação e continuar →'))fail_student_test_workflow('development does not have one save-and-continue form');
if(str_contains($test,'>Salvar revelação</button>')||str_contains($test,'Revisar teste →'))fail_student_test_workflow('development still exposes a competing save/review path');
if(!str_contains($test,'aria-disabled="true" tabindex="-1"'))fail_student_test_workflow('step strip still behaves like free navigation instead of progress indication');
if(!str_contains($test,'student_enrollment_owned_test_navigation_context')||!str_contains($test,"\$testsUrl='/aluno/testes.php'"))fail_student_test_workflow('owned test detail does not validate and preserve enrollment context');
if(!str_contains($shared,'student_enrollment_shared_test_navigation_context')||!str_contains($shared,'student_shell_start((string)$test[\'title\'].\' · Compartilhado\',$activity?:null,$student)'))fail_student_test_workflow('shared test does not preserve context/design authority');
if(!str_contains($delete,'student_enrollment_owned_test_navigation_context')||!str_contains($delete,"header('Location: '.\$testsUrl"))fail_student_test_workflow('delete flow does not return to the validated tests workspace');

echo "student-test-workflow: ok\n";
