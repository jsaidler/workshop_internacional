<?php
declare(strict_types=1);
function fail_student_login_experience(string $message): never {fwrite(STDERR,"student-login-experience: $message\n");exit(1);}
$root=dirname(__DIR__);$login=(string)file_get_contents($root.'/aluno/login.php');
if(str_contains($login,'student-grid')||str_contains($login,'grid-template-columns'))fail_student_login_experience('login and activation returned to a competing two-column layout');
$loginPos=strpos($login,'id="student-login-title"');$activationPos=strpos($login,'id="student-activation-title"');
if($loginPos===false||$activationPos===false||$loginPos>=$activationPos)fail_student_login_experience('activated-account login is not the primary flow');
foreach(['Conta ativada','Primeiro acesso','E-mail da inscrição','CPF','Ativar conta'] as $needle)if(!str_contains($login,$needle))fail_student_login_experience('missing first-access distinction: '.$needle);
if(!str_contains($login,'student-button student-button-secondary'))fail_student_login_experience('first-access action is not visually secondary');
if(!str_contains($login,"$mode==='activate'"))fail_student_login_experience('activation errors are not scoped to the activation flow');
if(!str_contains($login,"$mode!=='activate'"))fail_student_login_experience('login errors are not scoped to the login flow');
echo "student-login-experience: ok\n";
