<header class="student-academic-course-head student-agenda-head"><p class="student-kicker">Cursos</p><h1 class="student-title">Agenda</h1><p class="student-lead">Seus encontros e as próximas oportunidades de retorno como ouvinte.</p></header>
<?php if($notice!==''):?><p class="student-academic-attention" role="status"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="student-academic-attention" role="alert"><?=h($error)?></p><?php endif;?>
<?php $agendaGroups=course_agenda_partition_meetings($meetings);$upcoming=$agendaGroups['upcoming'];$history=$agendaGroups['history'];?>
<section class="student-academic-study" aria-labelledby="agenda-meetings">
  <header class="student-academic-study-head"><div><p class="student-kicker">Agenda</p><h2 id="agenda-meetings">Meus encontros</h2></div></header>
  <?php if(!$meetings):?>
    <p class="student-academic-empty">Ainda não há encontros com data definida nas suas turmas.</p>
  <?php elseif(!$upcoming):?>
    <p class="student-academic-empty">Nenhum próximo encontro agendado. Você pode consultar os anteriores abaixo.</p>
  <?php endif;?>
  <?php if($upcoming):?><div class="student-academic-material-list" data-agenda-upcoming>
    <?php foreach($upcoming as $meeting):$isHistory=false;require __DIR__.'/student_agenda_meeting_item.php';endforeach;?>
  </div><?php endif;?>
  <?php if($history):?>
  <details class="student-agenda-history" data-agenda-history>
    <summary>Encontros anteriores e cancelados <span>(<?=count($history)?>)</span></summary>
    <div class="student-academic-material-list">
      <?php foreach($history as $meeting):$isHistory=true;require __DIR__.'/student_agenda_meeting_item.php';endforeach;?>
    </div>
  </details>
  <?php endif;?>
</section>
<section class="student-academic-study" aria-labelledby="agenda-offers"><header class="student-academic-study-head"><div><p class="student-kicker">Retornar ao curso</p><h2 id="agenda-offers">Próximas turmas</h2></div></header>
<?php if(!$offers):?><p class="student-academic-empty">Não há novas turmas com encontros programados para os cursos em que você tem matrícula.</p>
<?php else:?><div class="student-academic-choice-list"><?php foreach($offers as $offer):$capacity=$offer['capacity'];$status=(string)($offer['reservation_status']??'');$open=course_agenda_window_open($offer);$confirmed=$status==='confirmed';$waiting=$status==='waiting';?>
<article class="student-academic-choice agenda-offer"><div><h3><?=h((string)$offer['course_title'])?></h3><p><?=h((string)$offer['cohort_title'])?></p><p><?=h(course_agenda_when((string)$offer['next_meeting'],(string)($offer['schedule'][0]['meeting_timezone']??'America/Sao_Paulo')))?> · <?=(int)$offer['future_meetings']?> encontro(s) programado(s)</p><p class="agenda-capacity"><strong><?=$capacity['remaining']?> <?=h($open?'vaga(s) disponíveis':'vaga(s) potenciais')?></strong><?php if(!$open):?> · Confirmações ainda não abertas<?php elseif($capacity['remaining']>0):?> · Reservas abertas<?php else:?> · Lotada (lista de espera)<?php endif;?></p>
<?php if(!empty($offer['schedule'])):?><details class="student-agenda-offer-schedule"><summary>Ver <?=count($offer['schedule'])?> encontros programados</summary><div class="student-academic-release-list" aria-label="Encontros desta turma"><?php foreach($offer['schedule'] as $lesson):?><div class="student-academic-release-line"><span><?=h((string)$lesson['lesson_title'])?></span><small><?=h(course_agenda_when((string)$lesson['meeting_starts_at'],(string)$lesson['meeting_timezone']))?></small></div><?php endforeach;?></div></details><?php endif;?>
<?php if($confirmed):?><p class="student-status">Ouvinte confirmado</p><?php elseif($waiting):?><p class="student-status">Na lista de espera</p><?php endif;?>
<form method="post" class="agenda-reservation-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-agenda'))?>"><input type="hidden" name="cohort_id" value="<?=(int)$offer['cohort_id']?>">
<?php if($confirmed || $waiting):?><button class="button button-secondary button-compact" name="action" value="cancel" data-confirm="Cancelar sua participação como ouvinte nesta turma?">Cancelar solicitação</button>
<?php else:?><button class="button button-secondary button-compact" name="action" value="request"><?=h($open && $capacity['remaining']>0?'Solicitar vaga':'Entrar na lista de espera')?></button><?php endif;?></form></div></article>
<?php endforeach;?></div><?php endif;?></section>
<p class="student-academic-empty">O retorno como ouvinte depende de novas turmas e vagas disponíveis. Sua matrícula e seu acesso aos materiais permanecem vinculados à turma original.</p>
