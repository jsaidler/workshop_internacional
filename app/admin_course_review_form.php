<?php
/** One canonical form for creating and editing a supplementary meeting. */
$reviewId=(int)($reviewEdit['id']??0);
$reviewTimezone=(string)($reviewEdit['meeting_timezone']??'America/Sao_Paulo');
$reviewCohortId=$reviewEdit!==null?(int)$reviewEdit['cohort_id']:(int)($cohort['id']??0);
?>
<form method="post" class="admin-form-grid">
  <input type="hidden" name="_csrf" value="<?=h(csrf_token('lessons-admin'))?>">
  <input type="hidden" name="action" value="save_review">
  <input type="hidden" name="course_id" value="<?=$courseId?>">
  <input type="hidden" name="review_id" value="<?=$reviewId?>">
  <label class="admin-form-span-2">Título<input name="title" maxlength="160" value="<?=h((string)($reviewEdit['title']??''))?>" required></label>
  <label class="admin-form-span-2">Participantes
    <select name="cohort_id">
      <option value="0"<?=$reviewCohortId===0?' selected':''?>>Alunos matriculados no curso (todas as turmas)</option>
      <?php foreach($reviewCohorts as $candidate):?>
      <option value="<?=(int)$candidate['id']?>"<?=(int)$candidate['id']===$reviewCohortId?' selected':''?>>Turma: <?=h((string)$candidate['title'])?></option>
      <?php endforeach;?>
    </select>
  </label>
  <label>Data e horário<input type="datetime-local" name="meeting_starts_at" value="<?=h(course_agenda_local($reviewEdit['meeting_starts_at']??null,$reviewTimezone))?>" required></label>
  <label>Fuso horário<input name="meeting_timezone" value="<?=h($reviewTimezone)?>" required></label>
  <label>Duração (minutos)<input type="number" name="meeting_duration_minutes" min="1" max="1440" value="<?=h((string)($reviewEdit['meeting_duration_minutes']??''))?>"></label>
  <label>Situação<select name="meeting_status">
    <?php foreach(['scheduled'=>'Agendada','completed'=>'Realizada','cancelled'=>'Cancelada'] as $value=>$label):?>
      <option value="<?=$value?>"<?=($reviewEdit['meeting_status']??'scheduled')===$value?' selected':''?>><?=$label?></option>
    <?php endforeach;?>
  </select></label>
  <label class="admin-form-span-2">Link HTTPS da reunião<input type="url" name="meeting_url" maxlength="2000" value="<?=h((string)($reviewEdit['meeting_url']??''))?>" placeholder="https://"></label>
  <label class="admin-form-span-2">Orientações de acesso<textarea name="meeting_notes" maxlength="2000" rows="3"><?=h((string)($reviewEdit['meeting_notes']??''))?></textarea></label>
  <p class="admin-form-span-2 muted">A revisão aparece na Agenda dos participantes autorizados. Para avisar por e-mail, use Comunicações.</p>
  <div class="admin-form-actions"><button class="admin-button" type="submit"><?=$reviewId?'Salvar revisão':'Criar revisão'?></button></div>
</form>
