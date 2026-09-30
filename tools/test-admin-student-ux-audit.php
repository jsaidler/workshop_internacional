<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function ux_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"ux-audit: $message\n");exit(1);}}
$adminShell=(string)file_get_contents($root.'/app/admin_shell.php');
$studentShell=(string)file_get_contents($root.'/app/student_shell.php');
$courses=(string)file_get_contents($root.'/admin/courses.php');
$registrations=(string)file_get_contents($root.'/admin/registrations.php');
$cohorts=(string)file_get_contents($root.'/admin/cohorts.php');
$students=(string)file_get_contents($root.'/admin/students.php');
$people=(string)file_get_contents($root.'/admin/people.php');
$lessons=(string)file_get_contents($root.'/admin/lessons.php');
$material=(string)file_get_contents($root.'/admin/material.php');
$studentIndex=(string)file_get_contents($root.'/aluno/index.php');
$studentNotebook=(string)file_get_contents($root.'/aluno/caderno.php');
$legacyTests=(string)file_get_contents($root.'/aluno/testes.php');
$uiCss=(string)file_get_contents($root.'/assets/ui-core.css');
$adminCss=(string)file_get_contents($root.'/assets/admin-system.css');
$studentCss=(string)file_get_contents($root.'/assets/student-area.css');
$doc=(string)file_get_contents($root.'/docs/ADMIN_STUDENT_UX_AUDIT_2026-09-28.md');

ux_expect(str_contains($adminShell,'function admin_navigation_groups'),'administração precisa de uma árvore global explícita');
ux_expect(str_contains($adminShell,"'Principal'=>")&&str_contains($adminShell,"'Operação'=>")&&str_contains($adminShell,"'Ensino'=>")&&str_contains($adminShell,"'Site'=>")&&str_contains($adminShell,"'Sistema'=>"),'sidebar deve refletir trabalho operacional, pedagógico, site e sistema');
ux_expect(str_contains($adminShell,"'registrations'=>['Inscrições'")&&str_contains($adminShell,"'cohorts'=>['Turmas'")&&str_contains($adminShell,"'students'=>['Alunos'")&&str_contains($adminShell,"'people'=>['Pessoas'"),'Operação deve expor suas coleções globais');
ux_expect(str_contains($adminShell,"'courses'=>['Cursos'")&&str_contains($adminShell,"'lessons'=>['Aulas'")&&str_contains($adminShell,"'material'=>['Material'"),'Ensino deve expor suas coleções globais');
ux_expect(str_contains($adminShell,"'forms'=>['Formulários'"),'Formulários deve ficar em Site');
ux_expect(str_contains($adminShell,"'integrity'=>['Integridade'"),'Integridade deve ficar em Sistema');
ux_expect(!str_contains($adminShell,'class="admin-context-nav"'),'não pode existir segunda barra horizontal global concorrente');
ux_expect(!str_contains($adminShell,'class="admin-course-nav"'),'curso não pode recriar uma árvore horizontal paralela');
ux_expect(str_contains($adminShell,"'cohorts'=>'/admin/cohorts.php?")&&str_contains($adminShell,"'students'=>'/admin/students.php?")&&str_contains($adminShell,"'lessons'=>'/admin/lessons.php?")&&str_contains($adminShell,"'material'=>'/admin/material.php?"),'links de curso devem abrir coleções globais filtradas');

ux_expect(str_contains($courses,"['overview','setup']")||str_contains($courses,"['overview','setup'],true"),'curso deve conservar apenas visão geral e configuração locais');
ux_expect(str_contains($courses,"in_array(\$view,['cohorts','students','lessons','material']")&&str_contains($courses,'admin_course_url($activityId,$courseId,$view)'),'URLs legadas de subáreas do curso devem redirecionar para coleções filtradas');
ux_expect(str_contains($courses,'Buscar curso')&&str_contains($courses,'LIMIT $pageSize OFFSET $offset'),'catálogo de cursos deve pesquisar e paginar no servidor');
ux_expect(str_contains($courses,'Abrir filtrado')&&str_contains($courses,"admin_course_url(\$activityId,\$courseId,'registrations')"),'curso deve ser ponto de entrada para coleções filtradas');
ux_expect(!str_contains($courses,'elseif($view===\'students\')'),'alunos não devem voltar a ser subaplicação dentro de courses.php');

ux_expect(str_contains($registrations,'Todos os cursos')&&str_contains($registrations,'Todas as turmas'),'Inscrições deve ser coleção global filtrável');
ux_expect(str_contains($registrations,'Disponibilidade agregada'),'Inscrições deve oferecer leitura agregada quando há curso filtrado');
ux_expect(str_contains($registrations,'Confirmadas sem turma'),'Inscrições deve manter estado operacional sem turma');
ux_expect(str_contains($registrations,'LIMIT $pageSize OFFSET $offset')&&str_contains($registrations,'admin-pagination'),'Inscrições deve paginar no servidor');
ux_expect(!str_contains($registrations,'Escolha o curso para administrar'),'Inscrições não pode exigir curso antes de listar');
ux_expect(!str_contains($registrations,'submissions-layout inbox-layout'),'Inscrições não deve voltar ao padrão visual de caixa de entrada');
ux_expect(!str_contains($registrations,'LIMIT 500'),'Inscrições não pode truncar silenciosamente em 500 registros');

