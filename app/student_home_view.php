<?php
declare(strict_types=1);
/** Same authenticated data projection as before; visual hierarchy rebuilt for mobile. */
$meeting=$home['meeting']??null;
$feedback=$home['feedback']??null;
$focus=$home['focus']??null;
$courses=$home['courses']??[];
$recent=$home['recent']??[];
$firstMaterial=null;
foreach($courses as $item){if(($item['material']??null)!==null){$firstMaterial=$item['material'];break;}}
$hero=$meeting?'meeting':($feedback?'feedback':($focus?'focus':($firstMaterial?'study':'empty')));
$continueMaterial=$firstMaterial && $hero!=='study';
$continueFocus=$focus && $hero!=='focus';
?>
<div class="student-v2-home" data-student-home>
  <header class="student-v2-heading">
    <p class="student-v2-eyebrow">Área do aluno</p>
    <h1>Bom ter você aqui.</h1>
    <p>Seu próximo passo, sem precisar procurar.</p>
  </header>
  <?php if($meeting):?>
    <section class="student-v2-next" aria-labelledby="student-home-feature-title" data-home-meeting>
      <div class="student-v2-feature-top"><span><?=!empty($meeting['in_window'])?'Horário previsto do encontro':'Próximo encontro'?></span><span class="student-v2-feature-state">Agendado</span></div>
      <div class="student-v2-date-line">
        <time datetime="<?=h((string)$meeting['date_iso'])?>"><strong><?=h((string)$meeting['day'])?></strong><span><?=h((string)$meeting['weekday'])?><br><?=h((string)$meeting['month'])?></span></time>
        <strong class="student-v2-hour"><?=h((string)$meeting['hour'])?></strong>
      </div>
      <h2 id="student-home-feature-title"><?=h((string)$meeting['lesson'])?></h2>
      <p class="student-v2-next-context"><?=h((string)$meeting['course'])?> · <?=h((string)$meeting['cohort'])?></p>
      <div class="student-v2-actions">
        <?php if((string)$meeting['url']!==''):?><a class="student-v2-cta" href="<?=h((string)$meeting['url'])?>" target="_blank" rel="noopener noreferrer">Acessar encontro ↗</a><?php else:?><span class="student-v2-unavailable">Link ainda não informado</span><?php endif;?>
        <a class="student-v2-secondary-link" href="/aluno/agenda.php">Ver agenda →</a>
      </div>
    </section>
  <?php elseif($feedback || $focus || $firstMaterial):?>
    <?php $primary=$feedback??$focus; ?>
    <section class="student-v2-next student-v2-next-simple" aria-labelledby="student-home-feature-title" <?=$feedback?'data-home-feedback-feature':($focus?'data-home-record-feature':'data-home-study-feature')?>>
      <div class="student-v2-feature-top"><span><?=$primary?'Continuar sua pesquisa':'Material disponível'?></span></div>
      <h2 id="student-home-feature-title"><?=h((string)($primary['title']??$firstMaterial['title']))?></h2>
      <p class="student-v2-next-context"><?=h((string)($primary['status']??'Disponível para estudar'))?></p>
      <a class="student-v2-cta" href="<?=h((string)($primary['url']??$firstMaterial['url']))?>"><?=h((string)($primary['action']??'Abrir material'))?> →</a>
    </section>
  <?php else:?>
    <section class="student-v2-next student-v2-next-simple" aria-labelledby="student-home-feature-title" data-home-empty-feature>
      <div class="student-v2-feature-top"><span>Seu espaço de pesquisa</span></div>
      <h2 id="student-home-feature-title">Comece pelo Caderno</h2>
      <p class="student-v2-next-context">Registre uma exposição, um processamento ou um resultado.</p>
      <button class="student-v2-cta" type="button" data-record-create-open>Novo registro +</button>
    </section>
  <?php endif;?>
  <?php if($continueMaterial || $continueFocus):?>
    <section class="student-v2-section" aria-labelledby="student-v2-continue-title">
      <h2 id="student-v2-continue-title">Continuar</h2>
      <div class="student-v2-list">
      <?php if($continueMaterial):?><a class="student-v2-row" href="<?=h((string)$firstMaterial['url'])?>">
        <span class="student-v2-row-icon" aria-hidden="true"><?=student_shell_nav_icon('courses')?></span>
        <span class="student-v2-row-text"><small>Material disponível</small><strong><?=h((string)$firstMaterial['title'])?></strong><?php if(count($courses)>1):?><em><?=h((string)($firstMaterial['course']??''))?> · <?=h((string)($firstMaterial['cohort']??''))?></em><?php endif;?></span><span aria-hidden="true">›</span>
      </a><?php endif;?>
      <?php if($continueFocus):?><a class="student-v2-row" href="<?=h((string)$focus['url'])?>">
        <span class="student-v2-row-icon" aria-hidden="true"><?=student_shell_nav_icon('notebook')?></span>
        <span class="student-v2-row-text"><small>Caderno · <?=h((string)$focus['status'])?></small><strong><?=h((string)$focus['title'])?></strong></span><span aria-hidden="true">›</span>
      </a><?php endif;?>
      </div>
    </section>
  <?php endif;?>
  <?php if($feedback && $meeting):?><section class="student-v2-section" aria-labelledby="student-v2-feedback-title" data-home-feedback>
    <h2 id="student-v2-feedback-title">Retorno do professor</h2>
    <a class="student-v2-feedback" href="<?=h((string)$feedback['url'])?>"><span><small><?=h((string)$feedback['status'])?></small><strong><?=h((string)$feedback['title'])?></strong></span><span aria-hidden="true">›</span></a>
  </section><?php endif;?>
  <?php if($hero!=='empty'||!empty($home['can_timer'])||!empty($home['can_reciprocity'])):?>
  <section class="student-v2-section student-v2-shortcuts" aria-labelledby="student-v2-shortcuts-title">
    <h2 id="student-v2-shortcuts-title">Acesso direto</h2>
    <div class="student-v2-footer-actions">
      <?php if($hero!=='empty'):?><button class="student-v2-tool-tile" type="button" data-record-create-open>
        <?=student_shell_nav_icon('notebook')?>
        <strong>Novo registro</strong><small>Exposição, processo ou resultado</small>
      </button><?php endif;?>
      <?php if(!empty($home['can_timer'])):?><a class="student-v2-tool-tile" href="/aluno/processamentos.php">
        <?=student_shell_nav_icon('laboratory')?>
        <strong>Processamentos</strong><small>Roteiros e temporização</small>
      </a><?php endif;?>
      <?php if(!empty($home['can_reciprocity'])):?><a class="student-v2-tool-tile" href="/aluno/reciprocidade.php">
        <?=student_shell_nav_icon('laboratory')?>
        <strong>Reciprocidade</strong><small>Compensação de exposição</small>
      </a><?php endif;?>
    </div>
  </section>
  <?php endif;?>
  <section class="student-v2-section" aria-labelledby="student-v2-courses-title">
    <div class="student-v2-section-heading"><h2 id="student-v2-courses-title">Meus cursos</h2><a href="/aluno/cursos.php">Ver todos →</a></div>
    <?php if(!$courses):?><p class="student-v2-muted">Nenhuma matrícula ativa. O Caderno pessoal permanece disponível.</p>
    <?php else:?><div class="student-v2-course-list"><?php foreach(array_slice($courses,0,3) as $item):?><article class="student-v2-course">
      <a class="student-v2-course-title" href="<?=h((string)$item['url'])?>"><strong><?=h((string)$item['title'])?></strong><span aria-hidden="true">›</span></a>
      <p><?=h((string)$item['cohort'])?></p>
      <div class="student-home-course-actions"><?php if($item['material']):?><a href="<?=h((string)$item['material']['url'])?>">Abrir material</a><?php else:?><span>Material aguardando liberação</span><?php endif;?><a href="<?=h((string)$item['questions_url'])?>">Dúvidas</a></div>
    </article><?php endforeach;?></div><?php endif;?>
  </section>
  <section class="student-v2-section" aria-labelledby="student-v2-notebook-title">
    <div class="student-v2-section-heading"><h2 id="student-v2-notebook-title">Caderno recente</h2><a href="/aluno/caderno.php">Ver caderno →</a></div>
    <?php if(!$recent):?><p class="student-v2-muted">Nenhum registro ainda.</p>
    <?php else:?><div class="student-v2-list"><?php foreach($recent as $record):?><a class="student-v2-row" href="<?=h((string)$record['url'])?>"><span class="student-v2-row-icon" aria-hidden="true"><?=student_shell_nav_icon('notebook')?></span><span class="student-v2-row-text"><small><?=h((string)$record['scope'])?> · <?=h((string)$record['date'])?></small><strong><?=h((string)$record['title'])?></strong></span><span aria-hidden="true">›</span></a><?php endforeach;?></div><?php endif;?>
  </section>

</div>