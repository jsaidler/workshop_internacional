<?php
declare(strict_types=1);

function fail_page_access(string $message): never {fwrite(STDERR,"cms-page-access: $message\n");exit(1);}
function must_page_access(bool $condition,string $message): void {if(!$condition)fail_page_access($message);}
function utc_now(): string {return gmdate('c');}
function app_config(): array {return ['timezone'=>'UTC'];}
function course_cohort_by_id(PDO $db,int $id): ?array {$q=$db->prepare('SELECT * FROM course_cohorts WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function cms_page_by_id(PDO $db,int $id): ?array {$q=$db->prepare('SELECT * FROM cms_pages WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
// This fixture intentionally verifies the legacy activity/cohort page-access fallback.
// Canonical course/material access has dedicated course-domain regressions.
function course_domain_available(PDO $db): bool{return false;}
function cms_page_workshop_root_id(PDO $db,array $page): int{return 0;}
function workshop_course_scope_available(PDO $db): bool{return false;}
function workshop_lesson_scope_available(PDO $db): bool{return false;}

$root=dirname(__DIR__);
require $root.'/app/cms_access.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,access_level TEXT NOT NULL DEFAULT 'public',show_in_nav INTEGER NOT NULL DEFAULT 1,updated_at TEXT NOT NULL)");
$db->exec("CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,title TEXT,status TEXT NOT NULL,cohort_uuid TEXT)");
$db->exec("CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,cohort_id INTEGER NOT NULL,status TEXT NOT NULL,confirmed_at TEXT)");
$db->exec("INSERT INTO cms_pages(id,activity_id,access_level,show_in_nav,updated_at) VALUES(1,10,'public',1,'now')");
$db->exec("INSERT INTO course_cohorts(id,activity_id,title,status,cohort_uuid) VALUES(101,10,'Turma A','active','a'),(102,10,'Turma B','active','b'),(201,20,'Outro curso','active','c')");
$db->exec("INSERT INTO course_enrollments(id,student_id,cohort_id,status,confirmed_at) VALUES(1,1,101,'active','2026-01-01'),(2,2,102,'active','2026-01-01'),(3,3,201,'active','2026-01-01')");
(require $root.'/migrations/070_cms_page_cohort_access.php')($db);
$columns=array_column($db->query('PRAGMA table_info(cms_pages)')->fetchAll(PDO::FETCH_ASSOC),'name');must_page_access(in_array('access_cohort_id',$columns,true),'migration did not add access_cohort_id');

$activity=['id'=>10];$anon=null;$studentA=['id'=>1];$studentB=['id'=>2];$other=['id'=>3];$page=cms_page_by_id($db,1)??fail_page_access('page missing');
must_page_access(cms_access_page_allowed($db,$activity,$page,$anon),'public page denied anonymous visitor');
$page=cms_access_set_page($db,$page,'authenticated');must_page_access(!cms_access_page_allowed($db,$activity,$page,$anon)&&cms_access_page_allowed($db,$activity,$page,$other),'authenticated page does not distinguish login from enrollment');
$page=cms_access_set_page($db,$page,'activity');must_page_access(cms_access_page_allowed($db,$activity,$page,$studentA)&&cms_access_page_allowed($db,$activity,$page,$studentB)&&!cms_access_page_allowed($db,$activity,$page,$other),'activity access does not require enrollment in the current activity');
$page=cms_access_set_page($db,$page,'cohort',101);must_page_access(cms_access_page_cohort_id($page)===101,'cohort id was not persisted');must_page_access(cms_access_page_allowed($db,$activity,$page,$studentA)&&!cms_access_page_allowed($db,$activity,$page,$studentB),'cohort page is not restricted to the selected cohort');
$foreignRejected=false;try{cms_access_set_page($db,$page,'cohort',201);}catch(RuntimeException){$foreignRejected=true;}must_page_access($foreignRejected,'cohort from another activity was accepted');
$missingRejected=false;try{cms_access_set_page($db,$page,'cohort',null);}catch(RuntimeException){$missingRejected=true;}must_page_access($missingRejected,'cohort access without cohort was accepted');
$page=cms_access_set_page($db,$page,'public',101);must_page_access(cms_access_page_cohort_id($page)===0,'cohort id was not cleared when page became public');

$editor=(string)file_get_contents($root.'/editor/cms-access-controls.js');$saveApi=(string)file_get_contents($root.'/admin/api/cms-page-access-save.php');$optionsApi=(string)file_get_contents($root.'/admin/api/cms-access-options.php');
must_page_access(str_contains($editor,'<option value="cohort">Turma específica</option>')&&str_contains($editor,'cms-page-access-cohort'),'page editor does not expose cohort access');
must_page_access(str_contains($saveApi,'cms_access_set_page'),'page access endpoint bypasses canonical setter');
must_page_access(str_contains($optionsApi,"'pageCohortId'=>cms_access_page_cohort_id"),'editor options do not return page cohort');
must_page_access(cms_access_page_label('cohort')==='Turma específica','cohort label missing');

echo "cms-page-access: ok\n";