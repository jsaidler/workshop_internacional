<?php
declare(strict_types=1);
/** Same operational transfer panel in the administrator route and browser audit. */
function admin_student_cohort_transfer_panel(array $selected,array $cohortOptions,int $activityId,int $courseId,int $cohortId,int $studentId):void {
?>
<?php
$hasRegistration=(int)($selected['source_submission_id']??0)>0;
$targets=array_values(array_filter($cohortOptions,static fn(array $candidate):bool=>(string)($candidate['status']??'')==='active'&&(int)$candidate['id']!==(int)$selected['cohort_id']));
?>
<section class="admin-operation-section admin-student-transfer" aria-labelledby="student-transfer-title">
  <h3 id="student-transfer-title">Mudar de turma</h3>
  <p>Esta pessoa participa atualmente de <strong><?=h((string)$selected['cohort_title'])?></strong>. A transferência altera a turma ativa, mas preserva os registros históricos.</p>
  <?php if($targets):?>
  <form class="admin-inline-actions" method="post">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('students-admin'))?>">
    <input type="hidden" name="action" value="<?=$hasRegistration?'move_registration':'move_manual_enrollment'?>">
    <input type="hidden" name="course_id" value="<?=$courseId?>">
    <input type="hidden" name="cohort_id" value="<?=$cohortId?>">
    <input type="hidden" name="student" value="<?=$studentId?>">
    <?php if($hasRegistration):?><input type="hidden" name="submission_id" value="<?=(int)$selected['source_submission_id']?>"><?php else:?><input type="hidden" name="enrollment_id" value="<?=(int)$selected['enrollment_id']?>"><?php endif;?>
    <label for="student-target-cohort">Turma de destino
      <select id="student-target-cohort" name="target_cohort_id" required>
        <option value="" selected>Selecione uma turma</option>
        <?php foreach($targets as $item):?><option value="<?=(int)$item['id']?>"><?=h((string)$item['title'])?></option><?php endforeach;?>
      </select>
    </label>
    <button class="admin-button secondary" type="submit">Confirmar transferência</button>
  </form>
  <p class="admin-operation-note">A turma de destino precisa estar ativa e ter vaga. Em caso de impedimento, a matrícula original continua válida.</p>
  <?php else:?><p class="admin-operation-note">Não há outra turma ativa neste curso para receber a transferência.</p><?php endif;?>
  <?php if($hasRegistration):?>
  <p><a href="<?=h(admin_course_url($activityId,$courseId,'registrations').'&submission='.(int)$selected['source_submission_id'])?>">Ver inscrição original</a></p>
  <p class="admin-operation-note">Pagamento, cancelamento e retirada de turma continuam vinculados à inscrição original.</p>
  <?php endif;?>
</section>
<?php if(!$hasRegistration):?>
<section class="admin-operation-danger"><h3>Desativar participação manual</h3><p>Disponível apenas para matrículas importadas ou criadas sem uma inscrição de origem. O histórico da pessoa é preservado.</p><form method="post" data-confirm="Desativar esta participação manual?"><input type="hidden" name="_csrf" value="<?=h(csrf_token('students-admin'))?>"><input type="hidden" name="action" value="disable_manual_enrollment"><input type="hidden" name="course_id" value="<?=$courseId?>"><input type="hidden" name="cohort_id" value="<?=$cohortId?>"><input type="hidden" name="student" value="<?=$studentId?>"><input type="hidden" name="enrollment_id" value="<?=(int)$selected['enrollment_id']?>"><button class="admin-button danger" type="submit">Desativar participação</button></form></section>
<?php endif;?>
<?php
}
