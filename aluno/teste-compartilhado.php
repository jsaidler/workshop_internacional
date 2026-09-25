<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);
$id=(int)($_GET['id']??0);
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode('/aluno/teste-compartilhado.php?id='.$id),true,303);exit;}
$test=student_test_accessible_to_student($db,$id,(int)$student['id']);
if(!$test||(int)$test['student_id']===(int)$student['id']){header('Location: /aluno/teste.php?id='.$id,true,303);exit;}
$media=student_test_media($db,$id);$sceneMedia=student_test_media_by_phase($media,'scene');$resultMedia=student_test_media_by_phase($media,'result');
student_shell_start((string)$test['title'].' · Compartilhado',null,$student);?>
<div class="student-appbar"><a class="student-back" href="/aluno/testes.php">← Testes</a><span class="student-sharing-badge"><?=h(student_test_visibility_label((string)$test['visibility']))?></span></div>
<p class="student-kicker"><?=h((string)$test['student_name'])?> · <?=h((string)$test['cohort_title'])?></p>
<h1 class="student-title student-title-record"><?=h((string)$test['title'])?></h1>
<p class="student-lead student-lead-compact">Ficha compartilhada pelo autor. A conversa de avaliação com o professor não faz parte do compartilhamento.</p>
<section class="student-workflow-panel">
  <div class="student-review-grid">
    <article><span>Exposição</span><dl><div><dt>Filme</dt><dd><?=student_review_value($test['film'])?></dd></div><div><dt>Lote</dt><dd><?=student_review_value($test['lot'])?></dd></div><div><dt>EI / ISO</dt><dd><?=student_review_value($test['iso_reference'])?></dd></div><div><dt>Diafragma</dt><dd><?=student_review_value($test['aperture'])?></dd></div><div><dt>Tempo calculado</dt><dd><?=student_review_value($test['calculated_time'])?></dd></div><div><dt>Após reciprocidade</dt><dd><?=student_review_value($test['reciprocity_time'])?></dd></div></dl><?php if(trim((string)$test['light_condition'])!==''):?><p><?=nl2br(h((string)$test['light_condition']))?></p><?php endif;?><?php if(trim((string)$test['tonal_range'])!==''):?><p><?=nl2br(h((string)$test['tonal_range']))?></p><?php endif;?></article>
    <article><span>Revelação</span><dl><div><dt>Revelador</dt><dd><?=student_review_value($test['developer'])?></dd></div><div><dt>Diluição</dt><dd><?=student_review_value($test['dilution'])?></dd></div><div><dt>Temperatura</dt><dd><?=student_review_value($test['temperature'])?></dd></div><div><dt>Tempo</dt><dd><?=student_review_value($test['development_time'])?></dd></div></dl><?php if(trim((string)$test['agitation'])!==''):?><p><?=nl2br(h((string)$test['agitation']))?></p><?php endif;?><?php if(trim((string)$test['notes'])!==''):?><p><?=nl2br(h((string)$test['notes']))?></p><?php endif;?></article>
  </div>
  <div class="student-review-media"><div><span>Cena</span><?php if($sceneMedia):?><div class="student-media-strip"><?php foreach($sceneMedia as $item):$src='/aluno/teste-media.php?id='.(int)$item['id'];?><a href="<?=h($src)?>" target="_blank" rel="noopener"><img src="<?=h($src)?>" alt="Cena do teste" loading="lazy"></a><?php endforeach;?></div><?php else:?><p>Nenhuma foto da cena.</p><?php endif;?></div><div><span>Resultado</span><?php if($resultMedia):?><div class="student-media-strip"><?php foreach($resultMedia as $item):$src='/aluno/teste-media.php?id='.(int)$item['id'];?><a href="<?=h($src)?>" target="_blank" rel="noopener"><img src="<?=h($src)?>" alt="Resultado do teste" loading="lazy"></a><?php endforeach;?></div><?php else:?><p>Nenhuma foto do resultado.</p><?php endif;?></div></div>
</section>
<?php student_shell_end();
