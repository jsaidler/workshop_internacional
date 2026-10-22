<?php
declare(strict_types=1);
function check_feature(bool $condition,string $what): void {
    if(!$condition){fwrite(STDERR,"course-comms: $what\n");exit(1);}
}
function utc_now(): string {return gmdate('Y-m-d\TH:i:s\Z');}
function course_by_id(PDO $db,int $id): ?array {
    $q=$db->prepare("SELECT * FROM courses WHERE id=? AND status!='archived'");
    $q->execute([$id]);return $q->fetch()?:null;
}
function admin_course_cohort(PDO $db,int $courseId,int $cohortId,bool $unused=false): ?array {
    $q=$db->prepare("SELECT * FROM course_cohorts WHERE id=? AND course_id=? AND status!='archived'");
    $q->execute([$cohortId,$courseId]);return $q->fetch()?:null;
}
function course_agenda_timezone(string $name): DateTimeZone {return new DateTimeZone($name);}
function course_agenda_to_utc(string $local,string $tz): ?string {
    if(trim($local)==='')return null;
    return (new DateTimeImmutable($local,new DateTimeZone($tz)))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
}
function course_agenda_safe_meeting_url(mixed $value): string {
    $v=trim((string)$value);return filter_var($v,FILTER_VALIDATE_URL) && parse_url($v,PHP_URL_SCHEME)==='https'?$v:'';
}
function course_agenda_with_lock(PDO $db,callable $work): mixed {
    $db->exec('BEGIN IMMEDIATE');
    try{$out=$work();$db->exec('COMMIT');return $out;}
    catch(Throwable $e){$db->exec('ROLLBACK');throw $e;}
}
function app_config(): array {
    return ['app_secret'=>str_repeat('x',40),'public_url'=>'https://example.test',
      'site_name'=>'Fotografia','email_from'=>'contato@example.test','brevo_api_key'=>'test-key'];
}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec(<<<'SQL'
CREATE TABLE activities(id INTEGER PRIMARY KEY);
CREATE TABLE student_users(id INTEGER PRIMARY KEY,status TEXT NOT NULL,name TEXT NOT NULL,email TEXT NOT NULL);
CREATE TABLE courses(id INTEGER PRIMARY KEY,activity_id INTEGER,status TEXT,title TEXT);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER,status TEXT,title TEXT);
CREATE TABLE course_enrollments(student_id INTEGER,cohort_id INTEGER,status TEXT);
CREATE TABLE course_listener_reservations(student_id INTEGER,cohort_id INTEGER,status TEXT);
INSERT INTO activities(id) VALUES(1),(2);
INSERT INTO courses(id,activity_id,status,title) VALUES(1,1,'active','Positivo direto'),(2,2,'active','Outro curso');
INSERT INTO course_cohorts(id,course_id,status,title) VALUES(11,1,'active','Atual'),(12,1,'closed','Antiga'),(21,2,'active','Externa');
INSERT INTO student_users(id,status,name,email) VALUES
(1,'active','A','a@example.test'),(2,'active','B','b@example.test'),
(3,'active','C','c@example.test'),(4,'active','D','d@example.test'),
(5,'active','E','e@example.test'),(6,'active','F','f@example.test');
INSERT INTO course_enrollments(student_id,cohort_id,status) VALUES
(1,11,'active'),(2,11,'active'),(3,21,'active'),(4,12,'active'),(5,12,'active');
INSERT INTO course_listener_reservations(student_id,cohort_id,status) VALUES
(5,11,'confirmed'),(4,11,'waiting');
SQL);
(require dirname(__DIR__).'/migrations/102_course_review_sessions.php')($db);
(require dirname(__DIR__).'/migrations/103_communications.php')($db);
(require dirname(__DIR__).'/migrations/108_communication_recipient_rich_body.php')($db);
(require dirname(__DIR__).'/migrations/109_communication_email_templates.php')($db);
(require dirname(__DIR__).'/migrations/105_communication_sender_settings.php')($db);
require dirname(__DIR__).'/app/course_reviews.php';
require dirname(__DIR__).'/app/communications_brevo_credentials.php';
require dirname(__DIR__).'/app/communications_site_url.php';
require dirname(__DIR__).'/app/communications_rich_text.php';
require dirname(__DIR__).'/app/communications_email_design.php';
require dirname(__DIR__).'/app/communications.php';
check_feature(communication_mail_status($db)['transport']==='brevo','configured external mail remains usable');
$senderBefore=communication_sender_settings($db);
check_feature($senderBefore['sender_email']==='contato@example.test','legacy sender fallback');
communication_sender_save($db,['sender_name'=>'Laboratório Saidler','sender_email'=>'avisos@example.test']);
check_feature(communication_sender_settings($db)['sender_email']==='avisos@example.test','sender updated through CMS');
check_feature(communication_mail_status($db)['sender_name']==='Laboratório Saidler','sender name persisted');
check_feature(communication_provider_configuration($db)['from']==='avisos@example.test','external sender override');
check_feature(communication_provider_configuration($db)['name']==='Laboratório Saidler','configured sender name reaches Brevo API');
$invalidSender=false;
try{communication_sender_save($db,['sender_email'=>"mail@example.test\r\nBCC: anyone@example.test"]);}catch(RuntimeException){$invalidSender=true;}
check_feature($invalidSender,'reject mail header injection');
communication_worker_record($db,true);
communication_worker_record($db,false);
check_feature((bool)communication_worker_status($db)['last_finished_at'],'worker heartbeat persisted');

