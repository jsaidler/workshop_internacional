<?php
declare(strict_types=1);

function fail(string $message): never { fwrite(STDERR,"student-area: $message\n"); exit(1); }
function utc_now(): string { return gmdate('c'); }
function activity_slug(string $value): string { $value=strtolower(trim((string)preg_replace('~[^a-z0-9]+~i','-',$value)));return trim($value,'-')?:'material'; }
function app_config(): array { return ['ip_hash_secret'=>str_repeat('x',32)]; }
function h(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function csrf_token(string $scope): string { return 'csrf-'.$scope; }

require __DIR__.'/../app/student_auth.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON; CREATE TABLE activities(id INTEGER PRIMARY KEY AUTOINCREMENT,slug TEXT NOT NULL,status TEXT NOT NULL DEFAULT \'active\',is_root INTEGER NOT NULL DEFAULT 0);');
$db->exec("INSERT INTO activities(slug,status,is_root) VALUES('workshop','active',1)");
$migration=require __DIR__.'/../migrations/031_student_area.php';$migration($db);

$result=student_admin_create_or_enroll($db,1,'Aluno Teste','ALUNO@example.com',null);
if(!is_string($result['generated_password']??null)||strlen($result['generated_password'])<12)fail('temporary password not generated');
$user=$db->query('SELECT * FROM student_users')->fetch();if(!$user)fail('student not created');
if($user['email']!=='aluno@example.com')fail('email not normalized');
if(!password_verify($result['generated_password'],$user['password_hash']))fail('password hash invalid');
if(!student_has_activity($db,(int)$user['id'],1))fail('enrollment missing');

$material=student_material_upsert($db,1,'pt-BR','Material protegido','material-protegido','<!doctype html><html><head></head><body><h1>Conteúdo</h1></body></html>','active');
if(($material['slug']??'')!=='material-protegido')fail('material slug invalid');
$found=student_material_by_slug($db,1,'pt-BR','material-protegido',true);if(!$found)fail('material not found');
$rendered=student_material_render((string)$found['html_content'],['name'=>'Aluno Teste','email'=>'aluno@example.com'],['id'=>1,'slug'=>'workshop','is_root'=>1]);
if(!str_contains($rendered,'student-access-bar')||!str_contains($rendered,'Acesso individual'))fail('protection UI not injected');
if(!str_contains($rendered,'Conteúdo'))fail('material content lost');

student_admin_set_enrollment($db,(int)$user['id'],1,'disabled');
if(student_has_activity($db,(int)$user['id'],1))fail('disabled enrollment still active');

echo "student-area: ok\n";
