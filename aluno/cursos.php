<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_enrollment_reconcile_confirmed($db);$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fcursos.php',true,303);exit;}
$enrollments=student_enrollment_list($db,(int)$student['id']);$requestedCohortUuid=is_string($_GET['cohort']??null)?trim((string)$_GET['cohort']):'';
$selectedEnrollment=null;if($requestedCohortUuid!=='')$selectedEnrollment=student_enrollment_dashboard_context($enrollments,$requestedCohortUuid);elseif(count($enrollments)===1)$selectedEnrollment=$enrollments[0];
$selectedActivity=null;if($selectedEnrollment){$selectedActivity=activity_by_id($db,(int)$selectedEnrollment['activity_id']);if(!$selectedActivity)$selectedEnrollment=null;}
student_shell_start('Curso',$selectedActivity,$student);?>
<?php if(!$enrollments):?>
  <p class="student-kicker">Curso</p><h1 class="student-title">Nenhuma matrícula ativa</h1><p class="student-lead">Quando uma matrícula estiver ativa, o material e as aulas aparecem aqui.</p>
<?php elseif(!$selectedEnrollment):?>
  <p class="student-kicker">Cursos</p><h1 class="student-title">Escolha a matrícula</h1><p class="student-lead">Você só verá esta escolha porque há mais de um curso ou turma disponível.</p>
  <div class="student-course-list" style="margin-top:28px"><?php foreach($enrollments as $enrollment):$url='/aluno/cursos.php?cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?><a class="student-course-list-item" href="<?=h($url)?>"><div><span class="student-status"><?=h(student_enrollment_cohort_status_label((string)$enrollment['cohort_status']))?></span><h2><?=h((string)$enrollment['course_title'])?></h2><p><?=h((string)$enrollment['cohort_title'])?></p></div><span>→</span></a><?php endforeach;?></div>
<?php else:
  $enrollment=$selectedEnrollment;$activity=$selectedActivity;$courseId=(int)($enrollment['course_id']??0);$workshopId=(int)($enrollment['workshop_page_id']??0);$pages=student_enrollment_pages_for_enrollment($db,$enrollment);
  if($courseId>0)$releases=course_lesson_release_rows_for_course($db,(int)$enrollment['cohort_id'],$courseId);elseif($workshopId>0)$releases=workshop_course_lesson_release_rows($db,(int)$enrollment['cohort_id'],$workshopId);else $releases=course_lesson_release_rows($db,(int)$enrollment['cohort_id'],(int)$enrollment['activity_id']);
  $releaseStates=[];$releasedCount=0;foreach($releases as $lesson){$state=cms_access_lesson_release_state(isset($lesson['released_at'])?(string)$lesson['released_at']:null);$releaseStates[(int)$lesson['id']]=$state;if($state==='released')$releasedCount++;}
  $materialUrls=[];foreach($pages as $page){$url=cms_page_url($activity,$page,(string)$page['locale']);$sep=str_contains($url,'?')?'&':'?';$url.=$sep.'cohort='.rawurlencode((string)$enrollment['cohort_uuid']);$materialUrls[(int)$page['id']]=$url;}
?>
  <header class="student-course-hero"><p class="student-kicker">Curso</p><h1 class="student-title"><?=h((string)$enrollment['course_title'])?></h1><div class="student-course-meta"><span><?=h((string)$enrollment['cohort_title'])?></span><span class="student-status"><?=h(student_enrollment_cohort_status_label((string)$enrollment['cohort_status']))?></span><?php if(count($enrollments)>1):?><a href="/aluno/cursos.php">Trocar curso</a><?php endif;?></div></header>
  <div class="student-course-dashboard">
    <section class="student-course-block" aria-labelledby="student-course-material"><p class="student-kicker">Estudar</p><h2 id="student-course-material">Material</h2><?php if(!$pages):?><p class="student-empty">Ainda não há material publicado para esta matrícula.</p><?php else:?><div class="student-course-materials"><?php foreach($pages as $page):?><a href="<?=h($materialUrls[(int)$page['id']]??'#')?>"><span><strong><?=h((string)$page['title'])?></strong></span><span>→</span></a><?php endforeach;?></div><?php endif;?></section>
    <aside class="student-course-side"><section class="student-course-block"><p class="student-kicker">Aulas</p><h2>Liberadas</h2><?php if($releases):?><p><?=h($releasedCount.' de '.count($releases))?> disponíveis.</p><div class="student-release-list"><?php foreach($releases as $lesson):$state=$releaseStates[(int)$lesson['id']]??'blocked';$label=match($state){'released'=>'Liberada','scheduled'=>'Agendada',default=>'Aguardando'};$stateClass=match($state){'released'=>'is-released','scheduled'=>'is-scheduled',default=>''};?><span class="<?=h($stateClass)?>"><b><?=h((string)$lesson['title'])?></b><small><?=h($label)?></small></span><?php endforeach;?></div><?php else:?><p>As aulas aparecerão conforme forem cadastradas.</p><?php endif;?></section><a class="button button-secondary" href="/aluno/duvidas.php?cohort=<?=h(rawurlencode((string)$enrollment['cohort_uuid']))?>">Dúvidas e respostas</a></aside>
  </div>
<?php endif;?>
<?php student_shell_end();
