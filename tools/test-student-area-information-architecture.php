<?php
declare(strict_types=1);

function fail(string $message): never {fwrite(STDERR,"student-area-ia: $message\n");exit(1);}
$root=dirname(__DIR__);
$area=(string)file_get_contents($root.'/admin/student-area.php');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$legacy=(string)file_get_contents($root.'/admin/student-operations.php');
$adminJs=(string)file_get_contents($root.'/assets/admin.js');
$csvTemplate=(string)file_get_contents($root.'/assets/modelo-importacao-alunos.csv');

if(str_contains($shell,'Operações e testes'))fail('artificial Operations and tests context item is still visible');
if(!str_contains($shell,"'students'=>['Área do aluno'"))fail('student area context item missing');
if(!str_contains($area,'$views=[\'overview\',\'cohorts\',\'students\',\'tests\',\'lessons\',\'pages\']'))fail('canonical object order is not encoded');

$labels=['Visão geral','Turmas','Alunos','Testes','Aulas','Páginas protegidas'];
$last=-1;foreach($labels as $label){$pos=strpos($area,"'".$label."'");if($pos===false)fail('navigation label missing: '.$label);if($pos<=$last)fail('student area navigation order changed around '.$label);$last=$pos;}
if(!str_contains($area,"\$action==='update_cohort'"))fail('cohort editing is not part of the student area');
if(!str_contains($area,"\$action==='import_students'"))fail('historical import is not part of the student area');
if(!str_contains($area,'name="return_view" value="students"'))fail('historical import is not contextualized under Students');
if(!str_contains($area,"\$view==='tests'"))fail('tests are not a first-class student area view');
if(str_contains($area,'Dados das turmas'))fail('legacy task label leaked into canonical navigation');
if(str_contains($area,'Operações e testes'))fail('legacy task grouping leaked into canonical page');
if(str_contains($legacy,'admin_shell_start('))fail('legacy operations page still renders a parallel admin surface');
if(!str_contains($legacy,"'import'=>'students'")||!str_contains($legacy,"'tests'=>'tests'"))fail('legacy routes do not redirect to canonical object views');

if(!str_contains($adminJs,"studentCsvInput.setAttribute('accept', '.csv,text/csv')"))fail('student import UI is not constrained to CSV');
if(!str_contains($adminJs,"templateLink.href = '/assets/modelo-importacao-alunos.csv'"))fail('CSV template download is not exposed in the student import UI');
if(!str_contains($adminJs,"Exemplo de linha: "))fail('CSV import does not show an example row');
$csvHeader=ltrim(strtok($csvTemplate,"\r\n"),"\xEF\xBB\xBF");
if($csvHeader!=='Nome;E-mail;CPF;Telefone;Instagram;Endereço;Cidade/UF;CEP')fail('CSV template header changed');


echo "student-area-ia: ok\n";
