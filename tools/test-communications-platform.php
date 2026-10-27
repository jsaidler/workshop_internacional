<?php
declare(strict_types=1);

function fail_platform(string $message):never {fwrite(STDERR,"communications-platform: $message\n");exit(1);}
function expect_platform(bool $condition,string $message):void {if(!$condition)fail_platform($message);}
function utc_now():string {return gmdate('Y-m-d\TH:i:s\Z');}
function course_agenda_with_lock(PDO $db,callable $callback):mixed {return $callback();}
function app_config():array {return ['site_name'=>'Workshop','email_from'=>'sender@example.test','app_secret'=>'synthetic-platform-test-secret'];}
function communication_site_url(PDO $db):string {return 'https://example.test';}

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->exec('PRAGMA foreign_keys=ON');
$db->exec(<<<'SQL'
CREATE TABLE activities(id INTEGER PRIMARY KEY);
CREATE TABLE admin_users(id INTEGER PRIMARY KEY,name TEXT,email TEXT);
CREATE TABLE courses(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,title TEXT,status TEXT NOT NULL DEFAULT 'active');
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER NOT NULL,title TEXT,status TEXT NOT NULL DEFAULT 'active');
CREATE TABLE student_users(id INTEGER PRIMARY KEY,name TEXT,email TEXT,status TEXT,last_login_at TEXT,activated_at TEXT);
CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT);
CREATE TABLE course_listener_reservations(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT);
CREATE TABLE student_tests(id INTEGER PRIMARY KEY,student_id INTEGER,status TEXT);
CREATE TABLE student_questions(id INTEGER PRIMARY KEY,student_id INTEGER);
INSERT INTO activities VALUES(1); INSERT INTO admin_users VALUES(1,'Admin','admin@example.test');
INSERT INTO courses VALUES(10,1,'Curso A','active'),(11,1,'Curso B','active');
INSERT INTO course_cohorts VALUES(20,10,'Turma A','active'),(21,11,'Turma B','active');
INSERT INTO student_users VALUES(1,'Ana','ana@example.test','active',NULL,'2026-01-01'),(2,'Bia','bia@example.test','active','2026-10-26T10:00:00Z',NULL),(3,'Caio','caio@example.test','active','2026-01-01T10:00:00Z','2026-01-01');
INSERT INTO course_enrollments VALUES(1,1,20,'active'),(2,2,20,'active'),(3,3,21,'completed');
INSERT INTO student_tests VALUES(1,1,'submitted'); INSERT INTO student_questions VALUES(1,2);
SQL);

(require dirname(__DIR__).'/migrations/103_communications.php')($db);
(require dirname(__DIR__).'/migrations/105_communication_sender_settings.php')($db);
(require dirname(__DIR__).'/migrations/108_communication_recipient_rich_body.php')($db);
(require dirname(__DIR__).'/migrations/109_communication_email_templates.php')($db);
(require dirname(__DIR__).'/migrations/110_premium_communications.php')($db);
(require dirname(__DIR__).'/migrations/111_communication_preheader.php')($db);
(require dirname(__DIR__).'/migrations/112_communication_builder_drafts.php')($db);
(require dirname(__DIR__).'/migrations/113_communications_platform.php')($db);
(require dirname(__DIR__).'/migrations/114_communication_dkim_selector.php')($db);
require dirname(__DIR__).'/app/communications_rich_text.php';
require dirname(__DIR__).'/app/communications_email_design.php';
require dirname(__DIR__).'/app/communications_builder_document.php';
require dirname(__DIR__).'/app/communications_builder_renderer.php';
require dirname(__DIR__).'/app/communications.php';
require dirname(__DIR__).'/app/communications_builder_drafts.php';

