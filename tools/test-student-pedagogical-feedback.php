<?php
declare(strict_types=1);
function fail_feedback(string $message): never {fwrite(STDERR,"student-pedagogical-feedback: $message\n");exit(1);}
function must_feedback(bool $ok,string $message): void {if(!$ok)fail_feedback($message);}
$root=dirname(__DIR__);
require_once $root.'/app/student_feedback.php';

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE activities(id INTEGER PRIMARY KEY,public_title TEXT NOT NULL);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,title TEXT NOT NULL,cohort_uuid TEXT NOT NULL,activity_id INTEGER NOT NULL);
CREATE TABLE student_tests(id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,cohort_id INTEGER NOT NULL,title TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'draft',reviewed_at TEXT NULL,updated_at TEXT NOT NULL);
CREATE TABLE student_test_messages(id INTEGER PRIMARY KEY AUTOINCREMENT,test_id INTEGER NOT NULL,student_id INTEGER NULL,author_role TEXT NOT NULL,body TEXT NOT NULL,created_at TEXT NOT NULL);");
$db->exec("INSERT INTO activities VALUES(1,'Positivo direto em filme de raio-X');INSERT INTO course_cohorts VALUES(1,'Turma Outubro 2026','cohort-a',1);");
$db->exec("INSERT INTO student_tests VALUES(10,7,1,'Retrato 07','submitted',NULL,'2026-10-04 09:00:00');");
$migration=require $root.'/migrations/083_student_pedagogical_feedback.php';$migration($db);

$columns=[];foreach($db->query('PRAGMA table_info(student_tests)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)$column['name']]=true;
must_feedback(isset($columns['feedback_at'],$columns['feedback_seen_at']),'migration did not add feedback markers');
must_feedback(student_feedback_available($db),'feedback service does not recognize migrated schema');

$db->exec("INSERT INTO student_test_messages(test_id,student_id,author_role,body,created_at) VALUES(10,NULL,'admin','Observe a agitação.','2026-10-04 10:00:00')");
$row=$db->query('SELECT * FROM student_tests WHERE id=10')->fetch(PDO::FETCH_ASSOC);
must_feedback((string)$row['feedback_at']==='2026-10-04 10:00:00'&&$row['feedback_seen_at']===null,'teacher message did not create unread feedback');
$pending=student_feedback_pending_for_student($db,7,null,10);
must_feedback(count($pending)===1&&student_feedback_state($pending[0])['unseen']===true,'unread teacher message is not prioritized');

student_feedback_mark_seen($db,10,7);
must_feedback(student_feedback_pending_for_student($db,7,null,10)===[],'seen submitted feedback remains pending');

$db->exec("UPDATE student_tests SET status='needs_revision',updated_at='2026-10-04 10:30:00' WHERE id=10");
$pending=student_feedback_pending_for_student($db,7,null,10);
must_feedback(count($pending)===1&&student_feedback_state($pending[0])['requires_action']===true,'revision request is not actionable');
student_feedback_mark_seen($db,10,7);
must_feedback(count(student_feedback_pending_for_student($db,7,null,10))===1,'reading a revision incorrectly resolves the required action');

$db->exec("UPDATE student_tests SET status='submitted',updated_at='2026-10-04 11:00:00' WHERE id=10");
must_feedback(student_feedback_pending_for_student($db,7,null,10)===[],'resubmission did not clear revision attention');

$db->exec("UPDATE student_tests SET status='reviewed',reviewed_at='2026-10-04 12:00:00',updated_at='2026-10-04 12:00:00' WHERE id=10");
$pending=student_feedback_pending_for_student($db,7,1,10);
must_feedback(count($pending)===1&&student_feedback_state($pending[0])['label']==='Avaliação disponível','review completion is not surfaced once');
student_feedback_mark_seen($db,10,7);
must_feedback(student_feedback_pending_for_student($db,7,1,10)===[],'reviewed feedback did not disappear after reading');

$db->exec("INSERT INTO student_test_messages(test_id,student_id,author_role,body,created_at) VALUES(10,NULL,'admin','Mais um ponto.','2026-10-04 12:15:00')");
must_feedback(count(student_feedback_pending_for_student($db,7,null,10))===1,'new teacher message after review did not reopen attention');
$db->exec("INSERT INTO student_test_messages(test_id,student_id,author_role,body,created_at) VALUES(10,7,'student','Entendido.','2026-10-04 12:20:00')");
must_feedback(student_feedback_pending_for_student($db,7,null,10)===[],'student reply did not acknowledge current feedback');

$home=(string)file_get_contents($root.'/aluno/index.php');$course=(string)file_get_contents($root.'/aluno/cursos.php');$result=(string)file_get_contents($root.'/aluno/teste.php');$questions=(string)file_get_contents($root.'/aluno/duvidas.php');$shell=(string)file_get_contents($root.'/app/student_shell.php');
must_feedback(strpos($home,'student_feedback_pending_for_student')<strpos($home,'foreach($records as $candidate)'),'dashboard no longer checks pedagogical return before unfinished notebook work');
must_feedback(str_contains($course,'student_feedback_course_attention')&&str_contains($course,'student-academic-attention'),'course lost contextual attention');
must_feedback(str_contains($result,'student_feedback_mark_seen')&&str_contains($result,'Enviar revisão para avaliação'),'result no longer owns evaluation/return loop');
must_feedback(str_contains($questions,'student_feedback_conversations_for_student')&&str_contains($questions,'Conversas de avaliação'),'questions screen no longer routes evaluation conversations back to results');
must_feedback(str_contains($shell,'student-feedback.css'),'student shell does not load pedagogical feedback styles');

echo "student-pedagogical-feedback: ok\n";
