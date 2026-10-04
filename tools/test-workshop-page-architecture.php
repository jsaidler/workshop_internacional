<?php
declare(strict_types=1);

function fail_workshop_page_architecture(string $message): never {fwrite(STDERR,"workshop-page-architecture: $message\n");exit(1);}
function must_workshop_page_architecture(bool $condition,string $message): void {if(!$condition)fail_workshop_page_architecture($message);}

$root=dirname(__DIR__);
$courseArchitecture=(string)file_get_contents($root.'/docs/COURSE_DOMAIN_REGISTRATION_MATERIAL_ARCHITECTURE_2026-09-27.md');
$workshopTransition=(string)file_get_contents($root.'/docs/WORKSHOP_PAGE_REGISTRATION_STUDENT_ARCHITECTURE_2026-09-27.md');
$agents=(string)file_get_contents($root.'/AGENTS.md');
$activities=(string)file_get_contents($root.'/admin/activities.php');
$activityRepo=(string)file_get_contents($root.'/app/activity_repository.php');
$adminShell=(string)file_get_contents($root.'/app/admin_shell.php');
$renderer=(string)file_get_contents($root.'/app/cms_renderer.php');
$headerCss=(string)file_get_contents($root.'/assets/cms-header.css');

must_workshop_page_architecture(str_contains($courseArchitecture,'**Curso** é a entidade de domínio'),'canonical course-domain authority is missing');
must_workshop_page_architecture(str_contains($courseArchitecture,'Material didático **continua sendo página CMS normal**'),'material no longer preserves the canonical CMS page/editor');
must_workshop_page_architecture(str_contains($workshopTransition,'`activity` permanece como contêiner interno'),'transitional rule removing activity from the workshop role is missing');
must_workshop_page_architecture(str_contains($agents,'COURSE_DOMAIN_REGISTRATION_MATERIAL_ARCHITECTURE_2026-09-27.md'),'mandatory repository reading does not include canonical course architecture');

must_workshop_page_architecture(!str_contains($activities,'activity_create('),'normal admin still creates activities as courses');
must_workshop_page_architecture(!str_contains($activities,'Novo curso ou workshop')&&!str_contains($activities,'Criar curso'),'normal admin still presents activity creation as course creation');
must_workshop_page_architecture(str_contains($activities,"activity_archive_legacy_site"),'legacy activity cannot be removed from normal operation safely');

must_workshop_page_architecture(str_contains($activityRepo,"return ['count'=>1,'activity'=>\$root,'activities'=>[\$root]]"),'admin activity resolution is not root-site only');
must_workshop_page_architecture(str_contains($activityRepo,'activity_legacy_non_root'),'legacy non-root activities are not isolated for maintenance');
must_workshop_page_architecture(!str_contains($adminShell,'Selecionar curso ou workshop')&&!str_contains($adminShell,'Gerenciar cursos e workshops'),'admin shell still exposes activity switching as course selection');
must_workshop_page_architecture(str_contains($adminShell,"'activities'=>['Estrutura do site','/admin/activities.php']"),'settings does not expose neutral site-structure maintenance');

must_workshop_page_architecture(str_contains($renderer,'cms_access_filter_html($db,$activity,$body,$currentStudent,$editor,null,$materialContext)'),'public renderer bypasses canonical section access filter with enrollment context');
must_workshop_page_architecture(!str_contains($renderer,'data-cms-student-context')&&!str_contains($headerCss,'.cms-student-context'),'protected material still renders a second student header bar');
must_workshop_page_architecture(str_contains($renderer,'cms_student_material_context_html')&&str_contains($renderer,"'/aluno/cursos.php?cohort='")&&str_contains($renderer,"'/aluno/duvidas.php?cohort='"),'protected material does not expose the current course context and course Dúvidas destination');
must_workshop_page_architecture(!str_contains($renderer,'/aluno/testes.php?cohort='),'protected material still exposes the obsolete Tests destination');

echo "workshop-page-architecture: ok\n";
