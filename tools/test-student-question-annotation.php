<?php
declare(strict_types=1);
function fail_question_annotation(string $message): never {fwrite(STDERR,"student-question-annotation: $message\n");exit(1);}
function must_question_annotation(bool $ok,string $message): void {if(!$ok)fail_question_annotation($message);}
function utc_now(): string {return '2026-10-01T12:00:00Z';}
require dirname(__DIR__).'/app/student_question_annotations.php';

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec('CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,title TEXT,activity_id INTEGER)');
$db->exec('CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,activity_id INTEGER,course_id INTEGER)');
$db->exec('CREATE TABLE course_material_pages(course_id INTEGER,page_id INTEGER)');
$db->exec('CREATE TABLE student_material_annotations(id INTEGER PRIMARY KEY,student_id INTEGER,page_id INTEGER,body TEXT,quote_exact TEXT)');
$db->exec('CREATE TABLE student_questions(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,source_annotation_id INTEGER,updated_at TEXT)');
$db->exec("INSERT INTO cms_pages VALUES(11,'Material',1)");
$db->exec('INSERT INTO course_cohorts VALUES(3,1,2)');
$db->exec('INSERT INTO course_cohorts VALUES(4,1,9)');
$db->exec('INSERT INTO course_material_pages VALUES(2,11)');
$db->exec("INSERT INTO student_material_annotations VALUES(5,7,11,'Minha anotação','Trecho estudado')");
$db->exec("INSERT INTO student_questions VALUES(8,7,3,NULL,'2026-10-01')");

$source=student_question_annotation_source($db,7,3,5);
must_question_annotation(is_array($source)&&(int)$source['id']===5,'owned annotation is not accepted for its course');
must_question_annotation(student_question_annotation_source($db,7,4,5)===null,'annotation leaked into an unrelated course');
student_question_attach_annotation($db,8,7,3,5);
$q=$db->query('SELECT source_annotation_id FROM student_questions WHERE id=8');
must_question_annotation((int)$q->fetchColumn()===5,'question did not retain its source annotation');
$linked=student_question_from_annotation($db,7,5);
must_question_annotation(is_array($linked)&&(int)$linked['id']===8,'annotation does not resolve back to its question');
$context=student_question_source_context($db,$linked);
must_question_annotation(is_array($context)&&(string)$context['quote_exact']==='Trecho estudado','question lost the selected material context');
echo "student-question-annotation: ok\n";
