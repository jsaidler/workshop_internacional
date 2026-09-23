<?php
declare(strict_types=1);

$root=dirname(__DIR__);
function ok(bool $condition,string $message): void { if(!$condition){fwrite(STDERR,"student-area: $message\n");exit(1);} }

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON; CREATE TABLE activities (id INTEGER PRIMARY KEY AUTOINCREMENT,admin_name TEXT NOT NULL,public_title TEXT NOT NULL,slug TEXT NOT NULL UNIQUE,status TEXT NOT NULL DEFAULT "active",is_root INTEGER NOT NULL DEFAULT 0,created_at TEXT NOT NULL,updated_at TEXT NOT NULL);');
$db->exec("INSERT INTO activities(admin_name,public_title,slug,status,is_root,created_at,updated_at) VALUES('Workshop','Workshop','workshop','active',1,'2026-01-01','2026-01-01')");
$migration=require $root.'/migrations/046_student_area.php';$migration($db);
foreach(['student_users','student_enrollments','student_materials','student_login_attempts'] as $table){$q=$db->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=?");$q->execute([$table]);ok((bool)$q->fetchColumn(),"missing table $table");}
require_once $root.'/app/student_auth.php';
ok(student_username_normalize('  Aluno.Teste ')==='aluno.teste','username normalization failed');
ok(student_password_is_valid('123456789012'),'12-char password should be valid');
ok(!student_password_is_valid('12345678901'),'11-char password should be rejected');
ok(student_safe_return_url('/aluno/material.php?m=abc')==='/aluno/material.php?m=abc','safe aluno return rejected');
ok(student_safe_return_url('//evil.example')==='/aluno/','protocol-relative redirect accepted');
$good='<!doctype html><html><head><style>body{color:#000}</style></head><body><h1>Material</h1><img src="data:image/png;base64,AA=="></body></html>';
ok(student_material_validate_html($good)===$good,'valid self-contained html changed unexpectedly');
$blocked=false;try{student_material_validate_html('<html><body><script>alert(1)</script></body></html>');}catch(RuntimeException $e){$blocked=$e->getMessage()==='unsafe_html';}ok($blocked,'script was not rejected');
$files=['aluno/login.php','aluno/index.php','aluno/senha.php','aluno/logout.php','aluno/material.php','admin/students.php','admin/student-materials.php','assets/student-area.css'];foreach($files as $file)ok(is_file($root.'/'.$file),"missing $file");
$material=file_get_contents($root.'/aluno/material.php');ok(is_string($material)&&str_contains($material,'student_material_for_view'),'protected material route lacks authorization lookup');
$auth=file_get_contents($root.'/app/student_auth.php');ok(is_string($auth)&&str_contains($auth,'password_hash(')&&str_contains($auth,'password_verify('),'password hashing contract missing');
echo "student-area: ok\n";
