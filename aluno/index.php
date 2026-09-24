<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
$db=database();
try{$activity=activity_for_request($db);}catch(Throwable $e){http_response_code(404);exit('Atividade não encontrada.');}
$student=require_student_for_activity($db,$activity);
$locale=public_locale();$materials=student_materials($db,(int)$activity['id'],$locale,true);
student_shell_start('Área do aluno',$activity,$student);?>
<p class="student-kicker">Área do aluno</p>
<h1 class="student-title">Materiais</h1>
<p class="student-lead">Conteúdo de apoio disponível exclusivamente para participantes autenticados.</p>
<?php if(!$materials):?>
  <div class="student-empty">Nenhum material foi publicado para este idioma ainda.</div>
<?php else:?><section class="student-grid" aria-label="Materiais disponíveis">
  <?php foreach($materials as $material):?>
    <a class="student-card" href="<?=h(student_url('/aluno/material.php',$activity,['slug'=>(string)$material['slug'],'lang'=>(string)$material['locale']]))?>">
      <span class="student-card-label">Material protegido</span>
      <h2><?=h((string)$material['title'])?></h2>
      <p>Acesso individual. O conteúdo não é indexado nem armazenado em cache público.</p>
    </a>
  <?php endforeach;?>
</section><?php endif;?>
<?php student_shell_end();
