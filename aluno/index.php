<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_enrollment_reconcile_confirmed($db);$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$enrollments=student_enrollment_list($db,(int)$student['id']);
$requestedCohortUuid=is_string($_GET['cohort']??null)?trim((string)$_GET['cohort']):'';
$selectedEnrollment=student_enrollment_dashboard_context($enrollments,$requestedCohortUuid);$selectedActivity=null;
if($selectedEnrollment){$selectedActivity=activity_by_id($db,(int)$selectedEnrollment['activity_id']);if(!$selectedActivity)$selectedEnrollment=null;}
student_shell_start('Área do aluno',$selectedActivity,$student);?>
<?php if(!$enrollments):?>
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title">Meus cursos</h1>
  <p class="student-lead">Quando uma matrícula for confirmada, o curso aparecerá aqui.</p>
  <div class="student-empty">Não há matrícula ativa vinculada a esta conta.</div>
<?php elseif(!$selectedEnrollment):?>
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title">Meus cursos</h1>
  <p class="student-lead">Escolha o curso e a turma em que você quer trabalhar. O contexto escolhido acompanha material e testes.</p>
  <section class="student-section" aria-label="Escolher curso ou turma"><div class="student-test-list">
    <?php foreach($enrollments as $enrollment):$statusLabel=student_enrollment_cohort_status_label((string)$enrollment['cohort_status']);$url='/aluno/?cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?>
      <a class="student-test-row" href="<?=h($url)?>"><div><span class="student-status"><?=h($statusLabel)?></span><h3><?=h((string)$enrollment['course_title'])?></h3><p><?=h((string)$enrollment['cohort_title'])?></p></div><div class="student-test-meta"><span>Abrir curso →</span></div></a>
    <?php endforeach;?>
  </div></section>
<?php else:
  $enrollment=$selectedEnrollment;$activity=$selectedActivity;$courseId=(int)($enrollment['course_id']??0);$workshopId=(int)($enrollment['workshop_page_id']??0);
  $pages=student_enrollment_pages_for_enrollment($db,$enrollment);
  if($courseId>0)$releases=course_lesson_release_rows_for_course($db,(int)$enrollment['cohort_id'],$courseId);
  elseif($workshopId>0)$releases=workshop_course_lesson_release_rows($db,(int)$enrollment['cohort_id'],$workshopId);
  else $releases=course_lesson_release_rows($db,(int)$enrollment['cohort_id'],(int)$enrollment['activity_id']);
  $releaseStates=[];$releasedCount=0;foreach($releases as $lesson){$state=cms_access_lesson_release_state(isset($lesson['released_at'])?(string)$lesson['released_at']:null);$releaseStates[(int)$lesson['id']]=$state;if($state==='released')$releasedCount++;}
  $allTests=student_tests_for_student($db,(int)$student['id']);$tests=student_enrollment_owned_tests_context($allTests,$enrollment);$showBack=count($enrollments)>1;
  student_course_context_header($enrollment,'overview',$showBack);?>
  <div class="student-overview-grid" aria-label="Visão geral do curso">
    <section class="student-overview-card student-overview-card-primary">
      <p class="student-card-label">Aulas</p><h2>Seu andamento</h2>
      <?php if(!$releases):?><p>Nenhuma aula foi cadastrada para este curso ainda.</p><?php else:?><p><?=$releasedCount?> de <?=count($releases)?> aulas liberadas para <?=h((string)$enrollment['cohort_title'])?>.</p><div class="student-lesson-list"><?php foreach($releases as $lesson):$state=$releaseStates[(int)$lesson['id']]??'blocked';$label=match($state){'released'=>'Disponível','scheduled'=>'Agendada',default=>'Aguardando'};?><div class="student-lesson-row<?=$state==='released'?' is-released':''?>"><strong><?=h((string)$lesson['title'])?></strong><span class="student-lesson-state"><?=h($label)?></span></div><?php endforeach;?></div><?php endif;?>
    </section>
    <section class="student-overview-card" id="material"><p class="student-card-label">Material</p><h2>Conteúdo do curso</h2><?php if(!$pages):?><p>Nenhuma página de material foi associada a este curso ainda.</p><?php else:?><p><?=student_quantity_label(count($pages),'página disponível','páginas disponíveis')?> para esta matrícula.</p><a class="button button-primary" href="/aluno/material.php?cohort=<?=h(rawurlencode((string)$enrollment['cohort_uuid']))?>">Abrir material →</a><?php endif;?></section>
    <section class="student-overview-card"><p class="student-card-label">Testes</p><h2>Seus registros</h2><p><?=student_quantity_label(count($tests),'teste registrado','testes registrados')?> nesta turma.</p><a class="button button-primary" href="/aluno/testes.php?cohort=<?=h(rawurlencode((string)$enrollment['cohort_uuid']))?>">Abrir testes →</a></section>
  </div>
<?php endif;?>
<?php student_shell_end();
