<?php
declare(strict_types=1);
function notify_assert(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"admin-notify: $message\n");exit(1);}}
function utc_now(): string {return gmdate('Y-m-d\TH:i:s\Z');}
function app_config(): array {return ['site_name'=>'Curso QA','public_url'=>'https://example.test'];}
function course_agenda_with_lock(PDO $db,callable $fn): mixed {
    $db->exec('BEGIN IMMEDIATE');try{$out=$fn();$db->exec('COMMIT');return $out;}
    catch(Throwable $e){$db->exec('ROLLBACK');throw $e;}
}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec(<<<'SQL'
CREATE TABLE admin_users(id INTEGER PRIMARY KEY,email TEXT NOT NULL);
CREATE TABLE student_users(id INTEGER PRIMARY KEY,status TEXT NOT NULL,name TEXT NOT NULL);
CREATE TABLE courses(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,title TEXT NOT NULL);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER NOT NULL,title TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'active');
CREATE TABLE student_questions(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,title TEXT,body TEXT);
CREATE TABLE student_tests(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,title TEXT,status TEXT);
INSERT INTO admin_users(id,email) VALUES(1,'teacher@example.test'),(2,'assistant@example.test');
INSERT INTO student_users(id,status,name) VALUES(1,'active','Aluna A'),(2,'active','Aluno B');
INSERT INTO courses(id,activity_id,title) VALUES(7,1,'Positivo direto');
INSERT INTO course_cohorts(id,course_id,title) VALUES(10,7,'Turma A');
INSERT INTO student_questions(id,student_id,cohort_id,title,body) VALUES(11,1,10,'O branqueamento','CONTEXTO PRIVADO DO ALUNO'),(12,2,10,'Óptica','CONTEUDO PRIVADO');
INSERT INTO student_tests(id,student_id,cohort_id,title,status) VALUES(21,1,10,'Resultado experimental','submitted');
SQL);
require dirname(__DIR__).'/app/admin_student_notifications.php';
(require dirname(__DIR__).'/migrations/104_admin_student_action_notifications.php')($db);
notify_assert(admin_student_notice_available($db),'migration missing');
$kinds=admin_student_notice_kinds();notify_assert(count($kinds)===4,'event catalog incomplete');
admin_student_notice_set_preferences($db,2,['test_submitted'=>1]);
notify_assert(admin_student_notice_preferences($db,2)['question_created']===false,'preferences suppression');
notify_assert(admin_student_notice_preferences($db,1)['question_created']===true,'default enabled');

admin_student_notice_capture($db,1,10,'question_created',11,'11');
admin_student_notice_capture($db,1,10,'question_created',11,'11');
notify_assert((int)$db->query('SELECT COUNT(*) FROM admin_student_notice_outbox')->fetchColumn()===1,'unique admin/event occurrence');
admin_student_notice_capture($db,1,10,'test_submitted',21,'first-submit');
admin_student_notice_capture($db,1,10,'test_submitted',21,'resubmitted-after-revision');
notify_assert((int)$db->query('SELECT COUNT(*) FROM admin_student_notice_outbox')->fetchColumn()===5,'resubmission must create new notices');

$sent=[];
$fake=function(array $message)use(&$sent):array{$sent[]=$message;return ['id'=>'accepted'];};
$stats=admin_student_notice_dispatch($db,20,$fake);
notify_assert($stats['sent']===5 && $stats['failed']===0,'normal sending');
notify_assert(count($sent)===5,'one transport call for every eligible notice');
notify_assert(str_contains($sent[0]['body'],'/admin/questions.php?')&&str_contains($sent[0]['body'],'course=7'),'admin URL and course scope');
notify_assert(!str_contains(implode("\n",array_column($sent,'body')),'CONTEXTO PRIVADO'),'private question body must not be emailed');
notify_assert(!str_contains($sent[0]['body'],'permanecem no CMS'),'no implementation notes in the email received by administrators');
notify_assert(admin_student_notice_dispatch($db,20,$fake)['sent']===0,'no duplicate sends');

admin_student_notice_capture($db,1,10,'question_replied',11,'message-15');
admin_student_notice_set_preferences($db,1,['question_created'=>1,'test_submitted'=>1,'test_commented'=>1]);
$skipped=admin_student_notice_dispatch($db,20,$fake);
notify_assert($skipped['skipped']===1,'opt-out after queue must suppress pending email');

admin_student_notice_set_preferences($db,1,['question_created'=>1,'question_replied'=>1,'test_submitted'=>1,'test_commented'=>1]);
admin_student_notice_capture($db,2,10,'question_created',12,'12');
$db->exec("UPDATE student_users SET status='disabled' WHERE id=2");
notify_assert(admin_student_notice_dispatch($db,20,$fake)['skipped']===1,'inactive student must not leak');
$db->exec("UPDATE student_users SET status='active' WHERE id=2");

admin_student_notice_capture($db,1,10,'test_commented',21,'comment-1');
$failure=static function(array $message):array {throw new RuntimeException('down');};
notify_assert(admin_student_notice_dispatch($db,20,$failure)['failed']===1,'transport failures handled');
notify_assert(admin_student_notice_dispatch($db,20,$fake)['sent']===1,'retry pending once');
notify_assert(admin_student_notice_dispatch($db,20,$fake)['sent']===0,'retry idempotent');

$db->beginTransaction();
admin_student_notice_capture($db,1,10,'question_replied',11,'rolled-back');
$db->rollBack();
$q=$db->query("SELECT COUNT(*) FROM admin_student_notice_outbox WHERE event_key='rolled-back'");
notify_assert((int)$q->fetchColumn()===0,'outbox must share source transaction');
echo "admin-notify: OK\n";
