<?php
declare(strict_types=1);

function fail(string $message): never {fwrite(STDERR,"student-accounts: $message\n");exit(1);}
function utc_now(): string {return gmdate('c');}
function activity_slug(string $value): string {$value=strtolower(trim((string)preg_replace('~[^a-z0-9]+~i','-',$value)));return trim($value,'-')?:'item';}
function app_config(): array {return ['app_secret'=>str_repeat('a',40),'ip_hash_secret'=>str_repeat('b',40)];}
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'csrf-'.$scope;}
function cms_page_document(array $document): array {return ['version'=>2,'theme'=>$document['theme']??'auto','meta'=>$document['meta']??['title'=>'','description'=>''],'html'=>(string)($document['html']??'')];}
function cms_page_by_id(PDO $db,int $id): ?array {$q=$db->prepare('SELECT * FROM cms_pages WHERE id=?');$q->execute([$id]);return $q->fetch()?:null;}
function cms_page_doc(array $page,bool $published=true): array {$json=$published?($page['published_document_json']??null):($page['draft_document_json']??null);$decoded=json_decode((string)$json,true);return is_array($decoded)?$decoded:[];}
function cms_form_by_key(PDO $db,int $activityId,string $locale,string $key): ?array {$q=$db->prepare('SELECT * FROM cms_forms WHERE activity_id=? AND locale=? AND form_key=?');$q->execute([$activityId,$locale,$key]);return $q->fetch()?:null;}
const PUBLIC_LOCALE_PT_BR='pt-BR';const PUBLIC_LOCALE_EN='en';

