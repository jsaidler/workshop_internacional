<?php
declare(strict_types=1);
function h(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'test-csrf';}
function check_auth(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"student-auth-view: $message\n");exit(1);}}
require __DIR__.'/../app/student_auth_view.php';
ob_start();student_auth_render_login('E-mail inválido <script>','Aviso','login','/aluno/');$login=ob_get_clean();
check_auth(str_contains($login,'name="mode" value="login"')&&str_contains($login,'name="mode" value="activate"'),'login and activation forms retained');
check_auth(substr_count($login,'name="_csrf"')===2&&str_contains($login,'name="cpf"'),'CSRF and activation recognition preserved');
check_auth(str_contains($login,'E-mail inválido &lt;script&gt;')&&!str_contains($login,'<script>'),'error output must be escaped');
ob_start();student_auth_render_password(true,'','/aluno/');$activation=ob_get_clean();
check_auth(str_contains($activation,'name="privacy_ack"')&&str_contains($activation,'name="primeiro" value="1"'),'first access requires privacy acknowledgement');
check_auth(str_contains($activation,'name="_csrf"')&&str_contains($activation,'name="confirm"')&&str_contains($activation,'minlength="12"'),'password constraints remain');
ob_start();student_auth_render_password(false,'','/aluno/perfil.php');$change=ob_get_clean();
check_auth(str_contains($change,'name="primeiro" value="0"')&&str_contains($change,'Salvar senha')&&!str_contains($change,'name="privacy_ack"'),'change-password does not require activation acknowledgement');
check_auth(str_contains($change,'href="/aluno/perfil.php"')&&str_contains($change,'name="next" value="/aluno/perfil.php"'),'return and redirect preserved');
foreach(['login.php','senha.php'] as $route){$s=file_get_contents(__DIR__.'/../aluno/'.$route);check_auth(str_contains($s,'student_auth_render_')&&str_contains($s,'verify_csrf('),'production route retains validation and shared renderer');}
echo "student-auth-view: ok\n";