$db->exec("INSERT INTO communication_preferences(student_id,newsletter_opt_in,changed_at,source) VALUES(1,1,'2026-10-27T00:00:00Z','profile'),(2,0,'2026-10-27T00:00:00Z','profile')");
$rules=['course_ids'=>[10],'enrollment_statuses'=>['active'],'newsletter_consent'=>'granted','profile'=>'activated','behaviors'=>['submitted_test']];
$members=communication_segment_evaluate($db,1,$rules,'all');
expect_platform(array_column($members,'id')===[1],'combined segment must select only the fully matching student');
$segmentId=communication_segment_save($db,1,0,['name'=>'Alunas engajadas','match_mode'=>'all']+$rules,1);
expect_platform($segmentId>0&&count(communication_recent_audit($db,1))===1,'segment save must be audited');

$now=utc_now();
$db->prepare("INSERT INTO communication_campaigns(activity_id,category,course_id,cohort_id,subject,body_text,body_html,preheader,selection_mode,template_key,status,approval_status,segment_id,created_at,updated_at) VALUES(1,'newsletter',10,NULL,'Boletim','Conteúdo','','Resumo','all','minimal','draft','draft',?,?,?)")
  ->execute([$segmentId,$now,$now]);$campaignId=(int)$db->lastInsertId();
communication_campaign_request_approval($db,1,$campaignId,1);
expect_platform(communication_campaign($db,1,$campaignId)['approval_status']==='pending','request must move campaign to pending approval');
communication_campaign_decide_approval($db,1,$campaignId,1,true,'Revisada');
expect_platform(communication_campaign($db,1,$campaignId)['approval_status']==='approved','approval decision must be persisted');
$snapshot=communication_segment_snapshot($db,1,$campaignId,$segmentId);
expect_platform($snapshot['ids']===[1]&&strlen($snapshot['hash'])===64,'segment snapshot must be deterministic and auditable');

$journeyId=communication_journey_save($db,1,0,['name'=>'Boas-vindas','trigger_key'=>'manual'],1);
communication_journey_step_save($db,1,$journeyId,['position'=>1,'delay_minutes'=>15,'category'=>'newsletter','subject'=>'Bem-vinda','body_text'=>'Mensagem inicial'],1);
communication_journey_set_status($db,1,$journeyId,'active',1);
expect_platform(communication_journey_enroll($db,$journeyId,1,'event-1',['course_id'=>10]),'first journey enrollment must be created');
expect_platform(!communication_journey_enroll($db,$journeyId,1,'event-1',['course_id'=>10]),'journey enrollment must be idempotent');
$triggerJourney=communication_journey_save($db,1,0,['name'=>'Entrada no curso','trigger_key'=>'enrollment_activated','course_id'=>10],1);
communication_journey_step_save($db,1,$triggerJourney,['position'=>1,'delay_minutes'=>0,'condition_key'=>'submitted_test','category'=>'operational','subject'=>'Comece por aqui','body_text'=>'Boas-vindas'],1);
communication_journey_set_status($db,1,$triggerJourney,'active',1);
expect_platform(communication_journey_trigger($db,1,'enrollment_activated',1,'enrollment-1',['course_id'=>10,'cohort_id'=>20])===1,'matching lifecycle event must enroll the student');
expect_platform(communication_journey_trigger($db,1,'enrollment_activated',1,'enrollment-1',['course_id'=>10,'cohort_id'=>20])===0,'lifecycle trigger must remain idempotent');
communication_set_opt_in($db,1,true,'profile_test');
communication_save_channel_preferences($db,1,['operational_email'=>0,'newsletter_frequency'=>'weekly'],'profile_test');
$preferences=communication_student_channel_preferences($db,1);
expect_platform($preferences['operational']['email_enabled']===0&&$preferences['operational']['inbox_enabled']===1&&$preferences['newsletter']['frequency']==='weekly','channel preferences must preserve mandatory inbox and selected frequency');
expect_platform((int)$db->query('SELECT COUNT(*) FROM communication_consent_events WHERE student_id=1')->fetchColumn()===1,'consent decisions must be append-only');
communication_save_channel_preferences($db,1,['operational_email'=>1,'newsletter_frequency'=>'weekly'],'profile_test');

