<?php
declare(strict_types=1);
function fail(string $message): never {fwrite(STDERR,"student-handbook: $message\n");exit(1);}
function cms_page_document(array $doc=[]): array {return ['version'=>2,'theme'=>$doc['theme']??'auto','meta'=>$doc['meta']??['title'=>'','description'=>''],'html'=>(string)($doc['html']??'')];}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE activities(id INTEGER PRIMARY KEY AUTOINCREMENT,slug TEXT,admin_name TEXT,public_title TEXT,status TEXT,is_root INTEGER); INSERT INTO activities(slug,admin_name,public_title,status,is_root) VALUES('direct-positive','Direct Positive Workshop','Direct Positive Workshop','active',1);");
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY AUTOINCREMENT,page_uuid TEXT,activity_id INTEGER,locale TEXT,slug TEXT,title TEXT,nav_title TEXT,status TEXT,is_home INTEGER,show_in_nav INTEGER,sort_order INTEGER,draft_document_json TEXT,published_document_json TEXT,draft_revision INTEGER,published_revision INTEGER,draft_updated_at TEXT,published_at TEXT,created_at TEXT,updated_at TEXT,access_level TEXT DEFAULT 'public');");
$db->exec("CREATE TABLE cms_page_seo(page_id INTEGER PRIMARY KEY,title TEXT,description TEXT,social_title TEXT,social_description TEXT,social_image TEXT,canonical_url TEXT,robots TEXT,updated_at TEXT);");
$db->exec("CREATE TABLE course_lessons(id INTEGER PRIMARY KEY AUTOINCREMENT,activity_id INTEGER,lesson_key TEXT,title TEXT,sort_order INTEGER,created_at TEXT,updated_at TEXT); INSERT INTO course_lessons(activity_id,lesson_key,title,sort_order,created_at,updated_at) VALUES(1,'aula-1','Aula 1',1,'x','x'),(1,'aula-2','Aula 2',2,'x','x'),(1,'aula-3','Aula 3',3,'x','x');");
$db->exec("CREATE TABLE course_page_sections(page_id INTEGER,section_key TEXT,lesson_id INTEGER,created_at TEXT,updated_at TEXT,PRIMARY KEY(page_id,section_key));");
$migration=require __DIR__.'/../migrations/048_seed_positive_handbook.php';$migration($db);
$clarify=require __DIR__.'/../migrations/049_clarify_handbook_dilution_records.php';$clarify($db);
$page=$db->query("SELECT * FROM cms_pages WHERE slug='caderno-positivo-direto'")->fetch();if(!$page)fail('page not seeded');
if($page['access_level']!=='enrolled')fail('page is not protected');if((int)$page['show_in_nav']!==0)fail('protected page exposed in nav');
$doc=json_decode((string)$page['published_document_json'],true);$html=(string)($doc['html']??'');
foreach(['Positivo direto','Fuji','Parodinal','Brewed','FeCl','Quatro','Apenas dois momentos decisivos'] as $needle)if(!str_contains($html,$needle))fail('missing content: '.$needle);
if(str_contains(mb_strtolower($html,'UTF-8'),'boliche tonal'))fail('reserved terminology leaked into handbook');
if(!str_contains($html,'data-cms-section="caderno-01-principio"')||!str_contains($html,'data-cms-section="caderno-18-final"'))fail('section identities missing');
if(!str_contains($html,'O resumo de receitas define as diluições como volume final de 550 ml')||!str_contains($html,'10 ml ou 20 ml de Parodinal + 550 ml de água'))fail('dilution source discrepancy not preserved');
$seo=$db->query('SELECT robots FROM cms_page_seo WHERE page_id='.(int)$page['id'])->fetchColumn();if($seo!=='noindex,nofollow')fail('seo protection missing');
$maps=$db->query('SELECT s.section_key,l.lesson_key FROM course_page_sections s JOIN course_lessons l ON l.id=s.lesson_id ORDER BY s.section_key')->fetchAll(PDO::FETCH_KEY_PAIR);
if(($maps['caderno-03-exposicao']??'')!=='aula-1')fail('exposure not mapped to lesson 1');if(($maps['caderno-08-parodinal']??'')!=='aula-2')fail('chemistry not mapped to lesson 2');
echo "student-handbook: ok\n";
