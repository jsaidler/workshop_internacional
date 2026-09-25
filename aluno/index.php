<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$studentId=(int)$student['id'];$enrollments=student_account_enrollments($db,$studentId);$recentTests=array_slice(student_tests_for_student($db,$studentId),0,4);
student_shell_start('Área do aluno',null,$student);?>
<p class="student-kicker">Área do aluno</p>
<h1 class="student-title">Cursos e pesquisa</h1>
<p class="student-lead">Acesse o material liberado para a sua turma e registre cada experimentação no mesmo ambiente.</p>
<?php if(!$enrollments):?>
  <div class="student-empty">Não há matrícula ativa vinculada a esta conta.</div>
<?php else:?><div class="student-dashboard">
  <section class="student-dashboard-section" aria-label="Cursos e materiais">
    <div class="student-dashboard-heading"><h2>Seus cursos</h2></div>
    <div class="student-course-list">
    <?php foreach($enrollments as $enrollment):$activity=activity_by_id($db,(int)$enrollment['activity_id']);if(!$activity)continue;$pages=student_account_pages_for_enrollment($db,$enrollment);$releases=course_lesson_release_rows($db,(int)$enrollment['cohort_id'],(int)$enrollment['activity_id']);?>
      <article class="student-course-card">
        <div class="student-course-top"><span class="student-card-label"><?=h((string)$enrollment['cohort_title'])?></span><span class="student-status">Matrícula ativa</span></div>
        <h2><?=h((string)$activity['public_title'])?></h2>
        <?php if($releases):?><div class="student-course-lessons" aria-label="Liberação das aulas"><?php foreach($releases as $lesson):?><div class="student-lesson-state<?=$lesson['released_at']?' is-open':''?>"><span><?=h((string)$lesson['title'])?></span><strong><?=$lesson['released_at']?'Liberada':'Aguardando'?></strong></div><?php endforeach;?></div><?php endif;?>
        <div class="student-course-actions">
          <?php if(!$pages):?><span class="student-status">Material ainda não publicado</span><?php else:?><?php foreach($pages as $page):$url=cms_page_url($activity,$page,(string)$page['locale']);$sep=str_contains($url,'?')?'&':'?';$url.=$sep.'cohort='.rawurlencode((string)$enrollment['cohort_uuid']);?><a class="student-button" href="<?=h($url)?>"><?=h((string)$page['title'])?></a><?php endforeach;?><?php endif;?>
          <a class="student-button student-button-secondary" href="/aluno/testes.php?new=1&cohort=<?=(int)$enrollment['cohort_id']?>">Registrar teste</a>
        </div>
      </article>
    <?php endforeach;?>
    </div>
  </section>
  <aside class="student-dashboard-section">
    <article class="student-quick-card"><p class="student-kicker">Caderno de testes</p><h2>Registrar experimentação</h2><p>Anote a exposição ainda na cena, fotografe a referência e complete a revelação e os resultados depois.</p><a class="student-button" href="/aluno/testes.php?new=1">Novo teste</a></article>
    <?php if($recentTests):?><div class="student-dashboard-heading" style="margin-top:28px"><h2>Recentes</h2><a href="/aluno/testes.php">Ver todos</a></div><div class="student-recent-list"><?php foreach($recentTests as $test):?><a class="student-recent-item" href="/aluno/teste.php?id=<?=(int)$test['id']?>"><strong><?=h((string)$test['title'])?></strong><span><?=h(student_test_status_label((string)$test['status']))?> · <?=h((string)$test['cohort_title'])?></span></a><?php endforeach;?></div><?php endif;?>
  </aside>
</div><?php endif;?>
<?php student_shell_end();
