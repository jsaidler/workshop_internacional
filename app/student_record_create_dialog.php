<?php
declare(strict_types=1);
/**
 * Shared short creation form, so the operational notebook and the mobile
 * audit render the same dialog and preserve failed POST drafts.
 */
function student_record_create_dialog(array $enrollments,string $csrfToken,array $draft=[],string $error='',string $actionUrl=''): void {
    $e=static fn(mixed $value):string=>htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $title=(string)($draft['title']??'');
    $date=(string)($draft['test_date']??date('Y-m-d'));
    $scope=(($draft['context_scope']??'personal')==='course'&&$enrollments)?'course':'personal';
    $cohort=(string)($draft['context_cohort_id']??'');
    ?>
<dialog class="student-create-dialog student-record-create-dialog" data-record-create-dialog aria-labelledby="student-create-title"<?=$error!==''?' data-record-create-error':''?>>
  <div class="student-create-dialog-head">
    <div><p class="student-kicker">Caderno</p><h2 id="student-create-title" tabindex="-1">Novo registro</h2></div>
    <button class="button button-secondary button-compact" type="button" data-record-create-close>Fechar</button>
  </div>
  <div class="student-create-dialog-intro"><p>Comece com o que já sabe. O restante pode ser preenchido depois.</p></div>
  <?php if($error!==''):?><p class="ui-alert ui-alert-error student-create-error" data-record-create-alert role="alert" tabindex="-1"><?=$e($error)?></p><?php endif;?>
  <form method="post"<?=$actionUrl!==''?' action="'.$e($actionUrl).'"':''?> class="student-form-grid" data-ui-validate>
    <input type="hidden" name="_csrf" value="<?=$e($csrfToken)?>">
    <input type="hidden" name="action" value="create">
    <label class="form-field student-span-2">Título<input name="title" maxlength="180" value="<?=$e($title)?>" placeholder="Ex.: EI 200 — primeiro teste"></label>
    <label class="form-field">Data<input type="date" name="test_date" value="<?=$e($date)?>"></label>
    <fieldset class="choice-field">
      <legend>Contexto</legend>
      <div class="choice-row">
        <label class="choice-option"><input type="radio" name="context_scope" value="personal"<?=$scope==='personal'?' checked':''?> data-process-context-radio><span>Pessoal</span></label>
        <?php if($enrollments):?><label class="choice-option"><input type="radio" name="context_scope" value="course"<?=$scope==='course'?' checked':''?> data-process-context-radio><span>Curso</span></label><?php endif;?>
      </div>
    </fieldset>
    <?php if($enrollments):?>
      <label class="form-field student-span-2" data-process-course-context<?=$scope==='course'?'':' hidden'?>>Curso e turma
        <select name="context_cohort_id"<?=$scope==='course'?' required':''?>>
          <option value="">Escolha…</option>
          <?php foreach($enrollments as $enrollment):?>
          <option value="<?=$e($enrollment['cohort_id']??'')?>"<?=$cohort===(string)($enrollment['cohort_id']??'')?' selected':''?>><?=$e($enrollment['course_title']??'Curso')?> · <?=$e($enrollment['cohort_title']??'Turma')?></option>
          <?php endforeach;?>
        </select>
      </label>
    <?php endif;?>
    <div class="student-actions student-span-2"><button class="button button-primary" type="submit">Criar registro</button></div>
  </form>
</dialog>
    <?php
}
