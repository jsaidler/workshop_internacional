<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function teaching_ux_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"admin-teaching-ux: $message\n");exit(1);}}
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$courses=(string)file_get_contents($root.'/admin/courses.php');
$lessons=(string)file_get_contents($root.'/admin/lessons.php');
$material=(string)file_get_contents($root.'/admin/material.php');
$teachingCss=(string)file_get_contents($root.'/assets/admin-teaching.css');
$collectionCss=(string)file_get_contents($root.'/assets/admin-collection-ux.css');

teaching_ux_expect(str_contains($shell,"/assets/admin-collection-ux.css"),'shell deve carregar primitivas compartilhadas de coleção');
teaching_ux_expect(str_contains($shell,"'release'=>['released'=>'Liberada'")&&str_contains($shell,"'page'=>['published'=>'Publicada'"),'estados de aula e página precisam de vocabulário humano compartilhado');
teaching_ux_expect(str_contains($collectionCss,'.admin-filter-chip')&&str_contains($collectionCss,'.admin-table-scroll:focus-visible'),'filtros e tabelas acessíveis devem pertencer ao contrato compartilhado');

teaching_ux_expect(str_contains($courses,"['/assets/admin-teaching.css']"),'Cursos deve consumir a camada visual de Ensino');
teaching_ux_expect(str_contains($courses,'admin-active-filters'),'Cursos deve expor busca ativa como filtro reversível');
teaching_ux_expect(str_contains($courses,'tabindex="0" role="region" aria-label="Lista de cursos"'),'tabela de cursos deve ser navegável quando houver overflow');
teaching_ux_expect(str_contains($courses,"course_catalog_url($activityId,(int)$row['id'],'overview',$q,$page)"),'abrir curso deve preservar busca e página do catálogo');
teaching_ux_expect(str_contains($courses,'admin-teaching-summary'),'detalhe do curso deve resumir relações sem recriar grade de cards');
teaching_ux_expect(!str_contains($courses,'admin-stat-grid'),'Cursos não deve voltar a empilhar cards métricos no detalhe');

teaching_ux_expect(str_contains($lessons,'Escolha uma turma'),'Aulas deve selecionar uma turma explicitamente para gerenciar liberação');
teaching_ux_expect(!str_contains($lessons,'foreach($cohorts as $cohort)'),'Aulas não pode criar uma coluna para cada turma');
teaching_ux_expect(str_contains($lessons,'Gerenciar liberação'),'liberação deve ser tratada como detalhe da aula, não como matriz crescente');
teaching_ux_expect(str_contains($lessons,'name="scheduled_at"')&&str_contains($lessons,'value="schedule"')&&str_contains($lessons,'value="release"')&&str_contains($lessons,'value="block"'),'Aulas deve expor agendamento, liberação imediata e bloqueio já suportados pelo domínio');
teaching_ux_expect(str_contains($lessons,"admin_status_label('release'"),'estado de liberação não deve vazar enum técnico na interface');
teaching_ux_expect(str_contains($lessons,'tabindex="0" role="region" aria-label="Lista de aulas"'),'lista de aulas deve ser focável em overflow');

teaching_ux_expect(str_contains($material,'admin-active-filters'),'Material deve expor filtros ativos e reversíveis');
teaching_ux_expect(str_contains($material,'NOT EXISTS (SELECT 1 FROM course_material_pages'),'seletor de associação deve consultar apenas páginas ainda disponíveis');
teaching_ux_expect(str_contains($material,'name="available_q"')&&str_contains($material,'LIMIT 50'),'associação de material deve ter busca limitada e explícita para alto volume');
teaching_ux_expect(str_contains($material,'admin-teaching-danger'),'desassociação deve ficar separada das ações cotidianas');
teaching_ux_expect(str_contains($material,"admin_status_label('page'"),'Material deve traduzir estado editorial da página');
teaching_ux_expect(str_contains($material,'tabindex="0" role="region" aria-label="Lista de material"'),'lista de material deve ser focável em overflow');
$detailPos=strpos($material,'if($selectedPage&&$course)');$listPos=strpos($material,'admin-list-summary');
teaching_ux_expect($detailPos!==false&&$listPos!==false&&$detailPos<$listPos,'detalhe de material deve assumir a leitura antes da coleção quando um registro é aberto');

foreach([$courses,$lessons,$material] as $page){teaching_ux_expect(!preg_match('/<style\b/i',$page),'rotas de Ensino não podem conter CSS visual inline');}
teaching_ux_expect(str_contains($teachingCss,'.admin-release-form')&&str_contains($teachingCss,'.admin-teaching-danger'),'componentes específicos de Ensino devem permanecer na camada compartilhada do domínio');

echo "admin-teaching-ux: ok\n";
