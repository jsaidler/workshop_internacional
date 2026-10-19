<?php
declare(strict_types=1);
function utc_now():string{return gmdate('c');}
function course_agenda_safe_meeting_url(mixed $v):string{return (string)$v;}
function student_feedback_pending_for_student(PDO $db,int $id,?int $cohort=null,int $limit=20):array{return [];}
function student_feedback_state(array $v):array{return ['unseen'=>false,'requires_action'=>false];}
require __DIR__.'/../app/student_home.php';
function check_home(bool $c,string $m):void{if(!$c)throw new RuntimeException('student-home-live: '.$m);}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec(<<<'SQL'
PRAGMA foreign_keys=ON;
CREATE TABLE student_users(id INTEGER PRIMARY KEY);
CREATE TABLE courses(id INTEGER PRIMARY KEY,title TEXT);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER,title TEXT,cohort_uuid TEXT,status TEXT);
CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT);
CREATE TABLE student_questions(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,title TEXT);
CREATE TABLE student_question_messages(id INTEGER PRIMARY KEY,question_id INTEGER,author_role TEXT,created_at TEXT);
INSERT INTO student_users VALUES(1),(2);
INSERT INTO courses VALUES(2,'Positivo direto em filme de raio-X');
INSERT INTO course_cohorts VALUES(11,2,'Turma Outubro','cohort-a','active'),(12,2,'Turma Novembro','cohort-b','active');
INSERT INTO course_enrollments VALUES(1,1,11,'active'),(2,1,12,'disabled'),(3,2,12,'active');
INSERT INTO student_questions VALUES(5,1,11,'Segunda revelação'),(6,1,12,'Outra turma'),(7,2,12,'Outra pessoa');
INSERT INTO student_question_messages VALUES(101,5,'admin','2026-10-17T18:00:00Z'),(102,6,'admin','2026-10-18T18:00:00Z'),(103,7,'admin','2026-10-19T18:00:00Z');
SQL);
$migration=require __DIR__.'/../migrations/099_student_home_notice_reads.php';
$migration($db);$migration($db);
$n=student_home_question_notices($db,1);
check_home(count($n)===1&&$n[0]['unread']===true,'active owner should see one unread answer');
check_home(str_contains($n[0]['href'],'cohort=cohort-a&id=5'),'notice URL must remain in origin cohort');
student_home_mark_question_read($db,2,5);
check_home(student_home_question_notices($db,1)[0]['unread']===true,'another account read a private notice');
student_home_mark_question_read($db,1,6);
check_home(student_home_question_notices($db,1)[0]['unread']===true,'disabled cohort acknowledged');
student_home_mark_question_read($db,1,5);
check_home(student_home_question_notices($db,1)[0]['unread']===false,'authorized opening must acknowledge reply');
$db->exec("INSERT INTO student_question_messages VALUES(104,5,'admin','2026-10-19T20:00:00Z')");
check_home(student_home_question_notices($db,1)[0]['unread']===true,'new admin reply must become unread again');
student_home_mark_question_read($db,1,5);
check_home(student_home_question_notices($db,1)[0]['unread']===false,'latest reply acknowledgment failed');
$now=strtotime('2030-10-22T20:00:00Z');
$m=['date_iso'=>'2030-10-22T22:00:00Z','in_window'=>false];
check_home(student_home_live_priority($m,[], $now)['kind']==='meeting','imminent meeting should have priority');
check_home(student_home_live_priority(['date_iso'=>'2030-11-22T22:00:00Z'],[['kind'=>'answer','unread'=>true]],$now)['kind']==='notice','distant meeting eclipsed response');
check_home(student_home_live_priority(null,[],$now)===[],'fabricated urgency');

/* Listener opportunity requires an exact annotation→CMS section→lesson match. */
function student_question_annotation_available(PDO $db):bool{return true;}
function course_agenda_can_listen(PDO $db,int $studentId,int $target):bool{
    $q=$db->prepare("SELECT 1 FROM course_cohorts c WHERE c.id=? AND c.status='active'
      AND EXISTS(SELECT 1 FROM course_enrollments e JOIN course_cohorts own ON own.id=e.cohort_id
       WHERE e.student_id=? AND e.status='active' AND own.course_id=c.course_id AND own.id!=c.id)
      AND NOT EXISTS(SELECT 1 FROM course_enrollments e WHERE e.student_id=? AND e.cohort_id=c.id AND e.status='active')");
    $q->execute([$target,$studentId,$studentId]);return (bool)$q->fetchColumn();
}
$db->exec(<<<'SQL'
ALTER TABLE student_questions ADD COLUMN source_annotation_id INTEGER NULL;
ALTER TABLE student_questions ADD COLUMN status TEXT NOT NULL DEFAULT 'open';
CREATE TABLE student_material_annotations(id INTEGER PRIMARY KEY,student_id INTEGER,page_id INTEGER,lesson_id INTEGER,section_key TEXT,anchor_type TEXT);
CREATE TABLE course_material_pages(course_id INTEGER,page_id INTEGER);
CREATE TABLE course_material_sections(course_id INTEGER,page_id INTEGER,section_key TEXT,lesson_id INTEGER);
CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,course_id INTEGER,title TEXT);
CREATE TABLE cohort_lesson_releases(cohort_id INTEGER,lesson_id INTEGER,meeting_starts_at TEXT,meeting_status TEXT,meeting_timezone TEXT);
INSERT INTO course_cohorts VALUES(13,2,'Turma Janeiro','cohort-c','active');
INSERT INTO course_material_pages VALUES(2,100);
INSERT INTO course_material_sections VALUES(2,100,'branqueamento',201);
INSERT INTO course_lessons VALUES(201,2,'Branqueamento');
INSERT INTO cohort_lesson_releases VALUES(13,201,'2030-12-01T21:00:00Z','scheduled','America/Sao_Paulo');
INSERT INTO student_material_annotations VALUES(301,1,100,201,'branqueamento','selection');
UPDATE student_questions SET source_annotation_id=301 WHERE id=5;
SQL);
$op=student_home_related_listener_opportunities($db,1);
check_home(count($op)===1 && $op[0]['kind']==='opportunity' && $op[0]['href']==='/aluno/agenda.php?view=ouvintes','exactly mapped future lesson should allow a consultation link');
check_home(count(student_home_related_listener_opportunities($db,2))===0,'another account sees unrelated doubt opportunity');
$db->exec("UPDATE student_material_annotations SET section_key='sem-vinculo' WHERE id=301");
check_home(student_home_related_listener_opportunities($db,1)===[],'lexically similar but unmapped section appeared as an opportunity');
$db->exec("UPDATE student_material_annotations SET section_key='branqueamento' WHERE id=301");
$db->exec("UPDATE student_questions SET status='resolved' WHERE id=5");
check_home(student_home_related_listener_opportunities($db,1)===[],'resolved doubt still produced an opportunity');
$db->exec("UPDATE student_questions SET status='open' WHERE id=5");
$db->exec("INSERT INTO course_enrollments VALUES(4,1,13,'active')");
check_home(student_home_related_listener_opportunities($db,1)===[],'already enrolled student offered a listener seat');

echo "student-home-live: ok\n";
