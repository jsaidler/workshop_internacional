<?php
declare(strict_types=1);

function fail(string $message): never {fwrite(STDERR,"student-area-ia: $message\n");exit(1);}
$root=dirname(__DIR__);
$guard=(string)file_get_contents($root.'/admin/student-area.php');
$people=(string)file_get_contents($root.'/admin/people.php');
$integrity=(string)file_get_contents($root.'/admin/data-integrity.php');
$courseAdmin=(string)file_get_contents($root.'/admin/courses.php');
$registrations=(string)file_get_contents($root.'/admin/registrations.php');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$legacy=(string)file_get_contents($root.'/admin/student-operations.php');
$adminJs=(string)file_get_contents($root.'/assets/admin.js');
$editorAccess=(string)file_get_contents($root.'/editor/cms-access-controls.js');
$csvEndpoint=(string)file_get_contents($root.'/admin/student-import-csv.php');
$csvTemplate=(string)file_get_contents($root.'/assets/modelo-importacao-alunos.csv');

if(str_contains($shell,'Operações e testes'))fail('artificial Operations and tests context item is still visible');
if(!str_contains($shell,"'courses'=>['Cursos'"))fail('course authority context item missing');
if(!str_contains($shell,"'registrations'=>['Inscrições'"))fail('course registrations context item missing');
if(!str_contains($shell,"'people'=>['Pessoas'"))fail('global people context item missing');
if(!str_contains($shell,"'integrity'=>['Integridade'"))fail('read-only integrity context item missing');
if(!str_contains($guard,'/admin/people.php'))fail('legacy student admin route does not redirect to global people');
if(!str_contains($people,'FROM student_users u')||!str_contains($people,'c.course_id IS NULL'))fail('global people view depends on course completeness');
if(!str_contains($integrity,'Somente leitura')||str_contains($integrity,'REQUEST_METHOD'))fail('integrity view is not strictly read-only');
foreach(['Turmas','Alunos','Aulas','Material'] as $label)if(!str_contains($courseAdmin,"'".$label."'"))fail('course administration object missing: '.$label);
if(!str_contains($courseAdmin,'course_material_add_page')||!str_contains($courseAdmin,'/editor/?page='))fail('material is not a contextual view of canonical CMS pages/editor');
if(!str_contains($registrations,'Escolha o curso')||!str_contains($registrations,'Confirmadas sem turma'))fail('registrations are not separated by course with pending cohort assignment');
if(str_contains($legacy,'admin_shell_start('))fail('legacy operations page still renders a parallel admin surface');
if(!str_contains($legacy,"'import'=>'students'")||!str_contains($legacy,"'tests'=>'tests'"))fail('legacy routes do not redirect to canonical object views');
foreach(['cms-access-audience','cms-access-availability','cms-visible-from','cms-access-lesson'] as $needle)if(!str_contains($editorAccess,$needle))fail('canonical editor missing section access property: '.$needle);
if(str_contains($adminJs,'studentCsvInput')||str_contains($adminJs,'Importar planilha'))fail('CSV UI still depends on JavaScript rewriting');
if(!str_contains($csvEndpoint,"strtolower(pathinfo(\$name,PATHINFO_EXTENSION))!=='csv'"))fail('CSV endpoint does not reject non-CSV uploads');
if(!str_contains($csvEndpoint,"student_import_historical_students(\$db,\$activityId,\$cohortId,\$file)"))fail('CSV endpoint is not connected to the historical importer');
$csvHeader=ltrim(strtok($csvTemplate,"\r\n"),"\xEF\xBB\xBF");if($csvHeader!=='Nome;E-mail;CPF;Telefone;Instagram;Endereço;Cidade/UF;CEP')fail('CSV template header changed');

echo "student-area-ia: ok\n";
