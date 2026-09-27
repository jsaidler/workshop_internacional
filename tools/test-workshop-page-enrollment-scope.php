<?php
declare(strict_types=1);

function fail_workshop_scope(string $message): never {fwrite(STDERR,"workshop-page-scope: $message\n");exit(1);}
function must_workshop_scope(bool $condition,string $message): void {if(!$condition)fail_workshop_scope($message);}

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec("CREATE TABLE activities(id INTEGER PRIMARY KEY); INSERT INTO activities VALUES(1);");
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,parent_page_id INTEGER NULL,status TEXT NOT NULL DEFAULT 'active',title TEXT,slug TEXT); INSERT INTO cms_pages VALUES(10,1,'pt-BR',NULL,'active','Workshop A','a'); INSERT INTO cms_pages VALUES(11,1,'pt-BR',10,'active','Inscrição A','inscricao-a'); INSERT INTO cms_pages VALUES(20,1,'pt-BR',NULL,'active','Workshop B','b'); INSERT INTO cms_pages VALUES(21,1,'pt-BR',20,'active','Inscrição B','inscricao-b');");
$db->exec("CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY AUTOINCREMENT,cohort_uuid TEXT UNIQUE,activity_id INTEGER NOT NULL,title TEXT,slug TEXT,status TEXT,is_registration_default INTEGER,created_at TEXT,updated_at TEXT); CREATE UNIQUE INDEX idx_course_cohorts_activity_slug ON course_cohorts(activity_id,slug); CREATE UNIQUE INDEX idx_course_cohorts_default ON course_cohorts(activity_id) WHERE is_registration_default=1 AND status!='archived'; INSERT INTO course_cohorts VALUES(1,'c1',1,'Turma atual','turma-atual','active',1,'x','x');");
$db->exec("CREATE TABLE course_lessons(id INTEGER PRIMARY KEY AUTOINCREMENT,activity_id INTEGER NOT NULL,lesson_key TEXT,title TEXT,sort_order INTEGER,created_at TEXT,updated_at TEXT); CREATE UNIQUE INDEX idx_course_lessons_activity_key ON course_lessons(activity_id,lesson_key); INSERT INTO course_lessons VALUES(1,1,'aula-1','Aula 1',1,'x','x');");
$db->exec("CREATE TABLE cms_form_submissions(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,page_id INTEGER NULL,cohort_id INTEGER NULL); INSERT INTO cms_form_submissions VALUES(1,1,11,1);");
$db->exec("CREATE TABLE course_page_sections(page_id INTEGER,section_key TEXT,lesson_id INTEGER,PRIMARY KEY(page_id,section_key)); INSERT INTO course_page_sections VALUES(10,'aula',1);");

$migration=require dirname(__DIR__).'/migrations/072_workshop_page_student_scope.php';$migration($db);
$cohort=$db->query('SELECT * FROM course_cohorts WHERE id=1')->fetch();$lesson=$db->query('SELECT * FROM course_lessons WHERE id=1')->fetch();
must_workshop_scope((int)$cohort['workshop_page_id']===10,'cohort was not deterministically backfilled from registration page hierarchy');
must_workshop_scope((int)$lesson['workshop_page_id']===10,'lesson was not deterministically backfilled from mapped material page');
$db->exec("INSERT INTO course_cohorts(cohort_uuid,activity_id,workshop_page_id,title,slug,status,is_registration_default,created_at,updated_at) VALUES('c2',1,20,'Turma atual','turma-atual','active',1,'x','x')");
$db->exec("INSERT INTO course_lessons(activity_id,workshop_page_id,lesson_key,title,sort_order,created_at,updated_at) VALUES(1,20,'aula-1','Aula 1',1,'x','x')");
must_workshop_scope((int)$db->query('SELECT COUNT(*) FROM course_cohorts')->fetchColumn()===2,'two workshops in one site cannot have independent default cohorts');
must_workshop_scope((int)$db->query('SELECT COUNT(*) FROM course_lessons')->fetchColumn()===2,'two workshops in one site cannot reuse lesson keys');

$enrollments=(string)file_get_contents(dirname(__DIR__).'/app/student_enrollments.php');$sharing=(string)file_get_contents(dirname(__DIR__).'/app/student_sharing.php');$index=(string)file_get_contents(dirname(__DIR__).'/aluno/index.php');
must_workshop_scope(str_contains($enrollments,'workshop_course_submission_root'),'registration reconciliation does not resolve workshop from submission page');
must_workshop_scope(str_contains($enrollments,'workshop_course_default_cohort'),'registration reconciliation still assigns activity-wide default cohorts');
must_workshop_scope(str_contains($enrollments,'student_enrollment_list'),'student workspace has no workshop-scoped enrollment list');
must_workshop_scope(str_contains($sharing,'ec.workshop_page_id=c.workshop_page_id'),'course-level test sharing is not constrained to the workshop');
must_workshop_scope(str_contains($index,"workshop_course_lesson_release_rows"),'student dashboard still reads activity-wide lessons');

echo "workshop-page-scope: ok\n";