$input=['title'=>'Revisão dos resultados','cohort_id'=>'0',
  'meeting_starts_at'=>'2030-07-01T19:00','meeting_timezone'=>'America/Sao_Paulo',
  'meeting_duration_minutes'=>'90','meeting_url'=>'https://example.test/meet',
  'meeting_notes'=>'Discussão','meeting_status'=>'scheduled'];
$global=course_review_save($db,1,0,$input);
check_feature(count(course_review_student_meetings($db,1,[]))===1,'course-wide review visible to enrolled');
check_feature(count(course_review_student_meetings($db,4,[]))===1,'old active enrollment eligible');
check_feature(count(course_review_student_meetings($db,3,[]))===0,'review cannot leak to other course');
check_feature(count(course_review_student_meetings($db,6,[]))===0,'review cannot leak to nonstudent');
$input['cohort_id']='11';$input['title']='Revisão da turma';
$scoped=course_review_save($db,1,0,$input);
check_feature(count(course_review_student_meetings($db,1,[]))===2,'cohort attendee gets two reviews');
check_feature(count(course_review_student_meetings($db,5,[]))===2,'confirmed listener attends');
check_feature(count(course_review_student_meetings($db,4,[]))===1,'waitlisted participant denied');
$input['title']='Mudança';$input['meeting_status']='cancelled';
check_feature(course_review_save($db,1,$scoped,$input)===$scoped,'review update id stable');
check_feature(count(course_review_list($db,1))===2,'review update does not duplicate');
$cross=false;try{course_review_save($db,2,$scoped,$input);}catch(RuntimeException){$cross=true;}
check_feature($cross,'course scope enforced');
$bad=false;try{$input['meeting_url']='http://unsafe.test';course_review_save($db,1,0,$input);}catch(RuntimeException){$bad=true;}
check_feature($bad,'no insecure meeting links');
check_feature((int)$db->query('SELECT COUNT(*) FROM course_enrollments')->fetchColumn()===5,'no synthetic enrollments');
check_feature((int)$db->query('SELECT COUNT(*) FROM course_review_sessions')->fetchColumn()===2,'reviews not multiplied by cohorts');

