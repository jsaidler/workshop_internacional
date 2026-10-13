<?php
declare(strict_types=1);
function must_agenda(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"course-agenda: $message\n");exit(1);}}
function utc_now(): string {return gmdate('Y-m-d\TH:i:s\Z');}
function course_by_id(PDO $db,int $id): ?array {$q=$db->prepare("SELECT * FROM courses WHERE id=? AND status!='archived'");$q->execute([$id]);return $q->fetch()?:null;}
function admin_course_cohort(PDO $db,int $courseId,int $cohortId,bool $includeArchived=true): ?array {$q=$db->prepare('SELECT * FROM course_cohorts WHERE id=? AND course_id=?');$q->execute([$cohortId,$courseId]);return $q->fetch()?:null;}
function course_sync_release_rows_for_course(PDO $db,int $cohortId,int $courseId): void {
    $q=$db->prepare('SELECT id FROM course_lessons WHERE course_id=?');$q->execute([$courseId]);
    foreach($q->fetchAll(PDO::FETCH_COLUMN) as $lessonId)$db->prepare('INSERT OR IGNORE INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at) VALUES(?,?,NULL,?,?)')->execute([$cohortId,$lessonId,utc_now(),utc_now()]);
}
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);$db->exec('PRAGMA foreign_keys=ON');
$db->exec(<<<'SQL'
CREATE TABLE student_users(id INTEGER PRIMARY KEY,status TEXT NOT NULL,name TEXT NOT NULL);
CREATE TABLE courses(id INTEGER PRIMARY KEY,status TEXT NOT NULL,title TEXT NOT NULL,updated_at TEXT NOT NULL);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER NOT NULL,status TEXT NOT NULL,title TEXT NOT NULL,updated_at TEXT NOT NULL);
CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,course_id INTEGER NOT NULL,title TEXT NOT NULL,sort_order INTEGER NOT NULL);
CREATE TABLE cohort_lesson_releases(cohort_id INTEGER NOT NULL,lesson_id INTEGER NOT NULL,released_at TEXT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL,PRIMARY KEY(cohort_id,lesson_id));
CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER NOT NULL,cohort_id INTEGER NOT NULL,status TEXT NOT NULL,UNIQUE(student_id,cohort_id));
INSERT INTO courses(id,status,title,updated_at) VALUES(1,'active','Positivo Direto','now'),(2,'active','Outro curso','now');
INSERT INTO course_cohorts(id,course_id,status,title,updated_at) VALUES(1,1,'closed','Turma antiga','now'),(2,1,'active','Próxima turma','now'),(3,2,'active','Outro curso','now');
INSERT INTO course_lessons(id,course_id,title,sort_order) VALUES(101,1,'Primeira aula',1),(102,1,'Segunda aula',2),(103,1,'Terceira aula',3),(201,2,'Outra aula',1);
SQL);
for($id=1;$id<=50;$id++)$db->prepare("INSERT INTO student_users(id,status,name) VALUES(?,'active',?)")->execute([$id,'Aluno '.$id]);
$migrate=require __DIR__.'/../migrations/098_course_cohort_agenda.php';$migrate($db);$migrate($db); // idempotente
require __DIR__.'/../app/course_agenda.php';
course_sync_release_rows_for_course($db,2,1);
$db->exec("UPDATE cohort_lesson_releases SET meeting_starts_at='2030-07-01T22:30:00Z',meeting_timezone='America/Sao_Paulo' WHERE cohort_id=2 AND lesson_id=101");
for($id=1;$id<=3;$id++)$db->prepare("INSERT INTO course_enrollments(student_id,cohort_id,status) VALUES(?,2,'active')")->execute([$id]);
for($id=10;$id<=17;$id++)$db->prepare("INSERT INTO course_enrollments(student_id,cohort_id,status) VALUES(?,1,'active')")->execute([$id]);
$db->exec("INSERT INTO course_enrollments(student_id,cohort_id,status) VALUES(30,3,'active')");
must_agenda(course_agenda_capacity($db,2)['remaining']===7,'initial seven seats');
must_agenda(!course_agenda_can_listen($db,1,2),'regular student cannot reserve own cohort');
must_agenda(!course_agenda_can_listen($db,30,2),'student from another course cannot reserve');
must_agenda(!course_agenda_can_listen($db,50,2),'user without enrollment cannot reserve');
must_agenda(course_agenda_can_listen($db,10,2),'past cohort eligibility');
$settings=['agenda_default_capacity'=>'10','agenda_capacity'=>'','agenda_timezone'=>'America/Sao_Paulo','agenda_enrollment_closes_at'=>'','agenda_listeners_open_at'=>''];
course_agenda_save_settings($db,1,2,$settings);
for($id=10;$id<=17;$id++)must_agenda(course_agenda_request($db,$id,2)['status']==='waiting','commercial window prevents confirmation');
must_agenda(course_agenda_capacity($db,2)['remaining']===7,'waiting does not occupy seats');
$settings['agenda_enrollment_closes_at']='2025-01-01T18:00';$settings['agenda_listeners_open_at']='2025-01-02T18:00';
course_agenda_save_settings($db,1,2,$settings);
for($id=10;$id<=16;$id++)must_agenda(course_agenda_request($db,$id,2)['status']==='confirmed','seven ordered listeners confirm');
must_agenda(course_agenda_request($db,17,2)['status']==='waiting','eighth remains in queue');
must_agenda(course_agenda_capacity($db,2)['occupied']===10,'shared capacity is ten');
$blocked=false;try{$db->exec("INSERT INTO course_enrollments(student_id,cohort_id,status) VALUES(40,2,'active')");}catch(PDOException $e){$blocked=str_contains($e->getMessage(),'cohort_capacity_full');}
must_agenda($blocked,'direct enrollment blocked even outside PHP business flow');
must_agenda(course_agenda_request($db,10,2)['status']==='confirmed','double request idempotent');
must_agenda((int)$db->query('SELECT COUNT(*) FROM course_listener_reservations WHERE cohort_id=2')->fetchColumn()===8,'no duplicate listener record');
course_agenda_cancel($db,10,2);
$q=$db->query("SELECT status FROM course_listener_reservations WHERE student_id=17 AND cohort_id=2");
must_agenda($q->fetchColumn()==='confirmed','cancellation promotes first waiting listener');
must_agenda(course_agenda_capacity($db,2)['occupied']===10,'capacity after queue promotion');
$settings['agenda_capacity']='5';course_agenda_save_settings($db,1,2,$settings);
must_agenda(course_agenda_capacity($db,2)['excess']===5,'reduced capacity preserves already confirmed attendees');
course_agenda_request($db,10,2);
must_agenda((int)$db->query("SELECT COUNT(*) FROM course_listener_reservations WHERE cohort_id=2 AND status='confirmed'")->fetchColumn()===7,'reduced capacity does not force or admit changes');
$settings['agenda_capacity']='';course_agenda_save_settings($db,1,2,$settings);
course_sync_release_rows_for_course($db,2,1);
$db->exec("UPDATE cohort_lesson_releases SET released_at='2028-01-01T12:00:00Z' WHERE cohort_id=2 AND lesson_id=101");
$input=['meeting_starts_at'=>'2030-07-01T19:30','meeting_timezone'=>'America/Sao_Paulo','meeting_duration_minutes'=>'120','meeting_url'=>'https://example.org/private-class','meeting_notes'=>'Código individual','meeting_status'=>'scheduled','release_mode'=>'keep'];
course_agenda_save_lesson_configuration($db,1,2,101,$input);
$q=$db->query('SELECT * FROM cohort_lesson_releases WHERE cohort_id=2 AND lesson_id=101');$meeting=$q->fetch();
must_agenda($meeting['released_at']==='2028-01-01T12:00:00Z','scheduling never releases material');
must_agenda($meeting['meeting_starts_at']==='2030-07-01T22:30:00Z','local time is saved as UTC');
$regular=course_agenda_student_meetings($db,1);$listener=course_agenda_student_meetings($db,11);$waiting=course_agenda_student_meetings($db,10);$other=course_agenda_student_meetings($db,30);
must_agenda(count($regular)===1 && $regular[0]['participation']==='regular','regular enrollment projects into agenda');
must_agenda(count($listener)===1 && $listener[0]['participation']==='listener','confirmed listener projects into agenda');
must_agenda(!$waiting && !$other,'waiting and other-course users cannot read private link');
must_agenda($listener[0]['meeting_url']==='https://example.org/private-class','confirmed listener has meeting link');
$invalid=false;try{course_agenda_save_lesson_configuration($db,1,2,101,$input+['release_mode'=>'schedule']);}catch(RuntimeException){$invalid=true;}
must_agenda(!$invalid,'save of valid meeting remains idempotent');
$input['release_mode']='schedule';$input['release_scheduled_at']='';$invalid=false;
try{course_agenda_save_lesson_configuration($db,1,2,101,$input);}catch(RuntimeException){$invalid=true;}
must_agenda($invalid,'invalid scheduled release rejected');
must_agenda($db->query('SELECT released_at FROM cohort_lesson_releases WHERE cohort_id=2 AND lesson_id=101')->fetchColumn()==='2028-01-01T12:00:00Z','rollback preserves existing release');
// Alterações na ficha única devem refletir a mesma ocorrência, sem criar outra aula.
$updated=$input;
$updated['release_mode']='keep';
$updated['meeting_starts_at']='2030-08-03T20:30';
$updated['meeting_url']='https://example.org/updated-class';
course_agenda_save_lesson_configuration($db,1,2,101,$updated);
$updatedMeeting=course_agenda_student_meetings($db,11);
must_agenda(count($updatedMeeting)===1 && $updatedMeeting[0]['meeting_starts_at']==='2030-08-03T23:30:00Z','rescheduled meeting appears for confirmed listener');
must_agenda($updatedMeeting[0]['meeting_url']==='https://example.org/updated-class','new meeting link appears without duplicate');
must_agenda((int)$db->query('SELECT COUNT(*) FROM cohort_lesson_releases WHERE cohort_id=2 AND lesson_id=101')->fetchColumn()===1,'single canonical occurrence after edits');
course_sync_release_rows_for_course($db,1,1);
$otherDate=$updated;
$otherDate['meeting_starts_at']='2030-09-09T19:30';
$otherDate['meeting_url']='https://example.org/old-cohort';
course_agenda_save_lesson_configuration($db,1,1,101,$otherDate);
$oldCohort=$db->query('SELECT meeting_starts_at FROM cohort_lesson_releases WHERE cohort_id=1 AND lesson_id=101')->fetchColumn();
$newCohort=$db->query('SELECT meeting_starts_at FROM cohort_lesson_releases WHERE cohort_id=2 AND lesson_id=101')->fetchColumn();
must_agenda($oldCohort!==$newCohort && $newCohort==='2030-08-03T23:30:00Z','cohorts reuse lesson identity but maintain separate calendars');
must_agenda($db->query('SELECT released_at FROM cohort_lesson_releases WHERE cohort_id=1 AND lesson_id=101')->fetchColumn()===null,'new cohort configuration does not release old cohort material');
// A Agenda precisa preservar a prioridade temporal e o sigilo de links antigos.
$now=strtotime('2030-05-01T12:00:00Z');
$examples=[
 ['meeting_starts_at'=>'2030-04-01T10:00:00Z','meeting_status'=>'completed','meeting_duration_minutes'=>120],
 ['meeting_starts_at'=>'2030-06-01T10:00:00Z','meeting_status'=>'scheduled','meeting_duration_minutes'=>120],
 ['meeting_starts_at'=>'2030-03-01T10:00:00Z','meeting_status'=>'scheduled','meeting_duration_minutes'=>120],
 ['meeting_starts_at'=>'2030-07-01T10:00:00Z','meeting_status'=>'scheduled','meeting_duration_minutes'=>120],
 ['meeting_starts_at'=>'2030-08-01T10:00:00Z','meeting_status'=>'cancelled','meeting_duration_minutes'=>120],
];
$partition=course_agenda_partition_meetings($examples,$now);
must_agenda(count($partition['upcoming'])===2 && count($partition['history'])===3,'upcoming vs past and cancelled are separate');
must_agenda($partition['upcoming'][0]['meeting_starts_at']==='2030-06-01T10:00:00Z','nearest scheduled meeting is first');
must_agenda($partition['history'][0]['meeting_starts_at']==='2030-08-01T10:00:00Z','history is in descending chronological order');
$ongoing=course_agenda_partition_meetings([['meeting_starts_at'=>'2030-05-01T11:30:00Z','meeting_status'=>'scheduled','meeting_duration_minutes'=>120]],$now);
must_agenda(count($ongoing['upcoming'])===1,'meeting in its registered duration stays prominent');
must_agenda(course_agenda_safe_meeting_url('javascript:alert(1)')==='' && course_agenda_safe_meeting_url('https://example.org/ok')!=='','only https meeting URLs are exposed');
echo "course-agenda: ok\n";
