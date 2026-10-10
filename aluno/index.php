<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_enrollment_reconcile_confirmed($db);$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2F',true,303);exit;}
$studentId=(int)$student['id'];$enrollments=student_enrollment_list($db,$studentId);$records=student_process_records_for_student($db,$studentId);
$pendingFeedback=student_feedback_pending_for_student($db,$studentId,null,1);$feedbackRecord=$pendingFeedback[0]??null;$feedbackState=$feedbackRecord?student_feedback_state($feedbackRecord):null;
$focusRecord=null;$focusState=null;
if(!$feedbackRecord){foreach($records as $candidate){
    $steps=student_process_steps($db,(int)$candidate['id']);$hasResult=trim((string)($candidate['notes']??''))!=='';
    if(student_experience_process_complete($steps)&&!$hasResult){$media=student_test_media($db,(int)$candidate['id']);$hasResult=(bool)student_test_media_by_phase($media,'result');}
    $state=student_experience_dashboard_state($candidate,$steps,$hasResult);if(!$state)continue;$focusRecord=$candidate;$focusState=$state;break;
}}
$courseUrl='/aluno/cursos.php';if(count($enrollments)===1)$courseUrl.='?cohort='.rawurlencode((string)$enrollments[0]['cohort_uuid']);
$studyUrl='';$studyTitle='';
if(!$feedbackRecord&&!$focusRecord&&count($enrollments)===1){
    $enrollment=$enrollments[0];$activity=activity_by_id($db,(int)$enrollment['activity_id']);$pages=student_enrollment_pages_for_enrollment($db,$enrollment);$courseId=(int)($enrollment['course_id']??0);$releaseRows=$courseId>0?course_lesson_release_rows_for_course($db,(int)$enrollment['cohort_id'],$courseId):[];
    if($activity){foreach($pages as $page){$available=true;if($courseId>0){$summary=course_material_page_release_summary($db,$courseId,(int)$enrollment['cohort_id'],(int)$page['id'],$releaseRows);$available=in_array((string)$summary['state'],['available','partial'],true);}if(!$available)continue;$studyUrl=cms_page_url($activity,$page,(string)$page['locale']);$studyUrl.=(str_contains($studyUrl,'?')?'&':'?').'cohort='.rawurlencode((string)$enrollment['cohort_uuid']);$studyTitle=(string)$page['title'];break;}}
}
student_shell_start('Início',null,$student);?>
<p class="student-kicker">Área do aluno</p><h1 class="student-title">Início</h1>
<?php if($feedbackRecord&&$feedbackState):$feedbackUrl='/aluno/teste.php?id='.(int)$feedbackRecord['id'].'&view=review';?>
<section class="student-dashboard-focus student-dashboard-feedback" aria-labelledby="student-dashboard-focus-title"><div class="student-dashboard-focus-copy"><p class="student-kicker">Retorno do professor</p><h2 id="student-dashboard-focus-title"><?=h((string)$feedbackRecord['title'])?></h2><p><?=h((string)$feedbackState['label'])?> · <?=h((string)$feedbackRecord['public_title'])?> · <?=h((string)$feedbackRecord['cohort_title'])?></p></div><div class="student-dashboard-focus-action"><a class="button button-primary" href="<?=h($feedbackUrl)?>"><?=h((string)$feedbackState['action'])?> →</a></div></section>
<?php elseif($focusRecord&&$focusState):$continueUrl='/aluno/teste.php?id='.(int)$focusRecord['id'].'&view='.rawurlencode((string)$focusState['view']);?>
<section class="student-dashboard-focus" aria-labelledby="student-dashboard-focus-title"><div class="student-dashboard-focus-copy"><p class="student-kicker">Continuar no Caderno</p><h2 id="student-dashboard-focus-title"><?=h((string)$focusRecord['title'])?></h2><p><?=h((string)$focusState['label'])?> · atualizado em <?=h(student_home_date((string)$focusRecord['updated_at']))?>.</p></div><div class="student-dashboard-focus-action"><a class="button button-primary" href="<?=h($continueUrl)?>"><?=h((string)$focusState['action'])?> →</a></div></section>
<?php elseif($studyUrl!==''):?>
<section class="student-dashboard-focus" aria-labelledby="student-dashboard-focus-title"><div class="student-dashboard-focus-copy"><p class="student-kicker">Estudar</p><h2 id="student-dashboard-focus-title"><?=h($studyTitle)?></h2><p><?=h((string)$enrollments[0]['course_title'])?> · <?=h((string)$enrollments[0]['cohort_title'])?></p></div><div class="student-dashboard-focus-action"><a class="button button-primary" href="<?=h($studyUrl)?>">Abrir material →</a></div></section>
<?php elseif(count($enrollments)>1):?>
<section class="student-dashboard-focus" aria-labelledby="student-dashboard-focus-title"><div class="student-dashboard-focus-copy"><p class="student-kicker">Estudar</p><h2 id="student-dashboard-focus-title">Escolha o curso</h2><p>Há <?=h(student_experience_count(count($enrollments),'matrícula','matrículas'))?> ativas. A escolha só é necessária porque ela muda o material e a turma que serão abertos.</p></div><div class="student-dashboard-focus-action"><a class="button button-primary" href="/aluno/cursos.php">Escolher curso →</a></div></section>
<?php elseif(count($enrollments)===1):?>
<section class="student-dashboard-focus" aria-labelledby="student-dashboard-focus-title"><div class="student-dashboard-focus-copy"><p class="student-kicker">Curso</p><h2 id="student-dashboard-focus-title"><?=h((string)$enrollments[0]['course_title'])?></h2><p>Nenhum material está liberado para leitura agora. Veja o contexto da turma e as próximas liberações.</p></div><div class="student-dashboard-focus-action"><a class="button button-primary" href="<?=h($courseUrl)?>">Abrir curso →</a></div></section>
<?php else:?>
<section class="student-dashboard-focus" aria-labelledby="student-dashboard-focus-title"><div class="student-dashboard-focus-copy"><p class="student-kicker">Caderno</p><h2 id="student-dashboard-focus-title">Novo registro</h2><p>Você ainda não tem matrícula ativa. O Caderno continua disponível para registros pessoais.</p></div><div class="student-dashboard-focus-action"><a class="button button-primary" href="/aluno/caderno.php?novo=1">Criar registro →</a></div></section>
<?php endif;?>
<nav class="student-dashboard-links" aria-label="Outros destinos">
  <a class="student-dashboard-link" href="<?=h($courseUrl)?>"><span><strong>Curso</strong><small><?php if(!$enrollments):?>Nenhuma matrícula ativa.<?php elseif(count($enrollments)===1):?><?=h((string)$enrollments[0]['course_title'].' · '.$enrollments[0]['cohort_title'])?><?php else:?><?=h(student_experience_count(count($enrollments),'matrícula','matrículas'))?> ativas.<?php endif;?></small></span><span>→</span></a>
  <a class="student-dashboard-link" href="/aluno/caderno.php"><span><strong>Caderno</strong><small><?php if(!$records):?>Nenhum registro ainda.<?php else:?><?=h(student_experience_count(count($records),'registro','registros'))?> no caderno.<?php endif;?></small></span><span>→</span></a>
</nav>
<?php student_shell_end();
function student_home_date(string $value): string {$ts=strtotime($value);return $ts===false?$value:date('d/m/Y',$ts);}
