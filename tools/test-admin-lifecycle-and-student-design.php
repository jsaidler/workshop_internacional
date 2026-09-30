<?php
declare(strict_types=1);

function fail_lifecycle(string $message): never {fwrite(STDERR,"admin-lifecycle-student-design: $message\n");exit(1);}
$root=dirname(__DIR__);
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$css=(string)file_get_contents($root.'/assets/student-area.css');
$activities=(string)file_get_contents($root.'/admin/activities.php');
$activityRepo=(string)file_get_contents($root.'/app/activity_repository.php');
$notebook=(string)file_get_contents($root.'/aluno/caderno.php');
$deletePage=(string)file_get_contents($root.'/aluno/excluir-teste.php');
$registrations=(string)file_get_contents($root.'/admin/registrations.php');

if(!str_contains($shell,'/template/page.css'))fail_lifecycle('student area does not inherit the global public stylesheet');
foreach(['fonts.googleapis.com','student-dashboard.css'] as $needle)if(str_contains($shell,$needle))fail_lifecycle('student shell still owns a parallel design dependency: '.$needle);
foreach(['--student-title:','--student-body:','--student-mono:','--student-bg:','--student-ink:','Saira Extra Condensed','IBM Plex Sans','IBM Plex Mono'] as $needle)if(str_contains($css,$needle))fail_lifecycle('student stylesheet still defines typography/palette authority: '.$needle);
foreach(['var(--title)','var(--body)','var(--mono)','var(--bg)','var(--surface)','var(--line)'] as $needle)if(!str_contains($css,$needle))fail_lifecycle('student stylesheet does not consume global token: '.$needle);
if(!str_contains($activities,'Identidade da instalação')||!str_contains($activityRepo,'activity_update_localized_identity'))fail_lifecycle('site identity is not editable from canonical structure settings');
if(str_contains($activities,'Novo curso ou workshop')||str_contains($activities,'activity_create('))fail_lifecycle('activity maintenance still creates workshops outside the page hierarchy');
if(!str_contains($notebook,'/aluno/excluir-teste.php?id=')||!str_contains($deletePage,'student_test_delete_owned')||!str_contains($deletePage,"header('Location: /aluno/caderno.php'"))fail_lifecycle('student cannot reach permanent record deletion from the canonical notebook');
if(!str_contains($registrations,'name="action" value="delete_registration"')||!str_contains($registrations,'admin_registration_delete'))fail_lifecycle('admin cannot permanently delete a registration from the canonical registration workspace');

$tmp=sys_get_temp_dir().'/student-lifecycle-'.bin2hex(random_bytes(5));
mkdir($tmp,0770,true);
function student_test_media_storage_root(): string {return $GLOBALS['student_lifecycle_tmp'];}
$GLOBALS['student_lifecycle_tmp']=$tmp;
require_once $root.'/app/student_lifecycle.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE student_tests(id INTEGER PRIMARY KEY,student_id INTEGER NOT NULL);');
$db->exec('CREATE TABLE student_test_media(id INTEGER PRIMARY KEY,test_id INTEGER NOT NULL,storage_path TEXT NOT NULL);');
$db->exec('CREATE TABLE student_test_messages(id INTEGER PRIMARY KEY,test_id INTEGER NOT NULL);');
$db->exec("CREATE TABLE cms_forms(id INTEGER PRIMARY KEY,form_key TEXT NOT NULL,purpose TEXT NOT NULL DEFAULT 'common');");
$db->exec('CREATE TABLE cms_form_submissions(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,form_id INTEGER NOT NULL);');
$db->exec('CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,source_submission_id INTEGER NULL);');
$db->exec("INSERT INTO student_tests(id,student_id) VALUES(10,7); INSERT INTO student_test_media(id,test_id,storage_path) VALUES(20,10,'photo.jpg'); INSERT INTO student_test_messages(id,test_id) VALUES(30,10); INSERT INTO cms_forms(id,form_key,purpose) VALUES(1,'registration','enrollment'); INSERT INTO cms_form_submissions(id,activity_id,form_id) VALUES(40,3,1); INSERT INTO course_enrollments(id,source_submission_id) VALUES(50,40);");
file_put_contents($tmp.'/photo.jpg','x');
student_test_delete_owned($db,10,7);
if((int)$db->query('SELECT COUNT(*) FROM student_tests')->fetchColumn()!==0)fail_lifecycle('record row survived deletion');
if((int)$db->query('SELECT COUNT(*) FROM student_test_media')->fetchColumn()!==0)fail_lifecycle('record media rows survived deletion');
if((int)$db->query('SELECT COUNT(*) FROM student_test_messages')->fetchColumn()!==0)fail_lifecycle('record messages survived deletion');
if(is_file($tmp.'/photo.jpg'))fail_lifecycle('record media file survived deletion');

admin_registration_delete($db,40,3);
if((int)$db->query('SELECT COUNT(*) FROM cms_form_submissions')->fetchColumn()!==0)fail_lifecycle('registration survived deletion');
if((int)$db->query('SELECT COUNT(*) FROM course_enrollments')->fetchColumn()!==0)fail_lifecycle('enrollment created by deleted registration survived');
@rmdir($tmp);

echo "admin-lifecycle-student-design: ok\n";
