<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_home.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fnotificacoes.php',true,303);exit;}
$studentId=(int)$student['id'];
$notices=student_home_live_notices($db,$studentId);
student_shell_start('Início',null,$student);
?>
<?php require __DIR__.'/../app/student_home_notifications_view.php'; ?>
<?php student_shell_end();