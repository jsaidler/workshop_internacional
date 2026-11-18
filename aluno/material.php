<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
student_private_headers();
header('Location: /aluno/',true,303);exit;
