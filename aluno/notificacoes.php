<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_home.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);
if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fnotificacoes.php',true,303);exit;}
$studentId=(int)$student['id'];
$notices=student_home_live_notices($db,$studentId);
student_shell_start('Notificações',null,$student);
?>
<div class="student-home-live student-home-notifications">
  <header class="student-home-live-header"><h1 class="student-title">Notificações</h1><a href="/aluno/">← Início</a></header>
  <?php if(!$notices):?><p class="student-home-live-empty">Nenhuma atualização disponível.</p><?php else:?>
  <div class="student-home-notice-list">
  <?php foreach($notices as $notice):?>
    <a href="<?=h((string)$notice['href'])?>" class="student-home-notice<?=$notice['unread']?' is-unread':''?>">
      <span class="student-home-notice-symbol" aria-hidden="true"><?=($notice['kind']==='answer')?'↗':'✓'?></span>
      <span><small><?=h(($notice['kind']==='answer'?'Resposta do professor':'Retorno da avaliação').' · '.$notice['cohort'])?></small>
        <strong><?=h((string)$notice['title'])?></strong>
        <small><?=h((string)$notice['course'])?></small>
      </span><span aria-hidden="true">›</span>
    </a>
  <?php endforeach;?></div><?php endif;?>
</div>
<?php student_shell_end();