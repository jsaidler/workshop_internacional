<?php
declare(strict_types=1);
/** Authenticated Home. Events and destinations are projected in the controller. */
$meeting=$home['meeting']??null;
$notices=$home['notices']??[];
$courses=$home['courses']??[];
$offers=$home['offers']??[];
$bibliographyUrl=$home['bibliography_url']??'';
$priority=student_home_live_priority($meeting,$notices,$home['now']??null);
$unread=count(array_filter($notices,static fn(array $item):bool=>!empty($item['unread'])));
$firstTwo=array_slice(array_values(array_filter($notices,static fn(array $item):bool=>
    ($priority['kind']??'')!=='notice'||$item['key']!==($priority['item']['key']??''))),0,2);
?>
<div class="student-home-live" data-student-home data-home-live>
  <h1 class="student-home-live-accessible-title">Início</h1>
  <?php if(($priority['kind']??'')==='meeting'):
    $event=$priority['item'];?>
    <section class="student-home-live-feature" aria-labelledby="student-home-main-event" data-home-meeting>
      <div class="student-home-live-feature-meta"><span>Próximo encontro</span><span><?=h(!empty($event['in_window'])?'No horário previsto':'Programado')?></span></div>
      <div class="student-home-meeting">
        <div class="student-home-meeting-date"><strong><?=h((string)$event['day'])?></strong><small><?=h((string)$event['month'])?></small></div>
        <div class="student-home-meeting-info"><h2 id="student-home-main-event"><?=h((string)$event['lesson'])?></h2>
          <p><?=h((string)$event['course'])?> · <?=h((string)$event['cohort'])?></p></div>
      </div>
      <div class="student-home-live-feature-foot"><time datetime="<?=h((string)$event['date_iso'])?>"><?=h((string)$event['hour'])?> · <?=h((string)$event['timezone_label'])?></time>
        <?php if(!empty($event['url'])):?><a class="button button-primary" href="<?=h((string)$event['url'])?>" target="_blank" rel="noopener noreferrer">Acessar encontro ↗</a>
        <?php else:?><a class="button button-primary" href="/aluno/agenda.php">Ver encontro →</a><?php endif;?>
      </div>
    </section>
  <?php elseif(($priority['kind']??'')==='notice'):
    $event=$priority['item'];?>
    <section class="student-home-live-feature student-home-live-feature-message" aria-labelledby="student-home-main-event" data-home-message>
      <p class="student-home-live-feature-meta"><?=h(($event['kind']==='answer'?'Resposta do professor':($event['kind']==='opportunity'?'Encontro de outra turma':'Retorno da avaliação')).' · '.$event['cohort'])?></p>
      <h2 id="student-home-main-event"><?=h((string)$event['title'])?></h2>
      <p><?=h((string)$event['course'])?></p>
      <a class="button button-primary" href="<?=h((string)$event['href'])?>"><?=h(($event['kind']==='answer'?'Ler resposta':($event['kind']==='opportunity'?'Ver como ouvinte':(!empty($event['action'])?'Ler retorno e revisar':'Ler retorno'))))?> →</a>
    </section>
  <?php endif;?>
  <?php if($meeting&&($priority['kind']??'')!=='meeting'):?>
    <section class="student-home-next" data-home-upcoming-meeting aria-label="Próximo encontro">
      <a href="/aluno/agenda.php"><span><small>Próximo encontro · <?=h((string)$meeting['day'].' '.(string)$meeting['month'].' · '.(string)$meeting['hour'])?></small>
        <strong><?=h((string)$meeting['lesson'])?></strong>
        <small><?=h((string)$meeting['cohort'])?></small></span><span aria-hidden="true">›</span></a>
    </section>
  <?php endif;?>
  <?php if($firstTwo):?>
    <section class="student-home-live-section" data-home-updates aria-labelledby="home-updates-label">
      <div class="student-home-live-section-head"><h2 id="home-updates-label">Atualizações</h2><a href="/aluno/notificacoes.php">Todas →</a></div>
      <div class="student-home-notice-list">
      <?php foreach($firstTwo as $notice):?>
        <a class="student-home-notice<?=$notice['unread']?' is-unread':''?>" href="<?=h((string)$notice['href'])?>">
          <span class="student-home-notice-symbol" aria-hidden="true"><?=($notice['kind']==='answer')?'↗':(($notice['kind']==='opportunity')?'◷':'✓')?></span>
          <span><small><?=h(($notice['kind']==='answer'?'Resposta recebida':($notice['kind']==='opportunity'?'Encontro como ouvinte':'Avaliação')).' · '.$notice['cohort'])?></small>
            <strong><?=h((string)$notice['title'])?></strong>
          </span><span aria-hidden="true">›</span>
        </a>
      <?php endforeach;?>
      </div>
    </section>
  <?php endif;?>
  <section class="student-home-live-section student-home-live-courses" aria-labelledby="home-my-courses" data-home-courses>
    <div class="student-home-live-section-head"><h2 id="home-my-courses">Meus cursos</h2><a href="/aluno/cursos.php">Ver todos →</a></div>
    <?php if(!$courses):?><p class="student-home-live-empty">Não há matrículas ativas.</p><?php endif;?>
    <?php foreach($courses as $course):?>
      <article class="student-home-course">
        <div><p class="student-home-course-cohort"><?=h((string)$course['cohort'])?></p>
          <a class="student-home-course-title" href="<?=h((string)$course['url'])?>"><?=h((string)$course['title'])?> <span aria-hidden="true">›</span></a></div>
        <div class="student-home-course-actions">
          <?php if($course['material']):?><a class="student-home-material" href="<?=h((string)$course['material']['url'])?>">Material</a>
          <?php else:?><span class="student-home-unavailable">Material aguardando liberação</span><?php endif;?>
          <a href="<?=h((string)$course['questions_url'])?>">Dúvidas</a>
        </div>
      </article>
    <?php endforeach;?>
  </section>
  <?php if($bibliographyUrl!==''):?>
    <section class="student-home-live-section" aria-labelledby="home-shared-reading">
      <div class="student-home-live-section-head"><h2 id="home-shared-reading">Referências</h2></div>
      <a class="student-home-notice" href="<?=h((string)$bibliographyUrl)?>">
        <span class="student-home-notice-symbol" aria-hidden="true">↗</span>
        <span><small>Disponível para todos os alunos</small><strong>Bibliografia da pesquisa fotográfica</strong></span>
        <span aria-hidden="true">›</span>
      </a>
    </section>
  <?php endif;?>
  <?php if($offers):?>
    <section class="student-home-live-section student-home-live-offers" aria-labelledby="home-other-courses">
      <div class="student-home-live-section-head"><h2 id="home-other-courses">Outras formações</h2></div>
      <?php foreach($offers as $offer):?>
        <a class="student-home-promo" href="<?=h((string)$offer['url'])?>">
          <small>FORMAÇÃO</small>
          <strong><?=h((string)$offer['title'])?></strong>
          <span>Conhecer a formação →</span>
        </a>
      <?php endforeach;?>
    </section>
  <?php endif;?>
</div>
