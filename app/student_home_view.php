<?php
declare(strict_types=1);
/** Operational Home view, also imported unchanged by the browser audit fixture.
 * $home: meeting, feedback, focus, courses, recent, can_timer, records_count.
 */
$meeting=$home['meeting']??null;
$feedback=$home['feedback']??null;
$focus=$home['focus']??null;
$courses=$home['courses']??[];
$recent=$home['recent']??[];
$firstMaterial=null;
foreach($courses as $item){if(($item['material']??null)!==null){$firstMaterial=$item['material'];break;}}
?>
<div class="student-home" data-student-home>
  <header class="student-home-heading">
    <div><p class="student-kicker">Área do aluno</p><h1 class="student-title">Início</h1></div>
    <a class="student-home-heading-agenda" href="/aluno/agenda.php">Agenda <span aria-hidden="true">→</span></a>
  </header>
  <?php if($meeting):?>
  <section class="student-home-feature" aria-labelledby="student-home-feature-title" data-home-meeting>
    <div class="student-home-feature-top"><p class="student-kicker">Próximo encontro</p><span class="student-home-feature-mark" aria-hidden="true"></span></div>
    <h2 id="student-home-feature-title"><?=h((string)$meeting['lesson'])?></h2>
    <p class="student-home-feature-context"><?=h((string)$meeting['course'])?> · <?=h((string)$meeting['cohort'])?></p>
    <p class="student-home-feature-time"><?=h((string)$meeting['when'])?></p>
    <div class="student-home-feature-actions">
      <?php if((string)$meeting['url']!==''):?><a class="button button-primary" href="<?=h((string)$meeting['url'])?>" target="_blank" rel="noopener noreferrer">Acessar encontro ↗</a><?php else:?><span class="student-home-feature-unavailable">Link ainda não informado</span><?php endif;?>
      <a class="student-home-feature-more" href="/aluno/agenda.php">Ver agenda →</a>
    </div>
  </section>
  <?php elseif($feedback):?>
  <section class="student-home-feature" aria-labelledby="student-home-feature-title" data-home-feedback-feature>
    <div class="student-home-feature-top"><p class="student-kicker">Retorno do professor</p><span class="student-home-feature-mark" aria-hidden="true"></span></div>
    <h2 id="student-home-feature-title"><?=h((string)$feedback['title'])?></h2>
    <p class="student-home-feature-context"><?=h((string)$feedback['status'])?></p>
    <div class="student-home-feature-actions"><a class="button button-primary" href="<?=h((string)$feedback['url'])?>"><?=h((string)$feedback['action'])?> →</a></div>
  </section>
  <?php elseif($focus):?>
  <section class="student-home-feature" aria-labelledby="student-home-feature-title" data-home-record-feature>
    <div class="student-home-feature-top"><p class="student-kicker">Continuar no Caderno</p><span class="student-home-feature-mark" aria-hidden="true"></span></div>
    <h2 id="student-home-feature-title"><?=h((string)$focus['title'])?></h2>
    <p class="student-home-feature-context"><?=h((string)$focus['status'])?></p>
    <div class="student-home-feature-actions"><a class="button button-primary" href="<?=h((string)$focus['url'])?>"><?=h((string)$focus['action'])?> →</a></div>
  </section>
  <?php elseif($firstMaterial):?>
  <section class="student-home-feature" aria-labelledby="student-home-feature-title" data-home-study-feature>
    <div class="student-home-feature-top"><p class="student-kicker">Material disponível</p><span class="student-home-feature-mark" aria-hidden="true"></span></div>
    <h2 id="student-home-feature-title"><?=h((string)$firstMaterial['title'])?></h2>
    <div class="student-home-feature-actions"><a class="button button-primary" href="<?=h((string)$firstMaterial['url'])?>">Abrir material →</a></div>
  </section>
  <?php else:?>
  <section class="student-home-feature student-home-feature-empty" aria-labelledby="student-home-feature-title" data-home-empty-feature>
    <div class="student-home-feature-top"><p class="student-kicker">Seu espaço de pesquisa</p><span class="student-home-feature-mark" aria-hidden="true"></span></div>
    <h2 id="student-home-feature-title">Comece pelo Caderno</h2>
    <p class="student-home-feature-context">Registre uma exposição, um processamento ou um resultado.</p>
    <div class="student-home-feature-actions"><button class="button button-primary" type="button" data-record-create-open>Novo registro +</button></div>
  </section>
  <?php endif;?>
  <?php if($feedback && $meeting):?>
    <a class="student-home-attention" href="<?=h((string)$feedback['url'])?>" data-home-feedback>
      <span><small>Retorno do professor</small><strong><?=h((string)$feedback['title'])?></strong><em><?=h((string)$feedback['status'])?></em></span><span aria-hidden="true">→</span>
    </a>
  <?php endif;?>
  <section class="student-home-section" aria-labelledby="student-home-quick-title">
    <div class="student-home-section-head"><h2 id="student-home-quick-title">Acesso rápido</h2></div>
    <div class="student-home-quick">
      <button type="button" data-record-create-open><span class="student-home-quick-symbol" aria-hidden="true">+</span><span>Novo registro</span></button>
      <?php if(!empty($home['can_timer'])):?><a href="/aluno/temporizador.php"><span class="student-home-quick-symbol" aria-hidden="true">◷</span><span>Temporizador</span></a><?php endif;?>
      <button type="button" data-toolbox-open><span class="student-home-quick-symbol" aria-hidden="true">⌁</span><span>Atalhos</span></button>
    </div>
  </section>
  <?php if($firstMaterial || ($focus && !$meeting) || ($focus && $meeting)):?>
  <section class="student-home-section" aria-labelledby="student-home-continue-title">
    <div class="student-home-section-head"><h2 id="student-home-continue-title">Continuar</h2></div>
    <div class="student-home-list">
      <?php if($firstMaterial && (!$firstMaterial || !isset($home['study_hero']))):?><a class="student-home-row" href="<?=h((string)$firstMaterial['url'])?>"><span><small>Material</small><strong><?=h((string)$firstMaterial['title'])?></strong></span><span aria-hidden="true">→</span></a><?php endif;?>
      <?php if($focus && (!$meeting && !$feedback ? false : true)):?><a class="student-home-row" href="<?=h((string)$focus['url'])?>"><span><small>Caderno</small><strong><?=h((string)$focus['title'])?></strong><em><?=h((string)$focus['status'])?></em></span><span aria-hidden="true">→</span></a><?php endif;?>
    </div>
  </section>
  <?php endif;?>
  <section class="student-home-section" aria-labelledby="student-home-courses-title">
    <div class="student-home-section-head"><h2 id="student-home-courses-title">Meus cursos</h2><a href="/aluno/cursos.php">Ver todos →</a></div>
    <?php if(!$courses):?><p class="student-home-muted">Nenhuma matrícula ativa. Seu Caderno e o Laboratório continuam disponíveis.</p>
    <?php else:?><div class="student-home-courses"><?php foreach(array_slice($courses,0,3) as $item):?>
      <article class="student-home-course">
        <a class="student-home-course-title" href="<?=h((string)$item['url'])?>"><strong><?=h((string)$item['title'])?></strong><span aria-hidden="true">→</span></a>
        <p><?=h((string)$item['cohort'])?></p>
        <div class="student-home-course-actions">
          <?php if($item['material']):?><a href="<?=h((string)$item['material']['url'])?>">Abrir material</a><?php else:?><span>Material aguardando liberação</span><?php endif;?>
          <a href="<?=h((string)$item['questions_url'])?>">Dúvidas</a>
        </div>
      </article>
    <?php endforeach;?></div><?php endif;?>
  </section>
  <section class="student-home-section" aria-labelledby="student-home-records-title">
    <div class="student-home-section-head"><h2 id="student-home-records-title">Caderno recente</h2><a href="/aluno/caderno.php">Ver caderno →</a></div>
    <?php if(!$recent):?><p class="student-home-muted">Nenhum registro ainda.</p>
    <?php else:?><div class="student-home-list"><?php foreach($recent as $record):?><a class="student-home-row" href="<?=h((string)$record['url'])?>"><span><small><?=h((string)$record['scope'])?> · <?=h((string)$record['date'])?></small><strong><?=h((string)$record['title'])?></strong></span><span aria-hidden="true">→</span></a><?php endforeach;?></div><?php endif;?>
  </section>
</div>
