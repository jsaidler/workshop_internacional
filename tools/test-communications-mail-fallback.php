<?php
declare(strict_types=1);
function mail_check(bool $ok,string $message):void {
    if(!$ok){fwrite(STDERR,"mail-fallback: $message\n");exit(1);}
}
function utc_now():string{return gmdate('Y-m-d\TH:i:s\Z');}
function app_config():array {return [
    'app_secret'=>str_repeat('a',40),
    'site_name'=>'Curso de fotografia',
    'public_url'=>'',
];}
putenv('BREVO_API_KEY=');
putenv('BREVO_FROM_EMAIL=');
$db=new PDO('sqlite::memory:',null,null,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC
]);
(require dirname(__DIR__).'/migrations/105_communication_sender_settings.php')($db);
require dirname(__DIR__).'/app/communications_brevo_credentials.php';
require dirname(__DIR__).'/app/communications_site_url.php';
require dirname(__DIR__).'/app/communications.php';
mail_check(communication_mail_status($db)['transport']==='php','PHP mail should be default without Brevo');
mail_check(communication_mail_status($db)['transport_label']==='E-mail da hospedagem','provider label must not expose env names');
mail_check(communication_sender_settings($db)['sender_email']==='','unknown sender stays empty, not fabricated');
communication_sender_save($db,['sender_name'=>'João Saidler','sender_email'=>'notificacoes@example.test']);
$settings=communication_sender_settings($db);
mail_check($settings['sender_name']==='João Saidler','sender name stored');
mail_check($settings['sender_email']==='notificacoes@example.test','sender email stored');
mail_check(communication_mail_status($db)['transport']==='php','saved sender cannot silently require Brevo');
mail_check(communication_mail_status($db)['sender_configured']===true,'UI can display sender readiness');
putenv('BREVO_API_KEY=xkeysib-fixture-only');
mail_check(communication_provider_configuration($db)['from']==='notificacoes@example.test','Brevo transactional setup must not require public_url');
mail_check(communication_mail_status($db)['transport']==='brevo','API selected with valid sender, even if URL missing');
putenv('BREVO_API_KEY=');

$invalid=false;
try{communication_sender_save($db,['sender_email'=>'invalid']);}catch(RuntimeException){$invalid=true;}
mail_check($invalid,'invalid email rejected');
$invalid=false;
try{communication_sender_save($db,['sender_name'=>"João\nCc: Another",'sender_email'=>'notificacoes@example.test']);}catch(RuntimeException){$invalid=true;}
mail_check($invalid,'header injection rejected');
communication_worker_record($db,true);
mail_check(!empty(communication_worker_status($db)['last_started_at']),'worker start recorded');
communication_worker_record($db,false);
mail_check(!empty(communication_worker_status($db)['last_finished_at']),'worker finish recorded');
communication_worker_record($db,true);
mail_check(communication_worker_status($db)['last_error']===null,'worker start clears error');
$before=(int)$db->query('SELECT COUNT(*) FROM communication_sender_settings')->fetchColumn();
(require dirname(__DIR__).'/migrations/105_communication_sender_settings.php')($db);
mail_check((int)$db->query('SELECT COUNT(*) FROM communication_sender_settings')->fetchColumn()===$before,'migration idempotence');
echo "mail-fallback: OK\n";