$visualCampaign=communication_create_campaign($db,1,['category'=>'operational','course_id'=>10,'cohort_id'=>20,'subject'=>'Mensagem visual','body_text'=>'Fallback acessível','selection_explicit'=>1,'recipient_ids'=>[1],'created_by'=>1]);
$mjml='<mjml><mj-head><mj-attributes><mj-all font-family="Arial,Helvetica,sans-serif"/></mj-attributes></mj-head><mj-body background-color="#f1f0ed"><mj-section background-color="#ffffff" padding="14px 0"><mj-column><mj-text font-size="16px" color="#202427">Layout premium aprovado</mj-text></mj-column></mj-section></mj-body></mjml>';
$savedVisual=communication_builder_draft_write($db,1,$visualCampaign,$mjml,[],0);
$templateId=communication_template_save_from_campaign($db,1,$visualCampaign,'Boas-vindas premium',1);
$templateCampaign=communication_create_campaign($db,1,['category'=>'newsletter','subject'=>'Será substituído','body_text'=>'Rascunho','selection_explicit'=>1,'recipient_ids'=>[1],'created_by'=>1]);
communication_template_apply_to_campaign($db,1,$templateCampaign,$templateId,1);
$appliedTemplate=communication_campaign($db,1,$templateCampaign);
expect_platform($appliedTemplate['subject']==='Mensagem visual'&&(int)$appliedTemplate['template_id']===$templateId&&communication_builder_draft_read($db,1,$templateCampaign)!==null,'a reusable template must restore its canonical campaign and builder content');
communication_campaign_request_approval($db,1,$visualCampaign,1);communication_campaign_decide_approval($db,1,$visualCampaign,1,true);
$frozen=communication_campaign($db,1,$visualCampaign);
expect_platform($frozen['content_source']==='builder'&&str_contains((string)$frozen['rendered_html'],'Layout premium aprovado')&&strlen((string)$frozen['content_hash'])===64,'approval must freeze the canonical server-rendered builder output');
communication_builder_draft_write($db,1,$visualCampaign,str_replace('aprovado','revisado',$mjml),[],$savedVisual['revision']);
expect_platform(communication_campaign($db,1,$visualCampaign)['approval_status']==='draft','editing the builder must revoke prior approval');
communication_campaign_request_approval($db,1,$visualCampaign,1);communication_campaign_decide_approval($db,1,$visualCampaign,1,true);
expect_platform(communication_queue_campaign($db,1,$visualCampaign)===1,'approved visual campaign must queue its eligible recipient');
$captured=[];$delivery=communication_dispatch($db,1,static function(array $payload)use(&$captured):array{$captured=$payload;return ['id'=>'synthetic-provider-id'];},1);
expect_platform($delivery['sent']===1&&str_contains((string)($captured['server_rendered_html']??''),'Layout premium revisado'),'dispatch must use the frozen server-rendered builder HTML');

$positionJourney=communication_journey_save($db,1,0,['name'=>'Posição inicial não contígua','trigger_key'=>'manual'],1);
communication_journey_step_save($db,1,$positionJourney,['position'=>5,'delay_minutes'=>0,'category'=>'operational','subject'=>'Etapa cinco','body_text'=>'Primeira etapa real'],1);
communication_journey_set_status($db,1,$positionJourney,'active',1);
expect_platform(communication_journey_enroll($db,$positionJourney,2,'event-position-5'),'journey with a non-contiguous first position must enroll');
$firstPosition=(int)$db->query('SELECT current_position FROM communication_journey_enrollments WHERE journey_id='.(int)$positionJourney)->fetchColumn();
expect_platform($firstPosition===5,'journey enrollment must start at the actual first step position');

