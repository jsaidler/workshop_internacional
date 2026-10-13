<?php
declare(strict_types=1);
/** Domain regression: paid registration assignment, enrollment, listener queue and rollback. */
function cr_transfer_must(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"course-registration-transfer: $message\n");exit(1);}}
function utc_now(): string {return gmdate('Y-m-d\TH:i:s\Z');}
function student_uuid(): string {static $n=0;return 'paid-transfer-'.(++$n);}
function student_normalize_email(string $value): string {return strtolower(trim($value));}
function student_account_cpf_lookup_hash(string $cpf): string {return '';}
function student_account_find_identity(PDO $db,string $email,string $hash): ?array {return null;}
function student_account_merge_profile_from_payload(PDO $db,int $studentId,array $payload): void {}
function student_account_upsert_from_submission(PDO $db,array $row): int {throw new RuntimeException('unexpected identity creation');}
function student_account_submission_confirmed(array $row): bool {return ($row['status']??'')!=='archived'&&(($row['status']??'')==='converted'||($row['payment_status']??'')==='paid');}
function course_cohort_by_id(PDO $db,int $id): ?array {$q=$db->prepare('SELECT * FROM course_cohorts WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function transfer_submission(PDO $db,int $id): array {$q=$db->prepare('SELECT * FROM cms_form_submissions WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('missing fixture submission');}
function transfer_enrollment(PDO $db,int $studentId,int $cohortId): ?array {$q=$db->prepare('SELECT * FROM course_enrollments WHERE student_id=? AND cohort_id=?');$q->execute([$studentId,$cohortId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function transfer_insert_enrollment(PDO $db,int $studentId,int $cohortId,?int $submissionId): void {$now=utc_now();$db->prepare("INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,source_submission_id,status,confirmed_at,created_at,updated_at) VALUES(?,?,?,?,'active',?,?,?)")->execute([student_uuid(),$studentId,$cohortId,$submissionId,$now,$now,$now]);}
function transfer_error(callable $fn,string $message): void {try{$fn();}catch(Throwable $e){cr_transfer_must(str_contains($e->getMessage(),$message),'expected '.$message.', got '.$e->getMessage());return;}cr_transfer_must(false,'expected error '.$message);}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec(<<<'SQL'
CREATE TABLE activities(id INTEGER PRIMARY KEY,public_title TEXT,admin_name TEXT,slug TEXT,is_root INTEGER);
CREATE TABLE student_users(id INTEGER PRIMARY KEY,status TEXT NOT NULL,name TEXT NOT NULL);
CREATE TABLE courses(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,status TEXT NOT NULL,title TEXT NOT NULL,public_page_id INTEGER NULL,updated_at TEXT NOT NULL);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER NOT NULL,activity_id INTEGER NOT NULL,cohort_uuid TEXT NOT NULL,status TEXT NOT NULL,title TEXT NOT NULL,workshop_page_id INTEGER NULL,updated_at TEXT NOT NULL);
CREATE TABLE cms_forms(id INTEGER PRIMARY KEY,form_key TEXT NOT NULL,purpose TEXT NOT NULL);
CREATE TABLE cms_form_submissions(id INTEGER PRIMARY KEY,form_id INTEGER NOT NULL,activity_id INTEGER NOT NULL,course_id INTEGER NOT NULL,cohort_id INTEGER NULL,student_id INTEGER NULL,payload_json TEXT NOT NULL,status TEXT NOT NULL,payment_status TEXT NOT NULL,payment_confirmed_at TEXT NULL,updated_at TEXT NOT NULL);
CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,course_id INTEGER NOT NULL,title TEXT NOT NULL,sort_order INTEGER NOT NULL);
CREATE TABLE cohort_lesson_releases(cohort_id INTEGER NOT NULL,lesson_id INTEGER NOT NULL,released_at TEXT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL,PRIMARY KEY(cohort_id,lesson_id));
CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY AUTOINCREMENT,enrollment_uuid TEXT NOT NULL UNIQUE,student_id INTEGER NOT NULL,cohort_id INTEGER NOT NULL,source_submission_id INTEGER NULL,status TEXT NOT NULL,confirmed_at TEXT NOT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL,UNIQUE(student_id,cohort_id));
INSERT INTO activities VALUES(1,'Workshop','Workshop','workshop',1);
INSERT INTO courses VALUES(1,1,'active','Positivo',NULL,'now'),(2,1,'active','Outro',NULL,'now');
INSERT INTO course_cohorts VALUES(10,1,1,'a','active','Turma A',NULL,'now'),(20,1,1,'b','active','Turma B',NULL,'now'),(30,1,1,'c','active','Turma C',NULL,'now'),(40,1,1,'d','archived','Arquivada',NULL,'now'),(50,1,1,'e','closed','Encerrada',NULL,'now'),(60,2,1,'f','active','Outro curso',NULL,'now');
INSERT INTO course_lessons VALUES(100,1,'Encontro',1),(200,2,'Outro encontro',1);
INSERT INTO cohort_lesson_releases VALUES(10,100,'2030-01-01T00:00:00Z','now','now'),(20,100,NULL,'now','now'),(30,100,NULL,'now','now'),(60,200,NULL,'now','now');
INSERT INTO cms_forms VALUES(1,'registration','enrollment');
INSERT INTO cms_form_submissions VALUES
(101,1,1,1,10,1,'{}','converted','paid','now','now'),
(102,1,1,1,30,3,'{}','converted','paid','now','now'),
(103,1,1,1,NULL,4,'{}','converted','paid','now','now'),
(104,1,1,1,NULL,5,'{}','new','pending',NULL,'now'),
(105,1,1,1,10,6,'{}','converted','paid','now','now'),
(106,1,1,1,10,7,'{}','converted','paid','now','now'),
(107,1,1,1,10,8,'{}','converted','paid','now','now');
SQL);
for($id=1;$id<=12;$id++)$db->prepare("INSERT INTO student_users VALUES(?,'active',?)")->execute([$id,'Aluno '.$id]);
$migrate=require __DIR__.'/../migrations/098_course_cohort_agenda.php';$migrate($db);
$db->exec("UPDATE course_cohorts SET agenda_enrollment_closes_at='2020-01-01T00:00:00Z',agenda_listeners_open_at='2020-01-01T00:00:00Z'");
$db->exec("UPDATE course_cohorts SET agenda_capacity=1 WHERE id=10");
$db->exec("UPDATE course_cohorts SET agenda_capacity=2 WHERE id IN (20,30)");
$db->exec("UPDATE cohort_lesson_releases SET meeting_starts_at='2030-07-01T22:00:00Z',meeting_timezone='America/Sao_Paulo',meeting_duration_minutes=120,meeting_url='https://example.org/a',meeting_status='scheduled' WHERE cohort_id=10");
$db->exec("UPDATE cohort_lesson_releases SET meeting_starts_at='2030-08-01T22:00:00Z',meeting_timezone='America/Sao_Paulo',meeting_duration_minutes=120,meeting_url='https://example.org/b',meeting_status='scheduled' WHERE cohort_id=20");
$db->exec("UPDATE cohort_lesson_releases SET meeting_starts_at='2030-09-01T22:00:00Z',meeting_timezone='America/Sao_Paulo',meeting_duration_minutes=120,meeting_url='https://example.org/c',meeting_status='scheduled' WHERE cohort_id=30");
require __DIR__.'/../app/courses.php';
require __DIR__.'/../app/student_enrollments.php';
require __DIR__.'/../app/course_agenda.php';

transfer_insert_enrollment($db,1,10,101);
transfer_insert_enrollment($db,2,20,null);
cr_transfer_must((int)course_agenda_student_meetings($db,1)[0]['cohort_id']===10,'original Agenda points to cohort A');
cr_transfer_must(course_agenda_request($db,2,10)['status']==='waiting','listener waits while A is full');
course_registration_assign_cohort($db,101,1,20);
cr_transfer_must((int)transfer_submission($db,101)['cohort_id']===20,'paid submission assigned to B');
cr_transfer_must(transfer_enrollment($db,1,10)['status']==='disabled','old enrollment preserved and disabled');
cr_transfer_must(transfer_enrollment($db,1,20)['status']==='active'&&(int)transfer_enrollment($db,1,20)['source_submission_id']===101,'paid enrollment activated at destination');
cr_transfer_must(count(course_agenda_student_meetings($db,1))===1&&(int)course_agenda_student_meetings($db,1)[0]['cohort_id']===20,'Agenda follows destination and hides origin');
cr_transfer_must(course_agenda_student_meetings($db,1)[0]['meeting_url']==='https://example.org/b','destination meeting URL used');
cr_transfer_must(course_agenda_reservations($db,10)[0]['status']==='confirmed','old vacancy promotes waiting listener');
cr_transfer_must((int)course_agenda_student_meetings($db,2)[0]['cohort_id']===10,'listener can access confirmed old cohort meeting');
cr_transfer_must(transfer_enrollment($db,3,30)===null,'single transfer must not reconcile an unrelated paid registration');
cr_transfer_must((int)course_enrollment_for_student($db,1,1,'b')['cohort_id']===20 && course_enrollment_for_student($db,1,1,'a')===null,'cohort-scoped access changes atomically');

course_agenda_cancel($db,2,10);
transfer_insert_enrollment($db,6,10,105);
transfer_error(fn()=>course_registration_assign_cohort($db,105,1,20),'limite');
cr_transfer_must((int)transfer_submission($db,105)['cohort_id']===10 && transfer_enrollment($db,6,10)['status']==='active' && transfer_enrollment($db,6,20)===null,'capacity failure rolls back submission and original enrollment');
cr_transfer_must((int)course_agenda_student_meetings($db,6)[0]['cohort_id']===10,'capacity failure leaves Agenda unchanged');

course_registration_assign_cohort($db,103,1,30);
course_registration_assign_cohort($db,103,1,30);
cr_transfer_must((int)transfer_submission($db,103)['cohort_id']===30 && (int)transfer_enrollment($db,4,30)['source_submission_id']===103,'paid previously unassigned registration activated');
cr_transfer_must((int)$db->query("SELECT COUNT(*) FROM course_enrollments WHERE student_id=4")->fetchColumn()===1,'repeated assignment is idempotent');
course_registration_assign_cohort($db,104,1,30);
cr_transfer_must((int)transfer_submission($db,104)['cohort_id']===30&&transfer_enrollment($db,5,30)===null,'pending payment can be assigned without unauthorized enrollment');
transfer_error(fn()=>course_registration_assign_cohort($db,105,1,40),'não está ativa');
transfer_error(fn()=>course_registration_assign_cohort($db,105,1,50),'não está ativa');
transfer_error(fn()=>course_registration_assign_cohort($db,105,1,60),'não pertence');
cr_transfer_must((int)transfer_submission($db,105)['cohort_id']===10,'invalid cohort changes no data');

$db->exec('UPDATE course_cohorts SET agenda_capacity=4 WHERE id=10');
transfer_insert_enrollment($db,7,10,106);
$db->exec("CREATE TRIGGER test_transfer_error BEFORE INSERT ON course_enrollments WHEN NEW.student_id=7 AND NEW.cohort_id=30 BEGIN SELECT RAISE(ABORT,'forced_write_failure'); END");
transfer_error(fn()=>course_registration_assign_cohort($db,106,1,30),'forced_write_failure');
cr_transfer_must((int)transfer_submission($db,106)['cohort_id']===10 && transfer_enrollment($db,7,10)['status']==='active' && transfer_enrollment($db,7,30)===null,'unexpected SQL error rolls back both tables');
$db->exec('DROP TRIGGER test_transfer_error');

transfer_insert_enrollment($db,8,10,107);
transfer_insert_enrollment($db,8,30,null);
transfer_error(fn()=>course_registration_assign_cohort($db,107,1,30),'outra origem');
cr_transfer_must((int)transfer_submission($db,107)['cohort_id']===10 && transfer_enrollment($db,8,30)['source_submission_id']===null,'manual provenance must not be overwritten');
cr_transfer_must(transfer_enrollment($db,8,10)['status']==='active','origin unchanged on source collision');

cr_transfer_must(course_agenda_request($db,6,20)['status']==='waiting','listener waits while B has two enrolled students');
course_registration_unassign_cohort($db,101,1);
cr_transfer_must(transfer_submission($db,101)['cohort_id']===null && transfer_enrollment($db,1,20)['status']==='disabled','unassignment updates both authorities');
cr_transfer_must(course_enrollment_for_student($db,1,1)===null && course_agenda_student_meetings($db,1)===[],'unassigned student loses course access and meeting links');
cr_transfer_must(course_agenda_request($db,6,20)['status']==='confirmed','unassignment promotes listener into newly free capacity');
cr_transfer_must(transfer_enrollment($db,3,30)===null,'unassignment does not reconcile other paid submissions');
$admin=file_get_contents(__DIR__.'/../admin/registrations.php');
cr_transfer_must(str_contains($admin, "!in_array(\$action,['assign_cohort','unassign_cohort'],true)"),'registrations POST must not trigger a second global reconciliation');
$hook=file_get_contents(__DIR__.'/../app/student_enrollments.php');
cr_transfer_must(str_contains($hook, "in_array((string)(\$_POST['action']??''),['assign_cohort','unassign_cohort'],true)"),'shutdown hook must skip per-registration assignment paths');
$accounts=file_get_contents(__DIR__.'/../app/student_accounts.php');
cr_transfer_must(str_contains($accounts, 'return student_enrollment_reconcile_confirmed($db,$activityId);'),'login and activation must delegate to the explicit-course authority');
echo "course-registration-transfer: ok\n";
