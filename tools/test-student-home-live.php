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
echo "student-home-live: ok\n";
