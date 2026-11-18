<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode('/aluno/testes.php'),true,303);exit;}
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'){http_response_code(405);header('Allow: POST');exit('Method Not Allowed');}
if(!verify_csrf('student-test-visibility',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
try{
    student_test_set_visibility($db,(int)($_POST['id']??0),(int)$student['id'],(string)($_POST['visibility']??'private'));
    $_SESSION['student_tests_notice']='Visibilidade do teste atualizada.';
}catch(Throwable $e){$_SESSION['student_tests_notice']='Não foi possível alterar a visibilidade: '.$e->getMessage();}
$cohortUuid=trim((string)($_POST['cohort']??''));$return='/aluno/testes.php';
if($cohortUuid!==''){
    $enrollments=student_account_enrollments($db,(int)$student['id']);
    $context=student_enrollment_dashboard_context($enrollments,$cohortUuid);
    if($context)$return.='?cohort='.rawurlencode((string)$context['cohort_uuid']);
}
header('Location: '.$return,true,303);exit;
