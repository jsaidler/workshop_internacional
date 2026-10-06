<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
student_private_headers();

$db=database();
student_account_reconcile_confirmed_registrations($db);
$student=student_account_current($db);
if(!$student){
    header('Location: /aluno/login.php?next=%2Faluno%2Fcaderno.php',true,303);
    exit;
}

$studentId=(int)$student['id'];
$enrollments=student_enrollment_list($db,$studentId);
$error='';

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-notebook',$_POST['_csrf']??null)){
        $error='Solicitação inválida.';
    }else{
        try{
            $action=(string)($_POST['action']??'create');
            if($action==='duplicate'){
                $record=student_process_duplicate($db,(int)($_POST['id']??0),$studentId);
                $_SESSION['student_process_notice']='Registro duplicado.';
                header('Location: /aluno/teste.php?id='.(int)$record['id'],true,303);
                exit;
            }
            if($action==='visibility'){
                student_test_set_visibility($db,(int)($_POST['id']??0),$studentId,(string)($_POST['visibility']??'private'));
                $_SESSION['student_process_notice']='Compartilhamento atualizado.';
                header('Location: /aluno/caderno.php',true,303);
                exit;
            }
            $record=student_process_create_record($db,$studentId,$_POST);
            header('Location: /aluno/teste.php?id='.(int)$record['id'],true,303);
            exit;
        }catch(Throwable $e){
            $error=$e->getMessage();
        }
    }
}

$notice=(string)($_SESSION['student_process_notice']??'');
unset($_SESSION['student_process_notice']);
$records=student_process_records_for_student($db,$studentId);
$shared=student_tests_shared_with_student($db,$studentId);

student_shell_start('Caderno de Processos',null,$student);
?>
<div class="student-page-heading student-notebook-heading">
  <div>
    <p class="student-kicker">Caderno</p>
    <h1 class="student-title">Caderno de Processos</h1>
    <p class="student-notebook-intro">Cada registro reúne o que você quiser documentar sobre uma fotografia: exposição, processamento e resultado. As partes podem ser preenchidas em qualquer ordem e retomadas depois.</p>
  </div>
  <button class="button button-primary" type="button" data-record-create-open>Novo registro</button>
</div>

<?php if($notice!==''):?><p class="ui-alert ui-alert-notice"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>

<dialog class="student-create-dialog" data-record-create-dialog aria-labelledby="student-create-title">
  <div class="student-create-dialog-head">
    <div><p class="student-kicker">Caderno</p><h2 id="student-create-title">Novo registro</h2></div>
    <button class="button button-secondary button-compact" type="button" data-record-create-close>Fechar</button>
  </div>
  <div class="student-create-dialog-intro">
    <p>Comece com um título e uma data. O registro não precisa ser preenchido em sequência: salve o que souber agora e complete ou corrija depois.</p>
  </div>
  <form method="post" class="student-form-grid" data-ui-validate>
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-notebook'))?>">
    <input type="hidden" name="action" value="create">
    <label class="form-field student-span-2">Título<input name="title" maxlength="180" placeholder="Ex.: EI 200 — primeiro teste"></label>
    <label class="form-field">Data<input type="date" name="test_date" value="<?=h(date('Y-m-d'))?>"></label>
    <fieldset class="choice-field">
      <legend>Contexto</legend>
      <div class="choice-row">
        <label class="choice-option"><input type="radio" name="context_scope" value="personal" checked data-process-context-radio><span>Pessoal</span></label>
        <?php if($enrollments):?><label class="choice-option"><input type="radio" name="context_scope" value="course" data-process-context-radio><span>Curso</span></label><?php endif;?>
      </div>
    </fieldset>
    <?php if($enrollments):?>
      <label class="form-field student-span-2" data-process-course-context hidden>Curso e turma
        <select name="context_cohort_id">
          <option value="">Escolha…</option>
          <?php foreach($enrollments as $enrollment):?><option value="<?=(int)$enrollment['cohort_id']?>"><?=h((string)$enrollment['course_title'])?> · <?=h((string)$enrollment['cohort_title'])?></option><?php endforeach;?>
        </select>
      </label>
    <?php endif;?>
    <div class="student-actions student-span-2"><button class="button button-primary" type="submit">Criar registro</button></div>
  </form>
</dialog>

