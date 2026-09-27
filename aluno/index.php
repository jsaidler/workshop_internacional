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
  <h1 class="student-title">Seus cursos</h1>
  <p class="student-lead">Quando uma matrícula for vinculada a esta conta, o curso e os materiais liberados aparecerão aqui.</p>
  <div class="student-empty">Não há matrícula ativa vinculada a esta conta.</div>
<?php elseif(!$selectedEnrollment):?>
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title">Seus cursos</h1>
  <p class="student-lead">Escolha a turma que deseja acessar. Cada matrícula mantém seu próprio material, aulas e registros.</p>
  <section class="student-section" aria-label="Escolher curso ou turma"><div class="student-test-list">
    <?php foreach($enrollments as $enrollment):$statusLabel=student_enrollment_cohort_status_label((string)$enrollment['cohort_status']);$url='/aluno/?cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?>
      <a class="student-test-row" href="<?=h($url)?>"><div><span class="student-status"><?=h($statusLabel)?></span><h3><?=h((string)$enrollment['course_title'])?></h3><p><?=h((string)$enrollment['cohort_title'])?></p></div><div class="student-test-meta"><span>Abrir →</span></div></a>
    <?php endforeach;?>
  </div></section>
<?php else:
  $enrollment=$selectedEnrollment;$activity=$selectedActivity;$courseId=(int)($enrollment['course_id']??0);$workshopId=(int)($enrollment['workshop_page_id']??0);
  $pages=student_enrollment_pages_for_enrollment($db,$enrollment);
  if($courseId>0)$releases=course_lesson_release_rows_for_course($db,(int)$enrollment['cohort_id'],$courseId);
  elseif($workshopId>0)$releases=workshop_course_lesson_release_rows($db,(int)$enrollment['cohort_id'],$workshopId);
  else $releases=course_lesson_release_rows($db,(int)$enrollment['cohort_id'],(int)$enrollment['activity_id']);
  $releaseStates=[];$releasedCount=0;foreach($releases as $lesson){$state=cms_access_lesson_release_state(isset($lesson['released_at'])?(string)$lesson['released_at']:null);$releaseStates[(int)$lesson['id']]=$state;if($state==='released')$releasedCount++;}$statusLabel=student_enrollment_cohort_status_label((string)$enrollment['cohort_status']);?>
  <div class="student-appbar"><?php if(count($enrollments)>1):?><a class="student-back" href="/aluno/">← Todos os cursos</a><?php else:?><span class="student-card-label">Área do aluno</span><?php endif;?><span class="student-status"><?=h($statusLabel)?></span></div>
  <p class="student-kicker"><?=h((string)$enrollment['cohort_title'])?></p>
  <h1 class="student-title"><?=h((string)$enrollment['course_title'])?></h1>
  <p class="student-lead">Veja o que já está liberado para a sua turma e acesse o material ou os registros de prática sem sair deste contexto.</p>
  <section class="student-course-stack" aria-label="Curso selecionado"><article class="student-course-card">
    <header><div><span class="student-card-label">Progresso da turma</span><h2>Conteúdo disponível</h2></div><span class="student-course-progress"><?=$releases?$releasedCount.' de '.count($releases).' aulas liberadas':'Sem aulas cadastradas'?></span></header>
    <?php if($releases):?><div class="student-release-list" aria-label="Liberação das aulas"><?php foreach($releases as $lesson):$state=$releaseStates[(int)$lesson['id']]??'blocked';$label=match($state){'released'=>'Liberada','scheduled'=>'Agendada',default=>'Aguardando'};$stateClass=match($state){'released'=>'is-released','scheduled'=>'is-scheduled',default=>''};?><span class="<?=h($stateClass)?>"><b><?=h((string)$lesson['title'])?></b><small><?=h($label)?></small></span><?php endforeach;?></div><?php endif;?>
    <div class="student-course-actions">
      <div><span class="student-card-label">Material</span><?php if(!$pages):?><p>Nenhuma página de material foi associada a este curso ainda.</p><?php else:?><p>Abra uma página para continuar pelo conteúdo já liberado.</p><div class="student-resource-list"><?php foreach($pages as $page):$url=cms_page_url($activity,$page,(string)$page['locale']);$sep=str_contains($url,'?')?'&':'?';$url.=$sep.'cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?><a class="student-resource-row" href="<?=h($url)?>"><span><?=h((string)$page['title'])?></span><strong>Abrir →</strong></a><?php endforeach;?></div><?php endif;?></div>
      <div><span class="student-card-label">Prática</span><p>Registre cena, exposição, revelação e resultado no mesmo teste.</p><a class="button button-primary student-course-primary" href="/aluno/testes.php?cohort=<?=h(rawurlencode((string)$enrollment['cohort_uuid']))?>">Abrir meus testes →</a></div>
    </div>
  </article></section>
<?php endif;?>
<?php student_shell_end();