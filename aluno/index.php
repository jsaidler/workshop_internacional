<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_enrollment_reconcile_confirmed($db);$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$enrollments=student_account_enrollments($db,(int)$student['id']);
student_shell_start('Área do aluno',null,$student);?>
<p class="student-kicker">Área do aluno</p>
<h1 class="student-title">Cursos e prática</h1>
<p class="student-lead">Acesse o material liberado para sua turma e registre as experimentações enquanto fotografa e revela.</p>
<?php if(!$enrollments):?>
  <div class="student-empty">Não há matrícula ativa vinculada a esta conta.</div>
<?php else:?><section class="student-course-stack" aria-label="Cursos e materiais">
  <?php foreach($enrollments as $enrollment):$activity=activity_by_id($db,(int)$enrollment['activity_id']);if(!$activity)continue;$pages=student_enrollment_pages_for_enrollment($db,$enrollment);$releases=course_lesson_release_rows($db,(int)$enrollment['cohort_id'],(int)$enrollment['activity_id']);$releaseStates=[];$releasedCount=0;foreach($releases as $lesson){$state=cms_access_lesson_release_state(isset($lesson['released_at'])?(string)$lesson['released_at']:null);$releaseStates[(int)$lesson['id']]=$state;if($state==='released')$releasedCount++;}?>
    <article class="student-course-card">
      <header><div><span class="student-card-label"><?=h((string)$enrollment['cohort_title'])?></span><h2><?=h((string)$activity['public_title'])?></h2></div><span class="student-course-progress"><?=$releasedCount?>/<?=count($releases)?> aulas liberadas</span></header>
      <?php if($releases):?><div class="student-release-list" aria-label="Liberação das aulas"><?php foreach($releases as $lesson):$state=$releaseStates[(int)$lesson['id']]??'blocked';$label=match($state){'released'=>'liberada','scheduled'=>'agendada',default=>'aguardando'};$stateClass=match($state){'released'=>' is-released','scheduled'=>' is-scheduled',default=>''};?><span class="<?=h(trim($stateClass))?>"><b><?=h((string)$lesson['title'])?></b><small><?=h($label)?></small></span><?php endforeach;?></div><?php endif;?>
      <div class="student-course-actions">
        <div><span class="student-card-label">Material</span><?php if(!$pages):?><p>Nenhuma página protegida foi publicada para este curso ainda.</p><?php else:?><?php foreach($pages as $page):$url=cms_page_url($activity,$page,(string)$page['locale']);$sep=str_contains($url,'?')?'&':'?';$url.=$sep.'cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?><a class="student-course-primary" href="<?=h($url)?>"><?=h((string)$page['title'])?> →</a><?php endforeach;?><?php endif;?></div>
        <div><span class="student-card-label">Prática</span><p>Registre exposição, cena, revelação e resultado no telefone.</p><a class="student-course-primary" href="/aluno/testes.php">Abrir meus testes →</a></div>
      </div>
    </article>
  <?php endforeach;?>
</section><?php endif;?>
<?php student_shell_end();
