<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);
$id=(int)($_GET['id']??0);$cohortValue=$_GET['cohort']??null;$requestedCohortUuid=is_string($cohortValue)?trim((string)$cohortValue):'';$nextParams=['id'=>$id];if($requestedCohortUuid!=='')$nextParams['cohort']=$requestedCohortUuid;$next='/aluno/teste-compartilhado.php?'.http_build_query($nextParams);
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$record=student_test_accessible_to_student($db,$id,(int)$student['id']);
if(!$record){http_response_code(404);student_shell_start('Registro não encontrado',null,$student);?><div class="student-empty">Registro não encontrado.</div><?php student_shell_end();exit;}
if((int)$record['student_id']===(int)$student['id']){header('Location: /aluno/teste.php?id='.$id,true,303);exit;}
$activity=activity_by_id($db,(int)$record['activity_id']);$steps=student_process_steps($db,$id);$media=student_test_media($db,$id);$sceneMedia=student_test_media_by_phase($media,'scene');$resultMedia=student_test_media_by_phase($media,'result');$messages=student_test_messages($db,$id);
require_once __DIR__.'/../app/student_shared_record_view.php';
student_shell_start((string)$record['title'].' · Compartilhado',$activity?:null,$student);
student_shared_record_view($record,$activity,$steps,$sceneMedia,$resultMedia,$messages,$id);
student_shell_end();
