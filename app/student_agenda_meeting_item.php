<?php
declare(strict_types=1);
$tz=(string)($meeting['meeting_timezone']??'America/Sao_Paulo');
$status=(string)($meeting['meeting_status']??'');
$cancelled=$status==='cancelled';
$statusText=$cancelled?'Cancelado':($status==='completed'?'Realizado':($isHistory?'Horário encerrado':'Agendado'));
$meetingUrl=!$isHistory && $status==='scheduled'?course_agenda_safe_meeting_url($meeting['meeting_url']??''):'';
$date=student_agenda_date_parts((string)($meeting['meeting_starts_at']??''),$tz);
$participation=(string)($meeting['participation']??'regular')==='listener'?'Ouvinte':'Matriculado';
?>
<article class="agenda-meeting student-v2-agenda-meeting <?=$isHistory?'is-history':'is-upcoming'?>" data-meeting-history="<?=$isHistory?'true':'false'?>">
  <div class="student-v2-agenda-meeting-meta">
    <span class="student-v2-agenda-status"><?=$isHistory?h($statusText):'Próximo encontro'?></span>
    <span class="student-status"><?=h($participation)?></span>
  </div>
  <div class="student-v2-agenda-meeting-main">
    <div class="student-v2-agenda-meeting-date">
      <time datetime="<?=h((string)$meeting['meeting_starts_at'])?>">
        <strong><?=h($date['day'])?></strong><span><?=h($date['weekday'])?><small><?=h($date['month'])?></small></span>
      </time>
      <strong class="student-v2-agenda-meeting-hour"><?=h($date['time'])?></strong>
    </div>
    <h3><?=h((string)($meeting['lesson_title']??''))?></h3>
    <p class="agenda-meeting-context"><?=h((string)($meeting['course_title']??''))?> · <?=h((string)($meeting['cohort_title']??''))?></p>
    <p class="student-v2-agenda-meeting-details"><?=h($date['full'])?><?php if(!empty($meeting['meeting_duration_minutes'])):?> · <?=(int)$meeting['meeting_duration_minutes']?> min<?php endif;?></p>
  </div>
  <?php if($meetingUrl!==''):?>
    <a class="student-v2-agenda-meeting-join" href="<?=h($meetingUrl)?>" target="_blank" rel="noopener noreferrer">Acessar encontro ↗</a>
  <?php elseif(!$isHistory && !$cancelled):?>
    <p class="agenda-meeting-notes student-v2-agenda-meeting-no-link">Link de acesso ainda não informado.</p>
  <?php endif;?>
  <?php if(!$isHistory && !$cancelled && trim((string)($meeting['meeting_notes']??''))!==''):?>
    <div class="student-v2-agenda-notes"><strong>Informações de acesso</strong><p><?=nl2br(h((string)$meeting['meeting_notes']))?></p></div>
  <?php endif;?>
</article>
