<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_auth.php';
student_security_headers();
if(($_SERVER['REQUEST_METHOD']??'GET')!=='POST'||!verify_csrf('student-logout',$_POST['_csrf']??null)){http_response_code(405);exit('Method not allowed');}
student_logout();header('Location: /aluno/login.php',true,303);exit;
