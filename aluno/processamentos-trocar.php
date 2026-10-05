<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();

// Compatibilidade: a troca de roteiro não possui mais conceito de
// "próximas etapas" nem origem ao vivo/retrospectiva. O Caderno associa
// uma referência ao registro e mantém todas as etapas disponíveis.
$testId=(int)($_GET['test']??$_POST['test']??0);
$target='/aluno/registro-roteiro.php'.($testId>0?'?test='.$testId:'');
header('Location: '.$target,true,303);
exit;
