<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/student_profile_view.php';
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope):string{return 'fixture-csrf-'.$scope;}
function check_profile_view(bool $condition,string $message):void{
 if(!$condition){fwrite(STDERR,'student-profile-view: '.$message."\n");exit(1);}
}
$profile=['name'=>'Teste <script>','email'=>'test@example.test','cpf'=>'000.000.000-00','phone'=>'(24) 99999-0000','instagram'=>'', 'address'=>'Rua Exemplo','city_state'=>'Petrópolis, RJ','postal_code'=>'25600-000'];
ob_start();student_profile_render($profile);$view=(string)ob_get_clean();
check_profile_view(str_contains($view,'data-student-profile-form'),'operational renderer lacks edit form');
check_profile_view(str_contains($view,'name="name"')&&str_contains($view,'name="email"')&&str_contains($view,'name="cpf"'),'mandatory identity fields lost');
check_profile_view(str_contains($view,'name="phone"')&&str_contains($view,'name="instagram"')&&str_contains($view,'name="address"')&&str_contains($view,'name="city_state"')&&str_contains($view,'name="postal_code"'),'optional fields lost');
check_profile_view(!str_contains($view,'Teste <script>')&&str_contains($view,'Teste &lt;script&gt;'),'profile must escape untrusted values');
check_profile_view(str_contains($view,'name="newsletter_opt_in"') && str_contains($view,'value="fixture-csrf-student-newsletter"'),'newsletter requires explicit preference and CSRF');
check_profile_view(str_contains($view,'data-profile-contact')&&str_contains($view,'data-profile-address'),'independent disclosures missing');
check_profile_view(substr_count($view,'<details')===2,'only secondary contact and address fields should be progressive');
check_profile_view(str_contains($view,'href="/aluno/senha.php?next=%2Faluno%2Fperfil.php"'),'account password path lost');
check_profile_view(str_contains($view,'action="/aluno/logout.php"')&&str_contains($view,'value="fixture-csrf-student-logout"'),'logout POST/CSRF lost');
ob_start();student_profile_render($profile,'Falha de validação');$failed=(string)ob_get_clean();
check_profile_view(substr_count($failed,'data-profile-')>=2&&substr_count($failed,' open>')===2,'failed form must expose all optional fields');
check_profile_view(str_contains($failed,'role="alert"')&&str_contains($failed,'Falha de validação'),'error must be visible and contextual');
$route=(string)file_get_contents(dirname(__DIR__).'/aluno/perfil.php');
check_profile_view(str_contains($route,'student_profile_render($profile,$error,$notice,communication_opted_in('),'real account route does not use the same view');
check_profile_view(str_contains($route,"verify_csrf('student-profile'")&&str_contains($route,'student_account_update_profile'),'authorization and persistence lost');
check_profile_view(str_contains($route,'array_key_exists($field,$_POST)'),'invalid POST no longer preserves entered data');
ob_start();student_profile_render($profile,'','',true);$subscribed=(string)ob_get_clean();
check_profile_view(str_contains($subscribed,'value="yes" checked'),'explicit opt-in must be shown');
check_profile_view(str_contains($route,"verify_csrf('student-newsletter'"),'preference mutation must check CSRF');
echo "student-profile-view: ok\n";
