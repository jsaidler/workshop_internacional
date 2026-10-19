<?php
declare(strict_types=1);

function resource_assert(bool $ok,string $message):void{if(!$ok)throw new RuntimeException('student-resource-pages: '.$message);}
function course_domain_available(PDO $db):bool{return true;}
function workshop_course_scope_available(PDO $db):bool{return false;}
function cms_page_workshop_root_id(PDO $db,array $page):int{return 0;}
function course_material_courses_for_page(PDO $db,int $pageId):array {
  $q=$db->prepare('SELECT c.* FROM course_material_pages m JOIN courses c ON c.id=m.course_id WHERE m.page_id=?');$q->execute([$pageId]);return $q->fetchAll(PDO::FETCH_ASSOC);
}
function course_enrollment_for_student(PDO $db,int $studentId,int $courseId,string $cohortUuid=''):?array {
  $q=$db->prepare("SELECT e.*,c.activity_id,c.course_id,c.cohort_uuid FROM course_enrollments e
    JOIN course_cohorts c ON c.id=e.cohort_id WHERE e.student_id=? AND c.course_id=? AND e.status='active'");
  $q->execute([$studentId,$courseId]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}
function course_material_page_is_associated(PDO $db,int $courseId,int $pageId):bool {
  $q=$db->prepare('SELECT 1 FROM course_material_pages WHERE course_id=? AND page_id=?');$q->execute([$courseId,$pageId]);return (bool)$q->fetchColumn();
}
function course_material_section_map(PDO $db,int $courseId,int $pageId):array {
  $q=$db->prepare('SELECT section_key,lesson_id FROM course_material_sections WHERE course_id=? AND page_id=?');$q->execute([$courseId,$pageId]);
  $out=[];foreach($q->fetchAll(PDO::FETCH_ASSOC) as $row)$out[$row['section_key']]=$row;return $out;
}
require __DIR__.'/../app/cms_access.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec(<<<'SQL'
PRAGMA foreign_keys=ON;
CREATE TABLE activities(id INTEGER PRIMARY KEY,slug TEXT,is_root INTEGER);
CREATE TABLE cms_pages(
 id INTEGER PRIMARY KEY AUTOINCREMENT,page_uuid TEXT UNIQUE,activity_id INTEGER,locale TEXT,
 slug TEXT,title TEXT,nav_title TEXT,status TEXT,is_home INTEGER,show_in_nav INTEGER,sort_order INTEGER,
 draft_document_json TEXT,published_document_json TEXT,draft_revision INTEGER,published_revision INTEGER,
 draft_updated_at TEXT,published_at TEXT,created_at TEXT,updated_at TEXT,access_level TEXT
);
CREATE TABLE courses(id INTEGER PRIMARY KEY,activity_id INTEGER,title TEXT,status TEXT);
CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,course_id INTEGER,title TEXT,sort_order INTEGER);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,activity_id INTEGER,course_id INTEGER,status TEXT,cohort_uuid TEXT);
CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT);
CREATE TABLE course_material_pages(course_id INTEGER,page_id INTEGER,sort_order INTEGER,created_at TEXT,updated_at TEXT,PRIMARY KEY(course_id,page_id));
CREATE TABLE course_material_sections(course_id INTEGER,page_id INTEGER,section_key TEXT,lesson_id INTEGER,created_at TEXT,updated_at TEXT,PRIMARY KEY(course_id,page_id,section_key));
CREATE TABLE cohort_lesson_releases(cohort_id INTEGER,lesson_id INTEGER,released_at TEXT,created_at TEXT,updated_at TEXT,PRIMARY KEY(cohort_id,lesson_id));
INSERT INTO activities VALUES(1,'',1);
INSERT INTO courses VALUES(2,1,'Positivo direto em filme de raio-X','active'),(3,1,'Fotografia experimental em grande formato','active');
INSERT INTO course_lessons VALUES(51,2,'Segunda aula',2),(50,2,'Primeira aula',1),(70,3,'Aula introdutória',1);
INSERT INTO course_cohorts VALUES(11,1,2,'active','cohort-rx'),(12,1,3,'active','cohort-other');
INSERT INTO course_enrollments VALUES(1,101,11,'active'),(2,102,12,'active'),(3,103,11,'inactive');
SQL);
$migration=require __DIR__.'/../migrations/100_student_materials_and_shared_bibliography.php';
$migration($db);
$q=$db->query("SELECT * FROM cms_pages WHERE slug='materiais-positivo-direto'");
$page=$q->fetch(PDO::FETCH_ASSOC);
resource_assert((bool)$page,'materials CMS page not created');
resource_assert($page['access_level']==='activity' && (int)$page['show_in_nav']===0,'materials page exposed in public navigation');
resource_assert((bool)$page['published_document_json'],'materials not published for release gating');
$material=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
resource_assert(str_contains($material['html'],'Bandejas de marmita')&&str_contains($material['html'],'600 ml'),'requested trays missing');
foreach(['Termômetro culinário','Béquer','1 litro','300 ml','bomba de vácuo','Sulfito de sódio','Hidróxido de sódio','Paracetamol','Cloreto férrico','Amônia','Peróxido de hidrogênio'] as $name)
    resource_assert(str_contains($material['html'],$name),'missing specified shopping material: '.$name);
