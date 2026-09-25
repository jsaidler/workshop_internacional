<?php
declare(strict_types=1);

function fail(string $message): never {fwrite(STDERR,"student-area-ia: $message\n");exit(1);}
$root=dirname(__DIR__);
$area=(string)file_get_contents($root.'/admin/student-area.php');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$legacy=(string)file_get_contents($root.'/admin/student-operations.php');

if(str_contains($shell,'Operações e testes'))fail('artificial Operations and tests context item is still visible');
if(!str_contains($shell,"'students'=>['Área do aluno'"))fail('student area context item missing');
if(!str_contains($area,"$views=['overview','cohorts','students','tests','lessons','pages']"))fail('canonical object order is not encoded');

$labels=['Visão geral','Turmas','Alunos','Testes','Aulas','Páginas protegidas'];
$last=-1;foreach($labels as $label){$pos=strpos($area,"'".$label."'");if($pos===false)fail('navigation label missing: '.$label);if($pos<=$last)fail('student area navigation order changed around '.$label);$last=$pos;}
if(!str_contains($area,"$action==='update_cohort'"))fail('cohort editing is not part of the student area');
if(!str_contains($area,"$action==='import_students'"))fail('historical import is not part of the student area');
if(!str_contains($area,"return_view\" value=\"students"))fail('historical import is not contextualized under Students');
if(!str_contains($area,"$view==='tests'"))fail('tests are not a first-class student area view');
if(str_contains($area,'Dados das turmas'))fail('legacy task label leaked into canonical navigation');
if(str_contains($area,'Operações e testes'))fail('legacy task grouping leaked into canonical page');
if(str_contains($legacy,'admin_shell_start('))fail('legacy operations page still renders a parallel admin surface');
if(!str_contains($legacy,"'import'=>'students'")||!str_contains($legacy,"'tests'=>'tests'"))fail('legacy routes do not redirect to canonical object views');

echo "student-area-ia: ok\n";
