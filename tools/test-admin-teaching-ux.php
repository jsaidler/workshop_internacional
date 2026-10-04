<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function teaching_ux_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"admin-teaching-ux: $message\n");exit(1);}}
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$courses=(string)file_get_contents($root.'/admin/courses.php');
$cohorts=(string)file_get_contents($root.'/admin/cohorts.php');
$lessons=(string)file_get_contents($root.'/admin/lessons.php');
$material=(string)file_get_contents($root.'/admin/material.php');
$teachingCss=(string)file_get_contents($root.'/assets/admin-teaching.css');
$collectionCss=(string)file_get_contents($root.'/assets/admin-collection-ux.css');

teaching_ux_expect(str_contains($shell,'/assets/admin-collection-ux.css'),'shell deve carregar primitivas compartilhadas de coleção');
teaching_ux_expect(str_contains($shell,"'release'=>['released'=>'Liberada'")&&str_contains($shell,"'page'=>['published'=>'Publicada'"),'estados de aula e página precisam de vocabulário humano compartilhado');
teaching_ux_expect(str_contains($collectionCss,'.admin-table-scroll:focus-visible'),'tabelas acessíveis devem pertencer ao contrato compartilhado');
teaching_ux_expect(str_contains($shell,'admin_course_workspace_items')&&str_contains($shell,'admin_cohort_workspace_items'),'curso e turma devem possuir navegação contextual persistente');

teaching_ux_expect(str_contains($courses,"'/assets/admin-teaching.css'"),'Cursos deve consumir a camada visual de Ensino');
teaching_ux_expect(str_contains($courses,'Abrir curso'),'catálogo deve conduzir ao workspace do curso');
teaching_ux_expect(str_contains($courses,'Precisa de atenção'),'visão do curso deve organizar trabalho e não apenas relações técnicas');
teaching_ux_expect(str_contains($courses,'Abrir turma'),'turmas devem ser objetos operacionais dentro do curso');
teaching_ux_expect(!str_contains($courses,'Curso é catálogo'),'interface não deve explicar arquitetura interna ao operador');

teaching_ux_expect(str_contains($cohorts,'Editar turma')&&str_contains($cohorts,'Arquivar turma'),'workspace da turma deve fechar seu ciclo administrativo');
teaching_ux_expect(str_contains($cohorts,'Aulas e acesso')&&str_contains($cohorts,'Acompanhamento'),'visão da turma deve expor as próximas tarefas reais');
teaching_ux_expect(str_contains($cohorts,'Visualizar como esta turma'),'turma deve permitir verificar o estado efetivo de acesso');

teaching_ux_expect(str_contains($lessons,'admin_course_context($course,$activityId,\'content\'')&&str_contains($lessons,"'Aulas'"),'estrutura de aulas deve pertencer ao contexto persistente do curso');
teaching_ux_expect(str_contains($lessons,'Aulas e acesso'),'disponibilidade deve pertencer à turma');
teaching_ux_expect(str_contains($lessons,'update_lesson')&&str_contains($lessons,'move_lesson'),'estrutura deve permitir corrigir e ordenar aulas existentes');
teaching_ux_expect(str_contains($lessons,'Ver conteúdo afetado'),'decisão de liberação deve revelar a consequência sobre o material');
teaching_ux_expect(str_contains($lessons,'name="scheduled_at"')&&str_contains($lessons,'value="schedule"')&&str_contains($lessons,'value="release"')&&str_contains($lessons,'value="block"'),'turma deve expor agendamento, liberação imediata e bloqueio');
teaching_ux_expect(str_contains($lessons,"admin_status_label('release'"),'estado de liberação não deve vazar enum técnico');
teaching_ux_expect(!str_contains($lessons,'Gerenciar liberação'),'liberação não deve exigir round-trip por detalhe de aula');

teaching_ux_expect(str_contains($material,'Organizar por aula'),'material deve apresentar a tarefa estrutural com linguagem operacional');
teaching_ux_expect(str_contains($material,'NOT EXISTS (SELECT 1 FROM course_material_pages'),'associação deve consultar apenas páginas ainda disponíveis');
teaching_ux_expect(str_contains($material,'name="available_q"')&&str_contains($material,'LIMIT 50'),'associação de material deve ter busca limitada para alto volume');
teaching_ux_expect(str_contains($material,'admin-teaching-danger'),'retirada de material deve ficar separada das ações cotidianas');
teaching_ux_expect(str_contains($material,"admin_status_label('page'"),'Material deve traduzir estado editorial da página');
teaching_ux_expect(!str_contains($material,'vínculo educacional')&&!str_contains($material,'página CMS'),'Material não deve ensinar o modelo interno do CMS ao operador');

foreach([$courses,$cohorts,$lessons,$material] as $page)teaching_ux_expect(!preg_match('/<style\\b/i',$page),'rotas de Ensino não podem conter CSS visual inline');
teaching_ux_expect(str_contains($teachingCss,'.admin-workspace-nav')&&str_contains($teachingCss,'.admin-release-form')&&str_contains($teachingCss,'.admin-teaching-danger'),'componentes específicos de Ensino devem permanecer na camada compartilhada do domínio');

echo "admin-teaching-ux: ok\n";
