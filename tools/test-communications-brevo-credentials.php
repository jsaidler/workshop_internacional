<?php
declare(strict_types=1);
function brevo_check(bool $ok,string $why):void {if(!$ok){fwrite(STDERR,"brevo-credentials: $why\n");exit(1);}}
function utc_now():string {return gmdate('Y-m-d\TH:i:s\Z');}
$GLOBALS['credential_secret']=str_repeat('x',48);
function app_config():array {return [
    'app_secret'=>$GLOBALS['credential_secret'],
    'public_url'=>'https://example.test',
    'site_name'=>'Fotografia QA',
];}
putenv('BREVO_API_KEY=');
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
(require dirname(__DIR__).'/migrations/106_communication_brevo_credentials.php')($db);
require dirname(__DIR__).'/app/communications_brevo_credentials.php';
brevo_check(!communication_brevo_public_status($db)['configured'],'no secret is the initial state');
brevo_check(communication_brevo_key($db)==='','no fallback secret in test');
$first='xkeysib-test-secret-'.str_repeat('a',48);
$other='xkeysib-test-secret-'.str_repeat('b',48);
$verified=[];
$verifier=static function(string $key)use(&$verified):void {$verified[]=$key;};
communication_brevo_save($db,$first,$verifier);
$stored=communication_brevo_saved($db);
brevo_check(is_array($stored)&&$stored['ciphertext']!==$first,'must persist encrypted instead of plaintext');
brevo_check(strpos(json_encode($stored,JSON_THROW_ON_ERROR),$first)===false,'no raw API key in persisted row');
brevo_check(communication_brevo_key($db)===$first,'decrypt saved key');
brevo_check(communication_brevo_public_status($db)['managed_in_cms']===true,'CMS source detected');
brevo_check(communication_brevo_public_status($db)['verified_at']!==null,'verified date recorded');
brevo_check($verified===[$first],'verifier must run before saving');

$failed=false;
try{communication_brevo_save($db,'too-short',$verifier);}catch(RuntimeException){$failed=true;}
brevo_check($failed&&communication_brevo_key($db)===$first,'invalid key cannot erase working credential');
$failed=false;
try{communication_brevo_save($db,'xkeysib-'.str_repeat('a',20)."\nAnother: header",$verifier);}catch(RuntimeException){$failed=true;}
brevo_check($failed,'reject CRLF key');
$failed=false;
try{communication_brevo_save($db,$other,static function(string $key):void {throw new RuntimeException('Invalid authorization');});}
catch(RuntimeException){$failed=true;}
brevo_check($failed&&communication_brevo_key($db)===$first,'invalid API authorization must preserve original key');

$bad=$stored;$bad['auth_tag']=base64_encode(str_repeat('z',16));
$failed=false;
try{communication_brevo_decrypt($bad);}catch(RuntimeException){$failed=true;}
brevo_check($failed,'tampered GCM authentication tag rejected');
$original=$GLOBALS['credential_secret'];
$GLOBALS['credential_secret']=str_repeat('y',48);
$failed=false;
try{communication_brevo_key($db);}catch(RuntimeException){$failed=true;}
brevo_check($failed,'only this installation secret can open stored key');
$GLOBALS['credential_secret']=$original;

communication_brevo_save($db,$other,$verifier);
brevo_check(communication_brevo_key($db)===$other,'rotation uses new verified key');
$second=communication_brevo_saved($db);
brevo_check($second['ciphertext']!==$stored['ciphertext'],'rotation changes encrypted material');
communication_brevo_check_connection($db,$verifier);
brevo_check(end($verified)===$other,'connection check uses stored credential');
communication_brevo_remove($db);
brevo_check(communication_brevo_saved($db)===null&&!communication_brevo_public_status($db)['configured'],'removal deletes credential');

(require dirname(__DIR__).'/migrations/106_communication_brevo_credentials.php')($db);
brevo_check(communication_brevo_saved($db)===null,'migration is idempotent');
echo "brevo-credentials: OK\n";
