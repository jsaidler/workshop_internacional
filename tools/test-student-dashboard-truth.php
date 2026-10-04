<?php
declare(strict_types=1);
function fail_student_dashboard_truth(string $message): never {fwrite(STDERR,"student-dashboard-truth: $message\n");exit(1);}
function must_student_dashboard_truth(bool $condition,string $message): void {if(!$condition)fail_student_dashboard_truth($message);}
function app_config(): array {return ['timezone'=>'UTC'];}
function utc_now(): string {return gmdate('c');}
$root=dirname(__DIR__);
require $root.'/app/cms_access.php';
require $root.'/app/student_enrollments.php';
require $root.'/app/student_experience.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,title TEXT NOT NULL,locale TEXT NOT NULL DEFAULT 'pt-BR',sort_order INTEGER NOT NULL DEFAULT 0,status TEXT NOT NULL DEFAULT 'published',published_document_json TEXT NULL,access_level TEXT NOT NULL DEFAULT 'public',access_cohort_id INTEGER NULL);");
$db->exec("INSERT INTO cms_pages(id,activity_id,title,sort_order,status,published_document_json,access_level,access_cohort_id) VALUES (1,10,'Material do curso',1,'published','{}','activity',NULL),(2,10,'Material Turma A',2,'published','{}','cohort',101),(3,10,'Material Turma B',3,'published','{}','cohort',102),(4,10,'Página autenticada',4,'published','{}','authenticated',NULL),(5,10,'Página pública',5,'published','{}','public',NULL),(6,10,'Material arquivado',6,'archived','{}','activity',NULL),(7,10,'Material sem publicação',7,'published',NULL,'activity',NULL),(8,20,'Outro curso',1,'published','{}','activity',NULL);");
$pagesA=student_enrollment_pages_for_enrollment($db,['activity_id'=>10,'cohort_id'=>101]);$idsA=array_map(static fn(array $page): int=>(int)$page['id'],$pagesA);must_student_dashboard_truth($idsA===[1,2],'course discovery must expose activity pages plus only the matching cohort page');
$pagesB=student_enrollment_pages_for_enrollment($db,['activity_id'=>10,'cohort_id'=>102]);$idsB=array_map(static fn(array $page): int=>(int)$page['id'],$pagesB);must_student_dashboard_truth($idsB===[1,3],'course discovery leaked or omitted a cohort-scoped page');
$now=strtotime('2030-01-02T12:00:00Z');must_student_dashboard_truth(cms_access_lesson_release_state(null,$now)==='blocked','NULL lesson release is not blocked');must_student_dashboard_truth(cms_access_lesson_release_state('2030-01-02T13:00:00Z',$now)==='scheduled','future lesson release is not scheduled');must_student_dashboard_truth(cms_access_lesson_release_state('2030-01-02T11:00:00Z',$now)==='released','past lesson release is not released');
$dry=[['stage_key'=>'dry']];must_student_dashboard_truth(student_experience_dashboard_state(['status'=>'submitted','context_scope'=>'course'],$dry,true)===null,'submitted record remained a dashboard priority');must_student_dashboard_truth(student_experience_dashboard_state(['status'=>'reviewed','context_scope'=>'course'],$dry,true)===null,'reviewed record remained a dashboard priority');$revision=student_experience_dashboard_state(['status'=>'needs_revision','context_scope'=>'course'],$dry,true);must_student_dashboard_truth(($revision['action']??'')==='Revisar registro','revision did not become actionable');must_student_dashboard_truth(student_experience_dashboard_state(['status'=>'draft','context_scope'=>'personal'],$dry,true)===null,'finished personal record remained a dashboard priority');$courseReady=student_experience_dashboard_state(['status'=>'draft','context_scope'=>'course'],$dry,true);must_student_dashboard_truth(($courseReady['action']??'')==='Revisar e enviar','course record ready to submit is not actionable');
$courses=(string)file_get_contents($root.'/aluno/cursos.php');$home=(string)file_get_contents($root.'/aluno/index.php');
must_student_dashboard_truth(str_contains($courses,'course_material_page_release_summary'),'course surface does not derive page availability from canonical lesson releases');
must_student_dashboard_truth(str_contains($courses,'$pendingLessons')&&str_contains($courses,'Próximas liberações'),'course surface does not keep pending lesson availability secondary to material');
must_student_dashboard_truth(!str_contains($courses,'$releasedCount')&&!str_contains($courses,'student-course-dashboard'),'course surface returned to progress-style parallel cards');
must_student_dashboard_truth(str_contains($home,'student_experience_dashboard_state'),'dashboard still prioritizes the newest record without checking whether it needs action');
must_student_dashboard_truth(str_contains($home,"count(\$enrollments)>1")&&str_contains($home,'Escolha o curso'),'dashboard does not model the multiple-enrollment decision');
must_student_dashboard_truth(!str_contains($home,'student-home-primary'),'dashboard returned to the old latest-record card');
echo "student-dashboard-truth: ok\n";
