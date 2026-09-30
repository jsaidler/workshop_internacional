<?php
declare(strict_types=1);
function fail_student_area_transversal(string $message): never {fwrite(STDERR,"student-area-transversal: $message\n");exit(1);}
$root=dirname(__DIR__);
$bootstrap=(string)file_get_contents($root.'/app/bootstrap.php');
$mobile=(string)file_get_contents($root.'/app/student_test_mobile.php');
$test=(string)file_get_contents($root.'/aluno/teste.php');
$shared=(string)file_get_contents($root.'/aluno/teste-compartilhado.php');

if(!str_contains($bootstrap,"'student_test_mobile'"))fail_student_area_transversal('shared record presentation helpers are not loaded by bootstrap');
foreach(['function student_test_message_date','function student_review_value'] as $needle)if(!str_contains($mobile,$needle))fail_student_area_transversal('canonical record presentation helper missing: '.$needle);
if(str_contains($test,'function student_test_message_date')||str_contains($test,'function student_review_value'))fail_student_area_transversal('record detail redeclared presentation helpers locally');
foreach(['student_test_message_date(','student_review_value('] as $needle)if(!str_contains($shared,$needle))fail_student_area_transversal('shared record no longer exercises canonical presentation helper: '.$needle);

if(!str_contains($test,'name="action" value="save_exposure" data-process-action'))fail_student_area_transversal('exposure form lost its canonical save/upload action authority');
if(substr_count($test,'enctype="multipart/form-data"')<2)fail_student_area_transversal('scene and result forms no longer own their media uploads');
if(!str_contains($test,'data-process-step-form')||!str_contains($test,'name="action" value="add_step"'))fail_student_area_transversal('guided processing stage is not a single canonical form');
if(!str_contains($test,"elseif(\$action==='upload')"))fail_student_area_transversal('media upload branch missing');
$uploadSave=strpos($test,"elseif(\$action==='upload')");$uploadMutation=strpos($test,'student_test_add_media_phase',$uploadSave?:0);
if($uploadSave===false||$uploadMutation===false)fail_student_area_transversal('media upload mutation missing');
$exposureSave=strpos($test,'student_test_update_exposure',$uploadSave);
$notesSave=strpos($test,"UPDATE student_tests SET notes=?,updated_at=?",$uploadSave);
if($exposureSave===false||$notesSave===false||$exposureSave>$uploadMutation||$notesSave>$uploadMutation)fail_student_area_transversal('media upload can mutate before preserving the edited scene/result data');
$deleteBranch=strpos($test,"elseif(\$action==='delete_media')");$deleteMutation=strpos($test,'student_test_delete_media',$deleteBranch?:0);
if($deleteBranch===false||$deleteMutation===false)fail_student_area_transversal('media delete branch missing');
if(!str_contains($test,'name="media_id"')||!str_contains($test,"[data-process-action]').value='delete_media'"))fail_student_area_transversal('media removal is not submitted through the active record form');

if(!str_contains($test,'href="/aluno/caderno.php"')||!str_contains($shared,'href="/aluno/caderno.php"'))fail_student_area_transversal('owned/shared records no longer converge on the global notebook');

echo "student-area-transversal: ok\n";
