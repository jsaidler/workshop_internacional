<?php
declare(strict_types=1);
function email_assert(bool $ok,string $name):void {if(!$ok){fwrite(STDERR,"email-design: ".$name."\n");exit(1);}}
function app_config():array {return ['site_name'=>'Ateliê fotográfico'];}
function communication_site_url(PDO $db):string {return 'https://workshop.example.test';}
function media_url(string $path):string {return '/uploads/media/'.ltrim($path,'/');}
require dirname(__DIR__).'/app/communications_rich_text.php';
require dirname(__DIR__).'/app/communications_email_design.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE media_assets(id INTEGER PRIMARY KEY,kind TEXT,visibility TEXT,archived_at TEXT,processing_status TEXT,active_version_id INTEGER,title TEXT,default_alt TEXT,width INTEGER);
CREATE TABLE media_versions(id INTEGER PRIMARY KEY,processing_status TEXT,original_path TEXT);
CREATE TABLE media_derivatives(version_id INTEGER,mime_type TEXT,width INTEGER,path TEXT);
INSERT INTO media_versions VALUES(4,'ready','aaa/bbb/original/file.jpg');
INSERT INTO media_assets VALUES(7,'image','public',NULL,'ready',4,'Retrato','Uma pessoa sob a luz',640);
INSERT INTO media_derivatives VALUES(4,'image/jpeg',768,'aaa/bbb/responsive/768.jpg');");
$email='<p>Antes da imagem</p><figure data-email-media-id="7" data-email-alt="Retrato de uma pessoa"><img src="javascript:alert(2)"></figure><script>alert(1)</script><p>Depois</p>';
$safe=communication_safe_html($email);
email_assert(str_contains($safe,'data-email-media-id="7"'),'structured media token retained');
email_assert(!str_contains($safe,'javascript')&&!str_contains($safe,'script'),'unsafe image and script removed');
communication_validate_images($db,$safe);
email_assert(str_contains(communication_html_text($safe),'Imagem: Retrato'),'text version has image description');
foreach(array_keys(communication_template_keys()) as $template){
 $doc=communication_email_document($safe,'Antes da imagem',$db,$template);
 email_assert(str_contains($doc,'https://workshop.example.test/uploads/media/aaa/bbb/responsive/768.jpg'),'image resolved from authorized media');
 email_assert(str_contains($doc,'alt="Retrato de uma pessoa"'),'image has alt text');
 email_assert(str_contains($doc,'Ateliê fotográfico'),'site brand visible');
 email_assert(!str_contains($doc,'javascript:')&&!str_contains($doc,'<script'),'sanitization after rendering');
}
$cta=communication_safe_html('<h2>Título</h2><p><a data-email-button="1" href="https://example.test/inscricao">Participar</a></p><hr><a href="javascript:alert(1)">Inseguro</a>');
email_assert(str_contains($cta,'data-email-button="1"')&&str_contains($cta,'<hr>'),'heading divider and CTA retained');
email_assert(!str_contains($cta,'javascript:'),'unsafe CTA URL rejected');
$styled=communication_email_document($cta,'Participar',$db,'destaque');
email_assert(str_contains($styled,'display:inline-block')&&str_contains($styled,'border-top:1px'),'email blocks styled safely');
$invalid=false;
try{communication_template_key('bad');}catch(RuntimeException){$invalid=true;}
email_assert($invalid,'unknown template rejected');
$inbox='Mensagem importante & privada <não exibir como HTML>';
$withPreview=communication_email_document('<p>Conteúdo real</p>','Conteúdo real',$db,'editorial',0,$inbox);
email_assert(str_contains($withPreview,'aria-hidden="true"')&&str_contains($withPreview,'Mensagem importante &amp; privada &lt;não exibir como HTML&gt;'),'inbox preview escaped and hidden');
email_assert(str_contains($withPreview,'Conteúdo real'),'preheader does not replace message content');
email_assert(!str_contains(communication_email_document('<p>Normal</p>','Normal',$db),'aria-hidden="true"'),'legacy emails contain no preheader wrapper');
$invalid=false;try{communication_preheader(str_repeat('a',151));}catch(RuntimeException){$invalid=true;}
email_assert($invalid,'overlong preheader rejected');
$invalid=false;try{communication_preheader("Cabeçalho\r\nBcc: leak@example.test");}catch(RuntimeException){$invalid=true;}
email_assert($invalid,'line breaks and header injection rejected');
$db->exec("UPDATE media_assets SET visibility='private' WHERE id=7");
$invalid=false;try{communication_validate_images($db,$safe);}catch(RuntimeException){$invalid=true;}
email_assert($invalid,'private media cannot enter outgoing email');
$db->exec("UPDATE media_assets SET visibility='public',archived_at='2026-01-01' WHERE id=7");
$invalid=false;try{communication_email_document($safe,'',$db,'editorial');}catch(RuntimeException){$invalid=true;}
email_assert($invalid,'archived media cannot render');
$db->exec("CREATE TABLE communication_campaigns(id INTEGER PRIMARY KEY)");
(require dirname(__DIR__).'/migrations/109_communication_email_templates.php')($db);
(require dirname(__DIR__).'/migrations/109_communication_email_templates.php')($db);
(require dirname(__DIR__).'/migrations/110_communication_preheader.php')($db);
(require dirname(__DIR__).'/migrations/110_communication_preheader.php')($db);
$cols=array_column($db->query('PRAGMA table_info(communication_campaigns)')->fetchAll(PDO::FETCH_ASSOC),'name');
email_assert(in_array('template_key',$cols,true),'migration 109 idempotent');
email_assert(in_array('preheader',$cols,true),'migration 110 idempotent');
$db->exec("INSERT INTO communication_campaigns(id) VALUES(9)");
email_assert((string)$db->query("SELECT preheader FROM communication_campaigns WHERE id=9")->fetchColumn()==='','historical campaigns default to empty preheader');
echo "email-design: OK\n";
