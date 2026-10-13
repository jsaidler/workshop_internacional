<?php
declare(strict_types=1);
require_once __DIR__.'/student_agenda_presentation.php';
$agendaGroups=course_agenda_partition_meetings($meetings);
$upcoming=$agendaGroups['upcoming'];
$history=$agendaGroups['history'];
$agendaView=student_agenda_selected_view($_GET['view']??'encontros');
?>
<div class="student-v2-agenda" data-student-agenda data-agenda-view="<?=h($agendaView)?>">
  <header class="student-v2-agenda-heading">
    <p class="student-v2-eyebrow">Seus compromissos</p>
    <h1>Agenda</h1>
    <p>Encontros da sua turma e oportunidades de retorno como ouvinte.</p>
  </header>
  <?php if($notice!==''):?><p class="student-v2-agenda-message" role="status"><?=h($notice)?></p><?php endif;?>
  <?php if($error!==''):?><p class="student-v2-agenda-message is-error" role="alert"><?=h($error)?></p><?php endif;?>
  <nav class="student-v2-agenda-views" aria-label="Visões da Agenda">
    <a href="?view=encontros" data-agenda-view-link="encontros" <?=$agendaView==='encontros'?'aria-current="page"':''?>>Meus encontros <span><?=count($upcoming)?></span></a>
    <a href="?view=ouvintes" data-agenda-view-link="ouvintes" <?=$agendaView==='ouvintes'?'aria-current="page"':''?>>Como ouvinte <span><?=count($offers)?></span></a>
  </nav>
  <?php if($agendaView==='encontros'):?>
  <section class="student-v2-agenda-section" aria-labelledby="agenda-meetings">
    <h2 class="student-v2-agenda-section-title" id="agenda-meetings">Meus encontros</h2>
    <?php if(!$upcoming):?>
      <div class="student-v2-agenda-empty" data-agenda-empty>
        <span class="student-v2-agenda-empty-icon" aria-hidden="true"><?=student_shell_nav_icon('agenda')?></span>
        <h3>Nenhum próximo encontro</h3>
        <p><?=!$meetings?'Ainda não há encontros com data definida nas suas turmas.':'Nenhum próximo encontro agendado. Você pode consultar os anteriores abaixo.'?></p>
      </div>
    <?php else:?>
      <div class="student-v2-agenda-meetings" data-agenda-upcoming>
        <?php foreach($upcoming as $meeting):$isHistory=false;require __DIR__.'/student_agenda_meeting_item.php';endforeach;?>
      </div>
    <?php endif;?>
    <?php if($history):?>
      <details class="student-agenda-history student-v2-agenda-history" data-agenda-history>
        <summary>Encontros anteriores e cancelados <span><?=count($history)?></span></summary>
        <div class="student-v2-agenda-history-items">
          <?php foreach($history as $meeting):$isHistory=true;require __DIR__.'/student_agenda_meeting_item.php';endforeach;?>
        </div>
      </details>
    <?php endif;?>
  </section>
  <?php else:?>
  <section class="student-v2-agenda-section" aria-labelledby="agenda-offers">
    <h2 class="student-v2-agenda-section-title" id="agenda-offers">Próximas turmas</h2>
    <p class="student-v2-agenda-section-lead">Você pode retornar a outra turma do mesmo curso. A confirmação depende da janela de reservas e da capacidade disponível.</p>
    <?php if(!$offers):?>
      <div class="student-v2-agenda-empty" data-agenda-offers-empty>
        <span class="student-v2-agenda-empty-icon" aria-hidden="true"><?=student_shell_nav_icon('courses')?></span>
        <h3>Nenhuma turma disponível</h3>
        <p>Não há novas turmas com encontros programados para os cursos em que você tem matrícula.</p>
      </div>
    <?php else:?>
      <div class="student-v2-agenda-offers">
        <?php foreach($offers as $offer):
          $capacity=$offer['capacity'];
          $status=(string)($offer['reservation_status']??'');
          $open=course_agenda_window_open($offer);
          $confirmed=$status==='confirmed';
          $waiting=$status==='waiting';
          $dates=$offer['schedule']??[];
          $firstDate=student_agenda_date_parts((string)($offer['next_meeting']??''),(string)($dates[0]['meeting_timezone']??'America/Sao_Paulo'));
        ?>
          <article class="student-v2-agenda-offer agenda-offer" data-reservation-status="<?=h($status)?>">
            <div class="student-v2-agenda-offer-overline">
              <span>Retorno ao curso</span>
              <?php if($confirmed):?><strong class="student-v2-agenda-reservation-state">Ouvinte confirmado</strong>
              <?php elseif($waiting):?><strong class="student-v2-agenda-reservation-state">Na lista de espera</strong>
              <?php else:?><span class="student-v2-agenda-reservation-state"><?=$open?'Reservas abertas':'Aguardando abertura'?></span><?php endif;?>
            </div>
            <h3><?=h((string)$offer['course_title'])?></h3>
            <p class="student-v2-agenda-offer-cohort"><?=h((string)$offer['cohort_title'])?></p>
            <div class="student-v2-agenda-offer-date">
              <span class="student-v2-agenda-date-chip"><strong><?=h($firstDate['day'])?></strong><small><?=h($firstDate['month'])?></small></span>
              <span class="student-v2-agenda-offer-date-copy"><strong>Primeiro encontro · <?=h($firstDate['time'])?></strong><small><?=h($firstDate['full'])?> · <?=(int)$offer['future_meetings']?> <?=((int)$offer['future_meetings'])===1?'encontro programado':'encontros programados'?></small></span>
            </div>
            <p class="agenda-capacity student-v2-agenda-capacity">
              <span><?=$open?'Vagas disponíveis':'Vagas potenciais'?></span>
              <strong><?=(int)$capacity['remaining']?></strong>
              <?php if($open && (int)$capacity['remaining']===0):?><small>Lotada (lista de espera)</small><?php elseif(!$open):?><small>Confirmações ainda não abertas</small><?php endif;?>
            </p>
            <?php if($dates):?>
              <details class="student-agenda-offer-schedule student-v2-agenda-schedule">
                <summary>Ver <?=count($dates)?> <?=count($dates)===1?'encontro programado':'encontros programados'?></summary>
                <div class="student-v2-agenda-schedule-body" aria-label="Encontros desta turma">
                  <?php foreach($dates as $lesson):?>
                    <div class="student-academic-release-line student-v2-agenda-schedule-row">
                      <span><?=h((string)$lesson['lesson_title'])?></span>
                      <small><?=h(course_agenda_when((string)$lesson['meeting_starts_at'],(string)$lesson['meeting_timezone']))?></small>
                    </div>
                  <?php endforeach;?>
                </div>
              </details>
            <?php endif;?>
            <form method="post" action="?view=ouvintes" class="agenda-reservation-form student-v2-agenda-reservation-form">
              <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-agenda'))?>">
              <input type="hidden" name="cohort_id" value="<?=(int)$offer['cohort_id']?>">
              <?php if($confirmed || $waiting):?>
                <button type="submit" name="action" value="cancel" class="student-v2-agenda-secondary-button" data-confirm="Cancelar sua participação como ouvinte nesta turma?">Cancelar solicitação</button>
              <?php else:?>
                <button type="submit" name="action" value="request" class="student-v2-agenda-primary-button"><?=h($open&&(int)$capacity['remaining']>0?'Solicitar vaga':'Entrar na lista de espera')?></button>
              <?php endif;?>
            </form>
            <p class="student-v2-agenda-eligibility">A solicitação não garante vaga. Sua matrícula original e o acesso aos materiais permanecem inalterados.</p>
          </article>
        <?php endforeach;?>
      </div>
    <?php endif;?>
  </section>
  <?php endif;?>
</div>
