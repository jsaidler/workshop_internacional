<?php
declare(strict_types=1);
function fail_coherence(string $message): never {fwrite(STDERR,"student-platform-coherence: $message\n");exit(1);}
$root=dirname(__DIR__);
$studentAdmin=(string)file_get_contents($root.'/admin/student-area.php');
$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
$site=(string)file_get_contents($root.'/admin/site.php');
$adminJs=(string)file_get_contents($root.'/assets/admin.js');
$media=(string)file_get_contents($root.'/admin/media.php');
$mediaPrivacy=(string)file_get_contents($root.'/app/media_privacy.php');
$studentMaterial=(string)file_get_contents($root.'/app/student_material.php');
$sharing=(string)file_get_contents($root.'/app/student_sharing.php');
$sharedTest=(string)file_get_contents($root.'/aluno/teste-compartilhado.php');
$submissions=(string)file_get_contents($root.'/admin/submissions.php');

foreach(['cms_design_font_import_css','cms_design_css','/template/page.css'] as $needle)if(!str_contains($studentShell,$needle))fail_coherence('student shell does not consume canonical design authority: '.$needle);
if(!str_contains($site,'name="course_public_title"')||!str_contains($site,'Nome público do curso'))fail_coherence('course public title is not editable in Site identity');
foreach(['private','cohort','course'] as $visibility)if(!str_contains($sharing,"'".$visibility."'"))fail_coherence('missing test visibility: '.$visibility);
if(str_contains($sharing,'t.activity_id'))fail_coherence('test sharing references nonexistent student_tests.activity_id');
if(!str_contains($sharedTest,'student_test_messages($db,$id)'))fail_coherence('shared test does not include the conversation under the same visibility');
if(str_contains($sharedTest,'A conversa de avaliação com o professor não faz parte do compartilhamento'))fail_coherence('shared test still claims the conversation is private');
if(!str_contains($sharedTest,'A conversa faz parte do registro e segue a mesma visibilidade do teste.'))fail_coherence('shared conversation visibility is not explicit');
if(str_contains($sharedTest,'name="message"'))fail_coherence('shared students can write into another student test');
if(!str_contains($submissions,'delete_registration')||!str_contains($submissions,'admin_registration_delete'))fail_coherence('permanent registration deletion missing');

if(str_contains($studentAdmin,'upload_private_media')||str_contains($studentAdmin,'name="media_file"'))fail_coherence('parallel private-media uploader still exists in student admin');
foreach(['course_page_media_bind','course_page_media_unbind','media_private_images','Abrir biblioteca de mídia','Visualizar como turma','preview_cohort'] as $needle)if(!str_contains($studentAdmin,$needle))fail_coherence('protected-page canonical workflow missing: '.$needle);
if(!str_contains($studentAdmin,'student_page_sections_from_document(cms_page_doc($selectedPage,false))'))fail_coherence('protected-page admin does not enumerate real document sections');
if(!str_contains($studentAdmin,'seção(ões) reais encontradas no documento'))fail_coherence('protected-page admin does not expose the real section count');
if(str_contains($studentAdmin,'Página inteira'))fail_coherence('synthetic whole-page section returned to protected-page mapping');
foreach(['Importar CSV','accept=".csv,text/csv"','/assets/modelo-importacao-alunos.csv','/admin/student-import-csv.php'] as $needle)if(!str_contains($studentAdmin,$needle))fail_coherence('CSV is not server-rendered canonically: '.$needle);
if(str_contains($adminJs,'studentCsvInput')||str_contains($adminJs,'Importar planilha'))fail_coherence('CSV workflow still depends on client-side rewriting');
if(!str_contains($media,'admin-media-privacy.js')||!str_contains($mediaPrivacy,"['public','private']"))fail_coherence('media library is not privacy authority');
if(!str_contains($studentMaterial,'course_page_media_slot')||str_contains($studentMaterial,'move_uploaded_file'))fail_coherence('protected material still resolves/uploads through parallel private storage');

$migration64=(string)file_get_contents($root.'/migrations/064_student_test_visibility.php');
if(str_contains($migration64,'student_tests(activity_id'))fail_coherence('visibility migration indexes nonexistent activity_id');

require_once $root.'/app/student_accounts.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,activity_id INTEGER,lesson_key TEXT,title TEXT);');
$db->exec('CREATE TABLE course_page_sections(page_id INTEGER,section_key TEXT,lesson_id INTEGER,created_at TEXT,updated_at TEXT,PRIMARY KEY(page_id,section_key));');
$db->exec('CREATE TABLE cohort_lesson_releases(cohort_id INTEGER,lesson_id INTEGER,released_at TEXT,created_at TEXT,updated_at TEXT,PRIMARY KEY(cohort_id,lesson_id));');
$db->exec("INSERT INTO course_lessons(id,activity_id,lesson_key,title) VALUES(1,1,'aula-1','Aula 1'),(2,1,'aula-2','Aula 2'),(3,1,'aula-3','Aula 3');");
$db->exec("INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(5,'sec-1',1,'x','x'),(5,'sec-2',2,'x','x'),(5,'sec-3',3,'x','x');");
$db->exec("INSERT INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at) VALUES(7,1,'2026-09-25','x','x'),(7,2,NULL,'x','x'),(7,3,NULL,'x','x');");
$document=['html'=>'<section data-cms-section="sec-1" data-cms-section-name="Seção 1">CONTEUDO_AULA_1</section><section data-cms-section="sec-2" data-cms-section-name="Seção 2">CONTEUDO_AULA_2</section><section data-cms-section="sec-3" data-cms-section-name="Seção 3">CONTEUDO_AULA_3</section>'];
$sections=student_page_sections_from_document($document);
if(count($sections)!==3||($sections['sec-2']??'')!=='Seção 2')fail_coherence('real document sections are not discoverable for protected-page administration');
$filtered=student_page_filter_document($db,['id'=>5],$document,['cohort_id'=>7]);
$html=(string)$filtered['html'];
if(!str_contains($html,'CONTEUDO_AULA_1'))fail_coherence('released lesson disappeared from filtered HTML');
if(str_contains($html,'CONTEUDO_AULA_2')||str_contains($html,'CONTEUDO_AULA_3'))fail_coherence('blocked lessons still reach filtered HTML');

$db2=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db2->exec('CREATE TABLE student_tests(id INTEGER PRIMARY KEY,cohort_id INTEGER NOT NULL,updated_at TEXT NOT NULL);');
(require $root.'/migrations/064_student_test_visibility.php')($db2);
$cols=array_column($db2->query('PRAGMA table_info(student_tests)')->fetchAll(PDO::FETCH_ASSOC),'name');
if(!in_array('visibility',$cols,true))fail_coherence('visibility column was not migrated');
$indexSql=(string)$db2->query("SELECT sql FROM sqlite_master WHERE type='index' AND name='idx_student_tests_visibility'")->fetchColumn();
if(!str_contains($indexSql,'visibility,cohort_id,updated_at'))fail_coherence('visibility index does not match real schema');

echo "student-platform-coherence: ok\n";
