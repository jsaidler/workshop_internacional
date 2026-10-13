<?php
declare(strict_types=1);
/** In-memory domain regression for moving a CSV/manual enrollment. */
function utc_now():string{return gmdate('Y-m-d\TH:i:s\Z');}
function student_uuid():string {static $sequence=0;return 'e2e-manual-transfer-'.(++$sequence);}
function course_by_id(PDO $db,int $id):?array{$q=$db->prepare('SELECT * FROM courses WHERE id=?');$q->execute([$id]);return $q->fetch()?:null;}
function admin_course_cohort(PDO $db,int $courseId,int $cohortId,bool $includeArchived=false):?array{$q=$db->prepare('SELECT * FROM course_cohorts WHERE id=? AND course_id=?');$q->execute([$cohortId,$courseId]);return $q->fetch()?:null;}
function course_sync_release_rows_for_course(PDO $db,int $cohortId,int $courseId):void{}
function must_move(bool $ok,string $message):void{if(!$ok){fwrite(STDERR,"admin-cohort-transfer: ".$message."\n");exit(1);}}
function enrollment(PDO $db,int $id):array{$q=$db->prepare('SELECT * FROM course_enrollments WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:throw new RuntimeException('fixture enrollment missing');}
function insert_enrollment(PDO $db,int $studentId,int $cohortId,?int $source=null,string $status='active'):int{
 static $seq=0;$now=utc_now();
 $db->prepare('INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,source_submission_id,status,confirmed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)')->execute(['fixture-'.(++$seq),$studentId,$cohortId,$source,$status,$now,$now,$now]);
 return (int)$db->lastInsertId();
}
function expect_transfer_error(callable $action,string $needle):void{
 try{$action();}catch(Throwable $e){must_move(str_contains($e->getMessage(),$needle),'expected '.$needle.', got '.$e->getMessage());return;}
 must_move(false,'expected failure: '.$needle);
}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec(<<<'SQL'
CREATE TABLE student_users(id INTEGER PRIMARY KEY,status TEXT NOT NULL,name TEXT NOT NULL);
CREATE TABLE courses(id INTEGER PRIMARY KEY,status TEXT NOT NULL,title TEXT NOT NULL,updated_at TEXT NOT NULL);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER NOT NULL,status TEXT NOT NULL,title TEXT NOT NULL,updated_at TEXT NOT NULL);
CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,course_id INTEGER NOT NULL,title TEXT NOT NULL,sort_order INTEGER NOT NULL);
CREATE TABLE cohort_lesson_releases(cohort_id INTEGER NOT NULL,lesson_id INTEGER NOT NULL,released_at TEXT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL,PRIMARY KEY(cohort_id,lesson_id));
CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY AUTOINCREMENT,enrollment_uuid TEXT NOT NULL UNIQUE,student_id INTEGER NOT NULL,cohort_id INTEGER NOT NULL,source_submission_id INTEGER NULL,status TEXT NOT NULL,confirmed_at TEXT NOT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL,UNIQUE(student_id,cohort_id),FOREIGN KEY(student_id) REFERENCES student_users(id),FOREIGN KEY(cohort_id) REFERENCES course_cohorts(id));
INSERT INTO courses VALUES(1,'active','Positivo direto','now'),(2,'active','Curso B','now');
INSERT INTO course_cohorts VALUES(10,1,'active','Turma A','now'),(20,1,'active','Turma B','now'),(30,2,'active','Outro curso','now'),(40,1,'archived','Arquivada','now'),(50,1,'closed','Encerrada','now');
INSERT INTO course_lessons VALUES(101,1,'Aula de laboratório',1),(201,2,'Aula outro curso',1);
INSERT INTO cohort_lesson_releases VALUES(10,101,NULL,'now','now'),(20,101,NULL,'now','now'),(30,201,NULL,'now','now'),(40,101,NULL,'now','now'),(50,101,NULL,'now','now');
SQL);
for($id=1;$id<=25;$id++)$db->prepare("INSERT INTO student_users VALUES(?,'active',?)")->execute([$id,'Aluno '.$id]);
$migrate=require __DIR__.'/../migrations/098_course_cohort_agenda.php';$migrate($db);
$db->exec("UPDATE cohort_lesson_releases SET meeting_starts_at='2030-08-03T22:00:00Z',meeting_timezone='America/Sao_Paulo',meeting_status='scheduled' WHERE lesson_id=101");
require __DIR__.'/../app/course_agenda.php';
require __DIR__.'/../app/admin_student_cohort_transfer.php';

