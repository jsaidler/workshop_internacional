<?php
declare(strict_types=1);
function fail_student_dashboard_truth(string $message): never {fwrite(STDERR,"student-dashboard-truth: $message\n");exit(1);}
function must_student_dashboard_truth(bool $condition,string $message): void {if(!$condition)fail_student_dashboard_truth($message);}
function app_config(): array {return ['timezone'=>'UTC'];}
function utc_now(): string {return gmdate('c');}
$root=dirname(__DIR__);
require $root.'/app/cms_access.php';
require $root.'/app/student_enrollments.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,title TEXT NOT NULL,locale TEXT NOT NULL DEFAULT 'pt-BR',sort_order INTEGER NOT NULL DEFAULT 0,status TEXT NOT NULL DEFAULT 'published',published_document_json TEXT NULL,access_level TEXT NOT NULL DEFAULT 'public',access_cohort_id INTEGER NULL);");
$db->exec("INSERT INTO cms_pages(id,activity_id,title,sort_order,status,published_document_json,access_level,access_cohort_id) VALUES (1,10,'Material do curso',1,'published','{}','activity',NULL),(2,10,'Material Turma A',2,'published','{}','cohort',101),(3,10,'Material Turma B',3,'published','{}','cohort',102),(4,10,'Página autenticada',4,'published','{}','authenticated',NULL),(5,10,'Página pública',5,'published','{}','public',NULL),(6,10,'Material arquivado',6,'archived','{}','activity',NULL),(7,10,'Material sem publicação',7,'published',NULL,'activity',NULL),(8,20,'Outro curso',1,'published','{}','activity',NULL);");
$pagesA=student_enrollment_pages_for_enrollment($db,['activity_id'=>10,'cohort_id'=>101]);$idsA=array_map(static fn(array $page): int=>(int)$page['id'],$pagesA);must_student_dashboard_truth($idsA===[1,2],'course discovery must expose activity pages plus only the matching cohort page');
$pagesB=student_enrollment_pages_for_enrollment($db,['activity_id'=>10,'cohort_id'=>102]);$idsB=array_map(static fn(array $page): int=>(int)$page['id'],$pagesB);must_student_dashboard_truth($idsB===[1,3],'course discovery leaked or omitted a cohort-scoped page');
$now=strtotime('2030-01-02T12:00:00Z');must_student_dashboard_truth(cms_access_lesson_release_state(null,$now)==='blocked','NULL lesson release is not blocked');must_student_dashboard_truth(cms_access_lesson_release_state('2030-01-02T13:00:00Z',$now)==='scheduled','future lesson release is not scheduled');must_student_dashboard_truth(cms_access_lesson_release_state('2030-01-02T11:00:00Z',$now)==='released','past lesson release is not released');
$courses=(string)file_get_contents($root.'/aluno/cursos.php');
must_student_dashboard_truth(str_contains($courses,'cms_access_lesson_release_state'),'courses surface does not derive lesson state from canonical access helper');
must_student_dashboard_truth(str_contains($courses,"'scheduled'=>'Agendada'")&&str_contains($courses,"'released'=>'Liberada'"),'courses surface does not expose user-facing scheduled/released labels');
must_student_dashboard_truth(str_contains($courses,"'released'=>'is-released'")&&str_contains($courses,"'scheduled'=>'is-scheduled'"),'courses surface lost semantic release-state classes');
must_student_dashboard_truth(str_contains($courses,'$releasedCount'),'courses surface does not count only currently released lessons');
must_student_dashboard_truth(!str_contains($courses,"\$lesson['released_at']?'liberada':'aguardando'"),'courses surface returned to raw released_at truthiness');
echo "student-dashboard-truth: ok\n";
