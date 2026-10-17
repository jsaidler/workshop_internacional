<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fcaderno.php',true,303);exit;}
header('Location: /aluno/caderno.php',true,303);
exit;
