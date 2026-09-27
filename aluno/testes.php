<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode('/aluno/testes.php'),true,303);exit;}
$studentId=(int)$student['id'];$enrollments=student_account_enrollments($db,$studentId);
$requestedCohortUuid=is_string($_GET['cohort']??null)?trim((string)$_GET['cohort']):'';
$selectedEnrollment=student_enrollment_dashboard_context($enrollments,$requestedCohortUuid);$selectedActivity=null;
if($selectedEnrollment){$selectedActivity=activity_by_id($db,(int)$selectedEnrollment['activity_id']);if(!$selectedActivity)$selectedEnrollment=null;}
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!$selectedEnrollment)$error='Escolha primeiro o curso e a turma deste teste.';
    elseif(!verify_csrf('student-tests',$_POST['_csrf']??null))$error='Solicitação inválida.';
    elseif((int)($_POST['cohort_id']??0)!==(int)$selectedEnrollment['cohort_id'])$error='A turma selecionada não corresponde ao contexto aberto.';
    else{
        try{
            $test=student_test_create($db,$studentId,(int)$selectedEnrollment['cohort_id'],$_POST);
            student_test_set_visibility($db,(int)$test['id'],$studentId,(string)($_POST['visibility']??'private'));
            header('Location: /aluno/teste.php?id='.(int)$test['id'].'&cohort='.rawurlencode((string)$selectedEnrollment['cohort_uuid']),true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
$notice=(string)($_SESSION['student_tests_notice']??'');unset($_SESSION['student_tests_notice']);
$allTests=student_tests_for_student($db,$studentId);$allShared=student_tests_shared_with_student($db,$studentId);
$tests=$selectedEnrollment?student_enrollment_owned_tests_context($allTests,$selectedEnrollment):[];
$shared=$selectedEnrollment?student_enrollment_shared_tests_context($allShared,$selectedEnrollment):[];
student_shell_start('Testes',$selectedActivity,$student);?>
<?php if(!$enrollments):?>
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title">Testes</h1>
  <p class="student-lead">Registre cada experimentação com os dados de exposição e revelação.</p>
  <div class="student-empty">Você precisa de uma matrícula ativa para registrar testes.</div>
<?php elseif(!$selectedEnrollment):?>
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title">Testes por curso</h1>
  <p class="student-lead">Escolha a turma em que você está trabalhando. Cada workspace mantém seus próprios registros e compartilhamentos.</p>
  <section class="student-section" aria-label="Escolher curso ou turma para os testes">
    <div class="student-test-list">
      <?php foreach($enrollments as $enrollment):$activity=activity_by_id($db,(int)$enrollment['activity_id']);if(!$activity)continue;$statusLabel=student_enrollment_cohort_status_label((string)$enrollment['cohort_status']);$url='/aluno/testes.php?cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?>
        <a class="student-test-row" href="<?=h($url)?>">
          <div><span class="student-status"><?=h($statusLabel)?></span><h3><?=h((string)$activity['public_title'])?></h3><p><?=h((string)$enrollment['cohort_title'])?></p></div>
          <div class="student-test-meta"><span>Abrir testes →</span></div>
        </a>
      <?php endforeach;?>
    </div>
  </section>
<?php else:$enrollment=$selectedEnrollment;$activity=$selectedActivity;$cohortUuid=(string)$enrollment['cohort_uuid'];$statusLabel=student_enrollment_cohort_status_label((string)$enrollment['cohort_status']);?>
  <div class="student-appbar">
    <?php if(count($enrollments)>1):?><a class="student-back" href="/aluno/testes.php">← Todos os cursos</a><?php else:?><a class="student-back" href="/aluno/?cohort=<?=h(rawurlencode($cohortUuid))?>">← Curso</a><?php endif;?>
    <span class="student-status"><?=h($statusLabel)?></span>
  </div>
  <p class="student-kicker"><?=h((string)$activity['public_title'])?> · <?=h((string)$enrollment['cohort_title'])?></p>
  <h1 class="student-title">Testes</h1>
  <p class="student-lead">Registre cada experimentação e mantenha cena, exposição, processo e resultado no mesmo lugar.</p>

  <?php if($notice!==''):?><p class="ui-alert ui-alert-notice"><?=h($notice)?></p><?php endif;?>
  <?php if($error):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
  <section class="student-section">
    <div class="student-section-heading"><div><p class="student-kicker">Novo registro</p><h2 class="student-subtitle">Criar teste</h2></div><p><?=h((string)$activity['public_title'])?> · <?=h((string)$enrollment['cohort_title'])?>. Comece pelo título; os dados técnicos e as fotografias entram depois.</p></div>
    <form class="student-form-grid student-create-test-form" method="post" action="/aluno/testes.php?cohort=<?=h(rawurlencode($cohortUuid))?>" data-ui-validate>
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-tests'))?>"><input type="hidden" name="cohort_id" value="<?=(int)$enrollment['cohort_id']?>">
      <label class="form-field">Título do teste<input name="title" required maxlength="180" placeholder="Ex.: Janela, EI 200, Parodinal 1+50"></label>
      <label class="form-field">Data<input type="date" name="test_date"></label>
      <fieldset class="choice-field student-span-2"><legend>Quem pode ver?</legend><div class="choice-row"><label class="choice-option"><input type="radio" name="visibility" value="private" checked><span>Somente eu e o professor</span></label><label class="choice-option"><input type="radio" name="visibility" value="cohort"><span>Minha turma</span></label><label class="choice-option"><input type="radio" name="visibility" value="course"><span>Todo o curso</span></label></div><span class="form-field-help">Você pode alterar o compartilhamento depois.</span></fieldset>
      <div class="student-actions student-span-2"><button class="button button-primary" type="submit">Criar ficha →</button></div>
    </form>
  </section>

  <section class="student-section">
    <div class="student-section-heading"><div><p class="student-kicker">Meus registros</p><h2 class="student-subtitle">Seus testes</h2></div><p><?=count($tests)?> registro(s) nesta turma.</p></div>
    <?php if(!$tests):?><div class="student-empty">Você ainda não registrou nenhum teste nesta turma.</div><?php else:?><div class="student-test-list"><?php foreach($tests as $test):$testUrl='/aluno/teste.php?id='.(int)$test['id'].'&cohort='.rawurlencode($cohortUuid);$visibility=(string)($test['visibility']??'private');?><article class="student-test-item"><a class="student-test-row" href="<?=h($testUrl)?>"><div><span class="student-status student-status-<?=h((string)$test['status'])?>"><?=h(student_test_status_label((string)$test['status']))?></span> <span class="student-status"><?=h(student_test_visibility_label($visibility))?></span><h3><?=h((string)$test['title'])?></h3><p><?=h((string)$test['public_title'])?> · <?=h((string)$test['cohort_title'])?></p></div><div class="student-test-meta"><span><?=h((string)($test['test_date']?:'sem data'))?></span><span>Atualizado <?=h(student_ops_datetime_exists((string)$test['updated_at']))?></span></div></a><div class="student-test-actions"><form method="post" action="/aluno/visibilidade-teste.php" class="student-visibility-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-visibility'))?>"><input type="hidden" name="id" value="<?=(int)$test['id']?>"><input type="hidden" name="cohort" value="<?=h($cohortUuid)?>"><fieldset class="choice-field choice-field-compact"><legend>Compartilhamento</legend><div class="choice-row"><label class="choice-option"><input type="radio" name="visibility" value="private"<?=$visibility==='private'?' checked':''?>><span>Privado</span></label><label class="choice-option"><input type="radio" name="visibility" value="cohort"<?=$visibility==='cohort'?' checked':''?>><span>Turma</span></label><label class="choice-option"><input type="radio" name="visibility" value="course"<?=$visibility==='course'?' checked':''?>><span>Curso</span></label></div></fieldset><button class="button button-secondary button-compact" type="submit">Salvar acesso</button></form><a class="student-link student-danger-link" href="/aluno/excluir-teste.php?id=<?=(int)$test['id']?>&amp;cohort=<?=h(rawurlencode($cohortUuid))?>">Excluir teste</a></div></article><?php endforeach;?></div><?php endif;?>
  </section>

  <section class="student-section">
    <div class="student-section-heading"><div><p class="student-kicker">Referências compartilhadas</p><h2 class="student-subtitle">Compartilhados com você</h2></div><p>Registros de turma ficam nesta turma; registros de curso podem vir de outras turmas do mesmo curso.</p></div>
    <?php if(!$shared):?><div class="student-empty">Nenhum teste deste contexto foi compartilhado com você ainda.</div><?php else:?><div class="student-test-list"><?php foreach($shared as $test):$sharedUrl='/aluno/teste-compartilhado.php?id='.(int)$test['id'].'&cohort='.rawurlencode($cohortUuid);?><a class="student-test-row" href="<?=h($sharedUrl)?>"><div><span class="student-status"><?=h(student_test_visibility_label((string)$test['visibility']))?></span><h3><?=h((string)$test['title'])?></h3><p><?=h((string)$test['student_name'])?> · <?=h((string)$test['cohort_title'])?></p></div><div class="student-test-meta"><span><?=h((string)($test['test_date']?:'sem data'))?></span><span>Atualizado <?=h(student_ops_datetime_exists((string)$test['updated_at']))?></span></div></a><?php endforeach;?></div><?php endif;?>
  </section>
<?php endif;?>
<?php student_shell_end();

function student_ops_datetime_exists(string $value): string {$ts=strtotime($value);return $ts===false?$value:date('d/m/Y',$ts);}
