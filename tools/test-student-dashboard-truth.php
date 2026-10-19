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
require $root.'/app/course_agenda.php';
require $root.'/app/student_home.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,title TEXT NOT NULL,locale TEXT NOT NULL DEFAULT 'pt-BR',sort_order INTEGER NOT NULL DEFAULT 0,status TEXT NOT NULL DEFAULT 'published',published_document_json TEXT NULL,access_level TEXT NOT NULL DEFAULT 'public',access_cohort_id INTEGER NULL);");
$db->exec("INSERT INTO cms_pages(id,activity_id,title,sort_order,status,published_document_json,access_level,access_cohort_id) VALUES (1,10,'Material do curso',1,'published','{}','activity',NULL),(2,10,'Material Turma A',2,'published','{}','cohort',101),(3,10,'Material Turma B',3,'published','{}','cohort',102),(4,10,'Página autenticada',4,'published','{}','authenticated',NULL),(5,10,'Página pública',5,'published','{}','public',NULL),(6,10,'Material arquivado',6,'archived','{}','activity',NULL),(7,10,'Material sem publicação',7,'published',NULL,'activity',NULL),(8,20,'Outro curso',1,'published','{}','activity',NULL);");
$pagesA=student_enrollment_pages_for_enrollment($db,['activity_id'=>10,'cohort_id'=>101]);$idsA=array_map(static fn(array $page): int=>(int)$page['id'],$pagesA);must_student_dashboard_truth($idsA===[1,2],'course discovery must expose activity pages plus only the matching cohort page');
$pagesB=student_enrollment_pages_for_enrollment($db,['activity_id'=>10,'cohort_id'=>102]);$idsB=array_map(static fn(array $page): int=>(int)$page['id'],$pagesB);must_student_dashboard_truth($idsB===[1,3],'course discovery leaked or omitted a cohort-scoped page');
$now=strtotime('2030-01-02T12:00:00Z');must_student_dashboard_truth(cms_access_lesson_release_state(null,$now)==='blocked','NULL lesson release is not blocked');must_student_dashboard_truth(cms_access_lesson_release_state('2030-01-02T13:00:00Z',$now)==='scheduled','future lesson release is not scheduled');must_student_dashboard_truth(cms_access_lesson_release_state('2030-01-02T11:00:00Z',$now)==='released','past lesson release is not released');
$dry=[['stage_key'=>'dry']];must_student_dashboard_truth(student_experience_dashboard_state(['status'=>'submitted','context_scope'=>'course'],$dry,true)===null,'submitted record remained a dashboard priority');must_student_dashboard_truth(student_experience_dashboard_state(['status'=>'reviewed','context_scope'=>'course'],$dry,true)===null,'reviewed record remained a dashboard priority');$revision=student_experience_dashboard_state(['status'=>'needs_revision','context_scope'=>'course'],$dry,true);must_student_dashboard_truth(($revision['action']??'')==='Revisar registro','revision did not become actionable');must_student_dashboard_truth(student_experience_dashboard_state(['status'=>'draft','context_scope'=>'personal'],$dry,true)===null,'finished personal record remained a dashboard priority');$courseReady=student_experience_dashboard_state(['status'=>'draft','context_scope'=>'course'],$dry,true);must_student_dashboard_truth(($courseReady['action']??'')==='Revisar e enviar','course record ready to submit is not actionable');
$courses=(string)file_get_contents($root.'/aluno/cursos.php');$courseView=(string)file_get_contents($root.'/app/student_courses_view.php');$home=(string)file_get_contents($root.'/aluno/index.php');
must_student_dashboard_truth(str_contains($courses,'course_material_page_release_summary'),'course surface does not derive page availability from canonical lesson releases');
must_student_dashboard_truth(str_contains($courses,'$pendingLessons')&&str_contains($courseView,'Próximas liberações')&&str_contains($courseView,'student-v2-course-releases'),'course surface does not keep pending lesson availability secondary to material');
must_student_dashboard_truth(!str_contains($courses,'$releasedCount')&&!str_contains($courses,'student-course-dashboard'),'course surface returned to progress-style parallel cards');
must_student_dashboard_truth(str_contains($home,'student_home_live_notices'),'Home does not aggregate real authenticated notices');
must_student_dashboard_truth(str_contains($home,'course_agenda_student_meetings'),'Home does not consume the canonical Agenda');
must_student_dashboard_truth(str_contains($home,'student_home_course_items'),'Home does not show available course materials');
must_student_dashboard_truth(str_contains((string)file_get_contents($root.'/app/student_home_view.php'),'data-home-courses'),'Home does not keep authorized course contexts directly accessible');
must_student_dashboard_truth(!str_contains((string)file_get_contents($root.'/app/student_home_view.php'),'data-record-create-open'),'Home repeats Caderno operations as a primary feature');
$meetingRows=[['meeting_status'=>'completed','meeting_starts_at'=>'2030-01-05T00:00:00Z','meeting_url'=>'https://example.org/completed'],['meeting_status'=>'scheduled','meeting_starts_at'=>'2030-01-06T00:00:00Z','meeting_timezone'=>'UTC','meeting_url'=>'javascript:alert(1)','lesson_title'=>'Aula 2'],['meeting_status'=>'scheduled','meeting_starts_at'=>'2030-01-07T00:00:00Z','meeting_timezone'=>'UTC','meeting_url'=>'https://example.org/allowed','lesson_title'=>'Aula 3']];
$meeting=student_home_next_meeting($meetingRows,strtotime('2030-01-04T00:00:00Z'));
must_student_dashboard_truth(($meeting['lesson']??'')==='Aula 2'&&($meeting['url']??'')==='','Home did not reject a dangerous meeting URL while selecting the first scheduled meeting');
must_student_dashboard_truth(student_home_next_meeting($meetingRows,strtotime('2030-01-08T00:00:00Z'))===null,'Home displayed past meetings');
$slotRows=[
 ['meeting_status'=>'completed','meeting_starts_at'=>'2030-01-06T17:00:00Z','meeting_duration_minutes'=>120,'meeting_url'=>'https://example.org/completed'],
 ['meeting_status'=>'scheduled','meeting_starts_at'=>'2030-01-06T18:00:00Z','meeting_duration_minutes'=>120,'meeting_timezone'=>'UTC','meeting_url'=>'https://example.org/in-session','lesson_title'=>'Encontro no horário'],
 ['meeting_status'=>'scheduled','meeting_starts_at'=>'2030-01-08T18:00:00Z','meeting_duration_minutes'=>120,'meeting_timezone'=>'UTC','meeting_url'=>'https://example.org/next','lesson_title'=>'Próximo encontro']
];
$inSlot=student_home_next_meeting($slotRows,strtotime('2030-01-06T19:01:00Z'));
must_student_dashboard_truth(($inSlot['lesson']??'')==='Encontro no horário'&&($inSlot['in_window']??false)===true,'Home hid a scheduled meeting while its published duration is still running');
$afterSlot=student_home_next_meeting($slotRows,strtotime('2030-01-06T20:01:00Z'));
must_student_dashboard_truth(($afterSlot['lesson']??'')==='Próximo encontro'&&empty($afterSlot['in_window']),'Home kept a meeting after its published duration');
$missingDuration=$slotRows;$missingDuration[1]['meeting_duration_minutes']=null;
must_student_dashboard_truth((student_home_next_meeting($missingDuration,strtotime('2030-01-06T19:01:00Z'))['lesson']??'')==='Próximo encontro','Home inferred an end time when meeting duration is unspecified');
must_student_dashboard_truth(str_contains(course_agenda_when('2030-01-06T22:00:00Z','America/Sao_Paulo'),'Brasília'),'Agenda exposes raw IANA timezone to students');
must_student_dashboard_truth(!str_contains($home,'student-home-primary'),'dashboard returned to the old latest-record card');
echo "student-dashboard-truth: ok\n";
