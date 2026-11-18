<?php
/** Included by admin/lessons.php within the existing course/cohort workspace. */
$reviewRows=course_review_list($db,$courseId,$cohort?(int)$cohort['id']:null);
$reviewCohorts=course_cohorts_for_course($db,$courseId);
?>
<section class="admin-card admin-teaching-detail" aria-labelledby="course-reviews-heading">
  <header><div><p class="admin-kicker">Encontros complementares</p>
    <h2 id="course-reviews-heading">Revisões</h2>
    <p>Encontros opcionais, sem alterar as aulas, o material ou a matrícula do curso.</p>
  </div></header>
  <?php if(!$reviewRows):?><p class="muted">Nenhuma revisão agendada.</p><?php endif;?>
  <?php foreach($reviewRows as $review):?>
  <details class="admin-secondary-setup admin-block-gap">
    <summary class="admin-button secondary"><?=h((string)$review['title'])?> · <?=h(course_agenda_when((string)$review['meeting_starts_at'],(string)$review['meeting_timezone']))?> · <?=h($review['meeting_status']==='cancelled'?'Cancelada':($review['meeting_status']==='completed'?'Realizada':'Agendada'))?></summary>
    <?php $reviewEdit=$review;require __DIR__.'/admin_course_review_form.php';?>
  </details>
  <?php endforeach;?>
  <details class="admin-secondary-setup admin-block-gap">
    <summary class="admin-button secondary">Agendar revisão</summary>
    <?php $reviewEdit=null;require __DIR__.'/admin_course_review_form.php';?>
  </details>
</section>
