<?php
declare(strict_types=1);

function fail_multi_course(string $message): never {fwrite(STDERR,"multi-course-enrollment: $message\n");exit(1);}
function utc_now(): string {return gmdate('c');}
function activity_slug(string $value): string {$value=strtolower(trim((string)preg_replace('~[^a-z0-9]+~i','-',$value)));return trim($value,'-')?:'item';}
function app_config(): array {return ['app_secret'=>str_repeat('a',40),'ip_hash_secret'=>str_repeat('b',40)];}
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'csrf-'.$scope;}
const PUBLIC_LOCALE_PT_BR='pt-BR';const PUBLIC_LOCALE_EN='en';
function workshop_course_submission_root(PDO $db,array $submission): ?array {return null;}

$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['REQUEST_METHOD']='GET';$_SESSION=[];
require __DIR__.'/../app/form_purpose.php';
require __DIR__.'/../app/student_auth.php';
require __DIR__.'/../app/student_accounts.php';
require __DIR__.'/../app/student_enrollments.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec("CREATE TABLE activities(id INTEGER PRIMARY KEY AUTOINCREMENT,admin_name TEXT NOT NULL,public_title TEXT NOT NULL,slug TEXT NOT NULL UNIQUE,status TEXT NOT NULL DEFAULT 'active',is_root INTEGER NOT NULL DEFAULT 0);");
$db->exec("INSERT INTO activities(admin_name,public_title,slug,status,is_root) VALUES('Curso A','Curso A','curso-a','active',1),('Curso B','Curso B','curso-b','active',0);");
$db->exec("CREATE TABLE cms_forms(id INTEGER PRIMARY KEY AUTOINCREMENT,form_uuid TEXT NOT NULL UNIQUE,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,form_key TEXT NOT NULL,title TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'active',purpose TEXT NOT NULL DEFAULT 'common',draft_schema_json TEXT NOT NULL,published_schema_json TEXT NULL,draft_revision INTEGER NOT NULL DEFAULT 1,published_revision INTEGER NULL,draft_updated_at TEXT NOT NULL,published_at TEXT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$db->exec("CREATE TABLE cms_form_submissions(id INTEGER PRIMARY KEY AUTOINCREMENT,submission_uuid TEXT NOT NULL UNIQUE,form_id INTEGER NOT NULL,page_id INTEGER NULL,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,payload_json TEXT NOT NULL,form_snapshot_json TEXT NOT NULL,source_url TEXT NOT NULL DEFAULT '',status TEXT NOT NULL DEFAULT 'new',notes TEXT NOT NULL DEFAULT '',consent INTEGER NOT NULL DEFAULT 0,ip_hash TEXT NOT NULL,user_agent_hash TEXT NOT NULL,payment_status TEXT NOT NULL DEFAULT 'pending',payment_confirmed_at TEXT NULL,payment_note TEXT NOT NULL DEFAULT '',created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$m46=require __DIR__.'/../migrations/046_student_area.php';$m46($db);
$m47=require __DIR__.'/../migrations/047_student_accounts_cohorts_privacy.php';$m47($db);

$now=utc_now();$schema=json_encode(['fields'=>[]],JSON_THROW_ON_ERROR);
$insertForm=$db->prepare('INSERT INTO cms_forms(form_uuid,activity_id,locale,form_key,title,purpose,draft_schema_json,published_schema_json,draft_updated_at,published_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
$insertForm->execute(['form-a',1,'pt-BR','registration-a','Inscrição A','enrollment',$schema,$schema,$now,$now,$now,$now]);$formA=(int)$db->lastInsertId();
$insertForm->execute(['form-b',2,'pt-BR','registration-b','Inscrição B','enrollment',$schema,$schema,$now,$now,$now,$now]);$formB=(int)$db->lastInsertId();
$payload=['name'=>'Aluno Existente','cpf'=>'123.456.789-00','phone'=>'24999999999','email'=>'aluno@example.com','instagram'=>'@aluno','address'=>'Rua Um, 10','city_state'=>'Petrópolis/RJ','postal_code'=>'25600-000'];
$insertSubmission=$db->prepare("INSERT INTO cms_form_submissions(submission_uuid,form_id,activity_id,locale,payload_json,form_snapshot_json,status,consent,ip_hash,user_agent_hash,payment_status,payment_confirmed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,'converted',1,'ip','ua','paid',?,?,?)");
$insertSubmission->execute(['sub-a',$formA,1,'pt-BR',json_encode($payload,JSON_THROW_ON_ERROR),$schema,$now,$now,$now]);
$resultA=student_enrollment_reconcile_confirmed($db,1);if(($resultA['processed']??0)!==1||($resultA['errors']??[]))fail_multi_course('first enrollment was not reconciled');
$user=$db->query('SELECT * FROM student_users LIMIT 1')->fetch();if(!$user)fail_multi_course('first enrollment did not create the global account');
$studentId=(int)$user['id'];$passwordHash=password_hash('senha-existente-123',PASSWORD_DEFAULT);$db->prepare('UPDATE student_users SET password_hash=?,must_change_password=0,activated_at=?,privacy_ack_at=?,privacy_notice_version=? WHERE id=?')->execute([$passwordHash,$now,$now,STUDENT_PRIVACY_NOTICE_VERSION,$studentId]);
$insertSubmission->execute(['sub-b',$formB,2,'pt-BR',json_encode($payload,JSON_THROW_ON_ERROR),$schema,$now,$now,$now]);$submissionB=(int)$db->lastInsertId();
$formBRow=$db->query('SELECT * FROM cms_forms WHERE id='.$formB)->fetch();$studentRow=$db->query('SELECT * FROM student_users WHERE id='.$studentId)->fetch();
$bound=student_enrollment_bind_authenticated_submission($db,$submissionB,$formBRow,$payload,$studentRow);if($bound!==$studentId)fail_multi_course('authenticated submission was not bound to session student identity');
$storedStudent=(int)$db->query('SELECT student_id FROM cms_form_submissions WHERE id='.$submissionB)->fetchColumn();if($storedStudent!==$studentId)fail_multi_course('submission did not persist the authenticated student_id');
$resultB=student_enrollment_reconcile_confirmed($db,2);if(($resultB['processed']??0)!==1||($resultB['errors']??[]))fail_multi_course('second-course enrollment was not reconciled');
if((int)$db->query('SELECT COUNT(*) FROM student_users')->fetchColumn()!==1)fail_multi_course('second course created a duplicate student account');
if((int)$db->query("SELECT COUNT(*) FROM course_enrollments WHERE student_id=$studentId AND status='active'")->fetchColumn()!==2)fail_multi_course('existing account does not have two independent active enrollments');
if(count(student_account_enrollments($db,$studentId))!==2)fail_multi_course('student enrollment query does not expose both courses');
$after=$db->query('SELECT * FROM student_users WHERE id='.$studentId)->fetch();if((string)$after['password_hash']!==$passwordHash||empty($after['activated_at']))fail_multi_course('new course reset password or activation state');
student_enrollment_reconcile_confirmed($db,2);if((int)$db->query("SELECT COUNT(*) FROM course_enrollments WHERE student_id=$studentId AND status='active'")->fetchColumn()!==2)fail_multi_course('reconciliation duplicated an active enrollment');

$formSubmit=(string)file_get_contents(__DIR__.'/../form-submit.php');
$bootstrap=(string)file_get_contents(__DIR__.'/../app/bootstrap.php');
$courses=(string)file_get_contents(__DIR__.'/../aluno/cursos.php');
if(!str_contains($formSubmit,'student_enrollment_bind_authenticated_submission'))fail_multi_course('public form submit path does not bind authenticated identity');
if(!str_contains($bootstrap,'student_enrollment_install_reconciliation_hook'))fail_multi_course('bootstrap still uses the legacy reconciliation hook');
if(!str_contains($courses,'student_enrollment_pages_for_enrollment'))fail_multi_course('courses surface no longer consumes canonical protected-page discovery');

echo "multi-course-enrollment: ok\n";