check_feature(!communication_opted_in($db,1),'no retroactive opt-in');
communication_set_opt_in($db,1,true);
communication_set_opt_in($db,3,true);
check_feature(count(communication_audience($db,1,'newsletter',0,0))===1,'newsletter respects opt-in and activity');
check_feature(count(communication_audience($db,1,'operational',1,11))===3,'operational includes confirmed listener');
check_feature(count(communication_audience($db,1,'operational',1,0))===4,'course audience distinct');
$badScope=false;try{communication_audience($db,1,'newsletter',2,0);}catch(RuntimeException){$badScope=true;}
check_feature($badScope,'campaign cannot target other activity');
$draft=communication_create_campaign($db,1,[
 'category'=>'newsletter','course_id'=>0,'cohort_id'=>0,
 'subject'=>'Próxima turma','body_text'=>'Novas datas disponíveis.'
]);
check_feature(communication_queue_campaign($db,1,$draft)===1,'newsletter queues only opt-in audience');
$double=false;try{communication_queue_campaign($db,1,$draft);}catch(RuntimeException){$double=true;}
check_feature($double,'double queue forbidden');
$token=communication_unsubscribe_token(1,'a@example.test');
check_feature(communication_unsubscribe_resolve($db,$token)['id']===1,'unsubscribe signed');
check_feature(communication_unsubscribe_resolve($db,'1.'.str_repeat('0',64))===null,'unsubscribe cannot be forged');
communication_set_opt_in($db,1,false,'unsubscribe_link');
$sent=[];
$fake=static function(array $payload)use(&$sent):array {$sent[]=$payload;return ['id'=>'fake-1'];};
$result=communication_dispatch($db,20,$fake);
check_feature($result['skipped']===1&&$result['sent']===0,'unsubscribed queued mail must be skipped');

$notice=communication_create_campaign($db,1,[
 'category'=>'operational','course_id'=>1,'cohort_id'=>11,
 'subject'=>'Revisão','body_text'=>'Encontro confirmado.'
]);
check_feature(communication_queue_campaign($db,1,$notice)===3,'academic notice queues only authorized members');
$db->exec("UPDATE course_enrollments SET status='disabled' WHERE student_id=2 AND cohort_id=11");
$result=communication_dispatch($db,20,$fake);
check_feature($result['sent']===2&&$result['skipped']===1,'eligibility rechecked before dispatch');
check_feature(count($sent)===2,'exactly two notices dispatched');
check_feature(count(communication_campaigns($db,1))===2,'history displays queued campaigns');

$unsafe='<p onclick="evil()"><strong>Mensagem <em>importante</em></strong><script>alert(1)</script><a href="javascript:alert(2)">x</a><a href="https://example.test">seguro</a><img src="https://tracker.test/pixel"></p>';
$clean=communication_safe_html($unsafe);
check_feature(str_contains($clean,'<strong>')&&str_contains($clean,'<em>'),'basic rich formatting is retained');
check_feature(!str_contains($clean,'onclick')&&!str_contains($clean,'script')&&!str_contains($clean,'javascript:')&&!str_contains($clean,'tracker.test'),'unsafe rich content must be stripped');
check_feature(str_contains($clean,'href="https://example.test"'),'safe HTTPS link kept');
check_feature(str_contains(communication_html_text($clean),'Mensagem importante'),'plaintext fallback derived from rich content');

$selected=communication_create_campaign($db,1,[
 'category'=>'operational','course_id'=>1,'cohort_id'=>11,
 'subject'=>'Mensagem individual','body_html'=>'<p><strong>Encontro</strong> especial.</p>',
 'body_text'=>'ignored fallback','selection_explicit'=>1,'recipient_ids'=>[5]
]);
check_feature(communication_campaign_target_ids($db,$selected)===[5],'only explicitly selected recipient saved');
check_feature(count(communication_campaign_audience($db,1,communication_campaign($db,1,$selected)))===1,'selected audience limited to one');
$invalid=false;try{
 communication_update_campaign($db,1,$selected,[
  'category'=>'operational','course_id'=>1,'cohort_id'=>11,
  'subject'=>'Wrong','body_html'=>'<p>Wrong</p>',
  'selection_explicit'=>1,'recipient_ids'=>[3]
 ]);
}catch(RuntimeException){$invalid=true;}
check_feature($invalid,'cross-course selected recipient rejected');
check_feature(communication_campaign_target_ids($db,$selected)===[5],'invalid edit does not alter previous recipient');
check_feature(communication_queue_campaign($db,1,$selected)===1,'only selected recipient queued');
check_feature((int)$db->query('SELECT COUNT(*) FROM communication_deliveries WHERE campaign_id='.$selected)->fetchColumn()===1,'no implicit additional delivery');
$richSent=[];$richSender=static function(array $payload)use(&$richSent):array{$richSent[]=$payload;return ['id'=>'fake-html'];};
$sentRich=communication_dispatch($db,20,$richSender);
check_feature($sentRich['sent']===1&&count($richSent)===1,'exactly one selected rich email sent');
check_feature($richSent[0]['email']==='e@example.test','only specifically chosen student received email');
check_feature(str_contains($richSent[0]['html'],'<strong>Encontro</strong>'),'HTML survives delivery preparation');
check_feature(str_contains($richSent[0]['body'],'Encontro especial.'),'plaintext alternative available');

