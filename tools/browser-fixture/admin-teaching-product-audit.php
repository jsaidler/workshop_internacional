<?php
declare(strict_types=1);

if(!function_exists('h')){
    function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
}
if(!function_exists('csrf_token')){
    function csrf_token(string $scope): string {return 'browser-fixture-token';}
}
require dirname(__DIR__,2).'/app/admin_shell.php';

$view=(string)($_GET['view']??'course');
$activity=['id'=>1,'admin_name'=>'JSaidler Fotografia','is_root'=>1,'slug'=>''];
$state=['activity'=>$activity];
$course=['id'=>11,'title'=>'Positivo Direto em Filme de Raio-X','status'=>'active'];
$cohort=['id'=>21,'title'=>'Outubro 2026','status'=>'active'];

$section=match($view){
    'overview'=>'overview',
    'site'=>'pages',
    'laboratory'=>'processes',
    'system'=>'system',
    'students'=>'students',
    'questions'=>'questions',
    'tests'=>'tests',
    'registrations'=>'registrations',
    'cohort','cohort-list'=>'cohorts',
    default=>'courses',
};
$title=match($view){
    'overview'=>'Visão geral',
    'site'=>'Páginas',
    'laboratory'=>'Processos globais',
    'system'=>'Sistema e atualizações',
    'students'=>'Alunos',
    'questions'=>'Dúvidas',
    'tests'=>'Testes',
    'registrations'=>'Inscrições',
    'cohort'=>'Turma',
    'cohort-list'=>'Turmas',
    'course-list'=>'Cursos',
    default=>'Curso',
};
$teaching=in_array($view,['course-list','course','cohort-list','cohort','registrations','students','questions','tests'],true);
$styles=$teaching?['/assets/admin-teaching.css','/assets/admin-operations.css']:[];
admin_shell_start($section,$title,$state,$styles);

