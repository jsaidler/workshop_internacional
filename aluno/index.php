<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_enrollment_reconcile_confirmed($db);$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$enrollments=student_account_enrollments($db,(int)$student['id']);
$requestedCohortUuid=is_string($_GET['cohort']??null)?trim((string)$_GET['cohort']):'';
$selectedEnrollment=student_enrollment_dashboard_context($enrollments,$requestedCohortUuid);$selectedActivity=null;
if($selectedEnrollment){$selectedActivity=activity_by_id($db,(int)$selectedEnrollment['activity_id']);if(!$selectedActivity)$selectedEnrollment=null;}
student_shell_start('Área do aluno',$selectedActivity,$student);?>
<?php if(!$enrollments):?>
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title">Cursos e prática</h1>
  <p class="student-lead">Acesse o material liberado para sua turma e registre as experimentações enquanto fotografa e revela.</p>
  <div class="student-empty">Não há matrícula ativa vinculada a esta conta.</div>
<?php elseif(!$selectedEnrollment):?>
  <p class="student-kicker">Área do aluno</p>
  <h1 class="student-title">Seus cursos</h1>
  <p class="student-lead">Escolha o curso ou a turma que deseja abrir. Cada matrícula mantém seu próprio material, aulas e contexto.</p>
  <section class="student-section" aria-label="Escolher curso ou turma">
    <div class="student-test-list">
      <?php foreach($enrollments as $enrollment):$activity=activity_by_id($db,(int)$enrollment['activity_id']);if(!$activity)continue;$statusLabel=student_enrollment_cohort_status_label((string)$enrollment['cohort_status']);$url='/aluno/?cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?>
        <a class="student-test-row" href="<?=h($url)?>">
          <div><span class="student-status"><?=h($statusLabel)?></span><h3><?=h((string)$activity['public_title'])?></h3><p><?=h((string)$enrollment['cohort_title'])?></p></div>
          <div class="student-test-meta"><span>Abrir curso →</span></div>
        </a>
      <?php endforeach;?>
    </div>
  </section>
<?php else:
  $enrollment=$selectedEnrollment;$activity=$selectedActivity;
  $pages=student_enrollment_pages_for_enrollment($db,$enrollment);$releases=course_lesson_release_rows($db,(int)$enrollment['cohort_id'],(int)$enrollment['activity_id']);$releaseStates=[];$releasedCount=0;
  foreach($releases as $lesson){$state=cms_access_lesson_release_state(isset($lesson['released_at'])?(string)$lesson['released_at']:null);$releaseStates[(int)$lesson['id']]=$state;if($state==='released')$releasedCount++;}
  $statusLabel=student_enrollment_cohort_status_label((string)$enrollment['cohort_status']);?>
  <div class="student-appbar">
    <?php if(count($enrollments)>1):?><a class="student-back" href="/aluno/">← Todos os cursos</a><?php else:?><span class="student-card-label">Área do aluno</span><?php endif;?>
    <span class="student-status"><?=h($statusLabel)?></span>
  </div>
  <p class="student-kicker"><?=h((string)$enrollment['cohort_title'])?></p>
  <h1 class="student-title"><?=h((string)$activity['public_title'])?></h1>
  <p class="student-lead">Acesse o material disponível para esta turma e registre as experimentações enquanto fotografa e revela.</p>
  <section class="student-course-stack" aria-label="Curso selecionado">
    <article class="student-course-card">
      <header><div><span class="student-card-label">Workspace da turma</span><h2>Material e prática</h2></div><span class="student-course-progress"><?=$releases?$releasedCount.'/'.count($releases).' aulas liberadas':'Sem aulas cadastradas'?></span></header>
      <?php if($releases):?><div class="student-release-list" aria-label="Liberação das aulas"><?php foreach($releases as $lesson):$state=$releaseStates[(int)$lesson['id']]??'blocked';$label=match($state){'released'=>'liberada','scheduled'=>'agendada',default=>'aguardando'};$stateClass=match($state){'released'=>'is-released','scheduled'=>'is-scheduled',default=>''};?><span class="<?=h($stateClass)?>"><b><?=h((string)$lesson['title'])?></b><small><?=h($label)?></small></span><?php endforeach;?></div><?php endif;?>
      <div class="student-course-actions">
        <div><span class="student-card-label">Material</span><?php if(!$pages):?><p>Nenhuma página protegida foi publicada para este curso ainda.</p><?php else:?><?php foreach($pages as $page):$url=cms_page_url($activity,$page,(string)$page['locale']);$sep=str_contains($url,'?')?'&':'?';$url.=$sep.'cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?><a class="student-course-primary" href="<?=h($url)?>"><?=h((string)$page['title'])?> →</a><?php endforeach;?><?php endif;?></div>
        <div><span class="student-card-label">Prática</span><p>Registre exposição, cena, revelação e resultado no telefone.</p><a class="student-course-primary" href="/aluno/testes.php?cohort=<?=h(rawurlencode((string)$enrollment['cohort_uuid']))?>">Abrir meus testes →</a></div>
      </div>
    </article>
  </section>
<?php endif;?>
<?php student_shell_end();
