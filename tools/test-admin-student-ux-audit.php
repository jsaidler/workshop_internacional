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
$studentHome=(string)file_get_contents($root.'/aluno/index.php');
$studentCourses=(string)file_get_contents($root.'/aluno/cursos.php');
$studentNotebook=(string)file_get_contents($root.'/aluno/caderno.php');
$studentRecord=(string)file_get_contents($root.'/aluno/teste.php');
$studentBench=(string)file_get_contents($root.'/aluno/ferramentas.php');
$legacyTests=(string)file_get_contents($root.'/aluno/testes.php');
$uiCss=(string)file_get_contents($root.'/assets/ui-core.css');
$adminCss=(string)file_get_contents($root.'/assets/admin-system.css');
$studentCss=(string)file_get_contents($root.'/assets/student-area.css');
$experienceCss=(string)file_get_contents($root.'/assets/student-experience.css');
$renderedCss=(string)file_get_contents($root.'/assets/student-rendered-fixes.css');
$doc=(string)file_get_contents($root.'/docs/ADMIN_STUDENT_UX_AUDIT_2026-09-28.md');
$studentDoc=(string)file_get_contents($root.'/docs/STUDENT_AREA_WORKFLOW_REDESIGN_2026-09-30.md');

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
foreach([$courses,$registrations,$cohorts,$students,$people,$lessons,$material] as $collection){ux_expect(str_contains($collection,'admin-data-toolbar'),'coleções devem consumir a barra de dados canônica');ux_expect(str_contains($collection,'admin-data-table'),'coleções devem usar tabela densa canônica');}

foreach(['>Início</a>','>Curso</a>','>Caderno</a>'] as $destination)ux_expect(str_contains($studentShell,$destination),'navegação principal do aluno perdeu '.$destination);
ux_expect(str_contains($studentShell,'data-student-toolbox')&&str_contains($studentShell,'data-toolbox-open'),'ferramentas pequenas deixaram de ser contextuais');
ux_expect(str_contains($studentShell,'student-user-menu')&&str_contains($studentShell,'Gerenciar conta'),'conta, tema e sessão devem ficar subordinados');
ux_expect(str_contains($studentHome,'student_experience_process_state')&&str_contains($studentHome,'<h1 class="student-title">Início</h1>'),'Início deve continuar trabalho real sem texto de bastidor');
ux_expect(str_contains($studentCourses,'elseif(count($enrollments)===1)$selectedEnrollment=$enrollments[0]'),'uma única matrícula não pode exigir página intermediária');
ux_expect(str_contains($studentCourses,'student-course-dashboard')&&str_contains($studentCourses,'Dúvidas e respostas'),'curso deve reunir material, aulas e dúvidas no mesmo workspace');
ux_expect(str_contains($studentNotebook,'Mais ações')&&str_contains($studentNotebook,'student_experience_process_state'),'Caderno deve priorizar estado e continuação do registro');
ux_expect(str_contains($studentNotebook,'data-record-create-dialog'),'novo registro deve abrir diretamente em diálogo, sem etapa intermediária');
ux_expect(!str_contains($studentNotebook,'student-process-compare'),'comparação não pode ocupar permanentemente cada registro');
ux_expect(str_contains($studentRecord,'student-process-now')&&str_contains($studentRecord,'student-process-history'),'processamento deve priorizar o passo atual sobre o histórico');
ux_expect(str_contains($studentRecord,'data-lab-timer')&&str_contains($studentRecord,'Desfazer última etapa'),'processamento deve integrar temporizador e subordinar correções');
ux_expect(str_contains($studentBench,'data-exposure-tool')&&str_contains($studentBench,'data-quick-reciprocity')&&str_contains($studentBench,'data-lab-timer'),'ferramentas pequenas não podem virar subpáginas');
ux_expect(str_contains($studentBench,'Modo de preparo')&&str_contains($studentBench,'student_experience_recipe_notes'),'receitas devem reunir cálculo e modo de preparo');
foreach(['Continue de onde faz sentido','Ferramentas pequenas ficam aqui','sem entrar e sair de várias páginas','O histórico fica abaixo','Aqui o foco é somente a etapa atual'] as $internalCopy)ux_expect(!str_contains($studentHome.$studentBench.$studentRecord,$internalCopy),'texto interno vazou para a interface do aluno: '.$internalCopy);
ux_expect(str_contains($legacyTests,"header('Location: /aluno/caderno.php'"),'rota legada de testes deve convergir para o Caderno global');

ux_expect(str_contains($uiCss,'--ux-space-7:48px'),'sistema deve possuir escala global de espaçamento na camada de primitivas');
ux_expect(str_contains($adminCss,'.admin-content{display:flex;flex-direction:column;gap:var(--ux-space-6)'),'ritmo de primeiro nível da administração deve pertencer à autoridade administrativa');
ux_expect(str_contains($adminCss,'row-gap:var(--ux-space-5,24px)'),'formulários administrativos devem consumir a escala global');
ux_expect(str_contains($adminCss,'gap:var(--ux-space-4,16px)'),'componentes de dados devem usar tokens da escala');
ux_expect(str_contains($studentCss,'gap:var(--ux-space-5) 20px'),'forms do aluno devem consumir ritmo global');
ux_expect(str_contains($experienceCss,'.student-process-now')&&str_contains($experienceCss,'.student-toolbox')&&str_contains($experienceCss,'.student-bench-grid'),'camada de experiência deve expressar foco operacional e ferramentas contextuais');
ux_expect(str_contains($renderedCss,'.student-sticky-action{margin-top:30px;padding-top:22px')&&str_contains($renderedCss,'.student-form-grid{gap:28px 20px}'),'ação principal não pode ficar colada aos campos');
ux_expect(str_contains($studentCss,'.student-link{display:inline-flex;min-height:40px'),'ações textuais do aluno devem ter affordance explícita');
ux_expect(str_contains($doc,'Coleções são o eixo primário'),'documentação administrativa deve fixar a arquitetura por coleções');
ux_expect(str_contains($doc,'Curso como catálogo e filtro'),'documentação administrativa deve impedir retorno da árvore local de curso');
ux_expect(str_contains($doc,'procurar → consumir → identificar lacuna'),'documentação deve preservar política de consumo global');
ux_expect(str_contains($studentDoc,'Secagem encerra o processamento')&&str_contains($studentDoc,'Uma utilidade simples não ganha subpágina'),'documentação canônica da experiência do aluno está incompleta');

echo "ux-audit: ok\n";
