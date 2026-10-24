<?php
declare(strict_types=1);
function current_admin():array{return ['id'=>1,'email'=>'admin-only@example.test'];}
function communication_site_url(PDO $db):string{return 'https://example.test';}
function media_url(string $path):string{return '/uploads/media/'.ltrim($path,'/');}
function communication_media_asset(PDO $db,int $id):array {throw new RuntimeException('No image expected in this test.');}
require dirname(__DIR__).'/app/communications_builder_test_send.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec("CREATE TABLE communication_campaigns(id INTEGER PRIMARY KEY,activity_id INTEGER,status TEXT,subject TEXT);
INSERT INTO communication_campaigns VALUES(1,9,'draft','Experimento fotográfico');
INSERT INTO communication_campaigns VALUES(2,8,'draft','Área diferente');
INSERT INTO communication_campaigns VALUES(3,9,'queued','Já na fila');");
$source='<mjml><mj-body><mj-section><mj-column><mj-text>Mensagem <strong>experimental</strong></mj-text>'
    .'<mj-button href="https://example.test/test">Ver detalhes</mj-button></mj-column></mj-section></mj-body></mjml>';
$payloads=[];
$fake=static function(array $payload)use(&$payloads):array{$payloads[]=$payload;return ['id'=>'fake-provider-accept'];};
$result=communication_builder_send_admin_test($db,9,1,$source,[],$fake);
if($result['accepted']!==true||$result['recipient']!=='admin-only@example.test'||count($payloads)!==1)
    throw new RuntimeException('Admin-only test did not send exactly once.');
$payload=$payloads[0];
if($payload['email']!=='admin-only@example.test'||$payload['subject']!=='[TESTE VISUAL] Experimento fotográfico')
    throw new RuntimeException('Visual test subject/recipient not locked to the administrator.');
if(!str_contains($payload['server_rendered_html'],'<table')
   ||!str_contains($payload['server_rendered_html'],'Mensagem <strong>experimental</strong>')
   ||!str_contains($payload['body'],'Nenhum aluno foi notificado'))
    throw new RuntimeException('Server HTML/plain text transport does not match canonical builder renderer.');
if($result['html_sha256']!==hash('sha256',$payload['server_rendered_html']))
    throw new RuntimeException('Visual proof checksum mismatch.');
foreach([[9,2],[9,3],[8,1]] as [$activity,$campaign]){
    $failed=false;
    try{communication_builder_send_admin_test($db,$activity,$campaign,$source,[],$fake);}
    catch(RuntimeException){$failed=true;}
    if(!$failed)throw new RuntimeException('Cross-activity/queued campaign allowed to send visual test.');
}
if(count($payloads)!==1)throw new RuntimeException('Invalid visual test reached transport.');
echo "builder admin visual proof: recipient, scope, canonical HTML OK\n";