$newsletterOne=communication_create_campaign($db,1,['category'=>'newsletter','subject'=>'Resumo semanal 1','body_text'=>'Primeiro resumo','selection_explicit'=>1,'recipient_ids'=>[1],'created_by'=>1]);
$db->prepare("UPDATE communication_campaigns SET approval_status='approved',approved_at=? WHERE id=?")->execute([utc_now(),$newsletterOne]);
communication_queue_campaign($db,1,$newsletterOne);
$newsletterFirst=communication_dispatch($db,1,static fn(array $payload):array=>['id'=>'newsletter-first'],1);
expect_platform($newsletterFirst['sent']===1,'first newsletter in the frequency window must be sent');
$newsletterTwo=communication_create_campaign($db,1,['category'=>'newsletter','subject'=>'Resumo semanal 2','body_text'=>'Segundo resumo','selection_explicit'=>1,'recipient_ids'=>[1],'created_by'=>1]);
$db->prepare("UPDATE communication_campaigns SET approval_status='approved',approved_at=? WHERE id=?")->execute([utc_now(),$newsletterTwo]);
communication_queue_campaign($db,1,$newsletterTwo);
communication_dispatch($db,1,static fn(array $payload):array=>['id'=>'must-not-send'],1);
$deferred=$db->query('SELECT status,attempts,error_code,available_at FROM communication_deliveries WHERE campaign_id='.(int)$newsletterTwo)->fetch(PDO::FETCH_ASSOC);
expect_platform($deferred['status']==='pending'&&(int)$deferred['attempts']===0&&$deferred['error_code']==='frequency_capped'&&$deferred['available_at']>utc_now(),'frequency cap must defer delivery without consuming an attempt');

$diagnostic=communication_domain_diagnostics($db,1,'mail',static function(string $name,int $type):array {
    if(str_starts_with($name,'_dmarc.'))return [['txt'=>'v=DMARC1; p=quarantine']];
    if(str_starts_with($name,'mail._domainkey.'))return [['txt'=>'v=DKIM1; k=rsa; p=synthetic']];
    return [['txt'=>'v=spf1 include:example.test -all']];
});
expect_platform($diagnostic['spf_status']==='valid'&&$diagnostic['dmarc_status']==='valid'&&$diagnostic['dkim_status']==='valid','domain diagnostics must verify SPF, DKIM and DMARC independently');
$cnameDiagnostic=communication_domain_diagnostics($db,1,'mail',static function(string $name,int $type):array {
    if($type===DNS_CNAME&&str_starts_with($name,'mail._domainkey.'))return [['target'=>'mail.example.dkim-provider.test']];
    if(str_starts_with($name,'_dmarc.'))return [['txt'=>'v=DMARC1; p=reject']];
    if(!str_contains($name,'._domainkey.'))return [['txt'=>'v=spf1 -all']];
    return [];
});
expect_platform($cnameDiagnostic['dkim_status']==='valid'&&$cnameDiagnostic['details']['dkim_cname']===['mail.example.dkim-provider.test'],'DKIM diagnostic must accept delegated CNAME records');

$sentDelivery=(int)$db->query("SELECT id FROM communication_deliveries WHERE status='sent' ORDER BY id LIMIT 1")->fetchColumn();
$event=$db->prepare("INSERT INTO communication_delivery_events(channel,delivery_id,event_key,provider_event,provider_message_id,happened_at,created_at) VALUES('campaign',?,?,'delivered','provider-message',?,?)");
$event->execute([$sentDelivery,'delivered-1',$now,$now]);$event->execute([$sentDelivery,'delivered-2',$now,$now]);
$metrics=communication_platform_metrics($db,1);
expect_platform($metrics['delivered']===1,'platform metrics must count a delivered recipient once even when providers repeat events');

$run=communication_worker_run_start($db);communication_worker_run_finish($db,$run,['claimed'=>2,'sent'=>1,'skipped'=>1,'failed'=>0]);
$saved=$db->query('SELECT * FROM communication_worker_runs')->fetch(PDO::FETCH_ASSOC);
expect_platform($saved['status']==='completed'&&(int)$saved['claimed_count']===2,'supervised worker run must preserve counters');

echo "communications-platform: ok\n";
