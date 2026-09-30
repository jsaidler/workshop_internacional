<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_enrollment_reconcile_confirmed($db);$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$enrollments=student_enrollment_list($db,(int)$student['id']);
$requestedCohortUuid=is_string($_GET['cohort']??null)?trim((string)$_GET['cohort']):'';
$selectedEnrollment=student_enrollment_dashboard_context($enrollments,$requestedCohortUuid);$selectedActivity=null;
if($selectedEnrollment){$selectedActivity=activity_by_id($db,(int)$selectedEnrollment['activity_id']);if(!$selectedActivity)$selectedEnrollment=null;}
student_shell_start('Meus cursos',$selectedActivity,$student);?>
<?php if(!$enrollments):?>
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title">Meus cursos</h1>
  <p class="student-lead">Seus materiais e aulas aparecem aqui quando uma matrícula estiver ativa.</p>
  <div class="student-empty">Não há matrícula ativa vinculada a esta conta.</div>
<?php elseif(!$selectedEnrollment):?>
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title">Meus cursos</h1>
  <p class="student-lead">Escolha o curso e a turma que deseja abrir.</p>
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
  $materialUrls=[];foreach($pages as $page){$url=cms_page_url($activity,$page,(string)$page['locale']);$sep=str_contains($url,'?')?'&':'?';$url.=$sep.'cohort='.rawurlencode((string)$enrollment['cohort_uuid']);$materialUrls[(int)$page['id']]=$url;}
  student_course_context_header($enrollment,'overview',$pages?($materialUrls[(int)$pages[0]['id']]??''):'');?>

  <section class="student-section" aria-labelledby="student-course-overview">
    <div class="student-section-heading"><div><p class="student-kicker">Seu curso</p><h2 class="student-subtitle" id="student-course-overview">Visão geral</h2></div><p>Veja o que já está disponível e continue de onde parou.</p></div>

    <?php if($releases):?>
      <div class="student-release-list" aria-label="Aulas do curso">
        <?php foreach($releases as $lesson):$state=$releaseStates[(int)$lesson['id']]??'blocked';$label=match($state){'released'=>'Disponível','scheduled'=>'Agendada',default=>'Aguardando'};$stateClass=match($state){'released'=>'is-released','scheduled'=>'is-scheduled',default=>''};?>
          <span class="<?=h($stateClass)?>"><b><?=h((string)$lesson['title'])?></b><small><?=h($label)?></small></span>
        <?php endforeach;?>
      </div>
    <?php else:?><div class="student-empty">As aulas deste curso ainda não foram cadastradas.</div><?php endif;?>
  </section>

  <section class="student-course-stack" aria-label="Acessos do curso"><article class="student-course-card">
    <header><div><span class="student-card-label">Progresso</span><h2><?=$releases?$releasedCount.'/'.count($releases).' aulas disponíveis':'Curso ativo'?></h2></div></header>
    <div class="student-course-actions">
      <div><span class="student-card-label">Material</span><?php if(!$pages):?><p>Nenhuma página de material foi publicada para este curso ainda.</p><?php else:?><p>Acesse o conteúdo liberado para sua turma.</p><?php foreach($pages as $page):?><a class="button button-primary student-course-primary" href="<?=h($materialUrls[(int)$page['id']]??'#')?>"><?=h((string)$page['title'])?> →</a><?php endforeach;?><?php endif;?></div>
      <div><span class="student-card-label">Dúvidas</span><p>Converse sobre o conteúdo e os processos desta turma.</p><a class="button button-primary student-course-primary" href="/aluno/duvidas.php?cohort=<?=h(rawurlencode((string)$enrollment['cohort_uuid']))?>">Abrir dúvidas →</a></div>
    </div>
  </article></section>
<?php endif;?>
<?php student_shell_end();
