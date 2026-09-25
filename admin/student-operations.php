<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();
require_admin();

$db=database();
$state=admin_activity_resolution($db);
$activity=$state['activity'];
if(!$activity){header('Location: /admin/activities.php');exit;}
$activityId=(int)$activity['id'];
$views=['cohorts','import','tests'];
$view=(string)($_GET['view']??'cohorts');
if(!in_array($view,$views,true))$view='cohorts';

function student_ops_url(int $activityId,string $view,array $extra=[]): string {
    return '/admin/student-operations.php?'.http_build_query(['activity'=>$activityId,'view'=>$view]+$extra);
}
function student_ops_datetime(?string $value): string {
    $value=trim((string)$value);if($value==='')return '—';$ts=strtotime($value);return $ts===false?$value:date('d/m/Y H:i',$ts);
}
function student_ops_value(mixed $value): string {$value=trim((string)$value);return $value===''?'—':h($value);}

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-operations',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $action=(string)($_POST['action']??'');
    $returnView=(string)($_POST['return_view']??$view);if(!in_array($returnView,$views,true))$returnView='cohorts';
    $extra=[];
    try{
        if($action==='update_cohort'){
            $cohortId=(int)($_POST['cohort_id']??0);
            course_update_cohort_details($db,$activityId,$cohortId,$_POST);
            $_SESSION['student_ops_notice']='Dados da turma atualizados.';$extra=['cohort'=>$cohortId];
        }elseif($action==='import_students'){
            $cohortId=(int)($_POST['cohort_id']??0);$file=$_FILES['spreadsheet']??null;
            if(!is_array($file))throw new RuntimeException('Selecione uma planilha.');
            $result=student_import_historical_students($db,$activityId,$cohortId,$file);
            $_SESSION['student_ops_notice']='Importação concluída: '.$result['imported'].' nova(s) conta(s), '.$result['existing'].' conta(s) já existente(s), '.$result['errors'].' linha(s) com erro.';
            $extra=['batch'=>(int)$result['batch_id']];
        }elseif($action==='test_message'){
            $testId=(int)($_POST['test_id']??0);student_test_add_admin_message($db,$testId,$activityId,(string)($_POST['message']??''));
            $_SESSION['student_ops_notice']='Comentário enviado ao aluno.';$extra=['test'=>$testId];
        }elseif($action==='test_status'){
            $testId=(int)($_POST['test_id']??0);$status=(string)($_POST['status']??'submitted');student_test_set_review_status($db,$testId,$activityId,$status);
            $_SESSION['student_ops_notice']='Estado da avaliação atualizado.';$extra=['test'=>$testId];
        }else throw new RuntimeException('Ação inválida.');
    }catch(Throwable $e){$_SESSION['student_ops_notice']='Não foi possível concluir: '.$e->getMessage();}
    header('Location: '.student_ops_url($activityId,$returnView,$extra),true,303);exit;
}

$cohorts=course_cohorts($db,$activityId);
$notice=$_SESSION['student_ops_notice']??null;unset($_SESSION['student_ops_notice']);
admin_shell_start('studentops','Operações e testes',$state);
?>
<?php if($notice):?><div class="admin-notice"><?=h((string)$notice)?></div><?php endif;?>
<section class="overview-hero"><div><p class="admin-kicker">Área do aluno</p><h2>Operações e testes</h2><p>Edite turmas, incorpore alunos de edições anteriores e acompanhe os testes registrados pelos alunos.</p></div></section>
<nav class="admin-subtabs" aria-label="Operações da área do aluno">
  <a href="<?=h(student_ops_url($activityId,'cohorts'))?>"<?=$view==='cohorts'?' aria-current="page"':''?>>Dados das turmas</a>
  <a href="<?=h(student_ops_url($activityId,'import'))?>"<?=$view==='import'?' aria-current="page"':''?>>Importar alunos</a>
  <a href="<?=h(student_ops_url($activityId,'tests'))?>"<?=$view==='tests'?' aria-current="page"':''?>>Testes dos alunos</a>
</nav>