ux_expect(str_contains($cohorts,'Todos os cursos')&&str_contains($cohorts,'LIMIT $pageSize OFFSET $offset'),'Turmas deve ser coleção global filtrável e paginada');
ux_expect(str_contains($cohorts,"SUM(CASE WHEN e.status='active'"),'contagem de alunos por turma deve ser agregada sem N+1');
ux_expect(str_contains($students,'Todos os cursos')&&str_contains($students,'Todas as turmas')&&str_contains($students,'LIMIT $pageSize OFFSET $offset'),'Alunos deve ser coleção global com curso, turma e paginação');
ux_expect(str_contains($students,'/admin/people.php?')&&str_contains($students,"'person'=>(int)\$row['student_id']"),'Alunos deve abrir a identidade global correspondente');
ux_expect(str_contains($people,'LIMIT $pageSize OFFSET $offset')&&str_contains($people,'admin-pagination'),'Pessoas deve possuir paginação real');
ux_expect(str_contains($people,'Todos os cursos')&&str_contains($people,'EXISTS (SELECT 1 FROM course_enrollments'),'Pessoas deve permitir filtrar identidade por participação em curso');
ux_expect(!str_contains($people,'submissions-layout inbox-layout'),'Pessoas não deve ser tratada como caixa de entrada');
ux_expect(!str_contains($people,'LIMIT 500'),'Pessoas não pode truncar silenciosamente em 500 registros');
ux_expect(str_contains($lessons,'Todos os cursos')&&str_contains($lessons,'LIMIT $pageSize OFFSET $offset'),'Aulas deve ser coleção global filtrável e paginada');
ux_expect(str_contains($material,'Todos os cursos')&&str_contains($material,'LIMIT $pageSize OFFSET $offset'),'Material deve ser coleção global filtrável e paginada');
ux_expect(str_contains($material,'course_material_add_page'),'Material deve preservar a relação canônica com páginas CMS');

foreach([$courses,$registrations,$cohorts,$students,$people,$lessons,$material] as $collection){
    ux_expect(str_contains($collection,'admin-data-toolbar'),'coleções devem consumir a barra de dados canônica');
    ux_expect(str_contains($collection,'admin-data-table'),'coleções devem usar tabela densa canônica');
}

ux_expect(str_contains($studentShell,'function student_course_context_header'),'área do aluno precisa de cabeçalho contextual canônico do curso');
ux_expect(str_contains($studentShell,'>Meus cursos</a>'),'topbar global deve expor Meus cursos');
ux_expect(str_contains($studentShell,'/aluno/caderno.php')&&str_contains($studentShell,'>Caderno</a>'),'Caderno deve ser área global independente de curso');
ux_expect(str_contains($studentShell,'/aluno/ferramentas.php')&&str_contains($studentShell,'>Ferramentas</a>'),'Ferramentas deve ser área global independente de curso');
ux_expect(!str_contains($studentShell,'>Testes</a>'),'Testes não pode continuar como item global ou contextual');
ux_expect(str_contains($studentIndex,'student_course_context_header($enrollment,\'overview\''),'visão geral deve usar contexto canônico do curso');
ux_expect(str_contains($studentNotebook,'Caderno de Processos')&&str_contains($studentNotebook,'context_scope'),'Caderno deve aceitar registros globais com contexto opcional');
ux_expect(str_contains($studentNotebook,'value="course"')&&str_contains($studentNotebook,'>Curso</option>'),'compartilhamento contextual deve usar curso, não workshop');
ux_expect(str_contains($legacyTests,"header('Location: /aluno/caderno.php'"),'rota legada de testes deve convergir para o Caderno global');

ux_expect(str_contains($uiCss,'--ux-space-7:48px'),'sistema deve possuir escala global de espaçamento na camada de primitivas');
ux_expect(str_contains($adminCss,'.admin-content{display:flex;flex-direction:column;gap:var(--ux-space-6)'),'ritmo de primeiro nível da administração deve pertencer à autoridade administrativa');
ux_expect(str_contains($adminCss,'row-gap:var(--ux-space-5,24px)'),'formulários administrativos devem consumir a escala global');
ux_expect(str_contains($adminCss,'gap:var(--ux-space-4,16px)'),'componentes de dados devem usar tokens da escala');
ux_expect(str_contains($studentCss,'.student-form-grid{row-gap:')||str_contains($studentCss,'.student-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:var(--ux-space-5)'),'forms do aluno devem consumir ritmo global');
ux_expect(str_contains($studentCss,'.student-course-context-nav'),'navegação contextual do aluno deve possuir primitiva visual global');
ux_expect(str_contains($doc,'Coleções são o eixo primário'),'documentação deve fixar a arquitetura por coleções');
ux_expect(str_contains($doc,'Curso como catálogo e filtro'),'documentação deve impedir retorno da árvore local de curso');
ux_expect(str_contains($doc,'procurar → consumir → identificar lacuna'),'documentação deve preservar política de consumo global');

echo "ux-audit: ok\n";
