<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();
try{$activity=activity_for_request($db);}catch(Throwable $e){$activity=root_activity($db);}
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!verify_csrf('student-logout',$_POST['_csrf']??null)){http_response_code(405);exit('Método inválido.');}
student_logout();
header('Location: '.student_url('/aluno/login.php',$activity),true,303);exit;
