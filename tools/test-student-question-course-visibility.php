<?php
declare(strict_types=1);

function fail_question_course(string $message): never {fwrite(STDERR,"student-question-course-visibility: $message\n");exit(1);}
function must_question_course(bool $ok,string $message): void {if(!$ok)fail_question_course($message);}
function utc_now(): string {return '2026-10-09T12:00:00Z';}
function student_uuid(): string {static $n=0;return 'fixture-question-'.(++$n);}
function student_workspace_text(mixed $value,int $max=5000): string {return mb_substr(trim((string)$value),0,$max);}
function student_test_assert_enrollment(PDO $db,int $studentId,int $cohortId): void {
    $q=$db->prepare("SELECT 1 FROM course_enrollments WHERE student_id=? AND cohort_id=? AND status='active'");
    $q->execute([$studentId,$cohortId]);if(!$q->fetchColumn())throw new RuntimeException('Matrícula inválida.');
}
function student_test_for_student(PDO $db,int $testId,int $studentId): ?array {if($studentId!==1)return null;return match($testId){51=>['id'=>51,'student_id'=>1,'cohort_id'=>10],52=>['id'=>52,'student_id'=>1,'cohort_id'=>30],default=>null};}

require dirname(__DIR__).'/app/student_workbench.php';
require dirname(__DIR__).'/app/student_workbench_hardening.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE student_users(id INTEGER PRIMARY KEY,name TEXT)');
$db->exec('CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER,title TEXT,status TEXT)');
$db->exec('CREATE TABLE course_enrollments(student_id INTEGER,cohort_id INTEGER,status TEXT)');
$db->exec('CREATE TABLE student_questions(id INTEGER PRIMARY KEY AUTOINCREMENT,question_uuid TEXT,student_id INTEGER,cohort_id INTEGER,test_id INTEGER,topic TEXT,title TEXT,body TEXT,visibility TEXT,status TEXT,created_at TEXT,updated_at TEXT)');
$db->exec('CREATE TABLE student_question_messages(id INTEGER PRIMARY KEY AUTOINCREMENT,message_uuid TEXT,question_id INTEGER,author_role TEXT,student_id INTEGER,body TEXT,created_at TEXT)');
$db->exec("INSERT INTO student_users VALUES(1,'Autora'),(2,'Outra turma'),(3,'Outro curso'),(4,'Matrícula inativa')");
$db->exec("INSERT INTO course_cohorts VALUES(10,100,'Turma A','active'),(11,100,'Turma B','active'),(20,200,'Turma de outro curso','active'),(30,NULL,'Turma sem curso','active')");
$db->exec("INSERT INTO course_enrollments VALUES(1,10,'active'),(1,30,'active'),(2,11,'active'),(3,20,'active'),(4,11,'cancelled')");

$global=student_question_create($db,1,10,['topic'=>'Material','title'=>'Compartilhada com curso','body'=>'Dúvida para todas as turmas','visibility'=>'course']);
$cohort=student_question_create($db,1,10,['topic'=>'Material','title'=>'Somente turma','body'=>'Dúvida da turma','visibility'=>'cohort']);
$private=student_question_create($db,1,10,['topic'=>'Material','title'=>'Privada','body'=>'Somente professor','visibility'=>'private']);
must_question_course((string)$global['visibility']==='course','creation did not persist course visibility');
$own=student_questions_for_cohort($db,1,10);
must_question_course(count($own)===3,'author lost its course/cohort/private questions');
$other=student_questions_for_cohort($db,2,11);
must_question_course(count($other)===1&&(int)$other[0]['id']===(int)$global['id'],'another cohort of the same course cannot see the course question');
must_question_course(student_questions_for_cohort($db,3,20)===[],'another course received a question');
must_question_course(student_questions_for_cohort($db,2,10)===[],'unenrolled cohort listing exposed questions');
must_question_course(student_questions_for_cohort($db,4,11)===[],'inactive enrollment received questions');
must_question_course(student_question_for_enrolled_student($db,2,(int)$global['id'])!==null,'question detail not accessible to another cohort of the same course');
must_question_course(student_question_for_enrolled_student($db,3,(int)$global['id'])===null,'question detail leaked to another course');
must_question_course(student_question_for_enrolled_student($db,4,(int)$global['id'])===null,'inactive enrollment opened course question');
must_question_course(student_question_for_enrolled_student($db,2,(int)$cohort['id'])===null,'cohort question leaked into another cohort');
must_question_course(student_question_for_enrolled_student($db,2,(int)$private['id'])===null,'private question leaked to another student');
must_question_course(student_question_in_cohort_context($db,$global,11),'same-course context was rejected');
must_question_course(!student_question_in_cohort_context($db,$global,20),'cross-course context was accepted');
must_question_course(!student_question_in_cohort_context($db,$cohort,11),'cohort-only question used in another cohort context');
student_question_reply_enrolled($db,2,(int)$global['id'],'Resposta colaborativa');
must_question_course((int)$db->query('SELECT COUNT(*) FROM student_question_messages')->fetchColumn()===1,'same-course student cannot reply');
try{student_question_reply_enrolled($db,3,(int)$global['id'],'Resposta indevida');fail_question_course('another course could reply');}catch(RuntimeException $expected){}
try{student_question_create($db,1,30,['title'=>'Não publicar sem curso','body'=>'Teste','visibility'=>'course']);fail_question_course('course sharing without course_id was permitted');}catch(RuntimeException $expected){}
$linked=student_question_create($db,1,10,['title'=>'Registro da turma','body'=>'Conversa no contexto correto','visibility'=>'cohort','test_id'=>51]);
must_question_course((int)$linked['test_id']===51,'a record in the same cohort could not be linked');
try{student_question_create($db,1,10,['title'=>'Registro de outra turma','body'=>'Não misturar contexto','visibility'=>'course','test_id'=>52]);fail_question_course('a record from another cohort was linked to this question');}catch(RuntimeException $expected){}
echo "student-question-course-visibility: ok\n";