<?php if($view==='cohorts'):
$selectedId=(int)($_GET['cohort']??0);$selected=null;foreach($cohorts as $c)if((int)$c['id']===$selectedId)$selected=$c;if(!$selected&&$cohorts)$selected=$cohorts[0];?>
<div class="admin-section-stack">
  <section class="admin-card">
    <header><div><h2>Turmas</h2><p>O nome, o período e as observações podem ser corrigidos sem recriar matrículas ou liberações.</p></div><div class="admin-card-actions"><a class="admin-button secondary" href="<?=h('/admin/student-area.php?'.http_build_query(['activity'=>$activityId,'view'=>'cohorts']))?>">Criar ou encerrar turmas</a></div></header>
    <?php if(!$cohorts):?><div class="admin-empty">Nenhuma turma cadastrada.</div><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Turma</th><th>Período</th><th>Status</th><th>Padrão</th><th></th></tr></thead><tbody><?php foreach($cohorts as $cohort):?><tr><td><strong><?=h((string)$cohort['title'])?></strong><div class="muted"><?=h((string)$cohort['slug'])?></div></td><td><?=h((string)($cohort['starts_at']?:'—'))?> → <?=h((string)($cohort['ends_at']?:'—'))?></td><td><?=h((string)$cohort['status'])?></td><td><?=$cohort['is_registration_default']?'Sim':'—'?></td><td><a class="admin-button secondary" href="<?=h(student_ops_url($activityId,'cohorts',['cohort'=>(int)$cohort['id']]))?>">Editar</a></td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
  </section>
  <?php if($selected):?><section class="admin-card"><header><div><h2>Editar · <?=h((string)$selected['title'])?></h2><p>Alterar o slug não muda a identidade interna da turma.</p></div></header>
    <form class="admin-form-grid" method="post">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-operations'))?>"><input type="hidden" name="action" value="update_cohort"><input type="hidden" name="return_view" value="cohorts"><input type="hidden" name="cohort_id" value="<?=(int)$selected['id']?>">
      <label class="admin-form-span-2">Nome<input name="title" value="<?=h((string)$selected['title'])?>" required></label>
      <label>Slug<input name="slug" value="<?=h((string)$selected['slug'])?>" required></label>
      <label>Status<select name="status"><option value="active"<?=$selected['status']==='active'?' selected':''?>>Ativa</option><option value="closed"<?=$selected['status']==='closed'?' selected':''?>>Encerrada</option><option value="archived"<?=$selected['status']==='archived'?' selected':''?>>Arquivada</option></select></label>
      <label>Início<input type="date" name="starts_at" value="<?=h((string)($selected['starts_at']??''))?>"></label>
      <label>Fim<input type="date" name="ends_at" value="<?=h((string)($selected['ends_at']??''))?>"></label>
      <label class="admin-form-span-2">Observações<textarea name="notes" rows="5" placeholder="Informações administrativas sobre esta edição. Não são mostradas ao aluno."><?=h((string)($selected['notes']??''))?></textarea></label>
      <label class="admin-form-span-2"><span>Turma padrão</span><span class="admin-inline-actions"><input type="checkbox" name="make_default" value="1"<?=$selected['is_registration_default']?' checked':''?>> receber novas matrículas confirmadas</span></label>
      <div class="admin-form-actions"><button class="admin-button" type="submit">Salvar turma</button></div>
    </form>
  </section><?php endif;?>
</div>

<?php elseif($view==='import'):
$batches=student_import_batches($db,$activityId,25);$batchId=(int)($_GET['batch']??0);$batchRows=$batchId>0?student_import_batch_rows($db,$batchId,$activityId):[];?>
<div class="admin-section-stack">
  <section class="admin-card"><header><div><h2>Importar alunos de turmas anteriores</h2><p>A importação cria contas compatíveis com o primeiro acesso por e-mail + CPF e vincula cada pessoa à turma escolhida. Contas já existentes são reaproveitadas.</p></div></header>
    <form class="admin-form-grid" method="post" enctype="multipart/form-data">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-operations'))?>"><input type="hidden" name="action" value="import_students"><input type="hidden" name="return_view" value="import">
      <label>Turma de destino<select name="cohort_id" required><option value="">Escolha</option><?php foreach($cohorts as $cohort):if($cohort['status']==='archived')continue;?><option value="<?=(int)$cohort['id']?>"><?=h((string)$cohort['title'])?></option><?php endforeach;?></select></label>
      <label class="admin-form-span-2">Planilha<input type="file" name="spreadsheet" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></label>
      <div class="admin-form-span-2"><p><strong>Colunas obrigatórias:</strong> Nome, E-mail e CPF. <strong>Opcionais:</strong> Telefone, Instagram, Endereço, Cidade/UF e CEP.</p><p class="muted">CSV ou XLSX · até 5 MB · até 2.000 alunos. O CPF é usado para identificar a conta e permitir o primeiro acesso; ele não é gravado no relatório de importação. Se o servidor não tiver suporte a XLSX, exporte a planilha como CSV.</p></div>
      <div class="admin-form-actions"><button class="admin-button" type="submit">Importar planilha</button></div>
    </form>
  </section>
  <section class="admin-card"><header><div><h2>Importações recentes</h2></div></header><?php if(!$batches):?><div class="admin-empty">Nenhuma importação realizada.</div><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Arquivo</th><th>Turma</th><th>Resultado</th><th>Data</th><th></th></tr></thead><tbody><?php foreach($batches as $batch):?><tr><td><?=h((string)$batch['original_name'])?></td><td><?=h((string)$batch['cohort_title'])?></td><td><?=(int)$batch['imported_rows']?> novas · <?=(int)$batch['existing_rows']?> existentes · <?=(int)$batch['error_rows']?> erros</td><td class="muted"><?=h(student_ops_datetime((string)$batch['created_at']))?></td><td><a class="admin-button secondary" href="<?=h(student_ops_url($activityId,'import',['batch'=>(int)$batch['id']]))?>">Ver relatório</a></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section>
  <?php if($batchId>0):?><section class="admin-card"><header><div><h2>Relatório da importação</h2><p>As linhas abaixo não armazenam o CPF da planilha.</p></div></header><?php if(!$batchRows):?><div class="admin-empty">Importação não encontrada neste site.</div><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Linha</th><th>Aluno</th><th>Resultado</th><th>Observação</th></tr></thead><tbody><?php foreach($batchRows as $row):?><tr><td><?=(int)$row['row_number']?></td><td><strong><?=h((string)$row['name'])?></strong><div class="muted"><?=h((string)$row['email'])?></div></td><td><?=h((string)$row['status'])?></td><td><?=h((string)($row['message']?:'—'))?></td></tr><?php endforeach;?></tbody></table></div><?php endif;?></section><?php endif;?>