$_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['REQUEST_METHOD']='GET';$_SESSION=[];
require __DIR__.'/../app/student_auth.php';
require __DIR__.'/../app/student_accounts.php';
require __DIR__.'/../app/student_enrollments.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);$db->exec('PRAGMA foreign_keys=ON');
$db->exec("CREATE TABLE activities(id INTEGER PRIMARY KEY AUTOINCREMENT,admin_name TEXT NOT NULL,public_title TEXT NOT NULL,slug TEXT NOT NULL UNIQUE,status TEXT NOT NULL DEFAULT 'active',is_root INTEGER NOT NULL DEFAULT 0);INSERT INTO activities(admin_name,public_title,slug,status,is_root) VALUES('Curso','Curso','curso','active',1);");
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY AUTOINCREMENT,page_uuid TEXT NOT NULL UNIQUE,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,slug TEXT NOT NULL DEFAULT '',title TEXT NOT NULL,nav_title TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'active',is_home INTEGER NOT NULL DEFAULT 0,show_in_nav INTEGER NOT NULL DEFAULT 1,sort_order INTEGER NOT NULL DEFAULT 0,draft_document_json TEXT NOT NULL,published_document_json TEXT NULL,draft_revision INTEGER NOT NULL DEFAULT 1,published_revision INTEGER NULL,draft_updated_at TEXT NOT NULL,published_at TEXT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$db->exec("CREATE TABLE cms_forms(id INTEGER PRIMARY KEY AUTOINCREMENT,form_uuid TEXT NOT NULL UNIQUE,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,form_key TEXT NOT NULL,title TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'active',purpose TEXT NOT NULL DEFAULT 'common',draft_schema_json TEXT NOT NULL,published_schema_json TEXT NULL,draft_revision INTEGER NOT NULL DEFAULT 1,published_revision INTEGER NULL,draft_updated_at TEXT NOT NULL,published_at TEXT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$db->exec("CREATE TABLE cms_form_submissions(id INTEGER PRIMARY KEY AUTOINCREMENT,submission_uuid TEXT NOT NULL UNIQUE,form_id INTEGER NOT NULL,page_id INTEGER NULL,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,payload_json TEXT NOT NULL,form_snapshot_json TEXT NOT NULL,source_url TEXT NOT NULL DEFAULT '',status TEXT NOT NULL DEFAULT 'new',notes TEXT NOT NULL DEFAULT '',consent INTEGER NOT NULL DEFAULT 0,ip_hash TEXT NOT NULL,user_agent_hash TEXT NOT NULL,payment_status TEXT NOT NULL DEFAULT 'pending',payment_confirmed_at TEXT NULL,payment_note TEXT NOT NULL DEFAULT '',created_at TEXT NOT NULL,updated_at TEXT NOT NULL);");
$m46=require __DIR__.'/../migrations/046_student_area.php';$m46($db);$m47=require __DIR__.'/../migrations/047_student_accounts_cohorts_privacy.php';$m47($db);

$now=utc_now();$schema=json_encode(['fields'=>[]],JSON_THROW_ON_ERROR);$db->prepare('INSERT INTO cms_forms(form_uuid,activity_id,locale,form_key,title,purpose,draft_schema_json,published_schema_json,draft_updated_at,published_at,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute(['form1',1,'pt-BR','registration','Inscrição','enrollment',$schema,$schema,$now,$now,$now,$now]);$formId=(int)$db->lastInsertId();
$payload=['name'=>'Aluno Teste','cpf'=>'123.456.789-00','phone'=>'24999999999','email'=>'aluno@example.com','instagram'=>'@aluno','address'=>'Rua Um, 10','city_state'=>'Petrópolis/RJ','postal_code'=>'25600-000'];
$db->prepare("INSERT INTO cms_form_submissions(submission_uuid,form_id,activity_id,locale,payload_json,form_snapshot_json,status,consent,ip_hash,user_agent_hash,payment_status,payment_confirmed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,'converted',1,'ip','ua','paid',?,?,?)")->execute(['sub1',$formId,1,'pt-BR',json_encode($payload,JSON_THROW_ON_ERROR),$schema,$now,$now,$now]);
$result=student_account_reconcile_confirmed_registrations($db,1);if(($result['processed']??0)!==1)fail('confirmed registration was not reconciled');$user=$db->query('SELECT * FROM student_users')->fetch();if(!$user)fail('student account not created');if(password_verify('12345678900',(string)$user['password_hash']))fail('CPF was stored as password');$profile=student_account_profile($db,(int)$user['id']);if(($profile['cpf']??'')!=='12345678900')fail('encrypted CPF did not roundtrip');if(($profile['phone']??'')!=='24999999999')fail('profile data missing');if(!student_account_has_activity($db,(int)$user['id'],1))fail('confirmed enrollment missing');

$activation=student_account_begin_activation($db,'aluno@example.com','12345678900');if($activation!=='ok')fail('first access identity proof failed');student_account_complete_activation($db,'uma-senha-segura-123',true);$activated=$db->query('SELECT * FROM student_users')->fetch();if(empty($activated['activated_at'])||!password_verify('uma-senha-segura-123',(string)$activated['password_hash']))fail('activation did not create personal password');if(password_verify('12345678900',(string)$activated['password_hash']))fail('CPF still authenticates as password');

// Regression: assigning a previously known student runs profile reconciliation
// inside the same SQLite BEGIN IMMEDIATE used to transfer the registration.
$updatePayload=$payload;
$updatePayload['name']='Aluno Atualizado';
$updatePayload['phone']='24888888888';
$updateSubmission=['payload_json'=>json_encode($updatePayload,JSON_THROW_ON_ERROR)];
$studentId=(int)$user['id'];
// The original nested-transaction regression concerns profiles not activated yet.
$db->prepare('UPDATE student_users SET activated_at=NULL WHERE id=?')->execute([$studentId]);
$db->exec('CREATE TABLE profile_tx_probe(id INTEGER PRIMARY KEY,note TEXT NOT NULL)');
$db->exec('BEGIN IMMEDIATE');
try{
    $db->exec("INSERT INTO profile_tx_probe VALUES(1,'outer transaction')");
    if(student_account_upsert_from_submission($db,$updateSubmission)!==$studentId)fail('existing student identity not reused inside BEGIN IMMEDIATE');
    if((string)student_account_profile($db,$studentId)['phone']!=='24888888888')fail('profile was not updated inside BEGIN IMMEDIATE');
    $db->exec('ROLLBACK');
}catch(Throwable $e){
    try{$db->exec('ROLLBACK');}catch(Throwable){}
    fail('profile reconciliation started a nested transaction: '.$e->getMessage());
}
if((int)$db->query('SELECT COUNT(*) FROM profile_tx_probe')->fetchColumn()!==0)fail('profile update committed the caller transaction');
if((string)student_account_profile($db,$studentId)['phone']!=='24999999999')fail('caller rollback did not restore the profile');
if((string)$db->query('SELECT name FROM student_users WHERE id='.$studentId)->fetchColumn()!=='Aluno Teste')fail('caller rollback did not restore the student identity');

// On a profile write failure, only the profile savepoint rolls back.
// The caller must retain control over the outer transaction and its own writes.
$db->exec("CREATE TRIGGER profile_tx_fail BEFORE UPDATE ON student_profiles WHEN NEW.phone='force_profile_error' BEGIN SELECT RAISE(ABORT,'forced_profile_failure'); END");
$invalidPayload=$updatePayload;
$invalidPayload['phone']='force_profile_error';
$db->exec('BEGIN IMMEDIATE');
try{
    $db->exec("INSERT INTO profile_tx_probe VALUES(2,'survives profile rollback')");
    $failed=false;
    try{student_account_upsert_from_submission($db,['payload_json'=>json_encode($invalidPayload,JSON_THROW_ON_ERROR)]);}
    catch(Throwable $e){if(!str_contains($e->getMessage(),'forced_profile_failure'))throw $e;$failed=true;}
    if(!$failed)fail('profile failure trigger did not fire');
    if((string)$db->query('SELECT name FROM student_users WHERE id='.$studentId)->fetchColumn()!=='Aluno Teste')fail('profile savepoint did not roll back student name');
    if((string)student_account_profile($db,$studentId)['phone']!=='24999999999')fail('profile savepoint did not roll back profile data');
    $db->exec('COMMIT');
}catch(Throwable $e){
    try{$db->exec('ROLLBACK');}catch(Throwable){}
    fail('profile savepoint escaped the caller transaction: '.$e->getMessage());
}
$db->exec('DROP TRIGGER profile_tx_fail');
if((int)$db->query('SELECT COUNT(*) FROM profile_tx_probe WHERE id=2')->fetchColumn()!==1)fail('profile failure rolled back unrelated outer changes');

// The same helper must also commit when it is called without an outer transaction.
if(student_account_upsert_from_submission($db,$updateSubmission)!==$studentId)fail('standalone profile reconciliation changed identity');
if((string)student_account_profile($db,$studentId)['phone']!=='24888888888')fail('standalone profile update not committed');
student_account_upsert_from_submission($db,['payload_json'=>json_encode($payload,JSON_THROW_ON_ERROR)]);
$db->prepare('UPDATE student_users SET activated_at=? WHERE id=?')->execute([$activated['activated_at'],$studentId]);

// Once activated, the account is authoritative: paid submission data is historical.
$owned=$payload;
$owned['name']='Nome escolhido na conta';
$owned['email']='novo-email@example.com';
$owned['phone']='24887776666';
student_account_update_profile($db,$studentId,$owned);
$protectedBefore=student_account_profile($db,$studentId);
student_account_reconcile_confirmed_registrations($db,1);
$protectedAfter=student_account_profile($db,$studentId);
if($protectedAfter['name']!==$owned['name']||$protectedAfter['email']!==$owned['email']||$protectedAfter['phone']!==$owned['phone'])fail('reconciliation reverted activated account changes');
if($protectedAfter['cpf_ciphertext']!==$protectedBefore['cpf_ciphertext'])fail('reconciliation re-encrypted an activated account without authorization');
$linked=student_enrollment_student_from_submission($db,['student_id'=>$studentId,'payload_json'=>json_encode($payload,JSON_THROW_ON_ERROR)]);
if($linked!==$studentId||student_account_profile($db,$studentId)['email']!==$owned['email'])fail('bound submission overwrote activated account email');
if(!student_account_login($db,$owned['email'],'uma-senha-segura-123'))fail('updated email cannot sign in with its existing password');
if(student_account_profile($db,$studentId)['email']!==$owned['email'])fail('sign-in reverted profile email from old submission');

// Automatic identity discovery requires the same existing account by both CPF and e-mail.
$protectedSnapshot=student_account_profile($db,$studentId);$partial=$payload;$partial['email']='partial@example.com';$partial['phone']='24000000001';
$partialRejected=false;try{student_account_upsert_from_submission($db,['payload_json'=>json_encode($partial,JSON_THROW_ON_ERROR)]);}catch(RuntimeException $e){$partialRejected=$e->getMessage()==='student_identity_partial_match';}
if(!$partialRejected)fail('CPF-only match was accepted as an existing identity');
if(student_account_profile($db,$studentId)!=$protectedSnapshot)fail('partial identity match changed the activated account');

$emailOnly=$owned;$emailOnly['cpf']='111.444.777-35';$emailOnly['phone']='24000000004';
$emailOnlyRejected=false;try{student_account_upsert_from_submission($db,['payload_json'=>json_encode($emailOnly,JSON_THROW_ON_ERROR)]);}catch(RuntimeException $e){$emailOnlyRejected=$e->getMessage()==='student_identity_partial_match';}
if(!$emailOnlyRejected)fail('e-mail-only match was accepted as an existing identity');
if(student_account_profile($db,$studentId)!=$protectedSnapshot)fail('e-mail-only identity match changed the activated account');

$secondPayload=['name'=>'Segunda Pessoa','cpf'=>'987.654.321-00','phone'=>'24000000002','email'=>'segunda@example.com','instagram'=>'','address'=>'Rua Dois, 20','city_state'=>'Niterói/RJ','postal_code'=>'24000-000'];
$secondId=student_account_upsert_from_submission($db,['payload_json'=>json_encode($secondPayload,JSON_THROW_ON_ERROR)]);
$secondBefore=student_account_profile($db,$secondId);$partialBound=$secondPayload;$partialBound['email']='email-nao-confirmado@example.com';$partialBound['phone']='24000000999';
$boundRejected=false;try{student_enrollment_student_from_submission($db,['student_id'=>$secondId,'payload_json'=>json_encode($partialBound,JSON_THROW_ON_ERROR)]);}catch(RuntimeException $e){$boundRejected=$e->getMessage()==='student_identity_partial_match';}
if(!$boundRejected)fail('partially matching payload changed an unactivated bound account');
if(student_account_profile($db,$secondId)!=$secondBefore)fail('bound partial identity match changed the unactivated profile');

$conflict=$payload;$conflict['email']=$secondPayload['email'];$conflictRejected=false;
try{student_account_upsert_from_submission($db,['payload_json'=>json_encode($conflict,JSON_THROW_ON_ERROR)]);}catch(RuntimeException $e){$conflictRejected=$e->getMessage()==='student_identity_conflict';}
if(!$conflictRejected)fail('conflicting CPF and e-mail accounts were associated');
if(student_account_profile($db,$studentId)!=$protectedSnapshot||student_account_profile($db,$secondId)!=$secondBefore)fail('identity conflict changed an existing account');

// A downstream enrollment failure must roll back the newly created account and submission links.
$atomicPayload=['name'=>'Falha Atômica','cpf'=>'111.222.333-44','phone'=>'24000000003','email'=>'atomic@example.com','instagram'=>'','address'=>'Rua Três, 30','city_state'=>'Rio de Janeiro/RJ','postal_code'=>'20000-000'];
$db->prepare("INSERT INTO cms_form_submissions(submission_uuid,form_id,activity_id,locale,payload_json,form_snapshot_json,status,consent,ip_hash,user_agent_hash,payment_status,payment_confirmed_at,created_at,updated_at) VALUES(?,?,?,?,?,?,'converted',1,'ip','ua','paid',?,?,?)")->execute(['sub-atomic',$formId,1,'pt-BR',json_encode($atomicPayload,JSON_THROW_ON_ERROR),$schema,$now,$now,$now]);$atomicSubmissionId=(int)$db->lastInsertId();
$db->exec("CREATE TRIGGER enrollment_atomic_fail BEFORE INSERT ON course_enrollments WHEN NEW.student_id NOT IN ($studentId,$secondId) BEGIN SELECT RAISE(ABORT,'forced_enrollment_failure'); END");
$atomicResult=student_account_reconcile_confirmed_registrations($db,1);$db->exec('DROP TRIGGER enrollment_atomic_fail');
if(!array_filter($atomicResult['errors']??[],static fn(array $error): bool=>str_contains((string)($error['error']??''),'forced_enrollment_failure')))fail('forced enrollment failure was not reported');
if((int)$db->query("SELECT COUNT(*) FROM student_users WHERE email='atomic@example.com'")->fetchColumn()!==0)fail('failed reconciliation left a partial account');
$atomicStored=$db->query('SELECT student_id,cohort_id FROM cms_form_submissions WHERE id='.$atomicSubmissionId)->fetch();if(!empty($atomicStored['student_id'])||!empty($atomicStored['cohort_id']))fail('failed reconciliation left submission identity or cohort links');
$db->prepare("UPDATE cms_form_submissions SET status='archived',payment_status='pending' WHERE id=?")->execute([$atomicSubmissionId]);

$document=['version'=>2,'theme'=>'auto','meta'=>['title'=>'Material','description'=>''],'html'=>'<section data-cms-section="intro" data-cms-section-name="Introdução"><p>ABERTO</p></section><section data-cms-section="conteudo-aula-1"><p>AULA1</p></section><section data-cms-section="conteudo-aula-2"><p>AULA2</p></section>'];$json=json_encode($document,JSON_THROW_ON_ERROR);$db->prepare('INSERT INTO cms_pages(page_uuid,activity_id,locale,slug,title,nav_title,status,is_home,show_in_nav,sort_order,draft_document_json,published_document_json,draft_updated_at,published_at,created_at,updated_at,access_level) VALUES(?,?,?,?,?,?,"active",0,0,1,?,?,?,?,?,?,"enrolled")')->execute(['page1',1,'pt-BR','material','Material','Material',$json,$json,$now,$now,$now,$now]);$pageId=(int)$db->lastInsertId();$lessons=course_lessons($db,1);if(count($lessons)<2)fail('default lessons missing');student_page_set_section_lesson($db,$pageId,'conteudo-aula-1',(int)$lessons[0]['id']);student_page_set_section_lesson($db,$pageId,'conteudo-aula-2',(int)$lessons[1]['id']);$enrollment=student_account_enrollment_for_activity($db,(int)$user['id'],1);if(!$enrollment)fail('page access enrollment missing');course_set_lesson_release($db,(int)$enrollment['cohort_id'],(int)$lessons[0]['id'],true);$page=cms_page_by_id($db,$pageId);$filtered=student_page_filter_document($db,$page,$document,$enrollment);if(!str_contains($filtered['html'],'ABERTO')||!str_contains($filtered['html'],'AULA1')||str_contains($filtered['html'],'AULA2'))fail('server-side lesson filtering failed');

$uuid=str_repeat('c',32);student_private_media_mkdir();$relative=$uuid.'.jpg';file_put_contents(student_private_media_root().'/'.$relative,'fake');$db->prepare('INSERT INTO student_private_media(asset_uuid,activity_id,page_id,title,original_name,mime_type,byte_size,storage_path,checksum,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)')->execute([$uuid,1,$pageId,'Imagem','imagem.jpg','image/jpeg',4,$relative,hash('sha256','fake'),$now,$now]);$expires=time()+60;$sig=student_private_media_sig((int)$user['id'],$uuid,$pageId,(int)$enrollment['cohort_id'],$expires);if(!student_private_media_authorize($db,$user,$uuid,$pageId,(int)$enrollment['cohort_id'],$expires,$sig))fail('signed private media authorization failed');if(student_private_media_authorize($db,$user,$uuid,$pageId,(int)$enrollment['cohort_id'],$expires,str_repeat('0',64)))fail('invalid private media signature accepted');@unlink(student_private_media_root().'/'.$relative);

echo "student-accounts: ok\n";
