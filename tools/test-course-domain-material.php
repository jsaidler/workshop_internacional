<?php
declare(strict_types=1);
function fail_course_domain(string $message): never {fwrite(STDERR,"course-domain-material: $message\n");exit(1);}function must_course_domain(bool $ok,string $message): void {if(!$ok)fail_course_domain($message);}
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec(<<<'SQL'
CREATE TABLE activities(id INTEGER PRIMARY KEY);
CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,slug TEXT NOT NULL,title TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'active',parent_page_id INTEGER NULL,access_level TEXT NOT NULL DEFAULT 'public',published_document_json TEXT NULL);
CREATE TABLE cms_forms(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,title TEXT NOT NULL,form_key TEXT NOT NULL,purpose TEXT NOT NULL,status TEXT NOT NULL DEFAULT 'active');
CREATE TABLE cms_form_submissions(id INTEGER PRIMARY KEY,submission_uuid TEXT,form_id INTEGER,page_id INTEGER,activity_id INTEGER,locale TEXT,payload_json TEXT,form_snapshot_json TEXT,source_url TEXT,status TEXT,notes TEXT,consent INTEGER,ip_hash TEXT,user_agent_hash TEXT,created_at TEXT,updated_at TEXT,payment_status TEXT,payment_confirmed_at TEXT,payment_note TEXT,student_id INTEGER NULL,cohort_id INTEGER NULL);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,cohort_uuid TEXT,activity_id INTEGER,title TEXT,slug TEXT,status TEXT,is_registration_default INTEGER,starts_at TEXT,ends_at TEXT,created_at TEXT,updated_at TEXT,workshop_page_id INTEGER NULL);
CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,activity_id INTEGER,lesson_key TEXT,title TEXT,sort_order INTEGER,created_at TEXT,updated_at TEXT,workshop_page_id INTEGER NULL);
CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY AUTOINCREMENT,enrollment_uuid TEXT,student_id INTEGER,cohort_id INTEGER,source_submission_id INTEGER,status TEXT,confirmed_at TEXT,created_at TEXT,updated_at TEXT,UNIQUE(student_id,cohort_id));
CREATE TABLE cohort_lesson_releases(cohort_id INTEGER,lesson_id INTEGER,released_at TEXT,created_at TEXT,updated_at TEXT,PRIMARY KEY(cohort_id,lesson_id));
CREATE TABLE course_page_sections(page_id INTEGER,section_key TEXT,lesson_id INTEGER,created_at TEXT,updated_at TEXT,PRIMARY KEY(page_id,section_key));
INSERT INTO activities(id) VALUES(1);
INSERT INTO cms_pages(id,activity_id,locale,slug,title,status,parent_page_id,access_level,published_document_json) VALUES
 (10,1,'pt-BR','positivo-direto','Positivo Direto em Filmes de Raios-X','active',NULL,'public','{}'),
 (11,1,'pt-BR','material','Material','active',10,'activity','{}');
INSERT INTO cms_forms(id,activity_id,title,form_key,purpose,status) VALUES(20,1,'Inscrição','registration','enrollment','active');
INSERT INTO course_cohorts(id,cohort_uuid,activity_id,title,slug,status,is_registration_default,created_at,updated_at,workshop_page_id) VALUES(30,'c',1,'Teste','teste','active',1,'now','now',10);
INSERT INTO course_lessons(id,activity_id,lesson_key,title,sort_order,created_at,updated_at,workshop_page_id) VALUES(40,1,'aula-1','Aula 1',1,'now','now',10);
INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(11,'revelacao',40,'now','now');
INSERT INTO cms_form_submissions(id,submission_uuid,form_id,page_id,activity_id,locale,payload_json,form_snapshot_json,source_url,status,notes,consent,ip_hash,user_agent_hash,created_at,updated_at,payment_status,payment_confirmed_at,payment_note,student_id,cohort_id) VALUES(50,'s',20,10,1,'pt-BR','{}','{}','','converted','',1,'','', 'now','now','paid','now','',1,NULL);
SQL);
$migration=require __DIR__.'/../migrations/073_course_domain_material.php';$migration($db);
$course=$db->query('SELECT * FROM courses LIMIT 1')->fetch();must_course_domain((bool)$course,'course was not seeded');$courseId=(int)$course['id'];must_course_domain((int)$course['public_page_id']===10,'public page was not preserved');must_course_domain((int)$course['registration_form_id']===20,'enrollment form was not associated');
must_course_domain((int)$db->query('SELECT course_id FROM course_cohorts WHERE id=30')->fetchColumn()===$courseId,'cohort was not scoped to course');must_course_domain((int)$db->query('SELECT course_id FROM course_lessons WHERE id=40')->fetchColumn()===$courseId,'lesson was not scoped to course');must_course_domain((int)$db->query('SELECT course_id FROM cms_form_submissions WHERE id=50')->fetchColumn()===$courseId,'registration was not scoped to course');
must_course_domain((int)$db->query('SELECT COUNT(*) FROM course_material_pages WHERE course_id='.$courseId.' AND page_id=11')->fetchColumn()===1,'material page relation missing');must_course_domain((int)$db->query("SELECT COUNT(*) FROM course_material_sections WHERE course_id=$courseId AND page_id=11 AND section_key='revelacao' AND lesson_id=40")->fetchColumn()===1,'section-to-lesson relation missing');
$blocked=false;try{$db->exec("INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,source_submission_id,status,confirmed_at,created_at,updated_at) VALUES('e1',1,30,50,'active','now','now','now')");}catch(PDOException $e){$blocked=str_contains($e->getMessage(),'course_registration_requires_explicit_cohort');}must_course_domain($blocked,'confirmed registration without explicit cohort assignment was enrolled');
$db->exec('UPDATE cms_form_submissions SET cohort_id=30 WHERE id=50');$db->exec("INSERT INTO course_enrollments(enrollment_uuid,student_id,cohort_id,source_submission_id,status,confirmed_at,created_at,updated_at) VALUES('e2',1,30,50,'active','now','now','now')");must_course_domain((int)$db->query('SELECT COUNT(*) FROM course_enrollments')->fetchColumn()===1,'explicit cohort assignment did not allow enrollment');
$courses=file_get_contents(__DIR__.'/../admin/courses.php');$material=file_get_contents(__DIR__.'/../admin/material.php');$registrations=file_get_contents(__DIR__.'/../admin/registrations.php');$student=file_get_contents(__DIR__.'/../aluno/cursos.php');
must_course_domain(str_contains($courses,"admin_course_url(\$activityId,\$courseId,'material')"),'course catalog does not route to the filtered material collection');
must_course_domain(str_contains($material,'course_material_add_page'),'material collection does not consume material-page relation');
must_course_domain(str_contains($material,'/editor/?page='),'material editing does not route to the canonical CMS editor');
must_course_domain(str_contains($registrations,'Todos os cursos')&&!str_contains($registrations,'Escolha o curso'),'registrations are not exposed as a global course-filterable collection');
must_course_domain(str_contains($student,'course_lesson_release_rows_for_course'),'student workspace does not consume canonical course lessons');
echo "course-domain-material: ok\n";
