<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_enrollment_reconcile_confirmed($db);$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$requestedCohortUuid=is_string($_GET['cohort']??null)?trim((string)$_GET['cohort']):'';if($requestedCohortUuid!==''){header('Location: /aluno/cursos.php?cohort='.rawurlencode($requestedCohortUuid),true,302);exit;}
$studentId=(int)$student['id'];$enrollments=student_enrollment_list($db,$studentId);$records=student_process_records_for_student($db,$studentId);$tools=student_tools_for_student($db,$studentId);$latestRecord=$records[0]??null;
$courseUrl='/aluno/cursos.php';if(count($enrollments)===1)$courseUrl.='?cohort='.rawurlencode((string)$enrollments[0]['cohort_uuid']);
student_shell_start('Início',null,$student);?>
<p class="student-kicker">Área do aluno</p><h1 class="student-title">Início</h1><p class="student-lead">Escolha o que você veio fazer: estudar, registrar um processo ou trabalhar no laboratório.</p>
<div class="student-home-grid">
  <section class="student-home-section" aria-labelledby="student-home-courses"><p class="student-kicker">Estudar</p><h2 id="student-home-courses">Cursos</h2><?php if(!$enrollments):?><p>Nenhuma matrícula ativa está vinculada a esta conta.</p><?php elseif(count($enrollments)===1):?><p><strong><?=h((string)$enrollments[0]['course_title'])?></strong><br><?=h((string)$enrollments[0]['cohort_title'])?></p><?php else:?><p><?=count($enrollments)?> cursos ou turmas disponíveis.</p><?php endif;?><div class="student-home-actions"><a class="button button-primary" href="<?=h($courseUrl)?>">Abrir cursos</a></div></section>
  <section class="student-home-section" aria-labelledby="student-home-notebook"><p class="student-kicker">Registrar</p><h2 id="student-home-notebook">Caderno</h2><?php if($latestRecord):?><p><strong><?=h((string)$latestRecord['title'])?></strong><br>Último registro atualizado em <?=h(student_home_date((string)$latestRecord['updated_at']))?>.</p><?php else:?><p>Seu histórico de exposição, processamento e resultado começa aqui.</p><?php endif;?><div class="student-home-actions"><a class="button button-primary" href="/aluno/caderno.php">Abrir Caderno</a></div></section>
  <section class="student-home-section" aria-labelledby="student-home-lab"><p class="student-kicker">Fazer</p><h2 id="student-home-lab">Laboratório</h2><?php if($tools):?><p><?=count($tools)?> instrumento(s) disponível(is) para exposição, processamento, química e estoque.</p><?php else:?><p>Os instrumentos liberados pelas suas matrículas aparecem aqui.</p><?php endif;?><div class="student-home-actions"><a class="button button-primary" href="/aluno/ferramentas.php">Abrir laboratório</a></div></section>
</div>
<section class="student-home-account" aria-label="Conta"><div><p class="student-kicker">Conta</p><p>Dados pessoais, senha e sessão.</p></div><a class="button button-secondary" href="/aluno/perfil.php">Gerenciar conta</a></section>
<?php student_shell_end();
function student_home_date(string $value): string {$ts=strtotime($value);return $ts===false?$value:date('d/m/Y',$ts);}
