<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$enrollments=student_account_enrollments($db,(int)$student['id']);
student_shell_start('Área do aluno',null,$student);?>
<p class="student-kicker">Área do aluno</p>
<h1 class="student-title">Seus cursos</h1>
<p class="student-lead">Os materiais aparecem conforme cada aula é liberada para a sua turma. Conteúdos ainda não liberados não são enviados ao navegador.</p>
<?php if(!$enrollments):?>
  <div class="student-empty">Não há matrícula ativa vinculada a esta conta.</div>
<?php else:?><section class="student-grid" aria-label="Cursos e materiais">
  <?php foreach($enrollments as $enrollment):$activity=activity_by_id($db,(int)$enrollment['activity_id']);if(!$activity)continue;$pages=student_account_pages_for_enrollment($db,$enrollment);$releases=course_lesson_release_rows($db,(int)$enrollment['cohort_id'],(int)$enrollment['activity_id']);?>
    <article class="student-card" style="display:block">
      <span class="student-card-label"><?=h((string)$enrollment['cohort_title'])?></span>
      <h2><?=h((string)$activity['public_title'])?></h2>
      <?php if($releases):?><p><?php foreach($releases as $i=>$lesson):?><span style="display:inline-block;margin-right:10px"><?=h((string)$lesson['title'])?>: <strong><?=$lesson['released_at']?'liberada':'aguardando'?></strong></span><?php endforeach;?></p><?php endif;?>
      <?php if(!$pages):?><p>Nenhuma página protegida foi publicada para este curso ainda.</p><?php else:?><div style="display:grid;gap:8px;margin-top:18px"><?php foreach($pages as $page):$url=cms_page_url($activity,$page,(string)$page['locale']);$sep=str_contains($url,'?')?'&':'?';$url.=$sep.'cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?><a class="student-link" href="<?=h($url)?>"><?=h((string)$page['title'])?> →</a><?php endforeach;?></div><?php endif;?>
    </article>
  <?php endforeach;?>
</section><?php endif;?>
<?php student_shell_end();
