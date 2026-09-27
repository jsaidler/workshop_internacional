<?php
declare(strict_types=1);

function fail_section_access(string $message): never {fwrite(STDERR,"cms-section-access: $message\n");exit(1);}
function must_section_access(bool $condition,string $message): void {if(!$condition)fail_section_access($message);}
function utc_now(): string {return gmdate('c');}
function app_config(): array {return ['timezone'=>'UTC'];}
function course_cohort_by_id(PDO $db,int $id): ?array {$q=$db->prepare('SELECT * FROM course_cohorts WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function cms_page_by_id(PDO $db,int $id): ?array {return null;}
// This fixture intentionally covers the legacy activity/cohort section-access fallback.
// Canonical course/material access has dedicated course-domain regressions.
function course_domain_available(PDO $db): bool{return false;}
function workshop_course_scope_available(PDO $db): bool{return false;}
function workshop_lesson_scope_available(PDO $db): bool{return false;}

$root=dirname(__DIR__);require $root.'/app/cms_access.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,title TEXT,status TEXT NOT NULL,cohort_uuid TEXT)");
$db->exec("CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL,cohort_id INTEGER NOT NULL,status TEXT NOT NULL,confirmed_at TEXT)");
$db->exec("CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,title TEXT,lesson_key TEXT)");
$db->exec("CREATE TABLE cohort_lesson_releases(cohort_id INTEGER NOT NULL,lesson_id INTEGER NOT NULL,released_at TEXT,PRIMARY KEY(cohort_id,lesson_id))");
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER,access_level TEXT,access_cohort_id INTEGER,show_in_nav INTEGER,updated_at TEXT)");
$db->exec("INSERT INTO course_cohorts VALUES(101,10,'Turma A','active','a'),(102,10,'Turma B','active','b'),(201,20,'Outra','active','c')");
$db->exec("INSERT INTO course_enrollments VALUES(1,1,101,'active','2026-01-01'),(2,2,102,'active','2026-01-01')");
$db->exec("INSERT INTO course_lessons VALUES(301,10,'Aula 1','aula-1'),(401,20,'Outra aula','outra')");
$db->exec("INSERT INTO cohort_lesson_releases VALUES(101,301,'2026-01-01T00:00:00Z'),(102,301,NULL)");
$page=['id'=>1,'activity_id'=>10];$activity=['id'=>10];

$valid=['html'=>'<section data-cms-section="s" data-cms-access="authenticated"><p>ok</p></section>'];cms_access_validate_document($db,$page,$valid);
$cohort=['html'=>'<section data-cms-section="s" data-cms-access="cohort" data-cms-cohort-id="101"><p>ok</p></section>'];cms_access_validate_document($db,$page,$cohort);
$lesson=['html'=>'<section data-cms-section="s" data-cms-availability="lesson" data-cms-lesson-id="301"><p>ok</p></section>'];cms_access_validate_document($db,$page,$lesson);
$scheduled=['html'=>'<section data-cms-section="s" data-cms-availability="scheduled" data-cms-visible-from="2026-09-26T12:00"><p>ok</p></section>'];cms_access_validate_document($db,$page,$scheduled);

foreach([
 ['html'=>'<section data-cms-section="s" data-cms-access="cohort" data-cms-cohort-id="201"></section>','label'=>'foreign cohort'],
 ['html'=>'<section data-cms-section="s" data-cms-availability="lesson" data-cms-lesson-id="401"></section>','label'=>'foreign lesson'],
 ['html'=>'<section data-cms-section="s" data-cms-availability="scheduled"></section>','label'=>'empty schedule'],
 ['html'=>'<section data-cms-section="s" data-cms-availability="scheduled" data-cms-visible-from="2026-09-27T12:00" data-cms-visible-until="2026-09-26T12:00"></section>','label'=>'reversed schedule'],
 ['html'=>'<section data-cms-section="s" data-cms-access="garbage"></section>','label'=>'invalid audience'],
] as $case){$rejected=false;try{cms_access_validate_document($db,$page,$case);}catch(RuntimeException){$rejected=true;}must_section_access($rejected,$case['label'].' was accepted');}

$unknownHtml='<section data-cms-section="x" data-cms-access="garbage">SECRET</section>';$filtered=cms_access_filter_html($db,$activity,$unknownHtml,['id'=>1],false,strtotime('2026-09-26T12:00:00Z'));must_section_access(!str_contains($filtered,'SECRET'),'unknown audience fails open');
$cohortLesson=['access'=>'cohort','cohort_id'=>101,'availability'=>'lesson','lesson_id'=>301,'visible_from'=>'','visible_until'=>''];must_section_access(cms_access_section_allowed($db,$activity,$cohortLesson,['id'=>1],strtotime('2026-09-26T12:00:00Z')),'released lesson denied selected cohort member');must_section_access(!cms_access_section_allowed($db,$activity,$cohortLesson,['id'=>2],strtotime('2026-09-26T12:00:00Z')),'lesson/cohort rule used enrollment from a different cohort');

$saveApi=(string)file_get_contents($root.'/admin/api/cms-page-save.php');$publishApi=(string)file_get_contents($root.'/admin/api/cms-page-publish.php');$editor=(string)file_get_contents($root.'/editor/cms-access-controls.js');
must_section_access(str_contains($saveApi,'cms_access_validate_document'),'draft save bypasses section access validation');must_section_access(str_contains($publishApi,'cms_access_validate_document'),'publish bypasses section access validation');must_section_access(str_contains($editor,'data-cms-access')&&str_contains($editor,'data-cms-availability')&&str_contains($editor,'data-cms-lesson-id'),'editor does not persist section access/lesson attributes in the CMS document');

echo "cms-section-access: ok\n";