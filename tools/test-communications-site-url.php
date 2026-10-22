<?php
declare(strict_types=1);
function site_url_check(bool $condition,string $message):void {if(!$condition){fwrite(STDERR,"site-url: $message\n");exit(1);}}
function utc_now():string {return gmdate('Y-m-d\TH:i:s\Z');}
$GLOBALS['legacy_url']='';
function app_config():array {return ['public_url'=>$GLOBALS['legacy_url'],'site_name'=>'QA'];}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
require dirname(__DIR__).'/app/communications_site_url.php';
(require dirname(__DIR__).'/migrations/107_communication_site_origin.php')($db);
site_url_check(communication_site_url($db)==='','missing URL remains empty');
foreach(['http://example.test','javascript:alert(1)','https://user:pass@example.test',
  'https://example.test/another-path','https://example.test?x=1','https://example.test/#evil',
  'https://example.test\r\nInjected','https://example..test'] as $bad){
    $failed=false;
    try{communication_site_url_save($db,$bad);}catch(RuntimeException){$failed=true;}
    site_url_check($failed,'invalid URL rejected: '.json_encode($bad));
}
communication_site_url_save($db,'https://jsaidlerfotografia.com.br/');
site_url_check(communication_site_url($db)==='https://jsaidlerfotografia.com.br','saved HTTPS URL usable');
$GLOBALS['legacy_url']='https://legacy.example.test';
site_url_check(communication_site_url($db)==='https://jsaidlerfotografia.com.br','CMS configuration overrides legacy');
communication_site_url_save($db,'https://second.example.test');
site_url_check(communication_site_url($db)==='https://second.example.test','update persists');
(require dirname(__DIR__).'/migrations/107_communication_site_origin.php')($db);
site_url_check(communication_site_url($db)==='https://second.example.test','migration idempotent');
echo "site-url: OK\n";
