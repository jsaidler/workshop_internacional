<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';require_once __DIR__.'/../app/student_calibration_editor.php';security_headers();student_private_headers();$db=database();$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fcalibracao.php',true,303);exit;}$studentId=(int)$student['id'];try{student_tool_require($db,$studentId,'calibration');}catch(Throwable){http_response_code(403);exit('Ferramenta indisponível.');}$error='';$editId=(int)($_GET['editar']??$_POST['id']??0);$editing=$editId>0?student_calibration_for_student($db,$studentId,$editId):null;if($editId>0&&!$editing){http_response_code(404);exit('Referência não encontrada.');}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){if(!verify_csrf('student-calibration',$_POST['_csrf']??null))$error='Solicitação inválida.';else try{student_calibration_save($db,$studentId,$_POST);header('Location: /aluno/calibracao.php?saved=1',true,303);exit;}catch(Throwable $e){$error=$e->getMessage();}}
$rows=student_calibrations($db,$studentId);$openEditor=$editing||isset($_GET['novo']);require_once __DIR__.'/../app/student_lab_catalog_views.php';
student_shell_start('Referências de calibração',null,$student);
student_lab_calibrations_view($rows,$editing,$error,(bool)$openEditor,isset($_GET['saved']),$error!==''?$_POST:[]);
student_shell_end();
