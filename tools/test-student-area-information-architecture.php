<?php
declare(strict_types=1);

function fail(string $message): never {fwrite(STDERR,"student-area-ia: $message\n");exit(1);}
$root=dirname(__DIR__);
$guard=(string)file_get_contents($root.'/admin/student-area.php');
$area=(string)file_get_contents($root.'/admin/student-area-legacy.php');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$legacy=(string)file_get_contents($root.'/admin/student-operations.php');
$adminJs=(string)file_get_contents($root.'/assets/admin.js');
$editorAccess=(string)file_get_contents($root.'/editor/cms-access-controls.js');
$csvEndpoint=(string)file_get_contents($root.'/admin/student-import-csv.php');
$csvTemplate=(string)file_get_contents($root.'/assets/modelo-importacao-alunos.csv');

if(str_contains($shell,'Operações e testes'))fail('artificial Operations and tests context item is still visible');
if(!str_contains($shell,"'students'=>['Área do aluno'"))fail('student area context item missing');
foreach(['Visão geral','Turmas','Alunos','Testes','Aulas'] as $label)if(!str_contains($area,"'".$label."'"))fail('student area object missing: '.$label);
if(!str_contains($area,"\$action==='update_cohort'"))fail('cohort editing is not part of the student area');
if(!str_contains($area,'/admin/student-import-csv.php?activity='))fail('historical CSV import is not contextualized under Students');
if(!str_contains($area,"\$view==='tests'"))fail('tests are not a first-class student area view');
if(str_contains($area,'Dados das turmas'))fail('legacy task label leaked into canonical navigation');
if(str_contains($area,'Operações e testes'))fail('legacy task grouping leaked into canonical page');
if(str_contains($legacy,'admin_shell_start('))fail('legacy operations page still renders a parallel admin surface');
if(!str_contains($legacy,"'import'=>'students'")||!str_contains($legacy,"'tests'=>'tests'"))fail('legacy routes do not redirect to canonical object views');

if(!str_contains($guard,"if(\$view==='pages')")||!str_contains($guard,"'/admin/pages.php'"))fail('protected-pages URL is not redirected server-side to canonical CMS pages');
foreach(['set_page_access','save_section_map','bind_page_media','unbind_page_media'] as $action)if(!str_contains($guard,"'".$action."'"))fail('obsolete editorial writer is not blocked: '.$action);
if(!str_contains($guard,'http_response_code(410)'))fail('obsolete editorial writers do not fail closed');
foreach(['cms-access-audience','cms-access-availability','cms-visible-from','cms-access-lesson'] as $needle)if(!str_contains($editorAccess,$needle))fail('canonical editor missing section access property: '.$needle);
foreach(['Importar CSV','accept=".csv,text/csv"','/assets/modelo-importacao-alunos.csv','Exemplo:'] as $needle)if(!str_contains($area,$needle))fail('server-rendered CSV UI missing: '.$needle);
if(str_contains($adminJs,'studentCsvInput')||str_contains($adminJs,'Importar planilha'))fail('CSV UI still depends on JavaScript rewriting');
if(!str_contains($csvEndpoint,"strtolower(pathinfo(\$name,PATHINFO_EXTENSION))!=='csv'"))fail('CSV endpoint does not reject non-CSV uploads');
if(!str_contains($csvEndpoint,"student_import_historical_students(\$db,\$activityId,\$cohortId,\$file)"))fail('CSV endpoint is not connected to the historical importer');
$csvHeader=ltrim(strtok($csvTemplate,"\r\n"),"\xEF\xBB\xBF");if($csvHeader!=='Nome;E-mail;CPF;Telefone;Instagram;Endereço;Cidade/UF;CEP')fail('CSV template header changed');

echo "student-area-ia: ok\n";
