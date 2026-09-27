<?php
declare(strict_types=1);
function fail_student_area_transversal(string $message): never {fwrite(STDERR,"student-area-transversal: $message\n");exit(1);}
$root=dirname(__DIR__);
$bootstrap=(string)file_get_contents($root.'/app/bootstrap.php');
$mobile=(string)file_get_contents($root.'/app/student_test_mobile.php');
$test=(string)file_get_contents($root.'/aluno/teste.php');
$shared=(string)file_get_contents($root.'/aluno/teste-compartilhado.php');

if(!str_contains($bootstrap,"'student_test_mobile'"))fail_student_area_transversal('shared test presentation helpers are not loaded by bootstrap');
foreach(['function student_test_message_date','function student_review_value'] as $needle)if(!str_contains($mobile,$needle))fail_student_area_transversal('canonical test presentation helper missing: '.$needle);
if(str_contains($test,'function student_test_message_date')||str_contains($test,'function student_review_value'))fail_student_area_transversal('test detail redeclared presentation helpers locally');
foreach(['student_test_message_date(','student_review_value('] as $needle)if(!str_contains($shared,$needle))fail_student_area_transversal('shared test no longer exercises canonical presentation helper: '.$needle);

foreach(['id="student-exposure-form"','id="student-development-form"'] as $needle)if(!str_contains($test,$needle))fail_student_area_transversal('editable stage is not a single form: '.$needle);
if(substr_count($test,'enctype="multipart/form-data"')<2)fail_student_area_transversal('stage forms do not own their media uploads');
if(!str_contains($test,'name="delete_media_id"'))fail_student_area_transversal('media removal is not submitted through the current stage form');
if(!str_contains($test,"if(\$phase==='scene'){student_test_update_exposure")||!str_contains($test,'else{student_test_update_development'))fail_student_area_transversal('media side actions do not save the current stage first');
$uploadSave=strpos($test,"elseif(\$action==='upload')");$uploadMutation=strpos($test,'student_test_add_media_phase',$uploadSave?:0);
if($uploadSave===false||$uploadMutation===false)fail_student_area_transversal('media upload branch missing');
$exposureSave=strpos($test,'student_test_update_exposure',$uploadSave);$developmentSave=strpos($test,'student_test_update_development',$uploadSave);
if($exposureSave===false||$developmentSave===false||$exposureSave>$uploadMutation||$developmentSave>$uploadMutation)fail_student_area_transversal('media upload can mutate before preserving edited fields');
$deleteBranch=strpos($test,"elseif(\$action==='delete_media')");$deleteMutation=strpos($test,'student_test_delete_media',$deleteBranch?:0);
if($deleteBranch===false||$deleteMutation===false)fail_student_area_transversal('media delete branch missing');
$deleteExposureSave=strpos($test,'student_test_update_exposure',$deleteBranch);$deleteDevelopmentSave=strpos($test,'student_test_update_development',$deleteBranch);
if($deleteExposureSave===false||$deleteDevelopmentSave===false||$deleteExposureSave>$deleteMutation||$deleteDevelopmentSave>$deleteMutation)fail_student_area_transversal('media removal can mutate before preserving edited fields');

echo "student-area-transversal: ok\n";