</div>

<?php elseif($view==='tests'):
$cohortFilter=(int)($_GET['cohort']??0);$statusFilter=(string)($_GET['status']??'');$q=trim((string)($_GET['q']??''));$tests=student_tests_for_admin($db,$activityId,$cohortFilter,$statusFilter,$q);$testId=(int)($_GET['test']??0);$selectedTest=$testId>0?student_test_for_admin($db,$testId,$activityId):null;$media=$selectedTest?student_test_media($db,(int)$selectedTest['id']):[];$messages=$selectedTest?student_test_messages($db,(int)$selectedTest['id']):[];?>
<div class="admin-section-stack">
  <section class="admin-card"><header><div><h2>Testes registrados</h2><p>Os testes enviados aparecem primeiro. Rascunhos ficam visíveis para acompanhamento, mas ainda não pedem avaliação.</p></div></header>
    <form class="admin-data-toolbar" method="get"><input type="hidden" name="activity" value="<?=$activityId?>"><input type="hidden" name="view" value="tests"><label class="grow">Buscar<input name="q" value="<?=h($q)?>" placeholder="Aluno, e-mail ou título do teste"></label><label>Turma<select name="cohort"><option value="0">Todas</option><?php foreach($cohorts as $cohort):?><option value="<?=(int)$cohort['id']?>"<?=$cohortFilter===(int)$cohort['id']?' selected':''?>><?=h((string)$cohort['title'])?></option><?php endforeach;?></select></label><label>Estado<select name="status"><option value="">Todos</option><?php foreach(['submitted'=>'Aguardando avaliação','needs_revision'=>'Ajustes solicitados','reviewed'=>'Revisado','draft'=>'Rascunho'] as $value=>$label):?><option value="<?=h($value)?>"<?=$statusFilter===$value?' selected':''?>><?=h($label)?></option><?php endforeach;?></select></label><button class="admin-button secondary" type="submit">Filtrar</button></form>
    <?php if(!$tests):?><div class="admin-empty">Nenhum teste encontrado.</div><?php else:?><div class="admin-table-scroll"><table class="admin-data-table"><thead><tr><th>Teste</th><th>Aluno</th><th>Turma</th><th>Estado</th><th>Atualização</th><th></th></tr></thead><tbody><?php foreach($tests as $test):?><tr><td><strong><?=h((string)$test['title'])?></strong><div class="muted"><?=h((string)($test['test_date']?:'sem data'))?></div></td><td><?=h((string)$test['student_name'])?><div class="muted"><?=h((string)$test['student_email'])?></div></td><td><?=h((string)$test['cohort_title'])?></td><td><?=h(student_test_status_label((string)$test['status']))?></td><td class="muted"><?=h(student_ops_datetime((string)$test['updated_at']))?></td><td><a class="admin-button secondary" href="<?=h(student_ops_url($activityId,'tests',['test'=>(int)$test['id'],'cohort'=>$cohortFilter,'status'=>$statusFilter,'q'=>$q]))?>">Abrir</a></td></tr><?php endforeach;?></tbody></table></div><?php endif;?>
  </section>

  <?php if($testId>0&&!$selectedTest):?><section class="admin-card"><div class="admin-empty">Teste não encontrado neste site.</div></section><?php endif;?>
  <?php if($selectedTest):?>
  <section class="admin-card"><header><div><p class="admin-kicker"><?=h((string)$selectedTest['cohort_title'])?> · <?=h((string)$selectedTest['student_name'])?></p><h2><?=h((string)$selectedTest['title'])?></h2><p><?=h(student_test_status_label((string)$selectedTest['status']))?> · atualizado em <?=h(student_ops_datetime((string)$selectedTest['updated_at']))?></p></div></header>
    <div class="admin-table-scroll"><table class="admin-data-table"><tbody>
      <tr><th>Data do teste</th><td><?=student_ops_value($selectedTest['test_date'])?></td><th>Filme</th><td><?=student_ops_value($selectedTest['film'])?></td></tr>
      <tr><th>Lote</th><td><?=student_ops_value($selectedTest['lot'])?></td><th>ISO de referência</th><td><?=student_ops_value($selectedTest['iso_reference'])?></td></tr>
      <tr><th>Diafragma</th><td><?=student_ops_value($selectedTest['aperture'])?></td><th>Tempo calculado</th><td><?=student_ops_value($selectedTest['calculated_time'])?></td></tr>
      <tr><th>Tempo com reciprocidade</th><td><?=student_ops_value($selectedTest['reciprocity_time'])?></td><th>Condição de luz</th><td><?=student_ops_value($selectedTest['light_condition'])?></td></tr>
      <tr><th>Diferença claras/sombras</th><td colspan="3"><?=student_ops_value($selectedTest['tonal_range'])?></td></tr>
      <tr><th>Revelador</th><td><?=student_ops_value($selectedTest['developer'])?></td><th>Diluição</th><td><?=student_ops_value($selectedTest['dilution'])?></td></tr>
      <tr><th>Temperatura</th><td><?=student_ops_value($selectedTest['temperature'])?></td><th>Tempo de revelação</th><td><?=student_ops_value($selectedTest['development_time'])?></td></tr>
      <tr><th>Movimentação</th><td colspan="3"><?=student_ops_value($selectedTest['agitation'])?></td></tr>
      <tr><th>Observações</th><td colspan="3"><?=nl2br(student_ops_value($selectedTest['notes']))?></td></tr>
    </tbody></table></div>
    <div class="admin-inline-actions" style="margin-top:18px"><?php foreach(['needs_revision'=>'Solicitar ajustes','reviewed'=>'Marcar como revisado','submitted'=>'Voltar para avaliação'] as $status=>$label):?><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-operations'))?>"><input type="hidden" name="action" value="test_status"><input type="hidden" name="return_view" value="tests"><input type="hidden" name="test_id" value="<?=(int)$selectedTest['id']?>"><input type="hidden" name="status" value="<?=h($status)?>"><button class="admin-button secondary" type="submit"><?=h($label)?></button></form><?php endforeach;?></div>
  </section>

  <section class="admin-card"><header><div><h2>Imagens do teste</h2><p><?=$media?count($media).' arquivo(s) enviado(s).':'Nenhuma imagem enviada.'?></p></div></header><?php if($media):?><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px"><?php foreach($media as $item):$src='/admin/student-test-media.php?'.http_build_query(['activity'=>$activityId,'id'=>(int)$item['id']]);?><a href="<?=h($src)?>" target="_blank" rel="noopener"><img src="<?=h($src)?>" alt="<?=h((string)$item['original_name'])?>" loading="lazy" style="display:block;width:100%;height:auto;border:1px solid var(--admin-line,#ccc)"><span class="muted"><?=h((string)$item['original_name'])?></span></a><?php endforeach;?></div><?php endif;?></section>

  <section class="admin-card"><header><div><h2>Avaliação e dúvidas</h2><p>Este histórico fica associado ao teste e pode ser continuado pelo aluno.</p></div></header>
    <?php if(!$messages):?><div class="admin-empty">Ainda não há mensagens neste teste.</div><?php else:?><div class="admin-section-stack"><?php foreach($messages as $message):?><article><p class="admin-kicker"><?=$message['author_role']==='admin'?'João / avaliação':h((string)($message['student_name']?:$selectedTest['student_name']))?> · <?=h(student_ops_datetime((string)$message['created_at']))?></p><p><?=nl2br(h((string)$message['body']))?></p></article><?php endforeach;?></div><?php endif;?>
    <form class="admin-form-grid" method="post" style="margin-top:20px"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-operations'))?>"><input type="hidden" name="action" value="test_message"><input type="hidden" name="return_view" value="tests"><input type="hidden" name="test_id" value="<?=(int)$selectedTest['id']?>"><label class="admin-form-span-2">Comentário para o aluno<textarea name="message" rows="6" required placeholder="Avaliação, orientação ou resposta à dúvida"></textarea></label><div class="admin-form-actions"><button class="admin-button" type="submit">Enviar comentário</button></div></form>
  </section>
  <?php endif;?>
</div>
<?php endif;?>
<?php admin_shell_end();
