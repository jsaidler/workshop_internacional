<?php
declare(strict_types=1);

$root=dirname(__DIR__);
function ux_audit_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"UX AUDIT FAIL: $message\n");exit(1);}}

$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$courses=(string)file_get_contents($root.'/admin/courses.php');
$registrations=(string)file_get_contents($root.'/admin/registrations.php');
$pages=(string)file_get_contents($root.'/admin/pages.php');
$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
$studentHome=(string)file_get_contents($root.'/aluno/index.php');
$studentTests=(string)file_get_contents($root.'/aluno/testes.php');
$renderer=(string)file_get_contents($root.'/app/cms_renderer.php');
$studentShellCss=(string)file_get_contents($root.'/assets/student-shell.css');
$adminCss=(string)file_get_contents($root.'/assets/admin-data-ux.css');
$doc=(string)file_get_contents($root.'/docs/UI_UX_AUDIT_ADMIN_STUDENT_2026-09-27.md');

ux_audit_expect(str_contains($shell,"'courses'=>['Cursos'"),'Navegação principal deve expor Cursos como domínio próprio.');
ux_audit_expect(str_contains($shell,"'forms'=>['Formulários'")&&str_contains($shell,"'responses'=>['Respostas'"),'Formulários e respostas devem pertencer a Conteúdo.');
ux_audit_expect(str_contains($shell,"'integrity'=>['Diagnóstico de dados'"),'Integridade deve ficar em Configurações como diagnóstico técnico.');
ux_audit_expect(str_contains($shell,'admin_course_context_nav'),'Curso deve ter uma navegação contextual compartilhada.');
foreach(['Visão geral','Inscrições','Turmas','Alunos','Aulas','Material'] as $label)ux_audit_expect(str_contains($shell,$label),'Navegação do curso perdeu: '.$label);

ux_audit_expect(!str_contains($courses,"action==='create_from_page'"),'Cursos não deve criar curso a partir de um formulário paralelo.');
ux_audit_expect(!str_contains($courses,'Associar página como curso'),'Tela Cursos não deve oferecer criação paralela de curso.');
ux_audit_expect(str_contains($courses,"??'overview'")&&str_contains($courses,"view==='setup'"),'Curso deve abrir pela Visão geral e manter compatibilidade da rota antiga.');
ux_audit_expect(str_contains($courses,'admin_course_context_nav'),'Curso deve consumir a navegação contextual compartilhada.');
ux_audit_expect(str_contains($courses,'course-summary-stats'),'Visão geral do curso deve expor indicadores operacionais.');
ux_audit_expect(str_contains($pages,"action==='course'"),'Páginas deve ser o ponto de entrada explícito para atribuir papel de curso.');
ux_audit_expect(str_contains($pages,'Usar esta página como curso')&&str_contains($pages,'Administrar curso'),'Páginas deve expor o papel de curso sem editor paralelo.');

ux_audit_expect(str_contains($registrations,'registration-summary-stats'),'Inscrições deve mostrar resumo operacional por estado.');
ux_audit_expect(str_contains($registrations,'registration_availability_groups'),'Inscrições deve agregar disponibilidades do formulário.');
ux_audit_expect(str_contains($registrations,'Confirmadas sem turma')&&str_contains($registrations,'Aguardando pagamento'),'Pagamento e atribuição de turma devem aparecer como estados distintos.');
ux_audit_expect(str_contains($registrations,'admin_course_context_nav')&&str_contains($registrations,"'registrations'"),'Inscrições deve permanecer dentro do contexto do curso.');

ux_audit_expect(str_contains($studentShell,'student_shell_topbar_markup'),'Barra superior do aluno deve possuir uma única implementação canônica.');
ux_audit_expect(str_contains($studentShell,'student_course_context_markup'),'Contexto de curso deve ser um componente de conteúdo compartilhado.');
ux_audit_expect(str_contains($studentShell,'>Meus cursos</a>')&&str_contains($studentShell,'>Conta</a>'),'Navegação global do aluno deve conter apenas destinos globais.');
ux_audit_expect(!str_contains($studentShell,'href="/aluno/testes.php"'),'Testes não deve continuar como navegação global paralela ao curso.');
ux_audit_expect(!str_contains($studentShell,'<span>01</span>'),'Navegação mobile não deve numerar itens arbitrariamente.');
ux_audit_expect(str_contains($studentShell,'>Claro</button>')&&str_contains($studentShell,'>Escuro</button>'),'Tema deve consumir o controle global com rótulos locais.');

ux_audit_expect(str_contains($renderer,'student_shell_topbar_markup')&&str_contains($renderer,"'courses'"),'Material CMS deve reutilizar exatamente a barra canônica do aluno.');
ux_audit_expect(str_contains($renderer,'student_course_context_markup')&&str_contains($renderer,"'material'"),'Material CMS deve manter o mesmo contexto do curso.');
ux_audit_expect(str_contains($renderer,'student-material-page'),'Renderer deve distinguir material em contexto autenticado sem criar outro renderer.');
ux_audit_expect(str_contains($renderer,'if($materialContext&&$currentStudent)')&&str_contains($renderer,'data-cms-public-header'),'Barra pública e barra do aluno devem ser mutuamente exclusivas no material.');

ux_audit_expect(str_contains($studentHome,'student-resource-list')&&str_contains($studentHome,'student-resource-row'),'Várias páginas de material devem aparecer como lista de recursos.');
ux_audit_expect(str_contains($studentHome,'student_course_context_markup')&&str_contains($studentHome,"'overview'"),'Visão geral deve consumir o contexto canônico do curso.');
ux_audit_expect(str_contains($studentHome,"'Liberada'")&&str_contains($studentHome,"'Aguardando'"),'Estados de aula devem usar linguagem direta.');
ux_audit_expect(str_contains($studentTests,'student_course_context_markup')&&str_contains($studentTests,"'tests'"),'Testes devem estar subordinados ao contexto do curso.');
ux_audit_expect(!str_contains($studentTests,'Todos os workshops')&&!str_contains($studentTests,'← Workshop'),'Testes não deve preservar navegação paralela por workshop.');

ux_audit_expect(str_contains($studentShellCss,'--student-space-5:24px'),'Shell do aluno deve declarar escala de ritmo vertical.');
ux_audit_expect(str_contains($studentShellCss,'.student-page .student-form-grid')&&str_contains($studentShellCss,'row-gap:var(--student-space-5)'),'Formulários do aluno não podem zerar o espaçamento vertical.');
ux_audit_expect(str_contains($studentShellCss,'.student-topbar')&&str_contains($studentShellCss,'.student-course-context'),'Shell deve separar barra global e contexto do curso.');
ux_audit_expect(str_contains($adminCss,'--admin-space-5:24px')&&str_contains($adminCss,'row-gap:18px'),'Administração deve aplicar escala e espaçamento vertical aos formulários.');
ux_audit_expect(str_contains($adminCss,'.registration-availability-groups'),'Resumo de disponibilidade deve usar composição global da administração.');

ux_audit_expect(str_contains($doc,'uma única barra superior')||str_contains($doc,'uma única barra superior canônica'),'Auditoria deve documentar a autoridade única da barra do aluno.');
ux_audit_expect(str_contains($doc,'Ritmo vertical')&&str_contains($doc,'Disponibilidade agregada'),'Auditoria deve documentar espaçamento e fluxo operacional de inscrições.');
ux_audit_expect(str_contains($doc,'falhar fechado')||str_contains($doc,'falha fechado'),'Auditoria deve preservar a regressão de segurança por curso.');

echo "Admin/student UI UX audit regression passed.\n";
