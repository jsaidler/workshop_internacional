<?php
declare(strict_types=1);
function fail_question_annotation(string $message): never {fwrite(STDERR,"student-question-annotation: $message\n");exit(1);}
function must_question_annotation(bool $ok,string $message): void {if(!$ok)fail_question_annotation($message);}
function utc_now(): string {return '2026-10-01T12:00:00Z';}
function student_workspace_text(mixed $value,int $max=5000): string {$value=trim((string)$value);return mb_strlen($value)>$max?mb_substr($value,0,$max):$value;}
function student_question_create(PDO $db,int $studentId,int $cohortId,array $input): array {
    $db->prepare("INSERT INTO student_questions(student_id,cohort_id,source_annotation_id,topic,title,body,visibility,status,updated_at) VALUES(?,?,NULL,?,?,?,?, 'open',?)")->execute([$studentId,$cohortId,(string)($input['topic']??''),(string)($input['title']??''),(string)($input['body']??''),(string)($input['visibility']??'private'),utc_now()]);
    $q=$db->prepare('SELECT * FROM student_questions WHERE id=?');$q->execute([(int)$db->lastInsertId()]);return $q->fetch(PDO::FETCH_ASSOC)?:[];
}
function course_domain_available(PDO $db): bool {return true;}
require dirname(__DIR__).'/app/student_enrollments.php';
require dirname(__DIR__).'/app/student_workbench_hardening.php';
require dirname(__DIR__).'/app/student_question_annotations.php';

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec('CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,title TEXT,activity_id INTEGER)');
$db->exec('CREATE TABLE activities(id INTEGER PRIMARY KEY,public_title TEXT,admin_name TEXT,slug TEXT,is_root INTEGER)');
$db->exec('CREATE TABLE courses(id INTEGER PRIMARY KEY,title TEXT,public_page_id INTEGER)');
$db->exec('CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,activity_id INTEGER,course_id INTEGER,title TEXT,cohort_uuid TEXT,status TEXT)');
$db->exec('CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT,confirmed_at TEXT)');
$db->exec('CREATE TABLE course_material_pages(course_id INTEGER,page_id INTEGER)');
$db->exec('CREATE TABLE student_material_annotations(id INTEGER PRIMARY KEY,student_id INTEGER,page_id INTEGER,body TEXT,quote_exact TEXT)');
$db->exec('CREATE TABLE student_questions(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER,cohort_id INTEGER,source_annotation_id INTEGER,topic TEXT,title TEXT,body TEXT,visibility TEXT,status TEXT,updated_at TEXT)');
$db->exec("INSERT INTO cms_pages VALUES(11,'Material',1)");
$db->exec("INSERT INTO activities VALUES(1,'Site','Site','site',1)");
$db->exec("INSERT INTO courses VALUES(2,'Curso A',11)");
$db->exec("INSERT INTO courses VALUES(9,'Curso independente',NULL)");
$db->exec("INSERT INTO course_cohorts VALUES(3,1,2,'Turma original','turma-a','active')");
$db->exec("INSERT INTO course_cohorts VALUES(5,1,2,'Turma posterior','turma-b','active')");
$db->exec("INSERT INTO course_cohorts VALUES(4,1,9,'Outro curso','turma-outra','active')");
$db->exec("INSERT INTO course_enrollments VALUES(1,7,3,'active','2026-10-01')");
$db->exec("INSERT INTO course_enrollments VALUES(2,7,5,'active','2026-10-02')");
$db->exec("INSERT INTO course_enrollments VALUES(3,7,4,'active','2026-10-03')");
$db->exec('INSERT INTO course_material_pages VALUES(2,11)');
$db->exec("INSERT INTO student_material_annotations VALUES(5,7,11,'Minha anotação','Trecho estudado')");
$db->exec("INSERT INTO student_material_annotations VALUES(6,7,11,'Por que isso acontece?','Outro trecho')");
$db->exec("INSERT INTO student_material_annotations VALUES(7,7,11,'Outra dúvida sobre o filme','Terceiro trecho')");
$db->exec("INSERT INTO student_questions(id,student_id,cohort_id,source_annotation_id,topic,title,body,visibility,status,updated_at) VALUES(8,7,3,NULL,'Material','Questão anterior','Corpo','private','open','2026-10-01')");

$source=student_question_annotation_source($db,7,3,5);
must_question_annotation(is_array($source)&&(int)$source['id']===5,'owned annotation is not accepted for its course');
$originPage=student_question_source_page($source,[['id'=>9,'locale'=>'pt-BR'],['id'=>11,'locale'=>'pt-BR']]);
must_question_annotation(is_array($originPage)&&(int)$originPage['id']===11,'linked annotation did not select its real source page');
must_question_annotation(student_question_source_page($source,[['id'=>9,'locale'=>'pt-BR']])===null,'linked annotation exposed a page outside the selected course');
must_question_annotation(student_question_source_page(['page_id'=>901],[['id'=>11]])===null,'foreign material page was accepted');
must_question_annotation(student_question_source_page(null,[['id'=>11]])===null,'missing material source generated a page');

