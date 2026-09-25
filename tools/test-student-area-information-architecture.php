<?php
declare(strict_types=1);

function fail(string $message): never {fwrite(STDERR,"student-area-ia: $message\n");exit(1);}
$root=dirname(__DIR__);
$area=(string)file_get_contents($root.'/admin/student-area.php');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$legacy=(string)file_get_contents($root.'/admin/student-operations.php');
$adminJs=(string)file_get_contents($root.'/assets/admin.js');
$csvEndpoint=(string)file_get_contents($root.'/admin/student-import-csv.php');
$csvTemplate=(string)file_get_contents($root.'/assets/modelo-importacao-alunos.csv');

if(str_contains($shell,'Operações e testes'))fail('artificial Operations and tests context item is still visible');
if(!str_contains($shell,"'students'=>['Área do aluno'"))fail('student area context item missing');
if(!str_contains($area,'$views=[\'overview\',\'cohorts\',\'students\',\'tests\',\'lessons\',\'pages\']'))fail('canonical object order is not encoded');

$labels=['Visão geral','Turmas','Alunos','Testes','Aulas','Páginas protegidas'];
$last=-1;foreach($labels as $label){$pos=strpos($area,"'".$label."'");if($pos===false)fail('navigation label missing: '.$label);if($pos<=$last)fail('student area navigation order changed around '.$label);$last=$pos;}
if(!str_contains($area,"\$action==='update_cohort'"))fail('cohort editing is not part of the student area');
if(!str_contains($area,'/admin/student-import-csv.php?activity='))fail('historical CSV import is not contextualized under Students');
if(!str_contains($area,"\$view==='tests'"))fail('tests are not a first-class student area view');
if(str_contains($area,'Dados das turmas'))fail('legacy task label leaked into canonical navigation');
if(str_contains($area,'Operações e testes'))fail('legacy task grouping leaked into canonical page');
if(str_contains($legacy,'admin_shell_start('))fail('legacy operations page still renders a parallel admin surface');
if(!str_contains($legacy,"'import'=>'students'")||!str_contains($legacy,"'tests'=>'tests'"))fail('legacy routes do not redirect to canonical object views');

foreach(['Importar CSV','accept=".csv,text/csv"','/assets/modelo-importacao-alunos.csv','Exemplo:'] as $needle)if(!str_contains($area,$needle))fail('server-rendered CSV UI missing: '.$needle);
if(str_contains($adminJs,'studentCsvInput')||str_contains($adminJs,'Importar planilha'))fail('CSV UI still depends on JavaScript rewriting');
if(!str_contains($csvEndpoint,"strtolower(pathinfo(\$name,PATHINFO_EXTENSION))!=='csv'"))fail('CSV endpoint does not reject non-CSV uploads');
if(!str_contains($csvEndpoint,"student_import_historical_students(\$db,\$activityId,\$cohortId,\$file)"))fail('CSV endpoint is not connected to the historical importer');
$csvHeader=ltrim(strtok($csvTemplate,"\r\n"),"\xEF\xBB\xBF");
if($csvHeader!=='Nome;E-mail;CPF;Telefone;Instagram;Endereço;Cidade/UF;CEP')fail('CSV template header changed');

echo "student-area-ia: ok\n";