<section class="student-section student-notebook-records">
  <div class="student-section-heading student-notebook-section-heading">
    <div><p class="student-kicker">Seus registros</p><h2 class="student-subtitle"><?=h(student_experience_count(count($records),'registro','registros'))?></h2></div>
    <p>Abra qualquer registro para acrescentar, revisar ou corrigir uma das partes.</p>
  </div>

  <?php if(!$records):?>
    <div class="student-empty student-notebook-empty">
      <strong>Comece pelo primeiro registro.</strong>
      <p>Você pode registrar uma exposição, anotar um processamento já feito ou começar diretamente pelo resultado. Nada precisa estar completo para ser salvo.</p>
      <button class="button button-primary" type="button" data-record-create-open>Criar primeiro registro</button>
    </div>
  <?php else:?>
    <div class="student-process-list student-notebook-list">
      <?php foreach($records as $record):
        $recordId=(int)$record['id'];
        $steps=student_process_steps($db,$recordId);
        $plan=student_process_plan_for_test($db,$recordId,$studentId);
        $planSteps=$plan?student_process_plan_steps($db,(int)$plan['id']):[];
        $recordMedia=student_test_media($db,$recordId);
        $sceneMedia=student_test_media_by_phase($recordMedia,'scene');
        $resultMedia=student_test_media_by_phase($recordMedia,'result');
        $personal=(string)($record['context_scope']??'course')==='personal';
        $context=$personal?'Pessoal':trim((string)($record['context_course_title']??$record['public_title']).' · '.(string)($record['context_cohort_title']??$record['cohort_title']));
        $visibility=$personal?'private':(string)($record['visibility']??'private');
        $exposureRecorded=student_notebook_has_exposure($record,$sceneMedia);
        $resultRecorded=student_notebook_has_result($record,$resultMedia);
        $processText=student_notebook_process_summary($steps,$plan,$planSteps);
        $researchSource=student_research_source($db,$record,$studentId);
        $researchIntent=trim((string)($record['research_intent']??''));
      ?>
      <article class="student-process-card student-notebook-card">
        <a class="student-process-card-main" href="/aluno/teste.php?id=<?=$recordId?>">
          <div class="student-notebook-card-copy">
            <span class="student-card-label"><?=h($context)?></span>
            <h3><?=h((string)$record['title'])?></h3>
            <?php if($researchSource):?><p class="student-research-card-lineage">Continuação de <strong><?=h((string)$researchSource['title'])?></strong><?php if($researchIntent!==''):?> · <?=h($researchIntent)?><?php endif;?></p><?php endif;?>
            <div class="student-record-summary" aria-label="Conteúdo do registro">
              <span><?=h($exposureRecorded?'Exposição registrada':'Exposição ainda não registrada')?></span>
              <span><?=h($processText)?></span>
              <span><?=h($resultRecorded?'Resultado registrado':'Resultado ainda não registrado')?></span>
            </div>
          </div>
          <div class="student-process-card-state">
            <strong>Abrir registro →</strong>
            <span><?=h((string)($record['test_date']?:'Sem data'))?><br>Atualizado <?=h(student_process_date((string)$record['updated_at']))?></span>
          </div>
        </a>
        <div class="student-process-card-footer">
          <span class="student-status"><?=h($personal?'Pessoal':student_test_visibility_label($visibility))?></span>
          <details class="student-record-menu">
            <summary>Mais ações</summary>
            <div class="student-record-menu-panel">
              <?php if($researchSource):?><a class="button button-secondary button-compact" href="/aluno/comparar-processos.php?id%5B%5D=<?=(int)$researchSource['id']?>&amp;id%5B%5D=<?=$recordId?>">Comparar com origem</a><?php else:?><a class="button button-secondary button-compact" href="/aluno/comparar-processos.php?id%5B%5D=<?=$recordId?>">Comparar com outro registro</a><?php endif;?>
              <form method="post">
                <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-notebook'))?>">
                <input type="hidden" name="action" value="duplicate"><input type="hidden" name="id" value="<?=$recordId?>">
                <button class="button button-secondary button-compact" type="submit">Duplicar registro</button>
              </form>
              <?php if(!$personal):?>
                <form method="post">
                  <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-notebook'))?>">
                  <input type="hidden" name="action" value="visibility"><input type="hidden" name="id" value="<?=$recordId?>">
                  <label class="form-field">Visibilidade<select name="visibility"><option value="private"<?=$visibility==='private'?' selected':''?>>Privado</option><option value="cohort"<?=$visibility==='cohort'?' selected':''?>>Turma</option><option value="course"<?=$visibility==='course'?' selected':''?>>Curso</option></select></label>
                  <button class="button button-secondary button-compact" type="submit">Salvar visibilidade</button>
                </form>
              <?php endif;?>
              <a class="button button-danger button-compact" href="/aluno/excluir-teste.php?id=<?=$recordId?>">Excluir registro</a>
            </div>
          </details>
        </div>
      </article>
      <?php endforeach;?>
    </div>
  <?php endif;?>
</section>

<?php if($shared):?>
<section class="student-section">
  <div class="student-section-heading"><div><p class="student-kicker">Compartilhados</p><h2 class="student-subtitle">Com você</h2></div><p><?=h(student_experience_count(count($shared),'registro','registros'))?></p></div>
  <div class="student-test-list">
    <?php foreach($shared as $record):?><a class="student-test-row" href="/aluno/teste-compartilhado.php?id=<?=(int)$record['id']?>"><div><span class="student-status"><?=h(student_test_visibility_label((string)$record['visibility']))?></span><h3><?=h((string)$record['title'])?></h3><p><?=h((string)$record['student_name'])?> · <?=h((string)$record['cohort_title'])?></p></div></a><?php endforeach;?>
  </div>
</section>
<?php endif;?>

<?php
student_shell_end();

function student_process_date(string $value): string {
    $ts=strtotime($value);
    return $ts===false?$value:date('d/m/Y',$ts);
}

function student_notebook_has_exposure(array $record,array $sceneMedia): bool {
    if($sceneMedia)return true;
    foreach(['film','iso_reference','aperture','calculated_time','reciprocity_time','lot','light_condition','tonal_range'] as $key){
        if(trim((string)($record[$key]??''))!=='')return true;
    }
    return false;
}

function student_notebook_has_result(array $record,array $resultMedia): bool {
    if($resultMedia)return true;
    if(trim((string)($record['notes']??''))!=='')return true;
    return in_array((string)($record['status']??'draft'),['submitted','needs_revision','reviewed'],true);
}

function student_notebook_process_summary(array $steps,?array $plan,array $planSteps): string {
    $count=count($steps);
    if($count>0)return 'Processamento: '.student_experience_count($count,'etapa registrada','etapas registradas');
    if($plan){
        $completed=0;
        foreach($planSteps as $step)if((string)($step['status']??'')==='completed')$completed++;
        if($completed>0)return 'Processamento: '.$completed.' de '.count($planSteps).' etapas registradas';
        $name=trim((string)($plan['source_name']??''));
        return $name!==''?'Roteiro associado: '.$name:'Roteiro associado';
    }
    return 'Processamento ainda não registrado';
}
