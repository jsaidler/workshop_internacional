<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function ux_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"ux-audit: $message\n");exit(1);}}
$adminShell=(string)file_get_contents($root.'/app/admin_shell.php');
$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
$courses=(string)file_get_contents($root.'/admin/courses.php');
$registrations=(string)file_get_contents($root.'/admin/registrations.php');
$people=(string)file_get_contents($root.'/admin/people.php');
$studentIndex=(string)file_get_contents($root.'/aluno/index.php');
$studentTests=(string)file_get_contents($root.'/aluno/testes.php');
$css=(string)file_get_contents($root.'/assets/experience-ux.css');
$dataCss=(string)file_get_contents($root.'/assets/admin-data-ux.css');
$doc=(string)file_get_contents($root.'/docs/ADMIN_STUDENT_UX_AUDIT_2026-09-28.md');

ux_expect(str_contains($adminShell,'function admin_navigation_groups'),'administração precisa de uma árvore global explícita');
ux_expect(str_contains($adminShell,"'Principal'=>")&&str_contains($adminShell,"'Conteúdo'=>")&&str_contains($adminShell,"'Cursos'=>")&&str_contains($adminShell,"'Sistema'=>"),'sidebar deve agrupar funções sem depender do volume atual');
ux_expect(str_contains($adminShell,"'forms'=>['Formulários'"),'Formulários deve ficar em Conteúdo');
ux_expect(str_contains($adminShell,"'integrity'=>['Integridade'"),'Integridade deve ficar em Sistema');
ux_expect(!str_contains($adminShell,'class="admin-context-nav"'),'não pode existir segunda barra horizontal global concorrente');
ux_expect(str_contains($adminShell,'function admin_course_context'),'curso precisa de uma única navegação contextual compartilhada');
ux_expect(str_contains($courses,"\$view=(string)(\$_GET['view']??'overview')")&&str_contains($courses,"['overview','setup','cohorts','students','lessons','material']"),'curso deve abrir em visão geral');
ux_expect(str_contains($courses,'admin_course_context($course'),'administração do curso deve consumir a navegação contextual global');
ux_expect(str_contains($courses,'Buscar curso')&&str_contains($courses,'LIMIT $coursePageSize OFFSET $courseOffset'),'listagem de cursos deve pesquisar e paginar no servidor');
ux_expect(str_contains($courses,'Buscar aluno')&&str_contains($courses,'LIMIT $studentPageSize OFFSET $studentOffset'),'alunos do curso devem pesquisar e paginar no servidor');
ux_expect(!str_contains($courses,'<div class="overview-grid">'),'coleção de cursos não deve voltar a ser grade de cards');
ux_expect(str_contains($registrations,'admin_course_context($course,$activityId,\'registrations\''),'Inscrições deve pertencer ao contexto do curso');
ux_expect(str_contains($registrations,'Disponibilidade agregada'),'Inscrições deve oferecer leitura agregada de disponibilidade');
ux_expect(str_contains($registrations,'Confirmadas sem turma'),'Inscrições deve manter estado operacional sem turma');
ux_expect(str_contains($registrations,'Buscar inscrição')&&str_contains($registrations,'LIMIT $pageSize OFFSET $offset'),'Inscrições deve pesquisar e paginar no servidor');
ux_expect(!str_contains($registrations,'LIMIT 500'),'Inscrições não pode truncar silenciosamente em 500 registros');
ux_expect(str_contains($people,'LIMIT $pageSize OFFSET $offset')&&str_contains($people,'admin-pagination'),'Pessoas deve possuir paginação real');
ux_expect(!str_contains($people,'LIMIT 500'),'Pessoas não pode truncar silenciosamente em 500 registros');

ux_expect(str_contains($studentShell,'function student_course_context_header'),'área do aluno precisa de cabeçalho contextual canônico do curso');
ux_expect(str_contains($studentShell,'>Meus cursos</a>'),'topbar global deve expor Meus cursos');
ux_expect(!str_contains($studentShell,'student-desktop-nav" aria-label="Área do aluno"><a href="/aluno/"') || !str_contains($studentShell,'>Testes</a><a href="/aluno/perfil.php"'),'Testes não pode continuar como item global paralelo ao curso');
ux_expect(str_contains($studentIndex,'student_course_context_header($enrollment,\'overview\''),'visão geral deve usar contexto canônico do curso');
ux_expect(str_contains($studentTests,'student_course_context_header($enrollment,\'tests\''),'testes deve usar o mesmo contexto canônico do curso');
ux_expect(str_contains($studentTests,'Todo o curso'),'linguagem deve usar curso, não workshop, no compartilhamento contextual');

ux_expect(str_contains($css,'--ux-space-7:48px'),'sistema deve possuir escala global de espaçamento');
ux_expect(str_contains($css,'.admin-content{display:flex;flex-direction:column;gap:var(--ux-space-6)'),'ritmo de primeiro nível da administração deve ser global');
ux_expect(str_contains($dataCss,'row-gap:var(--ux-space-5,24px)'),'formulários administrativos devem consumir a escala global');
ux_expect(str_contains($dataCss,'gap:var(--ux-space-4,16px)'),'componentes de dados devem usar tokens da escala, não valores intermediários arbitrários');
ux_expect(str_contains($css,'.student-form-grid{row-gap:'),'forms do aluno devem consumir ritmo global');
ux_expect(str_contains($css,'.student-course-context-nav'),'navegação contextual do aluno deve possuir primitiva visual global');
ux_expect(str_contains($doc,'Regra para volume de dados'),'documentação deve projetar a administração para volume futuro');
ux_expect(str_contains($doc,'uma única árvore global de navegação administrativa'),'documentação deve fechar a hierarquia administrativa única');
ux_expect(str_contains($doc,'procurar → consumir → identificar lacuna'),'documentação deve preservar política de consumo global');

echo "ux-audit: ok\n";
