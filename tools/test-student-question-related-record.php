<?php
declare(strict_types=1);

// Exercise the production authorization query, not a fixture-supplied access decision.
function course_domain_available(PDO $db): bool {return true;}
function workshop_course_scope_available(PDO $db): bool {return true;}
require dirname(__DIR__).'/app/student_sharing.php';
require dirname(__DIR__).'/app/student_question_annotations.php';

$db=new PDO('sqlite::memory:',null,null,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
]);
$db->exec('CREATE TABLE student_users(id INTEGER PRIMARY KEY,name TEXT,email TEXT)');
$db->exec('CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,activity_id INTEGER,course_id INTEGER,workshop_page_id INTEGER,title TEXT,status TEXT)');
$db->exec('CREATE TABLE course_enrollments(student_id INTEGER,cohort_id INTEGER,status TEXT)');
$db->exec('CREATE TABLE student_tests(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,visibility TEXT)');
$db->exec("INSERT INTO student_users VALUES(1,'Autora','a@example.invalid'),(2,'Outra turma','b@example.invalid'),(3,'Outro curso','c@example.invalid'),(4,'Mesma turma','d@example.invalid')");
$db->exec("INSERT INTO course_cohorts VALUES(10,1,100,NULL,'Turma A','active'),(11,1,100,NULL,'Turma B','active'),(20,1,200,NULL,'Turma X','active')");
$db->exec("INSERT INTO course_enrollments VALUES(1,10,'active'),(2,11,'active'),(3,20,'active'),(4,10,'active')");
$db->exec("INSERT INTO student_tests VALUES(101,1,10,'private'),(102,1,10,'cohort'),(103,1,10,'course')");

$check=static function(int $test,int $student,string $expect)use($db): void {
    $href=student_question_related_record_href($db,['test_id'=>$test],$student);
    if($href!==$expect){fwrite(STDERR,"question-related-record: FAIL test=$test viewer=$student href=$href expected=$expect\n");exit(1);}
};
$check(101,1,'/aluno/teste.php?id=101');
$check(101,4,'');
$check(101,2,'');
$check(102,4,'/aluno/teste-compartilhado.php?id=102');
$check(102,2,'');
$check(103,2,'/aluno/teste-compartilhado.php?id=103');
$check(103,3,'');
$check(0,1,'');
$check(999,1,'');
$db->exec("UPDATE course_enrollments SET status='cancelled' WHERE student_id=2");
$check(103,2,'');

$template=file_get_contents(dirname(__DIR__).'/aluno/duvidas.php');
if(!is_string($template)
   || !str_contains($template,'student_question_related_record_href($db,$question,$studentId)')
   || !str_contains($template,'href="<?=h($linkedTestUrl)?>"')
   || !str_contains($template,"if(\$linkedTestUrl!=='')")){
    fwrite(STDERR,"question-related-record: FAIL real thread must only render authorized related links\n");
    exit(1);
}
echo "question-related-record: ok\n";