// New placement: old membership preserved (disabled), destination active, agenda follows.
$old=insert_enrollment($db,1,10);
must_move(count(course_agenda_student_meetings($db,1))===1&&
 (int)course_agenda_student_meetings($db,1)[0]['cohort_id']===10,'origin meeting is projected');
admin_student_transfer_manual($db,1,$old,20);
must_move(enrollment($db,$old)['status']==='disabled','old membership remains as historical, disabled record');
$q=$db->query("SELECT * FROM course_enrollments WHERE student_id=1 AND cohort_id=20");
$new=$q->fetch(PDO::FETCH_ASSOC);
must_move($new&&(string)$new['status']==='active'&&$new['source_submission_id']===null,'new participation is active and manual');
$meetings=course_agenda_student_meetings($db,1);
must_move(count($meetings)===1&&(int)$meetings[0]['cohort_id']===20,'agenda must switch to the destination cohort');

// Full capacity: a failed transfer restores the origin in the same transaction.
$old2=insert_enrollment($db,2,10);
$db->exec('UPDATE course_cohorts SET agenda_capacity=1 WHERE id=20');
expect_transfer_error(fn()=>admin_student_transfer_manual($db,1,$old2,20),'lotada');
must_move(enrollment($db,$old2)['status']==='active','capacity refusal rolled back origin status');
must_move((int)$db->query("SELECT COUNT(*) FROM course_enrollments WHERE student_id=2 AND cohort_id=20")->fetchColumn()===0,'capacity refusal did not create destination');
must_move((int)course_agenda_student_meetings($db,2)[0]['cohort_id']===10,'agenda stayed with originating cohort after refusal');
$db->exec('UPDATE course_cohorts SET agenda_capacity=10 WHERE id=20');

// Isolation and safety: no cross-course, archived/closed or source-bound manual mutation.
expect_transfer_error(fn()=>admin_student_transfer_manual($db,1,$old2,30),'não está ativa neste curso');
expect_transfer_error(fn()=>admin_student_transfer_manual($db,1,$old2,40),'não está ativa');
expect_transfer_error(fn()=>admin_student_transfer_manual($db,1,$old2,50),'não está ativa');
expect_transfer_error(fn()=>admin_student_transfer_manual($db,1,$old2,10),'diferente');
$source=insert_enrollment($db,3,10,10001);
expect_transfer_error(fn()=>admin_student_transfer_manual($db,1,$source,20),'inscrição original');
must_move(enrollment($db,$source)['status']==='active','source-linked registration must remain unchanged');

// Duplicate active destination: refuse to disable the unrelated old membership.
$otherOld=insert_enrollment($db,4,10);
$otherDest=insert_enrollment($db,4,20);
expect_transfer_error(fn()=>admin_student_transfer_manual($db,1,$otherOld,20),'já possui');
must_move(enrollment($db,$otherOld)['status']==='active'&&enrollment($db,$otherDest)['status']==='active','pre-existing memberships must be untouched');

// Reactivate a disabled, manual destination without duplicating student/cohort uniqueness.
$old5=insert_enrollment($db,5,10);
$disabledDest=insert_enrollment($db,5,20,null,'disabled');
admin_student_transfer_manual($db,1,$old5,20);
must_move(enrollment($db,$old5)['status']==='disabled'&&enrollment($db,$disabledDest)['status']==='active','inactive manual destination reactivated');
must_move((int)$db->query('SELECT COUNT(*) FROM course_enrollments WHERE student_id=5')->fetchColumn()===2,'no duplicate record for reactivation');

// A previously submission-owned destination must never be silently converted into manual enrollment.
$old6=insert_enrollment($db,6,10);
$ownedDestination=insert_enrollment($db,6,20,20002,'disabled');
expect_transfer_error(fn()=>admin_student_transfer_manual($db,1,$old6,20),'inscrição');
must_move(enrollment($db,$old6)['status']==='active'&&enrollment($db,$ownedDestination)['status']==='disabled','registration-owned target untouched');

$admin=(string)file_get_contents(__DIR__.'/../admin/students.php');
must_move(str_contains($admin,"'move_manual_enrollment'")&&str_contains($admin,'admin_student_transfer_manual('),'admin POST uses the real transfer service');
must_move(str_contains($admin,"'move_registration'")&&str_contains($admin,'course_registration_assign_cohort('),'registration remains source-authoritative');
must_move(str_contains($admin,'name="target_cohort_id"')&&str_contains($admin,'csrf_token(\'students-admin\')'),'admin form keeps selection and CSRF');
must_move(str_contains($admin,'$redirectCohortId=$targetCohortId'),'success navigates to the destination cohort');
echo "admin-cohort-transfer: ok\n";
