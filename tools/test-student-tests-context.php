<?php
declare(strict_types=1);
function fail_student_tests_context(string $message): never {fwrite(STDERR,"student-tests-context: $message\n");exit(1);}
require_once dirname(__DIR__).'/app/student_enrollments.php';

$enrollment=['cohort_id'=>10,'activity_id'=>100,'workshop_page_id'=>1001,'cohort_uuid'=>'cohort-a'];
$owned=[
    ['id'=>1,'cohort_id'=>10,'activity_id'=>100,'workshop_page_id'=>1001],
    ['id'=>2,'cohort_id'=>20,'activity_id'=>100,'workshop_page_id'=>1002],
    ['id'=>3,'cohort_id'=>11,'activity_id'=>100,'workshop_page_id'=>1001],
];
$ownedContext=student_enrollment_owned_tests_context($owned,$enrollment);
if(array_column($ownedContext,'id')!==[1])fail_student_tests_context('owned contextual records leaked across enrollment cohorts');

$shared=[
    ['id'=>11,'cohort_id'=>10,'activity_id'=>100,'workshop_page_id'=>1001,'visibility'=>'cohort'],
    ['id'=>12,'cohort_id'=>11,'activity_id'=>100,'workshop_page_id'=>1001,'visibility'=>'course'],
    ['id'=>13,'cohort_id'=>11,'activity_id'=>100,'workshop_page_id'=>1001,'visibility'=>'cohort'],
    ['id'=>14,'cohort_id'=>20,'activity_id'=>100,'workshop_page_id'=>1002,'visibility'=>'course'],
    ['id'=>15,'cohort_id'=>10,'activity_id'=>100,'workshop_page_id'=>1001,'visibility'=>'private'],
];
$sharedContext=student_enrollment_shared_tests_context($shared,$enrollment);
if(array_column($sharedContext,'id')!==[11,12])fail_student_tests_context('shared records do not respect cohort/course visibility inside an enrollment context');

$root=dirname(__DIR__);
$notebook=(string)file_get_contents($root.'/aluno/caderno.php');
$legacy=(string)file_get_contents($root.'/aluno/testes.php');
$dashboard=(string)file_get_contents($root.'/aluno/cursos.php');
$visibility=(string)file_get_contents($root.'/aluno/visibilidade-teste.php');
$workbench=(string)file_get_contents($root.'/app/student_workbench.php');

foreach(['context_scope','context_cohort_id','student_process_records_for_student','student_tests_shared_with_student'] as $needle){
    if(!str_contains($notebook,$needle))fail_student_tests_context('global notebook missing context contract: '.$needle);
}
if(!str_contains($notebook,'value="personal"')||!str_contains($notebook,'value="course"'))fail_student_tests_context('notebook no longer offers personal and course/turma contexts');
if(!str_contains($workbench,"context_scope")||!str_contains($workbench,'context_cohort_id'))fail_student_tests_context('process domain does not persist optional record context');
if(!str_contains($legacy,"header('Location: /aluno/caderno.php'"))fail_student_tests_context('legacy tests route does not converge to global notebook');
if(str_contains($dashboard,'/aluno/testes.php?cohort='))fail_student_tests_context('course overview still opens obsolete course-local tests');
if(!str_contains($dashboard,'/aluno/duvidas.php?cohort='))fail_student_tests_context('course overview lost its course/turma contextual action');
if(!str_contains($visibility,'student_enrollment_dashboard_context($enrollments,$cohortUuid)'))fail_student_tests_context('legacy visibility update no longer validates contextual return scope');

echo "student-tests-context: ok\n";