resource_assert(substr_count($material['html'],'Onde procurar:')>=22,'shopping suggestion omitted for a material');
resource_assert(!str_contains($material['html'],'<style'),'page introduced a competing CSS system');
resource_assert((int)$db->query('SELECT COUNT(*) FROM course_material_pages')->fetchColumn()===1,'materials associated with another course');
resource_assert((int)$db->query('SELECT COUNT(*) FROM course_material_sections')->fetchColumn()===4,'all sections must be gated');
resource_assert((int)$db->query('SELECT COUNT(*) FROM course_material_sections WHERE lesson_id=50')->fetchColumn()===4,'not gated by FIRST lesson');
resource_assert((string)$db->query('SELECT released_at FROM cohort_lesson_releases WHERE cohort_id=11 AND lesson_id=50')->fetchColumn()==='','migration must not release a lesson');

$book=$db->query("SELECT * FROM cms_pages WHERE slug='bibliografia'")->fetch(PDO::FETCH_ASSOC);
resource_assert((bool)$book&&$book['access_level']==='authenticated'&&(int)$book['show_in_nav']===0,'bibliography not privately available to all students');
resource_assert((int)$db->query("SELECT COUNT(*) FROM course_material_pages m JOIN cms_pages p ON p.id=m.page_id WHERE p.slug='bibliografia'")->fetchColumn()===0,'bibliography restricted to one course');
$b=json_decode((string)$book['published_document_json'],true,512,JSON_THROW_ON_ERROR);
resource_assert(str_contains($b['html'],'TAYLOR, J. Traill')&&str_contains($b['html'],'CRAIG, C. C. Craig Film Holder'),'bibliography lost original endpoints');
resource_assert(str_contains($b['html'],'https://patents.google.com/patent/US2431356'),'original bibliographic source URL missing');
resource_assert(substr_count($b['html'],'data-cms-editable')>=40,'bibliography was truncated');

$activity=['id'=>1];$owner=['id'=>101];$otherCourse=['id'=>102];$inactive=['id'=>103];
resource_assert(cms_access_page_allowed($db,$activity,$page,$owner),'owner cannot open their course page');
resource_assert(!cms_access_page_allowed($db,$activity,$page,$otherCourse),'other course got access to course materials');
resource_assert(!cms_access_page_allowed($db,$activity,$page,$inactive),'inactive enrollment got access');
resource_assert(!cms_access_page_allowed($db,$activity,$page,null),'anonymous access to course page');
resource_assert(cms_access_page_allowed($db,$activity,$book,$owner) && cms_access_page_allowed($db,$activity,$book,$otherCourse),'shared bibliography incorrectly scoped to course');
resource_assert(!cms_access_page_allowed($db,$activity,$book,null),'bibliography anonymous access');
$context=['student_id'=>101,'activity_id'=>1,'course_id'=>2,'cohort_id'=>11,'material_page_id'=>(int)$page['id']];
$before=cms_access_filter_html($db,$activity,$material['html'],$owner,false,null,$context);
resource_assert(!str_contains($before,'Bandejas de marmita')&&!str_contains($before,'Cloreto férrico'),'unreleased content leaked into HTML');
$db->exec("UPDATE cohort_lesson_releases SET released_at='2020-01-01T00:00:00Z' WHERE cohort_id=11 AND lesson_id=50");
$after=cms_access_filter_html($db,$activity,$material['html'],$owner,false,null,$context);
resource_assert(str_contains($after,'Bandejas de marmita')&&str_contains($after,'Cloreto férrico'),'first-lesson release did not expose materials');

$beforeCount=(int)$db->query('SELECT COUNT(*) FROM cms_pages')->fetchColumn();
$hash=hash('sha256',(string)$page['published_document_json']);
$migration($db);
resource_assert((int)$db->query('SELECT COUNT(*) FROM cms_pages')->fetchColumn()===$beforeCount,'second migration duplicated pages');
$again=$db->query("SELECT published_document_json FROM cms_pages WHERE slug='materiais-positivo-direto'")->fetchColumn();
resource_assert(hash('sha256',(string)$again)===$hash,'second migration rewrote published content');
resource_assert((string)$db->query('SELECT released_at FROM cohort_lesson_releases WHERE cohort_id=11 AND lesson_id=50')->fetchColumn()==='2020-01-01T00:00:00Z','migration overwrote cohort release state');

echo "student-resource-pages: ok\n";
