<?php
declare(strict_types=1);
function fail_coherence(string $message): never {fwrite(STDERR,"student-platform-coherence: $message\n");exit(1);}
if(!function_exists('app_config')){function app_config(): array{return ['timezone'=>'UTC'];}}
if(!function_exists('utc_now')){function utc_now(): string{return gmdate('c');}}
$root=dirname(__DIR__);
$studentAdmin=(string)file_get_contents($root.'/admin/student-area.php');
$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
$site=(string)file_get_contents($root.'/admin/site.php');
$adminJs=(string)file_get_contents($root.'/assets/admin.js');
$media=(string)file_get_contents($root.'/admin/media.php');
$mediaPrivacy=(string)file_get_contents($root.'/app/media_privacy.php');
$sharing=(string)file_get_contents($root.'/app/student_sharing.php');
$sharedTest=(string)file_get_contents($root.'/aluno/teste-compartilhado.php');
$testPage=(string)file_get_contents($root.'/aluno/teste.php');
$testMobile=(string)file_get_contents($root.'/app/student_test_mobile.php');
$editorAccess=(string)file_get_contents($root.'/editor/cms-access-controls.js');
$editorHtml=(string)file_get_contents($root.'/editor/index.html');
$index=(string)file_get_contents($root.'/index.php');
$submissions=(string)file_get_contents($root.'/admin/submissions.php');

foreach(['cms_design_font_import_css','cms_design_css','/template/page.css'] as $needle)if(!str_contains($studentShell,$needle))fail_coherence('student shell does not consume canonical design authority: '.$needle);
if(!str_contains($site,'name="course_public_title"')||!str_contains($site,'Nome público do curso'))fail_coherence('course public title is not editable in Site identity');
foreach(['private','cohort','course'] as $visibility)if(!str_contains($sharing,"'".$visibility."'"))fail_coherence('missing test visibility: '.$visibility);
if(!str_contains($sharedTest,'student_test_messages($db,$id)')||!str_contains($sharedTest,'segue a mesma visibilidade do teste'))fail_coherence('shared conversation does not follow test visibility');
if(str_contains($sharedTest,'name="message"'))fail_coherence('shared students can write into another student test');
if(!str_contains($submissions,'delete_registration')||!str_contains($submissions,'admin_registration_delete'))fail_coherence('permanent registration deletion missing');

foreach(['data-cms-access-controls','cms-access-audience','cms-access-availability','cms-visible-from','cms-visible-until','cms-access-lesson'] as $needle)if(!str_contains($editorAccess,$needle))fail_coherence('section access control missing from canonical editor: '.$needle);
if(!str_contains($editorHtml,'/editor/cms-access-controls.js'))fail_coherence('canonical editor does not load access controls');
if(!str_contains($index,'cms_access_filter_html'))fail_coherence('public renderer path does not apply generic section authorization');
if(!str_contains($adminJs,"textContent.trim() === 'Páginas protegidas'"))fail_coherence('legacy protected-pages destination is not removed from student admin navigation');
if(!str_contains($adminJs,"view') === 'pages'"))fail_coherence('legacy protected-pages direct view is not redirected to CMS pages');
foreach(['Agendar','Liberar agora','Bloquear','/admin/api/course-lesson-release.php'] as $needle)if(!str_contains($adminJs,$needle))fail_coherence('lesson scheduling missing from existing lessons interface: '.$needle);
if(!str_contains($media,'admin-media-privacy.js')||!str_contains($mediaPrivacy,"['public','private']"))fail_coherence('media library is not privacy authority');

if(!str_contains($testPage,'name="bleach"')||!str_contains($testPage,'Solução peroxiacética')||!str_contains($testPage,'Cloreto férrico'))fail_coherence('bleach selector is missing or uses non-canonical terminology');
if(str_contains($testPage,'Ácido peracético'))fail_coherence('obsolete bleach terminology returned');
if(!str_contains($testPage,'<dt>Branqueador</dt>')||!str_contains($sharedTest,'<dt>Branqueador</dt>'))fail_coherence('bleach is not shown in test review/shared test');
if(!str_contains($testMobile,"SET developer=?,dilution=?,temperature=?,development_time=?,agitation=?,bleach=?,notes=?"))fail_coherence('bleach is not persisted with development parameters');
$migration66=(string)file_get_contents($root.'/migrations/066_cms_section_access_and_test_bleach.php');
if(!str_contains($migration66,'ALTER TABLE student_tests ADD COLUMN bleach'))fail_coherence('bleach database migration missing');
if(!str_contains($migration66,'data-cms-availability')||!str_contains($migration66,'data-cms-lesson-id'))fail_coherence('legacy section-to-lesson mapping is not migrated into CMS sections');

