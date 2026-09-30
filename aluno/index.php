<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_enrollment_reconcile_confirmed($db);$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$studentId=(int)$student['id'];$enrollments=student_enrollment_list($db,$studentId);$records=student_process_records_for_student($db,$studentId);$latestRecord=$records[0]??null;$latestSteps=$latestRecord?student_process_steps($db,(int)$latestRecord['id']):[];$latestState=$latestRecord?student_experience_process_state($latestRecord,$latestSteps):null;
$courseUrl='/aluno/cursos.php';if(count($enrollments)===1)$courseUrl.='?cohort='.rawurlencode((string)$enrollments[0]['cohort_uuid']);
student_shell_start('Início',null,$student);?>
<p class="student-kicker">Área do aluno</p><h1 class="student-title">Início</h1>
<?php if($latestRecord&&$latestState):$continueUrl='/aluno/teste.php?id='.(int)$latestRecord['id'].'&view='.rawurlencode((string)$latestState['view']);?>
<section class="student-home-primary" aria-labelledby="home-continue"><div class="student-home-primary-head"><div><p class="student-kicker">Continuar</p><h2 id="home-continue"><?=h((string)$latestRecord['title'])?></h2><p><strong><?=h((string)$latestState['label'])?></strong><br>Atualizado em <?=h(student_home_date((string)$latestRecord['updated_at']))?>.</p><a class="button button-primary" href="<?=h($continueUrl)?>"><?=h((string)$latestState['action'])?> →</a></div><span class="student-status"><?=h((string)($latestRecord['context_scope']??'course')==='personal'?'Pessoal':'Curso')?></span></div></section>
<?php else:?>
<section class="student-home-primary" aria-labelledby="home-start"><div class="student-home-primary-head"><div><p class="student-kicker">Caderno</p><h2 id="home-start">Novo registro</h2><a class="button button-primary" href="/aluno/caderno.php?novo=1">Criar registro →</a></div></div></section>
<?php endif;?>
<div class="student-home-secondary">
  <a href="<?=h($courseUrl)?>"><h3><?=count($enrollments)===1?h((string)$enrollments[0]['course_title']):'Curso'?></h3><p><?php if(!$enrollments):?>Nenhuma matrícula ativa.<?php elseif(count($enrollments)===1):?>Material e aulas da turma.<?php else:?><?=h(student_experience_count(count($enrollments),'matrícula','matrículas'))?> disponíveis.<?php endif;?></p></a>
  <a href="/aluno/caderno.php"><h3>Caderno de Processos</h3><p><?php if(!$records):?>Nenhum registro.<?php else:?><?=h(student_experience_count(count($records),'registro','registros'))?>.<?php endif;?></p></a>
</div>
<?php student_shell_end();
function student_home_date(string $value): string {$ts=strtotime($value);return $ts===false?$value:date('d/m/Y',$ts);}
