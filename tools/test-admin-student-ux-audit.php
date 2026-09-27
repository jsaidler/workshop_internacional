<?php
declare(strict_types=1);

$root=dirname(__DIR__);
function ux_audit_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"UX AUDIT FAIL: $message\n");exit(1);}}

$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$courses=(string)file_get_contents($root.'/admin/courses.php');
$pages=(string)file_get_contents($root.'/admin/pages.php');
$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
$studentHome=(string)file_get_contents($root.'/aluno/index.php');
$studentCss=(string)file_get_contents($root.'/assets/student-area.css');
$doc=(string)file_get_contents($root.'/docs/UI_UX_AUDIT_ADMIN_STUDENT_2026-09-27.md');

ux_audit_expect(str_contains($shell,"'courses'=>['Cursos'"),'Navegação principal deve expor Cursos como domínio próprio.');
ux_audit_expect(str_contains($shell,"'forms'=>['Formulários'")&&str_contains($shell,"'responses'=>['Respostas'"),'Formulários e respostas devem voltar ao contexto de Conteúdo.');
ux_audit_expect(str_contains($shell,"'integrity'=>['Diagnóstico de dados'"),'Integridade deve ficar em Configurações como diagnóstico técnico.');
ux_audit_expect(!str_contains($courses,"action==='create_from_page'"),'Cursos não deve criar curso a partir de um formulário paralelo.');
ux_audit_expect(!str_contains($courses,'Associar página como curso'),'Tela Cursos não deve oferecer criação paralela de curso.');
ux_audit_expect(str_contains($courses,'course-overview-stats'),'Cursos deve apresentar indicadores operacionais.');
ux_audit_expect(str_contains($pages,"$action==='course'")||str_contains($pages,"action==='course'"),'Páginas deve ser o ponto de entrada explícito para atribuir papel de curso.');
ux_audit_expect(str_contains($pages,'Usar esta página como curso')&&str_contains($pages,'Administrar curso'),'Páginas deve expor o papel de curso sem criar editor paralelo.');
ux_audit_expect(str_contains($studentShell,'>Início</a>'),'Área do aluno deve usar Início como home da aplicação.');
ux_audit_expect(str_contains($studentShell,'>Claro</button>')&&str_contains($studentShell,'>Escuro</button>'),'Tema deve manter o controle global com rótulos locais compreensíveis.');
ux_audit_expect(!str_contains($studentShell,'<span>01</span>'),'Navegação mobile não deve numerar itens arbitrariamente.');
ux_audit_expect(str_contains($studentHome,'student-resource-list')&&str_contains($studentHome,'student-resource-row'),'Várias páginas de material devem aparecer como lista de recursos.');
ux_audit_expect(str_contains($studentHome,"'Liberada'")&&str_contains($studentHome,"'Aguardando'"),'Estados de aula devem usar linguagem direta.');
ux_audit_expect(str_contains($studentCss,'.student-resource-row')&&str_contains($studentCss,'text-transform:none'),'Composição do aluno deve reduzir hierarquia editorial sem duplicar primitivos globais.');
ux_audit_expect(str_contains($doc,'arquitetura de informação')&&str_contains($doc,'Área do aluno'),'Auditoria deve permanecer documentada.');

echo "Admin/student UI UX audit regression passed.\n";
