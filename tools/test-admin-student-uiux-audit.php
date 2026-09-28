<?php
declare(strict_types=1);

function fail_uiux_audit(string $message): never {fwrite(STDERR,"admin-student-uiux-audit: $message\n");exit(1);}
function must_uiux_audit(bool $ok,string $message): void {if(!$ok)fail_uiux_audit($message);}

$root=dirname(__DIR__);
$doc=(string)file_get_contents($root.'/docs/ADMIN_STUDENT_UIUX_AUDIT_2026-09-27.md');
$adminShell=(string)file_get_contents($root.'/app/admin_shell.php');
$adminCss=(string)file_get_contents($root.'/assets/admin-ux-v4.css');
$courses=(string)file_get_contents($root.'/admin/courses.php');
$registrations=(string)file_get_contents($root.'/admin/registrations.php');
$people=(string)file_get_contents($root.'/admin/people.php');
$import=(string)file_get_contents($root.'/admin/student-import-csv.php');
$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
$studentCss=(string)file_get_contents($root.'/assets/student-area-v2.css');
$dashboard=(string)file_get_contents($root.'/aluno/index.php');
$material=(string)file_get_contents($root.'/aluno/material.php');
$tests=(string)file_get_contents($root.'/aluno/testes.php');
$test=(string)file_get_contents($root.'/aluno/teste.php');
$shared=(string)file_get_contents($root.'/aluno/teste-compartilhado.php');
$delete=(string)file_get_contents($root.'/aluno/excluir-teste.php');
$renderer=(string)file_get_contents($root.'/app/cms_renderer.php');

must_uiux_audit(str_contains($doc,'uma única barra superior canônica'),'audit does not establish a single canonical student topbar');
must_uiux_audit(str_contains($doc,'Ritmo vertical é parte do sistema'),'audit does not establish vertical rhythm as a system invariant');
must_uiux_audit(str_contains($doc,'Procurar → consumir → identificar lacuna'),'audit lost the global-authority consumption rule');

foreach(["'site'=>['Conteúdo'","'courses'=>['Cursos'","'media'=>['Mídia'","'analytics'=>['Métricas'","'settings'=>['Configurações'"] as $needle)must_uiux_audit(str_contains($adminShell,$needle),'admin primary IA missing '.$needle);
must_uiux_audit(str_contains($adminShell,"'pages','blocks','design','site','seo','forms','responses','overview'=>'site'"),'forms/responses are not part of content workspace');
must_uiux_audit(str_contains($adminShell,"'system','activities','integrity'=>'settings'"),'integrity is not isolated under settings');
must_uiux_audit(str_contains($adminShell,'admin_course_context_nav'),'admin course context has no canonical navigation helper');
foreach(['Visão geral','Inscrições','Turmas','Alunos','Aulas','Material'] as $label)must_uiux_audit(str_contains($adminShell,"'".$label."'"),'admin course tab missing '.$label);
must_uiux_audit(str_contains($adminShell,'/assets/admin-ux-v4.css'),'canonical admin UX layer is not loaded last');

must_uiux_audit(str_contains($courses,"\$view=(string)(\$_GET['view']??'overview')"),'course does not open on operational overview');
must_uiux_audit(str_contains($courses,'admin-course-summary')&&str_contains($courses,'admin-course-config'),'course overview/configuration hierarchy missing');
must_uiux_audit(str_contains($courses,'Importar alunos históricos')&&str_contains($courses,'name="course_id"'),'historical import is not inside explicit course/cohort context');
must_uiux_audit(str_contains($import,'A turma não pertence ao curso selecionado.'),'historical import endpoint does not validate course/cohort context');
must_uiux_audit(str_contains($registrations,'registration_availability_summary')&&str_contains($registrations,'admin-registration-summary'),'registrations are not operationally summarized');
must_uiux_audit(str_contains($registrations,'Disponibilidade declarada'),'aggregate availability is not exposed');
must_uiux_audit(str_contains($people,'FROM student_users u')&&str_contains($people,'Identidade global'),'people screen is not grounded in global identity');

foreach(['--admin-space-5:24px','.admin-card+.admin-card','.admin-form-grid','.registration-admin-group+.registration-admin-group','.admin-course-header','.admin-quick-grid'] as $needle)must_uiux_audit(str_contains($adminCss,$needle),'admin global rhythm/context missing '.$needle);

must_uiux_audit(str_contains($studentShell,'function student_global_topbar'),'student topbar is not a shared canonical component');
must_uiux_audit(str_contains($studentShell,'function student_global_mobile_nav'),'student mobile navigation is not a shared canonical component');
must_uiux_audit(str_contains($studentShell,'function student_course_context_header'),'student course context is not shared');
must_uiux_audit(str_contains($studentShell,'Meus cursos')&&str_contains($studentShell,'Conta'),'student global IA is incomplete');
must_uiux_audit(substr_count($studentShell,"'tests'=>['Testes'")===1,'tests must exist only in course context, not as a second global destination');
must_uiux_audit(str_contains($studentShell,'/assets/student-area-v2.css'),'student canonical UX layer is not loaded');

must_uiux_audit(str_contains($dashboard,"student_course_context_header(\$enrollment,'overview'"),'dashboard is not inside course context');
must_uiux_audit(str_contains($material,"student_course_context_header(\$enrollment,'material'"),'material index is not inside course context');
must_uiux_audit(str_contains($material,'student_enrollment_pages_for_enrollment')&&str_contains($material,'cms_page_url'),'material index does not reuse canonical CMS pages');
must_uiux_audit(str_contains($tests,"student_course_context_header(\$enrollment,'tests'"),'tests list is not inside course context');
must_uiux_audit(str_contains($test,"student_course_context_header(\$testContext,'tests'"),'owned test detail drops course context');
must_uiux_audit(str_contains($shared,"student_course_context_header(\$testContext,'tests'"),'shared test drops course context');
must_uiux_audit(str_contains($delete,"student_course_context_header(\$testContext,'tests'"),'test deletion drops course context');
foreach([$dashboard,$tests,$test,$shared,$delete] as $surface)must_uiux_audit(!str_contains($surface,'class="student-appbar"'),'legacy second topbar markup remains on a student surface');

must_uiux_audit(str_contains($renderer,"student_global_topbar(\$currentStudent,'courses')"),'protected material uses a different topbar implementation');
must_uiux_audit(str_contains($renderer,"student_global_mobile_nav(\$currentStudent,'courses')"),'protected material uses a different mobile navigation implementation');
must_uiux_audit(str_contains($renderer,"student_course_context_header(\$materialContext,'material'"),'protected material drops course context');
must_uiux_audit(str_contains($renderer,'cms_access_filter_html($db,$activity,$body,$currentStudent,$editor,null,$materialContext)'),'protected material lost server-side course/section filtering');

foreach(['--student-space-5:24px','.student-course-context','.student-message-form>.button','.student-login-card form>.button','.student-danger-zone form','.student-section+.student-section'] as $needle)must_uiux_audit(str_contains($studentCss,$needle),'student global rhythm missing '.$needle);
foreach(['.form-field{','.choice-field{','.button{','.ui-alert{'] as $forbidden)must_uiux_audit(!str_contains($studentCss,$forbidden),'student UX layer duplicates a global primitive: '.$forbidden);

echo "admin-student-uiux-audit: ok\n";