function audit_table(string $kind): void {
    if($kind==='courses'){?>
        <div class="admin-table-scroll" role="region" aria-label="Cursos">
            <table class="admin-data-table admin-responsive-list"><thead><tr><th>Curso</th><th>Turmas e alunos</th><th>Precisa de atenção</th><th></th></tr></thead><tbody>
            <tr><td data-primary="true" data-label="Curso"><div class="admin-table-primary"><strong>Positivo Direto em Filme de Raio-X</strong><span>Ativo · Workshop</span></div></td><td data-label="Turmas e alunos"><div class="admin-table-compact-counts"><span><strong>3</strong> turmas</span><span><strong>18</strong> alunos</span></div></td><td data-label="Precisa de atenção"><div class="admin-table-compact-counts"><span><strong>2</strong> sem turma</span><span><strong>4</strong> testes</span></div></td><td data-actions="true" data-label="" class="actions"><a class="admin-button admin-table-action" href="#course">Abrir curso</a></td></tr>
            <tr><td data-primary="true" data-label="Curso"><div class="admin-table-primary"><strong>Oficina Pinhole Lambe-Lambe</strong><span>Ativo · Oficina</span></div></td><td data-label="Turmas e alunos"><div class="admin-table-compact-counts"><span><strong>1</strong> turma</span><span><strong>8</strong> alunos</span></div></td><td data-label="Precisa de atenção"><span class="muted">Nada pendente</span></td><td data-actions="true" data-label="" class="actions"><a class="admin-button admin-table-action" href="#course">Abrir curso</a></td></tr>
            </tbody></table>
        </div><?php return;
    }
    if($kind==='cohorts'){?>
        <div class="admin-table-scroll" role="region" aria-label="Turmas">
            <table class="admin-data-table admin-responsive-list"><thead><tr><th>Turma</th><th>Período</th><th>Alunos</th><th>Aulas</th><th>Acompanhamento</th><th></th></tr></thead><tbody>
            <tr><td data-primary="true" data-label="Turma"><div class="admin-table-primary"><strong>Outubro 2026</strong><span>Em andamento</span></div></td><td data-label="Período">01/10/2026 — 31/10/2026</td><td data-label="Alunos"><strong>12</strong><div class="muted">1 ativação pendente</div></td><td data-label="Aulas"><div class="admin-table-compact-counts"><span><strong>2</strong> liberadas</span><span><strong>1</strong> bloqueada</span></div></td><td data-label="Acompanhamento"><div class="admin-table-compact-counts"><span><strong>2</strong> dúvidas</span><span><strong>3</strong> testes</span></div></td><td data-actions="true" data-label="" class="actions"><a class="admin-button secondary admin-table-action" href="#cohort">Abrir turma</a></td></tr>
            <tr><td data-primary="true" data-label="Turma"><div class="admin-table-primary"><strong>Setembro 2026</strong><span>Encerrada</span></div></td><td data-label="Período">01/09/2026 — 30/09/2026</td><td data-label="Alunos">9</td><td data-label="Aulas">3 liberadas</td><td data-label="Acompanhamento">Nada pendente</td><td data-actions="true" data-label="" class="actions"><a class="admin-button secondary admin-table-action" href="#cohort">Abrir turma</a></td></tr>
            </tbody></table>
        </div><?php return;
    }
    if($kind==='registrations'){?>
        <div class="admin-table-scroll" role="region" aria-label="Inscrições">
            <table class="admin-data-table admin-responsive-list"><thead><tr><th>Pessoa</th><th>Status</th><th>Pagamento</th><th>Turma</th><th>Data</th><th></th></tr></thead><tbody>
            <tr><td data-primary="true" data-label="Pessoa"><div class="admin-table-primary"><strong>Carlos Pereira</strong><span>carlos@example.com · #184</span></div></td><td data-label="Status"><?=admin_badge('Confirmada · sem turma','attention')?></td><td data-label="Pagamento"><?=admin_badge('Pago','good')?></td><td data-label="Turma">—</td><td data-label="Data">03/10/2026</td><td data-actions="true" data-label="" class="actions"><a class="admin-button secondary admin-table-action" href="#registration">Abrir</a></td></tr>
            <tr><td data-primary="true" data-label="Pessoa"><div class="admin-table-primary"><strong>Ana Souza</strong><span>ana@example.com · #183</span></div></td><td data-label="Status"><?=admin_badge('Com turma','good')?></td><td data-label="Pagamento"><?=admin_badge('Pago','good')?></td><td data-label="Turma">Outubro 2026</td><td data-label="Data">02/10/2026</td><td data-actions="true" data-label="" class="actions"><a class="admin-button secondary admin-table-action" href="#registration">Abrir</a></td></tr>
            </tbody></table>
        </div><?php return;
    }
    if($kind==='students'){?>
        <div class="admin-table-scroll" role="region" aria-label="Alunos">
            <table class="admin-data-table admin-responsive-list"><thead><tr><th>Aluno</th><th>Conta</th><th>Último acesso</th><th></th></tr></thead><tbody>
            <tr><td data-primary="true" data-label="Aluno"><div class="admin-table-primary"><strong>Ana Souza</strong><span>ana@example.com · ***.1234</span></div></td><td data-label="Conta"><?=admin_badge('Ativa','good')?></td><td data-label="Último acesso">03/10/2026 · 20:14</td><td data-actions="true" data-label="" class="actions"><a class="admin-button secondary admin-table-action" href="#student">Abrir</a></td></tr>
            <tr><td data-primary="true" data-label="Aluno"><div class="admin-table-primary"><strong>Rafael Lima</strong><span>rafael@example.com · ***.5678</span></div></td><td data-label="Conta"><?=admin_badge('Ativação pendente','attention')?></td><td data-label="Último acesso">—</td><td data-actions="true" data-label="" class="actions"><a class="admin-button secondary admin-table-action" href="#student">Abrir</a></td></tr>
            </tbody></table>
        </div><?php return;
    }
    if($kind==='questions'){?>
        <div class="admin-table-scroll" role="region" aria-label="Dúvidas">
            <table class="admin-data-table admin-responsive-list"><thead><tr><th>Dúvida</th><th>Aluno</th><th>Status</th><th>Respostas</th><th></th></tr></thead><tbody>
            <tr><td data-primary="true" data-label="Dúvida"><div class="admin-table-primary"><strong>Tempo no primeiro revelador</strong><span>Revelação</span></div></td><td data-label="Aluno">Ana Souza</td><td data-label="Status"><?=admin_badge('Aberta','attention')?></td><td data-label="Respostas">2</td><td data-actions="true" data-label="" class="actions"><a class="admin-button secondary admin-table-action" href="#question">Abrir</a></td></tr>
            </tbody></table>
        </div><?php return;
    }
    if($kind==='tests'){?>
        <div class="admin-table-scroll" role="region" aria-label="Testes">
            <table class="admin-data-table admin-responsive-list"><thead><tr><th>Teste</th><th>Aluno</th><th>Visibilidade</th><th>Estado</th><th></th></tr></thead><tbody>
            <tr><td data-primary="true" data-label="Teste"><div class="admin-table-primary"><strong>EI 400 — Parodinal</strong><span>02/10/2026</span></div></td><td data-label="Aluno">Rafael Lima</td><td data-label="Visibilidade">Privado</td><td data-label="Estado"><?=admin_badge('Aguardando avaliação','attention')?></td><td data-actions="true" data-label="" class="actions"><a class="admin-button secondary admin-table-action" href="#test">Revisar</a></td></tr>
            </tbody></table>
        </div><?php
    }
}