require_once $root.'/app/cms_access.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,activity_id INTEGER,title TEXT,cohort_uuid TEXT,status TEXT);');
$db->exec('CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT,confirmed_at TEXT);');
$db->exec('CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,activity_id INTEGER,lesson_key TEXT,title TEXT);');
$db->exec('CREATE TABLE cohort_lesson_releases(cohort_id INTEGER,lesson_id INTEGER,released_at TEXT,created_at TEXT,updated_at TEXT,PRIMARY KEY(cohort_id,lesson_id));');
$db->exec("INSERT INTO course_cohorts VALUES(7,1,'Turma A','a','active'),(8,1,'Turma B','b','active');");
$db->exec("INSERT INTO course_enrollments VALUES(1,10,7,'active','2026-01-01T00:00:00Z');");
$db->exec("INSERT INTO course_lessons VALUES(3,1,'aula-3','Aula 3');");
$db->exec("INSERT INTO cohort_lesson_releases VALUES(7,3,'2030-01-02T12:00:00Z','x','x');");
$activity=['id'=>1];$user=['id'=>10];
if(!cms_access_section_allowed($db,$activity,['access'=>'authenticated','availability'=>'immediate'],$user))fail_coherence('authenticated section rejects authenticated user');
if(cms_access_section_allowed($db,$activity,['access'=>'authenticated','availability'=>'immediate'],null))fail_coherence('authenticated section reaches anonymous visitor');
if(!cms_access_section_allowed($db,$activity,['access'=>'activity','availability'=>'immediate'],$user))fail_coherence('activity section rejects active participant');
if(!cms_access_section_allowed($db,$activity,['access'=>'cohort','cohort_id'=>7,'availability'=>'immediate'],$user))fail_coherence('cohort section rejects its cohort');
if(cms_access_section_allowed($db,$activity,['access'=>'cohort','cohort_id'=>8,'availability'=>'immediate'],$user))fail_coherence('cohort section leaks to another cohort');
$before=strtotime('2030-01-02T11:00:00Z');$after=strtotime('2030-01-02T13:00:00Z');
if(cms_access_section_allowed($db,$activity,['access'=>'activity','availability'=>'lesson','lesson_id'=>3],$user,$before))fail_coherence('future lesson release is treated as released');
if(!cms_access_section_allowed($db,$activity,['access'=>'activity','availability'=>'lesson','lesson_id'=>3],$user,$after))fail_coherence('lesson does not release when scheduled time arrives');
$scheduled=['access'=>'public','availability'=>'scheduled','visible_from'=>'2030-01-02T12:00','visible_until'=>'2030-01-02T14:00'];
if(cms_access_section_allowed($db,$activity,$scheduled,null,$before))fail_coherence('scheduled section is visible before its start');
if(!cms_access_section_allowed($db,$activity,$scheduled,null,$after))fail_coherence('scheduled section is hidden inside its window');
if(cms_access_section_allowed($db,$activity,$scheduled,null,strtotime('2030-01-02T15:00:00Z')))fail_coherence('scheduled section remains visible after its end');
$html='<section data-cms-section="public">PUBLIC</section><section data-cms-section="auth" data-cms-access="authenticated">AUTH</section><section data-cms-section="course" data-cms-access="activity">COURSE</section>';
$anonymous=cms_access_filter_html($db,$activity,$html,null,false,$after);if(!str_contains($anonymous,'PUBLIC')||str_contains($anonymous,'AUTH')||str_contains($anonymous,'COURSE'))fail_coherence('anonymous CMS filtering is incorrect');
$logged=cms_access_filter_html($db,$activity,$html,$user,false,$after);if(!str_contains($logged,'PUBLIC')||!str_contains($logged,'AUTH')||!str_contains($logged,'COURSE'))fail_coherence('authenticated CMS filtering is incorrect');

echo "student-platform-coherence: ok\n";
