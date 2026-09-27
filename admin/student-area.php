<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$activity=$state['activity'];if(!$activity){header('Location: /admin/activities.php');exit;}$activityId=(int)$activity['id'];
$courseId=(int)($_GET['course']??0);if($courseId>0){$course=course_by_id($db,$courseId);if($course&&(int)$course['activity_id']===$activityId){header('Location: /admin/courses.php?activity='.$activityId.'&course='.$courseId.'&view=students',true,303);exit;}}
$courses=course_list($db,$activityId);
admin_shell_start('students','Área do aluno',$state);?>
<section class="overview-hero"><div><p class="admin-kicker">Alunos</p><h2>Escolha o curso</h2><p>A identidade do aluno é global, mas matrículas, turmas, aulas, material e testes são administrados no contexto do curso.</p></div></section>
<div class="overview-grid">
<?php foreach($courses as $course):$q=$db->prepare("SELECT COUNT(*) FROM course_enrollments e JOIN course_cohorts c ON c.id=e.cohort_id WHERE c.course_id=? AND e.status='active' AND c.status!='archived'");$q->execute([(int)$course['id']]);$count=(int)$q->fetchColumn();?><article class="overview-card"><p class="admin-kicker">Curso</p><h2><?=h((string)$course['title'])?></h2><p><?=$count?> matrícula<?=$count===1?'':'s'?> ativa<?=$count===1?'':'s'?>.</p><div class="admin-card-actions"><a class="admin-button" href="/admin/courses.php?activity=<?=$activityId?>&course=<?=(int)$course['id']?>&view=students">Abrir alunos</a><a class="admin-button secondary" href="/admin/registrations.php?activity=<?=$activityId?>&course=<?=(int)$course['id']?>">Inscrições</a></div></article><?php endforeach;?>
</div>
<?php if(!$courses):?><section class="admin-card"><div class="admin-empty">Nenhum curso configurado. <a href="/admin/courses.php?activity=<?=$activityId?>">Associe uma página a um curso.</a></div></section><?php endif;?>
<?php admin_shell_end();
