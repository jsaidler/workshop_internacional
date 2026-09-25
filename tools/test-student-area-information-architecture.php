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
$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
$studentHome=(string)file_get_contents($root.'/aluno/index.php');
$studentTests=(string)file_get_contents($root.'/aluno/teste.php');
$studentTestHelpers=(string)file_get_contents($root.'/app/student_test_mobile.php');
$studentCss=(string)file_get_contents($root.'/assets/student-area.css');
$adminFormCss=(string)file_get_contents($root.'/assets/admin-form-ux.css');
$migration=(string)file_get_contents($root.'/migrations/063_student_mobile_test_media_kinds.php');

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
if(!str_contains($adminJs,"/admin/student-import-csv.php"))fail('student import form is not routed through the CSV-only endpoint');
if(!str_contains($adminJs,"templateLink.href = '/assets/modelo-importacao-alunos.csv'"))fail('CSV template download is not exposed in the student import UI');
if(!str_contains($adminJs,"Exemplo de linha: "))fail('CSV import does not show an example row');
if(!str_contains($csvEndpoint,"strtolower(pathinfo(\$name,PATHINFO_EXTENSION))!=='csv'"))fail('CSV endpoint does not reject non-CSV uploads');
if(!str_contains($csvEndpoint,"student_import_historical_students(\$db,\$activityId,\$cohortId,\$file)"))fail('CSV endpoint is not connected to the historical importer');
$csvHeader=ltrim(strtok($csvTemplate,"\r\n"),"\xEF\xBB\xBF");
if($csvHeader!=='Nome;E-mail;CPF;Telefone;Instagram;Endereço;Cidade/UF;CEP')fail('CSV template header changed');

if(!str_contains($migration,"'url'=>'/aluno/'"))fail('public navigation does not receive a student-area entry');
if(!str_contains($migration,'media_kind'))fail('student test media has no scene/result migration');
if(str_contains($studentShell,'Direct Positive Workshop'))fail('student shell still exposes the old parallel workshop brand');
if(!str_contains($studentShell,'João Saidler Fotografia'))fail('student shell is not tied to the public-site identity');
if(!str_contains($studentShell,'student-bottom-nav'))fail('student shell has no mobile navigation');
if(!str_contains($studentHome,'student-dashboard'))fail('student home is still the old sparse card list');
if(str_contains($studentHome,'style='))fail('student home contains inline layout styles');
foreach(['Cena e exposição','Revelação','Resultado'] as $stageLabel)if(!str_contains($studentTests,$stageLabel))fail('student test stage missing: '.$stageLabel);
if(!str_contains($studentTests,'capture="environment"'))fail('student test scene/result workflow does not offer direct camera capture');
if(!str_contains($studentTests,'value="scene"')||!str_contains($studentTests,'value="result"'))fail('student images are not classified as scene/result');
if(!str_contains($studentTestHelpers,'function student_test_update_stage'))fail('staged test updates are not partial');
if(!str_contains($studentTestHelpers,"['scene','result']"))fail('media kind helper does not constrain scene/result roles');
if(!str_contains($studentCss,'.student-test-progress')||!str_contains($studentCss,'.student-bottom-nav'))fail('mobile test application styles are missing');
if(!str_contains($adminFormCss,'form.admin-form-grid[enctype="multipart/form-data"]'))fail('private media upload overflow regression is not guarded');

echo "student-area-ia: ok\n";
