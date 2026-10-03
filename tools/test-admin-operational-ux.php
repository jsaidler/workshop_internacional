<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$read=static fn(string $path): string=>(string)file_get_contents($root.'/'.$path);
$assert=static function(bool $condition,string $message): void {if(!$condition){fwrite(STDERR,$message."\n");exit(1);}};

$operationsCss=$read('assets/admin-operations.css');$teachingCss=$read('assets/admin-teaching.css');$collectionCss=$read('assets/admin-collection-ux.css');
$assert($operationsCss!==''&&$teachingCss!==''&&$collectionCss!=='','Canonical admin stylesheets must exist.');
$assert(!str_contains($operationsCss,'!important')&&!str_contains($teachingCss,'!important')&&!str_contains($collectionCss,'!important'),'Administrative workflow stylesheets must not use !important.');
$assert(str_contains($teachingCss,'.admin-workspace-context')&&str_contains($teachingCss,'.admin-workspace-nav'),'Teaching stylesheet must own course/cohort workspace navigation.');

$shell=$read('app/admin_shell.php');
foreach(['function admin_course_url(','function admin_cohort_url(','function admin_course_context(','function admin_cohort_context(','function admin_course_workspace_items(','function admin_cohort_workspace_items('] as $needle)$assert(str_contains($shell,$needle),'Missing canonical admin workspace helper: '.$needle);
$assert(str_contains($shell,"'courses'=>['Cursos'")&&str_contains($shell,"'students'=>['Alunos'"),'Global teaching navigation must expose Courses and Students.');
$assert(!str_contains($shell,"'registrations'=>['Inscrições',admin_shell_url")&&!str_contains($shell,"'cohorts'=>['Turmas',admin_shell_url")&&!str_contains($shell,"'people'=>['Pessoas',admin_shell_url"),'Course collections must not return as parallel global navigation destinations.');
foreach(['Visão geral','Inscrições','Turmas','Conteúdo','Acompanhamento'] as $label)$assert(str_contains($shell,"'$label'"),'Course workspace is missing area: '.$label);
foreach(['Alunos','Aulas e acesso','Dúvidas','Testes'] as $label)$assert(str_contains($shell,"'$label'"),'Cohort workspace is missing area: '.$label);

$registrations=$read('admin/registrations.php');
$assert(str_contains($registrations,'admin_course_context($course'),'Registrations must live inside a persistent course context.');
$assert(!str_contains($registrations,'Todos os cursos'),'Registrations must not mix courses by default.');
$assert(str_contains($registrations,'Pagas sem turma')&&str_contains($registrations,"'unassigned'"),'Confirmed registrations without cohort must be a first-class work queue.');
$assert(str_contains($registrations,'Adicionar à turma'),'Cohort assignment must be available from the registration detail.');
$assert(str_contains($registrations,'pode permanecer sem turma'),'Paid registration must remain valid without automatic cohort assignment.');
$assert(str_contains($registrations,'admin-operation-danger'),'Destructive registration actions must remain separated.');

$cohorts=$read('admin/cohorts.php');
$assert(str_contains($cohorts,'admin_course_context($course')&&str_contains($cohorts,'admin_cohort_context($course,$cohort'),'Cohorts must support both course collection and cohort workspace contexts.');
$assert(str_contains($cohorts,"$action==='update'")&&str_contains($cohorts,"$action==='archive'")&&str_contains($cohorts,"$action==='restore'"),'Cohort lifecycle must support edit, archive and restore.');
$assert(str_contains($cohorts,'Abrir turma'),'Cohort collection must open the operational workspace.');
$assert(str_contains($cohorts,'Visualizar como esta turma'),'Cohort workspace must expose effective-access preview.');
$assert(!str_contains($cohorts,'name="slug"'),'Ordinary cohort creation must not require an internal slug decision.');

$lessons=$read('admin/lessons.php');
$assert(str_contains($lessons,'admin_course_context($course')&&str_contains($lessons,'admin_cohort_context($course,$cohort'),'Lessons must distinguish course structure from cohort access.');
$assert(str_contains($lessons,"$action==='update_lesson'")&&str_contains($lessons,"$action==='move_lesson'"),'Course lesson structure must be editable and reorderable.');
$assert(str_contains($lessons,'Ver conteúdo afetado')&&str_contains($lessons,'admin_course_lesson_material_items'),'Release decisions must expose their material consequences.');
$assert(str_contains($lessons,'Liberar agora')&&str_contains($lessons,'Agendar')&&str_contains($lessons,'Bloquear'),'Cohort access controls must be operable inline.');
$assert(!str_contains($lessons,'Todos os cursos'),'Lesson screens must not reconstruct context with a global course filter.');

$material=$read('admin/material.php');
$assert(str_contains($material,'Organizar por aula'),'Material UI must describe the structural task instead of calling section mapping a release.');
$assert(!str_contains($material,'Gerenciar liberação'),'Material structure must not claim to perform cohort release.');
$assert(!str_contains($material,'Todos os cursos'),'Material must stay inside the selected course.');

$students=$read('admin/students.php');
$assert(str_contains($students,'Ver histórico completo'),'Student context must keep global identity history secondary.');
$assert(str_contains($students,'move_registration'),'Student card must support moving a source registration to another cohort.');
$assert(str_contains($students,'source_submission_id')&&str_contains($students,'inscrição original'),'Registration-backed participation must preserve the registration as lifecycle authority.');
$assert(str_contains($students,"'students-import'"),'CSV import must be reachable from the current cohort workspace.');
$assert(!str_contains($students,'registration_admin_url('),'Students page must not depend on a page-local registration helper.');

$tests=$read('admin/tests.php');
$assert(str_contains($tests,'student_tests_for_admin')&&str_contains($tests,'student_test_set_review_status')&&str_contains($tests,'student_test_add_admin_message'),'Current admin must restore test review, state and private feedback operations.');
$assert(str_contains($tests,'admin_cohort_context')&&str_contains($tests,'admin_course_context'),'Tests must work at cohort resolution and course aggregate levels.');

$import=$read('admin/student-import-csv.php');
$assert(str_contains($import,'admin_course_cohort')&&str_contains($import,'/admin/students.php'),'Historical import must validate course/cohort scope and return to the current workspace.');
$assert(!str_contains($import,'student-area.php'),'Import result must not return to the retired student-area surface.');

$preview=$read('admin/course-preview.php');
$assert(str_contains($preview,'cms_access_filter_html')&&str_contains($preview,"'cohort_id'=>$cohortId")&&str_contains($preview,"'course_id'=>$courseId"),'Cohort preview must pass through the real server-side access filter with explicit course/cohort context.');

$legacy=$read('admin/student-area.php');
$assert(str_contains($legacy,"'tests'=>'/admin/tests.php'")&&str_contains($legacy,"default=>'/admin/students.php'"),'Legacy admin routes must land on current workspaces rather than orphaned views.');

echo "admin-operational-ux: ok\n";
