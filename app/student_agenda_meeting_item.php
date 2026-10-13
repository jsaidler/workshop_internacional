<?php
declare(strict_types=1);
$tz=(string)($meeting['meeting_timezone']??'America/Sao_Paulo');
$status=(string)($meeting['meeting_status']??'');
$cancelled=$status==='cancelled';
$statusText=$cancelled?'Cancelado':($status==='completed'?'Realizado':($isHistory?'Horário encerrado':'Agendado'));
$meetingUrl=!$isHistory && $status==='scheduled'?course_agenda_safe_meeting_url($meeting['meeting_url']??''):'';
?>
<article class="student-academic-material-row agenda-meeting" data-meeting-history="<?=$isHistory?'true':'false'?>">
  <div>
    <div class="agenda-meeting-meta">
      <span class="agenda-meeting-date"><time datetime="<?=h((string)$meeting['meeting_starts_at'])?>"><?=h(course_agenda_when((string)$meeting['meeting_starts_at'],$tz))?></time></span>
      <span class="student-status"><?=h(($meeting['participation']??'regular')==='listener'?'Ouvinte':'Matriculado')?></span>
      <span class="agenda-meeting-status"><?=h($statusText)?></span>
    </div>
    <h3><?=h((string)($meeting['lesson_title']??''))?></h3>
    <p class="agenda-meeting-context"><?=h((string)($meeting['course_title']??''))?> · <?=h((string)($meeting['cohort_title']??''))?><?php if(!empty($meeting['meeting_duration_minutes'])):?> · <?=(int)$meeting['meeting_duration_minutes']?> min<?php endif;?></p>
    <?php if($meetingUrl!==''):?>
      <p class="agenda-meeting-actions"><a class="button button-secondary button-compact" href="<?=h($meetingUrl)?>" target="_blank" rel="noopener noreferrer">Acessar encontro ↗</a></p>
    <?php elseif(!$isHistory && !$cancelled):?>
      <p class="agenda-meeting-notes">Link de acesso ainda não informado.</p>
    <?php endif;?>
    <?php if(!$isHistory && !$cancelled && trim((string)($meeting['meeting_notes']??''))!==''):?>
      <p class="agenda-access-notes agenda-meeting-notes"><?=nl2br(h((string)$meeting['meeting_notes']))?></p>
    <?php endif;?>
  </div>
</article>
