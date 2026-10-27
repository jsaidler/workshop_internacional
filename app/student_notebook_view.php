<?php
/** Authenticated Caderno index renderer. Service calls remain scoped to owning student. */
?>
<div class="student-v2-notebook" data-student-notebook>
<div class="student-page-heading student-notebook-heading student-v2-notebook-header">
  <div>
    <p class="student-kicker student-v2-eyebrow">Sua pesquisa</p>
    <h1 class="student-title">Caderno</h1>
    <p class="student-v2-notebook-lead">Exposição, processamento e resultado — registre no seu ritmo, em qualquer ordem.</p>
  </div>
  <button class="button button-primary student-v2-notebook-create" type="button" data-record-create-open><span aria-hidden="true">+</span> Novo registro</button>
</div>

<?php if($notice!==''):?><p class="ui-alert ui-alert-notice"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''&&!$failedCreate):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>

<?php student_record_create_dialog($enrollments,csrf_token('student-process-notebook'),$failedCreate?$_POST:[], $failedCreate?$error:''); ?>

<section class="student-section student-notebook-records student-v2-notebook-section" aria-labelledby="student-v2-notebook-records-title">
  <div class="student-section-heading student-notebook-section-heading">
    <div><p class="student-kicker">Sua coleção</p><h2 id="student-v2-notebook-records-title" class="student-subtitle"><?=h(student_experience_count(count($records),'registro','registros'))?></h2></div>
    <p>Abra um registro para editar qualquer parte.</p>
  </div>

  <?php if(!$records):?>
    <div class="student-empty student-notebook-empty student-v2-notebook-empty" data-notebook-empty>
      <strong>Comece pelo primeiro registro.</strong>
      <p>Você pode registrar uma exposição, anotar um processamento já feito ou começar diretamente pelo resultado. Nada precisa estar completo para ser salvo.</p>
      <button class="button button-primary" type="button" data-record-create-open>Criar primeiro registro</button>
    </div>
  <?php else:?>
    <div class="student-process-list student-notebook-list student-v2-notebook-list" data-notebook-records>
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
      <article class="student-process-card student-notebook-card student-v2-notebook-card" data-record-id="<?=$recordId?>">
        <a class="student-process-card-main" href="/aluno/teste.php?id=<?=$recordId?>">
          <div class="student-notebook-card-copy">
            <div class="student-v2-notebook-card-overline"><span class="student-card-label"><?=h($context)?></span><span class="student-v2-notebook-card-date">Atualizado <?=h(student_process_date((string)$record['updated_at']))?></span></div>
            <h3><?=h((string)$record['title'])?></h3>
            <?php if($researchSource):?><p class="student-research-card-lineage">Continuação de <strong><?=h((string)$researchSource['title'])?></strong><?php if($researchIntent!==''):?> · <?=h($researchIntent)?><?php endif;?></p><?php endif;?>
            <div class="student-record-summary" aria-label="Conteúdo do registro">
              <span class="<?=$exposureRecorded?'is-recorded':'is-empty'?>"><?=h($exposureRecorded?'Exposição registrada':'Exposição ainda não registrada')?></span>
              <span class="<?=count($steps)>0?'is-recorded':'is-neutral'?>"><?=h($processText)?></span>
              <span class="<?=$resultRecorded?'is-recorded':'is-empty'?>"><?=h($resultRecorded?'Resultado registrado':'Resultado ainda não registrado')?></span>
            </div>
          </div>
          <div class="student-process-card-state">
            <strong>Abrir registro →</strong>
            <span><?=h(!empty($record['test_date'])?student_process_date((string)$record['test_date']):'Sem data')?></span>
          </div>
        </a>
        <div class="student-process-card-footer">
          <span class="student-status student-v2-notebook-visibility"><?=h($personal?'Pessoal':student_test_visibility_label($visibility))?></span>
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
<section class="student-section student-v2-notebook-shared" aria-labelledby="student-v2-notebook-shared-title">
  <div class="student-section-heading"><div><p class="student-kicker">Compartilhados</p><h2 id="student-v2-notebook-shared-title" class="student-subtitle">Com você</h2></div><p><?=h(student_experience_count(count($shared),'registro','registros'))?></p></div>
  <div class="student-test-list">
    <?php foreach($shared as $record):?><a class="student-test-row" href="/aluno/teste-compartilhado.php?id=<?=(int)$record['id']?>"><div><span class="student-status"><?=h(student_test_visibility_label((string)$record['visibility']))?></span><h3><?=h((string)$record['title'])?></h3><p><?=h((string)$record['student_name'])?> · <?=h((string)$record['cohort_title'])?></p></div></a><?php endforeach;?>
  </div>
</section>
<?php endif;?>
</div>