must_question_annotation(student_question_annotation_source($db,7,4,5)===null,'annotation leaked into an unrelated course');
student_question_attach_annotation($db,8,7,3,5);
$q=$db->query('SELECT source_annotation_id FROM student_questions WHERE id=8');
must_question_annotation((int)$q->fetchColumn()===5,'question did not retain its source annotation');
$linked=student_question_from_annotation($db,7,5);
must_question_annotation(is_array($linked)&&(int)$linked['id']===8,'annotation does not resolve back to its question');
$context=student_question_source_context($db,$linked);
must_question_annotation(is_array($context)&&(string)$context['quote_exact']==='Trecho estudado','question lost the selected material context');

$created=student_question_create_from_annotation($db,7,3,6,['question_title'=>'Dúvida criada na anotação','question_visibility'=>'cohort']);
must_question_annotation((int)($created['source_annotation_id']??0)===6,'same-screen question was not linked to its annotation');
must_question_annotation((string)($created['title']??'')==='Dúvida criada na anotação','same-screen question lost its title');
must_question_annotation((string)($created['body']??'')==='Por que isso acontece?','same-screen question did not reuse annotation body');
must_question_annotation((string)($created['visibility']??'')==='cohort','same-screen question lost selected visibility');
$again=student_question_create_from_annotation($db,7,3,6,['question_title'=>'Não duplicar','question_visibility'=>'private']);
must_question_annotation((int)$again['id']===(int)$created['id'],'same annotation created a duplicate question');
$courseShared=student_question_create_from_annotation($db,7,3,7,['question_title'=>'Pergunta para todas as turmas','question_visibility'=>'course']);
must_question_annotation((string)($courseShared['visibility']??'')==='course','annotation did not persist visibility for all cohorts in the course');
must_question_annotation((int)($courseShared['source_annotation_id']??0)===7,'course-shared question lost its source annotation');
$questionsRoute=(string)file_get_contents(dirname(__DIR__).'/aluno/duvidas.php');
must_question_annotation(str_contains($questionsRoute,'student_question_source_page(')
    &&str_contains($questionsRoute,'student_enrollment_material_context(')
    &&str_contains($questionsRoute,'cms_access_page_allowed(')
    &&str_contains($questionsRoute,"['cohort_uuid']"),
    'material origin deep link must preserve the selected enrollment and CMS authorization');
// A personal annotation is shared across the student's enrollments, but the
// linked question must use a cohort where that conversation can actually open.
$private=$db->query('SELECT * FROM student_questions WHERE id=8')->fetch(PDO::FETCH_ASSOC);
must_question_annotation(student_question_annotation_url($db,7,$private,'turma-b')==='/aluno/duvidas.php?cohort=turma-a&id=8','private question incorrectly linked through another cohort');
must_question_annotation(student_question_annotation_url($db,7,$private,'turma-outra')==='/aluno/duvidas.php?cohort=turma-a&id=8','other course incorrectly replaced the question origin');
must_question_annotation(student_question_annotation_url($db,999,$private,'turma-a')==='','another student got a link to a private annotation question');
$db->exec("UPDATE course_enrollments SET status='disabled' WHERE student_id=7 AND cohort_id=3");
must_question_annotation(student_question_annotation_url($db,7,$private,'turma-b')==='','revoked private question exposed a broken/stale cohort URL');
$db->exec("UPDATE course_enrollments SET status='active' WHERE student_id=7 AND cohort_id=3");
must_question_annotation(student_question_annotation_url($db,7,$courseShared,'turma-b')==='/aluno/duvidas.php?cohort=turma-b&id='.$courseShared['id'],'course-visible question should be openable in another cohort of the same course');
must_question_annotation(student_question_annotation_url($db,7,$courseShared,'turma-outra')==='/aluno/duvidas.php?cohort=turma-a&id='.$courseShared['id'],'course-visible question must not use an unrelated course');
$db->exec("UPDATE course_enrollments SET status='disabled' WHERE student_id=7 AND cohort_id=3");
must_question_annotation(student_question_annotation_url($db,7,$courseShared,'turma-b')==='/aluno/duvidas.php?cohort=turma-b&id='.$courseShared['id'],'course-visible question failed with only another active course enrollment');
$db->exec("UPDATE course_cohorts SET status='archived' WHERE id=5");
must_question_annotation(student_question_annotation_url($db,7,$courseShared,'turma-b')==='','archived last same-course cohort leaked a question link');
$questionsRoute=(string)file_get_contents(dirname(__DIR__).'/aluno/duvidas.php');
must_question_annotation(str_contains($questionsRoute,'student_question_annotation_url('),'annotation-to-question redirect should resolve an authorized context');
$materialRoute=(string)file_get_contents(dirname(__DIR__).'/aluno/material-anotacao.php');
must_question_annotation(str_contains($materialRoute,'student_question_annotation_url('),'material POST response should return an authorized question link');
$renderer=(string)file_get_contents(dirname(__DIR__).'/app/student_notes_experience.php');
must_question_annotation(str_contains($renderer,'student_question_annotation_url('),'material note should render question links in an authorized context');
echo "student-question-annotation: ok\n";