switch($view){
    case 'overview':?>
        <section class="admin-page-intro"><div><h2>Hoje</h2><p>O que precisa de decisão ou acompanhamento.</p></div></section>
        <section class="admin-card"><header><div><h2>Trabalho em aberto</h2><p>Entre diretamente no item.</p></div></header><div class="admin-work-queue"><a class="admin-work-item" href="#"><strong>Positivo Direto · 2 inscrições sem turma</strong><span>Atribuir turma</span></a><a class="admin-work-item" href="#"><strong>Positivo Direto · 4 testes</strong><span>Avaliar testes</span></a></div></section>
        <?php break;
    case 'course-list':?>
        <section class="admin-page-intro"><div><h2>Cursos</h2><p>Escolha um curso para trabalhar.</p></div><a class="admin-button" href="#new">Novo curso</a></section>
        <?php audit_table('courses');break;
    case 'course':
        admin_course_context($course,1,'overview','Trabalho e pendências deste curso.');?>
        <div class="admin-detail-lead"><div class="admin-teaching-meta"><?=admin_badge('Ativo','good')?><span>Página pública: Workshop</span></div><div class="admin-page-actions"><a class="admin-button secondary" href="#">Configurar curso</a><a class="admin-button secondary" href="#">Editar página</a></div></div>
        <section class="admin-card"><header><div><h2>Precisa de atenção</h2><p>Itens que pedem decisão.</p></div></header><div class="admin-work-queue"><a class="admin-work-item" href="#"><strong>2 inscrições pagas sem turma</strong><span>Atribuir turma</span></a><a class="admin-work-item" href="#"><strong>3 testes aguardando avaliação</strong><span>Avaliar testes</span></a></div></section>
        <section class="admin-card"><header><div><h2>Turmas</h2><p>3 turmas · 18 alunos ativos.</p></div><a class="admin-button" href="#">Nova turma</a></header><?php audit_table('cohorts');?></section>
        <?php break;
    case 'cohort-list':
        admin_course_context($course,1,'cohorts','Execuções deste curso.');?>
        <section class="admin-page-intro"><div><h2>Turmas</h2><p>Abra uma turma para administrar alunos, aulas, acesso e acompanhamento.</p></div><a class="admin-button" href="#">Nova turma</a></section>
        <?php audit_table('cohorts');break;
    case 'cohort':
        admin_cohort_context($course,$cohort,1,'overview','01/10/2026 — 31/10/2026');?>
        <div class="admin-detail-lead"><div class="admin-teaching-meta"><?=admin_badge('Em andamento','good')?><span>01/10/2026 — 31/10/2026</span></div><div class="admin-page-actions"><a class="admin-button secondary" href="#">Editar turma</a><a class="admin-button secondary" href="#">Visualizar como esta turma ↗</a></div></div>
        <dl class="admin-teaching-summary"><div><dt>Alunos</dt><dd>12<small>1 ativação pendente</small></dd></div><div><dt>Aulas liberadas</dt><dd>2<small>1 bloqueada</small></dd></div><div><dt>Dúvidas abertas</dt><dd>2</dd></div><div><dt>Testes para acompanhar</dt><dd>3</dd></div></dl>
        <div class="admin-workspace-grid"><section class="admin-card"><header><div><h2>Alunos</h2><p>12 matrículas ativas.</p></div><a class="admin-button secondary" href="#">Ver alunos</a></header></section><section class="admin-card"><header><div><h2>Aulas e acesso</h2><p>2 liberadas, 1 bloqueada.</p></div><a class="admin-button secondary" href="#">Gerenciar acesso</a></header></section><section class="admin-card"><header><div><h2>Acompanhamento</h2><p>2 dúvidas · 3 testes.</p></div><div class="admin-card-actions"><a class="admin-button secondary" href="#">Dúvidas</a><a class="admin-button secondary" href="#">Testes</a></div></header></section></div>
        <?php break;
    case 'registrations':
        admin_course_context($course,1,'registrations','Entrada e atribuição de turma.');?>
        <nav class="admin-status-tabs" aria-label="Estado"><a aria-current="page" href="#">Ativas <span>18</span></a><a href="#">Aguardando pagamento <span>3</span></a><a href="#">Pagas sem turma <span>2</span></a><a href="#">Com turma <span>13</span></a></nav>
        <?php audit_table('registrations');break;
    case 'students':
        admin_cohort_context($course,$cohort,1,'students','Participantes desta turma.');?>
        <div class="admin-detail-lead"><span>12 matrículas ativas no contexto atual.</span><div class="admin-page-actions"><a class="admin-button" href="#">Importar CSV</a></div></div>
        <?php audit_table('students');break;
    case 'questions':
        admin_cohort_context($course,$cohort,1,'questions','Dúvidas desta turma.');
        audit_table('questions');
        break;
    case 'tests':
        admin_cohort_context($course,$cohort,1,'tests','Experimentações desta turma.');
        audit_table('tests');
        break;
    case 'site':?>
        <section class="admin-page-intro"><div><h2>Páginas</h2><p>Conteúdo editorial e publicação do site.</p></div><a class="admin-button" href="#">Nova página</a></section>
        <section class="admin-card"><header><div><h2>Workshop</h2><p>Publicada · PT-BR</p></div><a class="admin-button secondary" href="#">Editar</a></header></section>
        <?php break;
    case 'laboratory':?>
        <section class="admin-page-intro"><div><h2>Processos globais</h2><p>Processos químicos usados no laboratório.</p></div></section>
        <section class="admin-card"><header><div><h2>Positivo direto</h2><p>Versão publicada v3.</p></div><a class="admin-button secondary" href="#">Gerenciar</a></header></section>
        <?php break;
    case 'system':?>
        <section class="admin-page-intro"><div><h2>Sistema e atualizações</h2><p>Versão instalada e canal de produção.</p></div></section>
        <section class="admin-card"><header><div><h2>Atualização</h2><p>O sistema está atualizado.</p></div><button class="admin-button" type="button">Verificar novamente</button></header></section>
        <?php break;
}
admin_shell_end();
