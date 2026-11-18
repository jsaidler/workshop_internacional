<?php
declare(strict_types=1);
/** Visual projection only; enrollments, CMS access and feedback remain route-authorized. */
?>
<div class="student-v2-courses" data-student-courses>
<?php if(!$enrollments):?>
  <header class="student-v2-courses-heading">
    <p class="student-v2-eyebrow">Área de estudo</p>
    <h1 class="student-title">Seus cursos</h1>
    <p>Os materiais, as aulas e as conversas de cada turma ficam aqui.</p>
  </header>
  <section class="student-v2-course-empty" data-courses-empty>
    <span aria-hidden="true"><?=student_shell_nav_icon('courses')?></span>
    <h2>Nenhuma matrícula ativa</h2>
    <p>Quando uma matrícula estiver ativa, o material e as aulas aparecem aqui.</p>
  </section>
<?php elseif(!$selectedEnrollment):?>
  <header class="student-v2-courses-heading">
    <p class="student-v2-eyebrow">Área de estudo</p>
    <h1 class="student-title">Seus cursos</h1>
    <p>Selecione a turma que deseja consultar.</p>
  </header>
  <nav class="student-v2-course-selector student-academic-choice-list" aria-label="Matrículas disponíveis" data-courses-selection>
    <?php foreach($enrollments as $enrollment):$url='/aluno/cursos.php?cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?>
      <a class="student-v2-course-choice student-academic-choice" href="<?=h($url)?>">
        <span class="student-v2-course-choice-status"><?=h(student_enrollment_cohort_status_label((string)$enrollment['cohort_status']))?></span>
        <strong><?=h((string)$enrollment['course_title'])?></strong>
        <span class="student-v2-course-choice-cohort"><?=h((string)$enrollment['cohort_title'])?></span>
        <span class="student-v2-course-choice-arrow" aria-hidden="true">↗</span>
      </a>
    <?php endforeach;?>
  </nav>
<?php else:
  $enrollment=$selectedEnrollment;
  $courseName=(string)$enrollment['course_title'];
  $cohortName=(string)$enrollment['cohort_title'];
  $cohortUuid=(string)$enrollment['cohort_uuid'];
  $questionsUrl='/aluno/duvidas.php?cohort='.rawurlencode($cohortUuid);
  $latestFeedback=$attention['feedback'][0]??null;
  // Identify the first page the student can act on. Never promote scheduled or blocked material.
  $nextReadIndex=null;
  foreach($materialItems as $readIndex=>$readItem){
    if(in_array((string)($readItem['summary']['state']??''),['available','partial'],true)){
      $nextReadIndex=$readIndex;
      break;
    }
  }
