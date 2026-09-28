<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function ux_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"ux-audit: $message\n");exit(1);}}
$adminShell=(string)file_get_contents($root.'/app/admin_shell.php');
$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
$courses=(string)file_get_contents($root.'/admin/courses.php');
$registrations=(string)file_get_contents($root.'/admin/registrations.php');
$studentIndex=(string)file_get_contents($root.'/aluno/index.php');
$studentTests=(string)file_get_contents($root.'/aluno/testes.php');
$css=(string)file_get_contents($root.'/assets/experience-ux.css');
$doc=(string)file_get_contents($root.'/docs/ADMIN_STUDENT_UX_AUDIT_2026-09-28.md');

ux_expect(str_contains($adminShell,"'courses'=>['Cursos'"),'navegação primária deve ter Cursos como workspace próprio');
ux_expect(str_contains($adminShell,"'forms'=>['Formulários'"),'Formulários deve ficar em Conteúdo');
ux_expect(str_contains($adminShell,"'integrity'=>['Integridade'"),'Integridade deve ficar em Configurações');
ux_expect(str_contains($adminShell,'function admin_course_context'),'curso precisa de uma única navegação contextual compartilhada');
ux_expect(str_contains($courses,"'overview'=>'overview'"),'curso deve abrir em visão geral');
ux_expect(str_contains($courses,'admin_course_context($course'),'administração do curso deve consumir a navegação contextual global');
ux_expect(str_contains($courses,'Configuração avançada'),'criação/associação de curso deve ser secundária');
ux_expect(str_contains($registrations,"admin_course_context($course,$activityId,'registrations'"),'Inscrições deve pertencer ao contexto do curso');
ux_expect(str_contains($registrations,'Disponibilidade agregada'),'Inscrições deve oferecer leitura agregada de disponibilidade');
ux_expect(str_contains($registrations,'Confirmadas sem turma'),'Inscrições deve manter estado operacional sem turma');

ux_expect(str_contains($studentShell,'function student_course_context_header'),'área do aluno precisa de cabeçalho contextual canônico do curso');
ux_expect(str_contains($studentShell,'>Meus cursos</a>'),'topbar global deve expor Meus cursos');
ux_expect(!str_contains($studentShell,'student-desktop-nav" aria-label="Área do aluno"><a href="/aluno/"') || !str_contains($studentShell,'>Testes</a><a href="/aluno/perfil.php"'),'Testes não pode continuar como item global paralelo ao curso');
ux_expect(str_contains($studentIndex,"student_course_context_header($enrollment,'overview'"),'visão geral deve usar contexto canônico do curso');
ux_expect(str_contains($studentTests,"student_course_context_header($enrollment,'tests'"),'testes deve usar o mesmo contexto canônico do curso');
ux_expect(str_contains($studentTests,'Todo o curso'),'linguagem deve usar curso, não workshop, no compartilhamento contextual');

ux_expect(str_contains($css,'--ux-space-7:48px'),'sistema deve possuir escala global de espaçamento');
ux_expect(str_contains($css,'.admin-form-grid{row-gap:'),'forms administrativos devem consumir ritmo global');
ux_expect(str_contains($css,'.student-form-grid{row-gap:'),'forms do aluno devem consumir ritmo global');
ux_expect(str_contains($css,'.student-course-context-nav'),'navegação contextual deve possuir primitiva visual global');
ux_expect(str_contains($doc,'Existe uma única barra superior global canônica'),'documentação deve fechar a regra de uma única topbar');
ux_expect(str_contains($doc,'procurar → consumir → identificar lacuna'),'documentação deve preservar política de consumo global');

echo "ux-audit: ok\n";
