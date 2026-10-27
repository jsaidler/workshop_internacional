<?php
declare(strict_types=1);
function draft_check(bool $ok,string $message):void {
    if(!$ok)throw new RuntimeException('builder draft: '.$message);
}
function utc_now():string{return gmdate('Y-m-d\TH:i:s\Z');}
function course_agenda_with_lock(PDO $db,callable $operation):mixed {
    $db->exec('BEGIN IMMEDIATE');
    try{$result=$operation();$db->exec('COMMIT');return $result;}
    catch(Throwable $e){$db->exec('ROLLBACK');throw $e;}
}
function communication_site_url(PDO $db):string{return 'https://example.test';}
function media_url(string $path):string{return '/uploads/media/'.ltrim($path,'/');}
function communication_media_asset(PDO $db,int $id):array {
    if($id!==7)throw new RuntimeException('Imagem não disponível.');
    return ['email_path'=>'photography.png'];
}
require dirname(__DIR__).'/app/communications_builder_drafts.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec("CREATE TABLE communication_campaigns(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,status TEXT NOT NULL,subject TEXT NOT NULL,body_html TEXT NOT NULL,body_text TEXT NOT NULL);
INSERT INTO communication_campaigns VALUES(1,1,'draft','Mensagem antiga','<p>Original intocado</p>','Original intocado');
INSERT INTO communication_campaigns VALUES(2,2,'draft','Outro administrador','<p>Fora da área</p>','Fora da área');
INSERT INTO communication_campaigns VALUES(3,1,'queued','Já enfileirada','<p>Enviada</p>','Enviada');");
(require dirname(__DIR__).'/migrations/112_communication_builder_drafts.php')($db);
(require dirname(__DIR__).'/migrations/112_communication_builder_drafts.php')($db);
$doc='<mjml><mj-head><mj-attributes><mj-all font-family="Arial,Helvetica,sans-serif"/></mj-attributes></mj-head>'
  .'<mj-body background-color="#f1f0ed"><mj-section background-color="#ffffff" padding="14px 0">'
  .'<mj-column><mj-text font-size="16px" color="#202427">Primeira composição</mj-text></mj-column>'
  .'</mj-section></mj-body></mjml>';
$blank=communication_builder_draft_read($db,1,1);
draft_check($blank['exists']===false&&$blank['revision']===0,'new drafts start empty');
$first=communication_builder_draft_write($db,1,1,$doc,[],0);
draft_check($first['revision']===1,'first write increments revision');
$stored=communication_builder_draft_read($db,1,1);
draft_check($stored['exists']===true&&str_contains($stored['mjml'],'Primeira composição'),'document round-trips from database');
$second=communication_builder_draft_write($db,1,1,str_replace('Primeira','Segunda',$doc),[],1);
draft_check($second['revision']===2&&str_contains(communication_builder_draft_read($db,1,1)['mjml'],'Segunda'),'subsequent write persists');
$failed=false;
try{communication_builder_draft_write($db,1,1,$doc,[],1);}catch(RuntimeException $e){$failed=str_contains($e->getMessage(),'outra aba');}
draft_check($failed,'stale tab cannot overwrite latest revision');
draft_check(communication_builder_draft_read($db,1,1)['revision']===2,'conflicting write changes nothing');
foreach([[2,1],[1,2],[3,1]] as [$campaign,$activity]){
    $rejected=false;
    try{communication_builder_draft_write($db,$activity,$campaign,$doc,[],0);}catch(RuntimeException){$rejected=true;}
    draft_check($rejected,'cross-activity/queued writes forbidden');
}
$image='<mj-image src="https://example.test/uploads/media/photography.png" alt="Bancada do laboratório" padding="14px 0"/>';
$withImage=str_replace('<mj-text font-size="16px" color="#202427">Primeira composição</mj-text>',$image,$doc);
$next=communication_builder_draft_write($db,1,1,$withImage,['https://example.test/uploads/media/photography.png'=>7],2);
draft_check($next['revision']===3,'public library image accepted');
$refs=communication_builder_draft_read($db,1,1)['media_refs'];
draft_check($refs['https://example.test/uploads/media/photography.png']===7,'image identity retained for later revalidation');
$rejected=false;
try{communication_builder_draft_write($db,1,1,$withImage,['https://example.test/uploads/media/photography.png'=>8],3);}catch(RuntimeException){$rejected=true;}
draft_check($rejected,'wrong image version/ID refused');
$row=$db->query('SELECT body_html,body_text,status FROM communication_campaigns WHERE id=1')->fetch();
draft_check($row['body_html']==='<p>Original intocado</p>'&&$row['body_text']==='Original intocado'&&$row['status']==='draft',
 'legacy body, delivery status untouched');
$db->exec('DELETE FROM communication_campaigns WHERE id=1');
draft_check((int)$db->query('SELECT COUNT(*) FROM communication_builder_drafts')->fetchColumn()===0,'deleted campaign cascades editable document');
echo "builder drafts: versioning, isolation, conflicts, immutability OK\n";
