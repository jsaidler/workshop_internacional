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
$workspaceDoc=(string)file_get_contents($root.'/docs/ADMIN_COURSE_COHORT_WORKSPACE_2026-10-03.md');

if(str_contains($shell,'Operações e testes'))fail('artificial Operations and tests context item is still visible');
if(!str_contains($shell,"'courses'=>['Cursos'")||!str_contains($shell,"'students'=>['Alunos'"))fail('global teaching navigation must expose Cursos and Alunos');
if(!str_contains($shell,'function admin_course_workspace_items')||!str_contains($shell,'function admin_cohort_workspace_items'))fail('course/cohort workspace navigation missing');
foreach(["'registrations'=>['Inscrições',admin_course_url","'cohorts'=>['Turmas',admin_course_url","'content'=>['Conteúdo',admin_course_url","'followup'=>['Acompanhamento',admin_course_url"] as $needle)if(!str_contains($shell,$needle))fail('course workspace item missing: '.$needle);
foreach(["'students'=>['Alunos',admin_cohort_url","'lessons'=>['Aulas e acesso',admin_cohort_url","'questions'=>['Dúvidas',admin_cohort_url","'tests'=>['Testes',admin_cohort_url"] as $needle)if(!str_contains($shell,$needle))fail('cohort workspace item missing: '.$needle);
if(!str_contains($shell,"'integrity'=>['Integridade'"))fail('read-only integrity context item missing');
if(!str_contains($shell,"'system','activities','integrity'=>'settings'"))fail('integrity must live under settings, not operational navigation');
if(!str_contains($guard,"'tests'=>'/admin/tests.php'")||!str_contains($guard,"'lessons'=>'/admin/lessons.php'")||!str_contains($guard,"default=>'/admin/students.php'"))fail('legacy student-area route does not converge to current workspaces');
if(!str_contains($people,'FROM student_users u')||!str_contains($people,'c.course_id IS NULL'))fail('global people history depends on course completeness');
if(!str_contains($integrity,'Somente leitura')||str_contains($integrity,'REQUEST_METHOD'))fail('integrity view is not strictly read-only');
if(!str_contains($courseAdmin,'Abrir curso')||!str_contains($courseAdmin,'admin_course_context($course'))fail('course catalog does not enter the persistent course workspace');
if(!str_contains($cohorts,'admin_course_context($course')||!str_contains($cohorts,'admin_cohort_context($course,$cohort'))fail('cohorts are not operated inside course/cohort workspaces');
if(!str_contains($students,'Busca global de pessoas com histórico de participação nos cursos.')||!str_contains($students,'admin_cohort_context($course,$cohort'))fail('students do not combine global identity search with cohort operation');
if(!str_contains($lessons,'admin_course_context($course')||!str_contains($lessons,'admin_cohort_context($course,$cohort'))fail('lessons do not separate course structure from cohort access');
if(!str_contains($material,'course_material_add_page')||!str_contains($material,'/editor/?page='))fail('material workspace does not preserve canonical CMS page/editor relation');
if(str_contains($registrations,'Todos os cursos')||!str_contains($registrations,'Pagas sem turma')||!str_contains($registrations,'Ver disponibilidade agregada'))fail('registrations do not preserve the canonical course-scoped workflow');
if(str_contains($legacy,'admin_shell_start('))fail('legacy operations page still renders a parallel admin surface');
if(!str_contains($legacy,"'import','students'=>'/admin/students.php'")||!str_contains($legacy,"'tests'=>'/admin/tests.php'"))fail('legacy routes do not redirect to canonical object views');
foreach(['cms-access-audience','cms-access-availability','cms-visible-from','cms-access-lesson'] as $needle)if(!str_contains($editorAccess,$needle))fail('canonical editor missing section access property: '.$needle);
if(str_contains($adminJs,'studentCsvInput')||str_contains($adminJs,'Importar planilha'))fail('CSV UI still depends on JavaScript rewriting');
if(!str_contains($csvEndpoint,"strtolower(pathinfo(\$name,PATHINFO_EXTENSION))!=='csv'"))fail('CSV endpoint does not reject non-CSV uploads');
if(!str_contains($csvEndpoint,'admin_course_cohort($db,$courseId,$cohortId,false)'))fail('CSV endpoint does not validate cohort ownership in the current course');
if(!str_contains($csvEndpoint,"student_import_historical_students(\$db,\$activityId,\$cohortId,\$file)"))fail('CSV endpoint is not connected to the historical importer');
if(!str_contains($csvEndpoint,"'/admin/students.php?'.http_build_query"))fail('CSV endpoint does not return to Turma → Alunos');
if(!str_contains($workspaceDoc,'Inscrições deixam de misturar cursos por padrão')||!str_contains($workspaceDoc,'A importação CSV volta ao fluxo atual em `Turma → Alunos → Importar CSV`'))fail('workspace documentation does not match the current information architecture');
$csvHeader=ltrim(strtok($csvTemplate,"\r\n"),"\xEF\xBB\xBF");if($csvHeader!=='Nome;E-mail;CPF;Telefone;Instagram;Endereço;Cidade/UF;CEP')fail('CSV template header changed');

echo "student-area-ia: ok\n";