?>
  <header class="student-v2-course-detail-heading">
    <?php if(count($enrollments)>1):?><a class="student-v2-course-back" href="/aluno/cursos.php">← Todos os cursos</a><?php endif;?>
    <p class="student-v2-eyebrow">Seu curso</p>
    <h1 class="student-title"><?=h($courseName)?></h1>
    <div class="student-v2-course-identity">
      <span><?=h($cohortName)?></span>
      <span class="student-v2-course-state"><?=h(student_enrollment_cohort_status_label((string)$enrollment['cohort_status']))?></span>
    </div>
  </header>
  <nav class="student-v2-course-local-nav" aria-label="Dentro deste curso">
    <a href="#student-course-material" aria-current="location"><?=student_shell_nav_icon('courses')?> Material</a>
    <a href="<?=h($questionsUrl)?>" aria-label="Dúvidas e respostas"><?=student_shell_nav_icon('notebook')?> Dúvidas <span aria-hidden="true">↗</span></a>
  </nav>
  <?php if(!empty($attention['has_attention'])):?>
    <section class="student-v2-course-attention" aria-labelledby="student-course-attention" data-course-attention>
      <div class="student-v2-course-attention-head"><span>Precisa de atenção</span><h2 id="student-course-attention">Há algo para retomar</h2></div>
      <?php if($latestFeedback):$state=student_feedback_state($latestFeedback);?>
        <a href="/aluno/teste.php?id=<?=(int)$latestFeedback['id']?>&amp;view=review">
          <span><small><?=h((string)$state['label'])?></small><strong><?=h((string)$latestFeedback['title'])?></strong></span>
          <span aria-hidden="true">↗</span>
        </a>
      <?php endif;?>
      <?php if((int)$attention['question_count']>0):?>
        <a href="<?=h($questionsUrl)?>">
          <span><small><?=h(student_experience_count((int)$attention['question_count'],'dúvida aberta','dúvidas abertas'))?></small><strong>Continuar conversa</strong></span>
          <span aria-hidden="true">↗</span>
        </a>
      <?php endif;?>
    </section>
  <?php endif;?>
  <section class="student-v2-course-material" aria-labelledby="student-course-material" data-course-material>
    <div class="student-v2-course-section-heading"><div><span class="student-v2-eyebrow">Estudar</span><h2 id="student-course-material">Material</h2></div><span><?=count($materialItems)?> <?=count($materialItems)===1?'página':'páginas'?></span></div>
    <?php if(!$materialItems):?>
      <div class="student-v2-course-material-empty" data-course-material-empty>
        <p>Ainda não há material publicado para esta matrícula.</p>
      </div>
    <?php else:?>
      <div class="student-v2-course-pages student-academic-material-list">
        <?php foreach($materialItems as $index=>$item):
          $page=$item['page'];$summary=$item['summary'];$state=(string)$summary['state'];
          $isNext=$nextReadIndex!==null && $index===$nextReadIndex;
          $detail=match($state){
            'partial'=>'Parte desta página já está disponível.',
            'scheduled'=>'O conteúdo desta página está vinculado a uma aula agendada.',
            'blocked'=>'O conteúdo desta página aguarda liberação.',
            default=>'',
          };
        ?>
          <a class="student-v2-course-page student-academic-material-row <?=$state==='scheduled'||$state==='blocked'?'is-waiting':''?><?=$isNext?' is-next':''?>" href="<?=h((string)$item['url'])?>">
            <span class="student-v2-course-page-number" aria-hidden="true"><?=str_pad((string)($index+1),2,'0',STR_PAD_LEFT)?></span>
            <span class="student-v2-course-page-copy">
              <strong class="student-academic-material-title"><?=h((string)$page['title'])?></strong>
              <?php if($detail!==''):?><small class="student-academic-material-detail"><?=h($detail)?></small><?php endif;?>
              <span class="student-academic-material-state" data-state="<?=h($state)?>"><?=h((string)$summary['label'])?></span>
              <?php if($isNext):?><small class="student-v2-course-page-action">Abrir material →</small><?php endif;?>
            </span>
            <span class="student-v2-course-page-arrow" aria-hidden="true">›</span>
          </a>
        <?php endforeach;?>
      </div>
    <?php endif;?>
  </section>
  <?php if($pendingLessons):?>
    <details class="student-v2-course-releases student-academic-release-summary" data-course-releases>
      <summary>Próximas liberações <span><?=count($pendingLessons)?></span></summary>
      <div class="student-v2-course-release-list student-academic-release-list">
        <?php foreach($pendingLessons as [$lesson,$state]):$when=$state==='scheduled'?student_course_release_when((string)($lesson['released_at']??'')):'Aguardando liberação';?>
          <div class="student-v2-course-release-row student-academic-release-line"><span><?=h((string)$lesson['title'])?></span><small><?=h($when)?></small></div>
        <?php endforeach;?>
      </div>
    </details>
  <?php elseif(!$releases):?>
    <p class="student-v2-course-no-lessons">As aulas aparecerão conforme forem cadastradas.</p>
  <?php endif;?>
<?php endif;?>
</div>
