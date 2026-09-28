<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next='.rawurlencode('/aluno/material.php'),true,303);exit;}
$enrollments=student_enrollment_list($db,(int)$student['id']);
$requestedCohortUuid=is_string($_GET['cohort']??null)?trim((string)$_GET['cohort']):'';
$selectedEnrollment=student_enrollment_dashboard_context($enrollments,$requestedCohortUuid);
if(!$selectedEnrollment&&$enrollments){header('Location: /aluno/',true,303);exit;}
$activity=$selectedEnrollment?activity_by_id($db,(int)$selectedEnrollment['activity_id']):null;
student_shell_start('Material · Área do aluno',$activity?:null,$student);?>
<?php if(!$enrollments):?>
  <p class="student-kicker">Área do aluno</p><h1 class="student-title">Material</h1><p class="student-lead">O material aparece quando existe uma matrícula ativa.</p><div class="student-empty">Não há matrícula ativa vinculada a esta conta.</div>
<?php elseif($selectedEnrollment):$enrollment=$selectedEnrollment;$pages=student_enrollment_pages_for_enrollment($db,$enrollment);$courseId=(int)($enrollment['course_id']??0);$workshopId=(int)($enrollment['workshop_page_id']??0);
  if($courseId>0)$releases=course_lesson_release_rows_for_course($db,(int)$enrollment['cohort_id'],$courseId);
  elseif($workshopId>0)$releases=workshop_course_lesson_release_rows($db,(int)$enrollment['cohort_id'],$workshopId);
  else $releases=course_lesson_release_rows($db,(int)$enrollment['cohort_id'],(int)$enrollment['activity_id']);
  $showBack=count($enrollments)>1;student_course_context_header($enrollment,'material',$showBack);?>
  <section class="student-section"><div class="student-section-heading"><div><p class="student-kicker">Material</p><h2 class="student-subtitle">Conteúdo disponível</h2></div><p>As páginas continuam sendo páginas do site. Aqui você encontra somente o material associado ao seu curso e acessível pela sua matrícula.</p></div>
  <?php if(!$pages):?><div class="student-empty">Nenhuma página de material foi associada a este curso ainda.</div><?php else:?><div class="student-material-list"><?php foreach($pages as $page):$url=cms_page_url($activity,$page,(string)$page['locale']);$sep=str_contains($url,'?')?'&':'?';$url.=$sep.'cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?><a class="student-material-link" href="<?=h($url)?>"><strong><?=h((string)$page['title'])?></strong><span>Abrir página →</span></a><?php endforeach;?></div><?php endif;?></section>
  <section class="student-section"><div class="student-section-heading"><div><p class="student-kicker">Aulas</p><h2 class="student-subtitle">Liberação</h2></div><p>Quando uma aula é liberada, as seções de material associadas a ela passam a aparecer automaticamente.</p></div>
  <?php if(!$releases):?><div class="student-empty">Nenhuma aula cadastrada.</div><?php else:?><div class="student-lesson-list"><?php foreach($releases as $lesson):$state=cms_access_lesson_release_state(isset($lesson['released_at'])?(string)$lesson['released_at']:null);$label=match($state){'released'=>'Disponível','scheduled'=>'Agendada',default=>'Aguardando'};?><div class="student-lesson-row<?=$state==='released'?' is-released':''?>"><strong><?=h((string)$lesson['title'])?></strong><span class="student-lesson-state"><?=h($label)?></span></div><?php endforeach;?></div><?php endif;?></section>
<?php endif;?>
<?php student_shell_end();