$duplicate=communication_duplicate_campaign($db,1,$selected);
$duplicateRow=communication_campaign($db,1,$duplicate);
check_feature($duplicateRow['status']==='draft'&&$duplicateRow['subject']==='Mensagem individual','duplicated message remains a draft');
check_feature(communication_campaign_target_ids($db,$duplicate)===[5],'duplication retains current eligible recipient selection');

/* Manual processing, cancellation, history, idempotence and activity isolation. */
$storage=dirname(__DIR__).'/storage';
if(!is_dir($storage))mkdir($storage,0770,true);
$pending=communication_create_campaign($db,1,[
 'category'=>'operational','course_id'=>1,'cohort_id'=>11,
 'subject'=>'Cancelamento parcial','body_text'=>'Mensagem',
 'selection_explicit'=>1,'recipient_ids'=>[1,5]
]);
check_feature(communication_queue_campaign($db,1,$pending)===2,'two individual recipients queued');
$seenBefore=count($sent);
$batch=communication_dispatch_manual($db,1,1,$fake);
check_feature(!$batch['busy']&&$batch['sent']===1,'manual batch sends without a cron');
check_feature(count($sent)===$seenBefore+1,'manual action sends exactly one recipient');
$state=communication_queue_summary($db,1);
check_feature($state['pending']===1,'manual batch leaves remaining recipients queued');
check_feature(communication_cancel_queue($db,1)===1,'admin cancels only pending messages');
check_feature(communication_queue_summary($db,1)['pending']===0,'cancellation empties pending queue');
check_feature(communication_queue_summary($db,1)['cancelled']===1,'cancellation has a distinct auditable reason');
check_feature(communication_campaign($db,1,$pending)['status']==='completed','partially cancelled campaign finalizes');
check_feature((int)$db->query("SELECT COUNT(*) FROM communication_deliveries WHERE campaign_id=$pending AND status='sent'")===1,'accepted message stays accepted');
check_feature((int)$db->query("SELECT COUNT(*) FROM communication_deliveries WHERE campaign_id=$pending AND error_code='cancelled_by_admin'")===1,'cancellation persists separate from other skipped messages');
$afterCancel=count($sent);
check_feature(communication_dispatch_manual($db,1,1,$fake)['sent']===0&&count($sent)===$afterCancel,'no duplicate send on repeated manual clicks');

$other=communication_create_campaign($db,2,[
 'category'=>'operational','course_id'=>2,'cohort_id'=>21,
 'subject'=>'Outro setor','body_text'=>'Fora desta área',
 'selection_explicit'=>1,'recipient_ids'=>[3]
]);
check_feature(communication_queue_campaign($db,2,$other)===1,'other activity queued separately');
check_feature(communication_queue_summary($db,1)['pending']===0,'dashboard queue is scoped');
check_feature(communication_cancel_queue($db,1)===0,'cancel cannot cross activity');
check_feature(communication_dispatch_manual($db,1,1,$fake)['sent']===0,'manual execution cannot cross activity');
check_feature(communication_queue_summary($db,2)['pending']===1,'other activity queue preserved');
check_feature(communication_cancel_queue($db,2,$other)===1,'cancel specific campaign within activity');
$otherRows=communication_campaigns($db,2);
check_feature((int)$otherRows[0]['cancelled_count']===1,'campaign history exposes cancelled count');

echo "course-comms: OK\n";
