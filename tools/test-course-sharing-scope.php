<?php
declare(strict_types=1);
function fail_course_sharing(string $message): never {fwrite(STDERR,"course-sharing-scope: $message\n");exit(1);}function must_course_sharing(bool $ok,string $message): void {if(!$ok)fail_course_sharing($message);}
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec(<<<'SQL'
CREATE TABLE courses(id INTEGER PRIMARY KEY,title TEXT,status TEXT);
CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,title TEXT);
CREATE TABLE activities(id INTEGER PRIMARY KEY,public_title TEXT);
CREATE TABLE student_users(id INTEGER PRIMARY KEY,name TEXT,email TEXT);
CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,activity_id INTEGER,course_id INTEGER NULL,workshop_page_id INTEGER NULL,title TEXT,status TEXT);
CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT);
CREATE TABLE student_tests(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,visibility TEXT,updated_at TEXT);
CREATE TABLE student_test_media(id INTEGER PRIMARY KEY,test_id INTEGER);
INSERT INTO courses VALUES(1,'Curso A','active'),(2,'Curso B','active');
INSERT INTO cms_pages VALUES(10,'A'),(20,'B');
INSERT INTO activities VALUES(1,'Site');
INSERT INTO student_users VALUES(1,'Autor','a@x'),(2,'Leitor A','b@x'),(3,'Leitor B','c@x');
INSERT INTO course_cohorts VALUES(11,1,1,10,'Turma A1','active'),(12,1,1,10,'Turma A2','active'),(21,1,2,20,'Turma B','active');
INSERT INTO course_enrollments VALUES(1,1,11,'active'),(2,2,12,'active'),(3,3,21,'active');
INSERT INTO student_tests VALUES(100,1,11,'course','2026-01-01');
SQL);
function course_domain_available(PDO $db): bool{return true;}function workshop_course_scope_available(PDO $db): bool{return true;}
require __DIR__.'/../app/student_sharing.php';
$a=student_test_accessible_to_student($db,100,2);must_course_sharing((bool)$a,'student in same course cannot read course-visible test');$b=student_test_accessible_to_student($db,100,3);must_course_sharing($b===null,'student in different course can read course-visible test');must_course_sharing(student_test_visibility_label('course')==='Curso','course visibility still uses workshop terminology');
echo "course-sharing-scope: ok\n";
