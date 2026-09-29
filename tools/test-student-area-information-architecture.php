<?php
declare(strict_types=1);

function fail(string $message): never {fwrite(STDERR,"student-area-ia: $message\n");exit(1);}
$root=dirname(__DIR__);
$guard=(string)file_get_contents($root.'/admin/student-area.php');
$people=(string)file_get_contents($root.'/admin/people.php');
$integrity=(string)file_get_contents($root.'/admin/data-integrity.php');
$courseAdmin=(string)file_get_contents($root.'/admin/courses.php');
$registrations=(string)file_get_contents($root.'/admin/registrations.php');
$cohorts=(string)file_get_contents($root.'/admin/cohorts.php');
$students=(string)file_get_contents($root.'/admin/students.php');
$lessons=(string)file_get_contents($root.'/admin/lessons.php');
$material=(string)file_get_contents($root.'/admin/material.php');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$legacy=(string)file_get_contents($root.'/admin/student-operations.php');
$adminJs=(string)file_get_contents($root.'/assets/admin.js');
$editorAccess=(string)file_get_contents($root.'/editor/cms-access-controls.js');
$csvEndpoint=(string)file_get_contents($root.'/admin/student-import-csv.php');
$csvTemplate=(string)file_get_contents($root.'/assets/modelo-importacao-alunos.csv');

if(str_contains($shell,'Operações e testes'))fail('artificial Operations and tests context item is still visible');
foreach(["'registrations'=>['Inscrições'","'cohorts'=>['Turmas'","'students'=>['Alunos'","'people'=>['Pessoas'"] as $needle)if(!str_contains($shell,$needle))fail('operation collection missing from global navigation: '.$needle);
foreach(["'courses'=>['Cursos'","'lessons'=>['Aulas'","'material'=>['Material'"] as $needle)if(!str_contains($shell,$needle))fail('teaching collection missing from global navigation: '.$needle);
if(!str_contains($shell,"'integrity'=>['Integridade'"))fail('read-only integrity context item missing');
if(!str_contains($shell,"'system','activities','integrity'=>'settings'"))fail('integrity must live under settings, not operational navigation');
if(str_contains($shell,'class="admin-course-nav"'))fail('course subtree navigation returned to the shell');
if(!str_contains($guard,'/admin/people.php'))fail('legacy student admin route does not redirect to global people');
if(!str_contains($people,'FROM student_users u')||!str_contains($people,'c.course_id IS NULL'))fail('global people view depends on course completeness');
if(!str_contains($integrity,'Somente leitura')||str_contains($integrity,'REQUEST_METHOD'))fail('integrity view is not strictly read-only');
if(!str_contains($courseAdmin,'Abrir filtrado'))fail('course catalog does not expose related collections as filters');
if(!str_contains($cohorts,'Todos os cursos'))fail('cohorts are not a global collection');
if(!str_contains($students,'Todos os cursos')||!str_contains($students,'Todas as turmas'))fail('students are not a global filterable collection');
if(!str_contains($lessons,'Todos os cursos'))fail('lessons are not a global collection');
if(!str_contains($material,'course_material_add_page')||!str_contains($material,'/editor/?page='))fail('material collection does not preserve canonical CMS page/editor relation');
if(!str_contains($registrations,'Todos os cursos')||!str_contains($registrations,'Disponibilidade agregada')||!str_contains($registrations,'Confirmadas sem turma'))fail('registrations are not a global operational collection');
if(str_contains($legacy,'admin_shell_start('))fail('legacy operations page still renders a parallel admin surface');
if(!str_contains($legacy,"'import'=>'students'")||!str_contains($legacy,"'tests'=>'tests'"))fail('legacy routes do not redirect to canonical object views');
foreach(['cms-access-audience','cms-access-availability','cms-visible-from','cms-access-lesson'] as $needle)if(!str_contains($editorAccess,$needle))fail('canonical editor missing section access property: '.$needle);
if(str_contains($adminJs,'studentCsvInput')||str_contains($adminJs,'Importar planilha'))fail('CSV UI still depends on JavaScript rewriting');
if(!str_contains($csvEndpoint,"strtolower(pathinfo(\$name,PATHINFO_EXTENSION))!=='csv'"))fail('CSV endpoint does not reject non-CSV uploads');
if(!str_contains($csvEndpoint,"student_import_historical_students(\$db,\$activityId,\$cohortId,\$file)"))fail('CSV endpoint is not connected to the historical importer');
$csvHeader=ltrim(strtok($csvTemplate,"\r\n"),"\xEF\xBB\xBF");if($csvHeader!=='Nome;E-mail;CPF;Telefone;Instagram;Endereço;Cidade/UF;CEP')fail('CSV template header changed');

echo "student-area-ia: ok\n";
