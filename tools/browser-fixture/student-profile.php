<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/cms_settings.php';
/* Real PHP form renderers; synthetic data and POST simulation, no auth/DB. */
require_once __DIR__.'/../../app/student_workbench.php';
require_once __DIR__.'/../../app/student_profile_view.php';

require_once __DIR__.'/../../app/student_shell.php';
function h(string $s):string{return htmlspecialchars($s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function database(): PDO {
 static $db=null;
 if($db instanceof PDO)return $db;
 $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $db->exec('CREATE TABLE student_tools(tool_key TEXT,enabled INTEGER,access_mode TEXT,sort_order INTEGER,label TEXT);
 CREATE TABLE student_tool_courses(tool_key TEXT,course_id INTEGER);
 CREATE TABLE course_cohorts(id INTEGER,course_id INTEGER,status TEXT);
 CREATE TABLE course_enrollments(id INTEGER,cohort_id INTEGER,student_id INTEGER,status TEXT)');
 return $db;
}
function student_account_current(PDO $db): ?array {return ['id'=>1,'name'=>'Aluno'];}
function csrf_token(string $scope): string {return 'fixture-only-token';}


$mode=(string)($_GET['mode']??'view');
if(!in_array($mode,['view','error','saved'],true)){http_response_code(400);exit('Invalid fixture state');}
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&$mode==='view'){
    header('Location: /tools/browser-fixture/student-profile.php?mode=saved',true,303);exit;
}
$profile=['name'=>'Aluno Exemplo','email'=>'aluno@example.test','cpf'=>'000.000.000-00','phone'=>'(24) 90000-0000','instagram'=>'','address'=>'Rua Exemplo, 10','city_state'=>'Petrópolis - RJ','postal_code'=>'25600-000'];
if($mode==='error'){$profile['name']='Nome em edição';$profile['phone']='(24) 99999-1111';}
$_SERVER['REQUEST_URI']='/aluno/perfil.php';
student_shell_start('Conta · Área do aluno',null,['id'=>1,'name'=>'Aluno Exemplo']);
student_profile_render($profile,$mode==='error'?'Confira os dados da conta.':'',$mode==='saved'?'Dados atualizados.':'');
student_shell_end();
