<?php
declare(strict_types=1);
/** Visual fixture generated from the real CMS migration document. No fake text. */
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec(<<<'SQL'
CREATE TABLE activities(id INTEGER PRIMARY KEY,slug TEXT,is_root INTEGER);
CREATE TABLE cms_pages(id INTEGER PRIMARY KEY AUTOINCREMENT,page_uuid TEXT UNIQUE,activity_id INTEGER,locale TEXT,slug TEXT,title TEXT,nav_title TEXT,status TEXT,is_home INTEGER,show_in_nav INTEGER,sort_order INTEGER,draft_document_json TEXT,published_document_json TEXT,draft_revision INTEGER,published_revision INTEGER,draft_updated_at TEXT,published_at TEXT,created_at TEXT,updated_at TEXT,access_level TEXT);
CREATE TABLE courses(id INTEGER PRIMARY KEY,activity_id INTEGER,title TEXT,status TEXT);
CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,course_id INTEGER,title TEXT,sort_order INTEGER);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER,status TEXT);
CREATE TABLE course_material_pages(course_id INTEGER,page_id INTEGER,sort_order INTEGER,created_at TEXT,updated_at TEXT,PRIMARY KEY(course_id,page_id));
CREATE TABLE course_material_sections(course_id INTEGER,page_id INTEGER,section_key TEXT,lesson_id INTEGER,created_at TEXT,updated_at TEXT,PRIMARY KEY(course_id,page_id,section_key));
CREATE TABLE cohort_lesson_releases(cohort_id INTEGER,lesson_id INTEGER,released_at TEXT,created_at TEXT,updated_at TEXT,PRIMARY KEY(cohort_id,lesson_id));
INSERT INTO activities VALUES(1,'',1);
INSERT INTO courses VALUES(1,1,'Positivo Direto em Filme de Raio-X','active');
INSERT INTO course_lessons VALUES(1,1,'Primeira aula',1);
INSERT INTO course_cohorts VALUES(1,1,'active');
INSERT INTO cms_pages(page_uuid,activity_id,locale,slug,title,nav_title,status,is_home,show_in_nav,sort_order,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at,access_level)
 VALUES('canonical-study',1,'pt-BR','caderno-positivo-direto','Caderno de Positivo Direto','Material','active',0,0,1,'{"html":"<section></section>"}','{"html":"<section></section>"}',1,1,'','','','','activity');
INSERT INTO course_material_pages VALUES(1,1,1,'2026-01-01','2026-01-01');
SQL);
$migration=require __DIR__.'/../../migrations/100_student_materials_and_shared_bibliography.php';
$migration($db);
$revision=require __DIR__.'/../../migrations/101_student_materials_review_draft.php';
$revision($db);
$which=($_GET['page']??'materials')==='bibliography'?'bibliografia':'materiais-positivo-direto';
$q=$db->prepare("SELECT * FROM cms_pages WHERE slug=? AND locale='pt-BR'");$q->execute([$which]);$page=$q->fetch();
if(!$page){http_response_code(404);exit('Página não criada pela migração.');}
// Materials are intentionally an unpublished review draft; the fixture
// previews the draft in isolation without changing public access semantics.
$previewColumn=$which==='materiais-positivo-direto'?'draft_document_json':'published_document_json';
$document=json_decode((string)$page[$previewColumn],true,512,JSON_THROW_ON_ERROR);
$title=htmlspecialchars((string)$page['title'],ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
?>
<!doctype html><html lang="pt-BR" data-theme="<?=htmlspecialchars((string)($_GET['theme']??'light'))?>"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/cms-content-layout.css"><title><?=$title?></title></head>
<body class="cms-public" data-resource="<?=htmlspecialchars($which)?>"><main><?= $document['html'] ?></main></body></html>
