<?php
declare(strict_types=1);

function fail_student_account_experience(string $message): never {fwrite(STDERR,"student-account-experience: $message\n");exit(1);}
function must_student_account_experience(bool $condition,string $message): void {if(!$condition)fail_student_account_experience($message);}

$root=dirname(__DIR__);
$profile=(string)file_get_contents($root.'/aluno/perfil.php');
$password=(string)file_get_contents($root.'/aluno/senha.php');
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$logout=(string)file_get_contents($root.'/aluno/logout.php');

must_student_account_experience(str_contains($profile,'<h1 class="student-title student-title-record">Conta</h1>'),'Conta is not the account destination heading');
must_student_account_experience(str_contains($profile,'class="student-form-grid"'),'profile form does not use the responsive canonical grid');
must_student_account_experience(!str_contains($profile,'grid-template-columns:1fr 1fr'),'profile returned to an inline desktop-only grid');
must_student_account_experience(substr_count($profile,'student-span-2')>=3,'full-width profile rows are not expressed through the responsive grid');
must_student_account_experience(str_contains($profile,'href="/aluno/senha.php?next=%2Faluno%2Fperfil.php"'),'password management is not discoverable from Conta');
must_student_account_experience(str_contains($profile,'action="/aluno/logout.php"'),'logout is not discoverable from Conta');
must_student_account_experience(str_contains($profile,"csrf_token('student-logout')"),'Conta logout does not use the canonical CSRF scope');
must_student_account_experience(str_contains($password,'href="/aluno/perfil.php"')&&str_contains($password,'← Conta'),'password management has no route back to Conta');
must_student_account_experience(str_contains($password,'if(!$first)'),'activation flow does not remain separate from authenticated account navigation');
must_student_account_experience(str_contains($shell,'href="/aluno/perfil.php"')&&str_contains($shell,'>Conta</a>'),'student navigation no longer points Conta to the account destination');
must_student_account_experience(str_contains($logout,"verify_csrf('student-logout'")&&str_contains($logout,"REQUEST_METHOD")&&str_contains($logout,"POST"),'logout endpoint lost its POST/CSRF contract');

echo "student-account-experience: ok\n";
