<?php
declare(strict_types=1);

function fail(string $message): never {fwrite(STDERR,"student-workspace: $message\n");exit(1);}
function utc_now(): string {return gmdate('c');}
function activity_slug(string $value): string {$value=strtolower(trim((string)preg_replace('~[^a-z0-9]+~i','-',$value)));return trim($value,'-')?:'item';}
function app_config(): array {return ['app_secret'=>str_repeat('a',40),'ip_hash_secret'=>str_repeat('b',40)];}
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'csrf-'.$scope;}
function cms_page_document(array $document): array {return $document;}
function cms_page_by_id(PDO $db,int $id): ?array {return null;}
function cms_page_doc(array $page,bool $published=true): array {return [];}
function cms_form_by_key(PDO $db,int $activityId,string $locale,string $key): ?array {return null;}
const PUBLIC_LOCALE_PT_BR='pt-BR';const PUBLIC_LOCALE_EN='en';

$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['REQUEST_METHOD']='GET';$_SESSION=[];
require __DIR__.'/../app/student_auth.php';
require __DIR__.'/../app/student_accounts.php';
require __DIR__.'/../app/student_workspace.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$db->exec('PRAGMA foreign_keys=ON');
$db->exec("CREATE TABLE activities(id INTEGER PRIMARY KEY AUTOINCREMENT,admin_name TEXT NOT NULL,public_title TEXT NOT NULL,slug TEXT NOT NULL UNIQUE,status TEXT NOT NULL DEFAULT 'active',is_root INTEGER NOT NULL DEFAULT 0);INSERT INTO activities(admin_name,public_title,slug,status,is_root) VALUES('Curso','Curso','curso','active',1);");
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY AUTOINCREMENT,page_uuid TEXT NOT NULL UNIQUE,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,slug TEXT NOT NULL DEFAULT '',title TEXT NOT NULL,nav_title TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'active',is_home INTEGER NOT NULL DEFAULT 0,show_in_nav INTEGER NOT NULL DEFAULT 1,sort_order INTEGER NOT NULL DEFAULT 0,draft_document_json TEXT NOT NULL,published_document_json TEXT NULL,draft_revision INTEGER NOT NULL DEFAULT 1,published_revision INTEGER NULL,draft_updated_at TEXT NOT NULL,published_at TEXT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$db->exec("CREATE TABLE cms_forms(id INTEGER PRIMARY KEY AUTOINCREMENT,form_uuid TEXT NOT NULL UNIQUE,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,form_key TEXT NOT NULL,title TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'active',draft_schema_json TEXT NOT NULL,published_schema_json TEXT NULL,draft_revision INTEGER NOT NULL DEFAULT 1,published_revision INTEGER NULL,draft_updated_at TEXT NOT NULL,published_at TEXT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$db->exec("CREATE TABLE cms_form_submissions(id INTEGER PRIMARY KEY AUTOINCREMENT,submission_uuid TEXT NOT NULL UNIQUE,form_id INTEGER NOT NULL,page_id INTEGER NULL,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,payload_json TEXT NOT NULL,form_snapshot_json TEXT NOT NULL,source_url TEXT NOT NULL DEFAULT '',status TEXT NOT NULL DEFAULT 'new',notes TEXT NOT NULL DEFAULT '',consent INTEGER NOT NULL DEFAULT 0,ip_hash TEXT NOT NULL,user_agent_hash TEXT NOT NULL,payment_status TEXT NOT NULL DEFAULT 'pending',payment_confirmed_at TEXT NULL,payment_note TEXT NOT NULL DEFAULT '',created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$m46=require __DIR__.'/../migrations/046_student_area.php';$m46($db);$m47=require __DIR__.'/../migrations/047_student_accounts_cohorts_privacy.php';$m47($db);$m62=require __DIR__.'/../migrations/062_student_operations_and_test_log.php';$m62($db);

$cohort=course_default_cohort($db,1,true);if(!$cohort)fail('default cohort missing');$cohortId=(int)$cohort['id'];
$edited=course_update_cohort_details($db,1,$cohortId,['title'=>'Turma agosto 2026','slug'=>'agosto-2026','status'=>'closed','starts_at'=>'2026-08-01','ends_at'=>'2026-08-31','notes'=>'Turma histórica','make_default'=>'1']);
if(($edited['title']??'')!=='Turma agosto 2026'||($edited['slug']??'')!=='agosto-2026'||($edited['notes']??'')!=='Turma histórica'||(int)($edited['is_registration_default']??0)!==1)fail('cohort metadata was not updated');

$tmp=tempnam(sys_get_temp_dir(),'students-');if($tmp===false)fail('temp file failed');file_put_contents($tmp,"Nome;E-mail;CPF;Telefone;Instagram\nAluno Histórico;historico@example.com;12345678900;24999999999;@historico\n");
$file=['error'=>UPLOAD_ERR_OK,'size'=>filesize($tmp),'tmp_name'=>$tmp,'name'=>'turma-anterior.csv'];
$import=student_import_historical_students($db,1,$cohortId,$file);if(($import['imported']??0)!==1||($import['errors']??0)!==0)fail('historical student was not imported');
$user=$db->query("SELECT * FROM student_users WHERE email='historico@example.com'")->fetch();if(!$user)fail('import did not create student account');$studentId=(int)$user['id'];if(password_verify('12345678900',(string)$user['password_hash']))fail('import stored CPF as password');
$profile=student_account_profile($db,$studentId);if(($profile['cpf']??'')!=='12345678900'||($profile['phone']??'')!=='24999999999')fail('imported profile data missing');
$enrollment=student_account_enrollment_for_activity($db,$studentId,1);if(!$enrollment||(int)$enrollment['cohort_id']!==$cohortId)fail('historical import did not enroll student in target cohort');
$batchRows=student_import_batch_rows($db,(int)$import['batch_id'],1);if(!$batchRows||str_contains(json_encode($batchRows,JSON_THROW_ON_ERROR),'12345678900'))fail('raw CPF leaked into import audit');

$importAgain=student_import_historical_students($db,1,$cohortId,$file);if(($importAgain['existing']??0)!==1||($importAgain['imported']??0)!==0)fail('second import duplicated an existing account');
if((int)$db->query("SELECT COUNT(*) FROM student_users WHERE email='historico@example.com'")->fetchColumn()!==1)fail('duplicate account created');

$activation=student_account_begin_activation($db,'historico@example.com','12345678900');if($activation!=='ok')fail('historically imported student cannot use first-access activation');

$test=student_test_create($db,$studentId,$cohortId,['title'=>'Teste janela','test_date'=>'2026-09-24','film'=>'Fuji Super HR-U','lot'=>'L1','iso_reference'=>'200','aperture'=>'f/16','calculated_time'=>'4 s','reciprocity_time'=>'5 s','light_condition'=>'Janela lateral','tonal_range'=>'5 EV','developer'=>'Parodinal','dilution'=>'10 + 550 ml','temperature'=>'26 °C','development_time'=>'7 min','agitation'=>'leve','notes'=>'Primeiro teste']);
if(($test['status']??'')!=='draft'||($test['film']??'')!=='Fuji Super HR-U')fail('test draft was not created');$testId=(int)$test['id'];
student_test_submit($db,$testId,$studentId);$submitted=student_test_for_admin($db,$testId,1);if(!$submitted||$submitted['status']!=='submitted'||empty($submitted['submitted_at']))fail('test submission failed');
student_test_add_student_message($db,$testId,$studentId,'As sombras ficaram agrupadas.');student_test_add_admin_message($db,$testId,1,'Repita alterando somente a exposição.');$messages=student_test_messages($db,$testId);if(count($messages)!==2||$messages[0]['author_role']!=='student'||$messages[1]['author_role']!=='admin')fail('test discussion thread failed');
student_test_set_review_status($db,$testId,1,'needs_revision');if((student_test_for_student($db,$testId,$studentId)['status']??'')!=='needs_revision')fail('needs-revision status failed');student_test_set_review_status($db,$testId,1,'reviewed');if((student_test_for_student($db,$testId,$studentId)['status']??'')!=='reviewed')fail('reviewed status failed');
try{student_test_update($db,$testId,$studentId,['title'=>'Mudança']);fail('reviewed test remained editable');}catch(RuntimeException $e){if(!str_contains($e->getMessage(),'revisado'))throw $e;}

@unlink($tmp);
echo "student-workspace: ok\n";
