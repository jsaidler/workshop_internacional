<?php
declare(strict_types=1);

function fail_student_tool_release(string $message): never {fwrite(STDERR,"student-tool-release: $message\n");exit(1);}
function must_student_tool_release(bool $ok,string $message): void {if(!$ok)fail_student_tool_release($message);}
function cms_access_lesson_release_state(?string $releasedAt,?int $now=null): string {if(!$releasedAt)return 'blocked';$ts=strtotime($releasedAt);if($ts===false)return 'blocked';return $ts<=($now??time())?'released':'scheduled';}

require dirname(__DIR__).'/app/student_workbench.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE student_tools(tool_key TEXT PRIMARY KEY,label TEXT,description TEXT,access_mode TEXT,sort_order INTEGER,enabled INTEGER,created_at TEXT,updated_at TEXT)");
$db->exec("CREATE TABLE student_tool_courses(tool_key TEXT,course_id INTEGER,release_lesson_id INTEGER NULL,PRIMARY KEY(tool_key,course_id))");
$db->exec("CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,course_id INTEGER,title TEXT)");
$db->exec("CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER,status TEXT)");
$db->exec("CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT)");
$db->exec("CREATE TABLE cohort_lesson_releases(cohort_id INTEGER,lesson_id INTEGER,released_at TEXT)");

$db->exec("INSERT INTO student_tools VALUES
 ('solution_prep','Preparo de soluções','','course',10,1,'x','x'),
 ('exposure','Exposição','','course',20,1,'x','x'),
 ('lab_inventory','Inventário','','all',30,1,'x','x')");
$db->exec("INSERT INTO student_tool_courses VALUES('solution_prep',10,100),('exposure',10,NULL)");
$db->exec("INSERT INTO course_lessons VALUES(100,10,'Primeira aula'),(101,10,'Segunda aula'),(200,11,'Outra aula')");
$db->exec("INSERT INTO course_cohorts VALUES(20,10,'active')");
$db->exec("INSERT INTO course_enrollments VALUES(1,7,20,'active')");
$db->exec("INSERT INTO cohort_lesson_releases VALUES(20,100,NULL),(20,101,NULL)");

must_student_tool_release(student_tool_allowed($db,7,'exposure'),'ungated course tool should be available after enrollment');
must_student_tool_release(student_tool_allowed($db,7,'lab_inventory'),'all-access tool should remain available');
must_student_tool_release(!student_tool_allowed($db,7,'solution_prep'),'sensitive tool leaked before the first lesson release');

$db->exec("UPDATE cohort_lesson_releases SET released_at='2999-01-01T00:00:00Z' WHERE cohort_id=20 AND lesson_id=100");
must_student_tool_release(!student_tool_allowed($db,7,'solution_prep'),'scheduled future lesson released the sensitive tool too early');

$db->exec("UPDATE cohort_lesson_releases SET released_at='2000-01-01T00:00:00Z' WHERE cohort_id=20 AND lesson_id=100");
must_student_tool_release(student_tool_allowed($db,7,'solution_prep'),'released first lesson did not unlock the sensitive tool');

student_tool_course_set_release_lesson($db,10,'solution_prep',101);
$rows=student_tool_course_access_rows($db,10);$solution=array_values(array_filter($rows,static fn(array $row): bool=>(string)$row['tool_key']==='solution_prep'))[0]??null;
must_student_tool_release($solution&&(int)$solution['release_lesson_id']===101,'admin gate did not move the tool to the selected lesson');
$linked=student_tools_released_by_lesson($db,10,101);
must_student_tool_release(count($linked)===1&&(string)$linked[0]['tool_key']==='solution_prep','lesson impact does not expose linked student area');

$thrown=false;try{student_tool_course_set_release_lesson($db,10,'solution_prep',200);}catch(RuntimeException){$thrown=true;}
must_student_tool_release($thrown,'tool gate accepted a lesson from another course');

$workbench=(string)file_get_contents(dirname(__DIR__).'/app/student_workbench.php');
$migration=(string)file_get_contents(dirname(__DIR__).'/migrations/096_student_tool_lesson_release.php');
$lessons=(string)file_get_contents(dirname(__DIR__).'/admin/lessons.php');
$tools=(string)file_get_contents(dirname(__DIR__).'/aluno/ferramentas.php');
$shell=(string)file_get_contents(dirname(__DIR__).'/app/student_shell.php');
must_student_tool_release(str_contains($migration,"tool_key='solution_prep'")&&str_contains($migration,'ORDER BY l.sort_order,l.id'),'existing recipe access is not bound to the first course lesson');
must_student_tool_release(str_contains($lessons,'set_tool_release')&&str_contains($lessons,'A URL direta obedece à mesma regra.'),'admin cannot configure or explain server-side tool release');
must_student_tool_release(str_contains($tools,"isset(\$byKey['solution_prep'])?student_solution_formulas():[]"),'recipes are materialized even when the student is unauthorized');
must_student_tool_release(str_contains($shell,"if(isset(\$toolKeys['solution_prep']))"),'quick access still leaks the recipes destination before authorization');

echo "student-tool-release: ok\n";
