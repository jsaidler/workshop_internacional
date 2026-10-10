<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);$testId=(int)($_GET['test']??$_POST['test']??0);$next='/aluno/teste.php?id='.$testId.'#processamento';
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$record=student_test_for_student($db,$testId,$studentId);
if(!$record){http_response_code(404);exit('Registro não encontrado.');}
// Compatibilidade com links antigos. O Caderno não possui um modo separado
// para algo "já realizado": todo dado de processamento pertence ao registro.
header('Location: '.$next,true,303);exit;