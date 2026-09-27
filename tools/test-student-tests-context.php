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
if(array_column($ownedContext,'id')!==[1])fail_student_tests_context('owned tests leaked across enrollment cohorts');

$shared=[
    ['id'=>11,'cohort_id'=>10,'activity_id'=>100,'workshop_page_id'=>1001,'visibility'=>'cohort'],
    ['id'=>12,'cohort_id'=>11,'activity_id'=>100,'workshop_page_id'=>1001,'visibility'=>'course'],
    ['id'=>13,'cohort_id'=>11,'activity_id'=>100,'workshop_page_id'=>1001,'visibility'=>'cohort'],
    ['id'=>14,'cohort_id'=>20,'activity_id'=>100,'workshop_page_id'=>1002,'visibility'=>'course'],
    ['id'=>15,'cohort_id'=>10,'activity_id'=>100,'workshop_page_id'=>1001,'visibility'=>'private'],
];
$sharedContext=student_enrollment_shared_tests_context($shared,$enrollment);
if(array_column($sharedContext,'id')!==[11,12])fail_student_tests_context('shared tests do not respect cohort/workshop visibility inside the selected enrollment');

$root=dirname(__DIR__);
$index=(string)file_get_contents($root.'/aluno/testes.php');
$dashboard=(string)file_get_contents($root.'/aluno/index.php');
$visibility=(string)file_get_contents($root.'/aluno/visibilidade-teste.php');
foreach(['student_enrollment_dashboard_context','Testes por workshop','type="hidden" name="cohort_id"','student_enrollment_owned_tests_context','student_enrollment_shared_tests_context'] as $needle)if(!str_contains($index,$needle))fail_student_tests_context('tests index missing contextual contract: '.$needle);
if(str_contains($index,'<select name="cohort_id"'))fail_student_tests_context('selected tests workspace still exposes a cross-workshop cohort selector');
if(!str_contains($dashboard,'/aluno/testes.php?cohort='))fail_student_tests_context('workshop workspace does not preserve context when opening tests');
if(!str_contains($visibility,'student_enrollment_dashboard_context($enrollments,$cohortUuid)'))fail_student_tests_context('visibility update does not validate return context');

echo "student-tests-context: ok\n";
