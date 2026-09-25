<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
$id=(int)($_GET['id']??$_POST['id']??0);$step=student_test_stage_key((string)($_GET['step']??$_POST['step']??'exposure'));$next=student_test_stage_url($id,$step);
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$test=student_test_for_student($db,$id,$studentId);
if(!$test){http_response_code(404);student_shell_start('Teste não encontrado',null,$student);?><div class="student-empty">Teste não encontrado.</div><?php student_shell_end();exit;}
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-test-'.$id,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else{
        try{
            $action=(string)($_POST['action']??'save_stage');
            $returnStep=student_test_stage_key((string)($_POST['return_step']??$step));
            if($action==='save_stage'){
                $stage=student_test_stage_key((string)($_POST['stage']??$step));student_test_update_stage($db,$id,$studentId,$stage,$_POST);
                $returnStep=student_test_stage_key((string)($_POST['next_step']??$stage));
            }elseif($action==='submit')student_test_submit($db,$id,$studentId);
            elseif($action==='message')student_test_add_student_message($db,$id,$studentId,(string)($_POST['message']??''));
            elseif($action==='upload'){
                $file=$_FILES['image']??null;if(!is_array($file))throw new RuntimeException('Selecione uma imagem.');
                $kind=student_test_media_kind((string)($_POST['media_kind']??'result'));student_test_add_media_kind($db,$id,$studentId,$kind,$file);$returnStep=$kind==='scene'?'exposure':'result';
            }elseif($action==='delete_media')student_test_delete_media($db,(int)($_POST['media_id']??0),$studentId);
            else throw new RuntimeException('Ação inválida.');
            $_SESSION['student_test_notice']=match($action){'save_stage'=>'Registro salvo.','submit'=>'Teste enviado para avaliação.','message'=>'Mensagem enviada.','upload'=>'Imagem adicionada.','delete_media'=>'Imagem removida.',default=>'Alteração salva.'};
            header('Location: '.student_test_stage_url($id,$returnStep),true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
$test=student_test_for_student($db,$id,$studentId)??$test;$sceneMedia=student_test_media_by_kind($db,$id,'scene');$resultMedia=student_test_media_by_kind($db,$id,'result');$messages=student_test_messages($db,$id);$state=student_test_stage_state($test,$sceneMedia,$resultMedia);$notice=$_SESSION['student_test_notice']??null;unset($_SESSION['student_test_notice']);$locked=$test['status']==='reviewed';
student_shell_start((string)$test['title'].' · Teste',null,$student);?>
<div class="student-test-shell">
<a class="student-back" href="/aluno/testes.php">← Testes</a>
<p class="student-kicker"><?=h((string)$test['public_title'])?> · <?=h((string)$test['cohort_title'])?></p>
<div class="student-title-row"><h1 class="student-title student-title-record"><?=h((string)$test['title'])?></h1><span class="student-status student-status-<?=h((string)$test['status'])?>"><?=h(student_test_status_label((string)$test['status']))?></span></div>
<p class="student-lead">Registre o teste na ordem em que ele acontece: cena e exposição, revelação, resultado e avaliação.</p>
<?php if($notice):?><p class="student-notice"><?=h((string)$notice)?></p><?php endif;?><?php if($error):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>

<nav class="student-test-progress" aria-label="Etapas do teste">
  <a href="<?=h(student_test_stage_url($id,'exposure'))?>"<?=$step==='exposure'?' aria-current="step"':''?><?=$state['exposure']?' class="is-complete"':''?>><small>01</small><strong>Cena e exposição</strong></a>
  <a href="<?=h(student_test_stage_url($id,'development'))?>"<?=$step==='development'?' aria-current="step"':''?><?=$state['development']?' class="is-complete"':''?>><small>02</small><strong>Revelação</strong></a>
  <a href="<?=h(student_test_stage_url($id,'result'))?>"<?=$step==='result'?' aria-current="step"':''?><?=$state['result']?' class="is-complete"':''?>><small>03</small><strong>Resultado</strong></a>
</nav>

<?php if($step==='exposure'):?>
<section class="student-step">
  <header class="student-step-header"><p class="student-kicker">Etapa 01</p><h2>Cena e exposição</h2><p>Anote os parâmetros no momento da exposição e guarde uma fotografia da cena como referência visual.</p></header>
  <div class="student-step-body">
    <form method="post" class="student-form-grid">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="save_stage"><input type="hidden" name="stage" value="exposure">
      <label class="student-field student-span-2">Título<input name="title" value="<?=h((string)$test['title'])?>" required maxlength="180"<?=$locked?' disabled':''?>></label>
      <label class="student-field">Data<input type="date" name="test_date" value="<?=h((string)($test['test_date']??''))?>"<?=$locked?' disabled':''?>></label>
      <label class="student-field">Filme<input name="film" value="<?=h((string)$test['film'])?>" placeholder="Filme e formato"<?=$locked?' disabled':''?>></label>
      <label class="student-field">Lote<input name="lot" value="<?=h((string)$test['lot'])?>"<?=$locked?' disabled':''?>></label>
      <label class="student-field">ISO / EI de referência<input name="iso_reference" inputmode="decimal" value="<?=h((string)$test['iso_reference'])?>"<?=$locked?' disabled':''?>></label>
      <label class="student-field">Diafragma<input name="aperture" value="<?=h((string)$test['aperture'])?>" placeholder="ex.: f/64"<?=$locked?' disabled':''?>></label>
      <label class="student-field">Tempo calculado<input name="calculated_time" value="<?=h((string)$test['calculated_time'])?>"<?=$locked?' disabled':''?>></label>
      <label class="student-field">Tempo com reciprocidade<input name="reciprocity_time" value="<?=h((string)$test['reciprocity_time'])?>"<?=$locked?' disabled':''?>></label>
      <label class="student-field student-span-2">Condição da luz<textarea name="light_condition" rows="3" placeholder="Fonte, direção, mudanças durante a exposição…"<?=$locked?' disabled':''?>><?=h((string)$test['light_condition'])?></textarea></label>
      <label class="student-field student-span-2">Regiões claras e sombras que quer preservar<textarea name="tonal_range" rows="3"<?=$locked?' disabled':''?>><?=h((string)$test['tonal_range'])?></textarea></label>
      <?php if(!$locked):?><div class="student-step-actions student-span-2"><button class="student-button student-button-secondary" type="submit" name="next_step" value="exposure">Salvar</button><button class="student-button student-step-next" type="submit" name="next_step" value="development">Salvar e ir para revelação →</button></div><?php endif;?>
    </form>

    <div class="student-photo-block">
      <div class="student-photo-block-header"><div><h3>Foto da cena</h3><p>Uma referência do que estava diante da câmera. No celular você pode fotografar agora ou escolher uma imagem já feita.</p></div><span class="student-status"><?=count($sceneMedia)?> foto(s)</span></div>
      <?=student_test_media_cards($sceneMedia,$id,$studentId,$locked,'exposure','Cena') ?>
      <?php if(!$locked&&count($sceneMedia)+count($resultMedia)<STUDENT_TEST_MEDIA_MAX_FILES):?><?=student_test_capture_controls($id,'scene','Fotografar cena','Escolher foto')?><?php endif;?>
    </div>
  </div>
</section>

<?php elseif($step==='development'):?>
<section class="student-step">
  <header class="student-step-header"><p class="student-kicker">Etapa 02</p><h2>Revelação</h2><p>Registre a condição efetivamente usada. Esses parâmetros precisam ficar separados da exposição para que seja possível comparar testes.</p></header>
  <div class="student-step-body">
    <form method="post" class="student-form-grid">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="save_stage"><input type="hidden" name="stage" value="development">
      <label class="student-field">Revelador<input name="developer" value="<?=h((string)$test['developer'])?>" placeholder="ex.: Parodinal"<?=$locked?' disabled':''?>></label>
      <label class="student-field">Diluição / quantidade<input name="dilution" value="<?=h((string)$test['dilution'])?>" placeholder="ex.: 10 ml + água"<?=$locked?' disabled':''?>></label>
      <label class="student-field">Temperatura<input name="temperature" inputmode="decimal" value="<?=h((string)$test['temperature'])?>" placeholder="ex.: 26 °C"<?=$locked?' disabled':''?>></label>
      <label class="student-field">Tempo de revelação<input name="development_time" value="<?=h((string)$test['development_time'])?>" placeholder="ex.: 5 min"<?=$locked?' disabled':''?>></label>
      <label class="student-field student-span-2">Movimentação / agitação<textarea name="agitation" rows="4" placeholder="Descreva a movimentação usada durante a primeira revelação."<?=$locked?' disabled':''?>><?=h((string)$test['agitation'])?></textarea></label>
      <?php if(!$locked):?><div class="student-step-actions student-span-2"><a class="student-button student-button-secondary" href="<?=h(student_test_stage_url($id,'exposure'))?>">← Voltar à exposição</a><button class="student-button student-step-next" type="submit" name="next_step" value="result">Salvar e registrar resultado →</button></div><?php endif;?>
    </form>
  </div>
</section>

<?php else:?>
<section class="student-step">
  <header class="student-step-header"><p class="student-kicker">Etapa 03</p><h2>Resultado</h2><p>Fotografe a chapa pronta, registre o que observou e envie o conjunto para avaliação quando estiver completo.</p></header>
  <div class="student-step-body">
    <div class="student-photo-block">
      <div class="student-photo-block-header"><div><h3>Fotos do resultado</h3><p>Registre a chapa inteira e, quando necessário, detalhes que ajudem a avaliar densidade, transparência, manchas ou separação tonal.</p></div><span class="student-status"><?=count($resultMedia)?> foto(s)</span></div>
      <?=student_test_media_cards($resultMedia,$id,$studentId,$locked,'result','Resultado') ?>
      <?php if(!$locked&&count($sceneMedia)+count($resultMedia)<STUDENT_TEST_MEDIA_MAX_FILES):?><?=student_test_capture_controls($id,'result','Fotografar resultado','Escolher foto')?><?php endif;?>
    </div>
    <form method="post" class="student-form-grid">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="save_stage"><input type="hidden" name="stage" value="result">
      <label class="student-field student-span-2">Observações do resultado<textarea name="notes" rows="7" placeholder="O que aconteceu? O que chamou atenção? O que pretende alterar no próximo teste?"<?=$locked?' disabled':''?>><?=h((string)$test['notes'])?></textarea></label>
      <?php if(!$locked):?><div class="student-step-actions student-span-2"><a class="student-button student-button-secondary" href="<?=h(student_test_stage_url($id,'development'))?>">← Voltar à revelação</a><button class="student-button" type="submit" name="next_step" value="result">Salvar resultado</button></div><?php endif;?>
    </form>
  </div>
</section>

<?php if(!$locked):?><article class="student-review-card"><p class="student-kicker">Avaliação</p><h3>Pronto para João avaliar?</h3><p>O envio muda o estado do registro para “Aguardando avaliação”. Você ainda pode corrigir os dados até ele ser marcado como revisado.</p><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="submit"><input type="hidden" name="return_step" value="result"><button class="student-button" type="submit"><?=$test['status']==='submitted'?'Reenviar para avaliação':'Enviar para avaliação'?></button></form></article><?php endif;?>

<section class="student-section" id="conversa">
  <div class="student-section-heading"><div><p class="student-kicker">Acompanhamento</p><h2 class="student-subtitle">Avaliação e dúvidas</h2></div><p>A conversa fica vinculada a este teste e às imagens que você registrou.</p></div>
  <?php if(!$messages):?><div class="student-empty">Ainda não há mensagens neste teste.</div><?php else:?><div class="student-thread"><?php foreach($messages as $message):?><article class="student-message <?=$message['author_role']==='admin'?'student-message-admin':'student-message-student'?>"><header><strong><?=$message['author_role']==='admin'?'João · avaliação':h((string)$student['name'])?></strong><span><?=h(student_test_message_date((string)$message['created_at']))?></span></header><p><?=nl2br(h((string)$message['body']))?></p></article><?php endforeach;?></div><?php endif;?>
  <form method="post" class="student-message-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="message"><input type="hidden" name="return_step" value="result"><label class="student-field">Nova dúvida ou comentário<textarea name="message" rows="5" required></textarea></label><button class="student-button" type="submit">Enviar mensagem</button></form>
</section>
<?php endif;?>
</div>
<?php student_shell_end();

function student_test_message_date(string $value): string {$ts=strtotime($value);return $ts===false?$value:date('d/m/Y H:i',$ts);}
function student_test_media_cards(array $media,int $testId,int $studentId,bool $locked,string $returnStep,string $altPrefix): string {
    if(!$media)return '';$csrf=csrf_token('student-test-'.$testId);$out='<div class="student-media-grid">';
    foreach($media as $item){$src='/aluno/teste-media.php?id='.(int)$item['id'];$out.='<figure class="student-media-card"><a href="'.h($src).'" target="_blank" rel="noopener"><img src="'.h($src).'" alt="'.h($altPrefix.' · '.(string)$item['original_name']).'" loading="lazy"></a><figcaption><span>'.h((string)$item['original_name']).'</span>';
        if(!$locked)$out.='<form method="post"><input type="hidden" name="_csrf" value="'.h($csrf).'"><input type="hidden" name="id" value="'.$testId.'"><input type="hidden" name="action" value="delete_media"><input type="hidden" name="return_step" value="'.h($returnStep).'"><input type="hidden" name="media_id" value="'.(int)$item['id'].'"><button type="submit">remover</button></form>';
        $out.='</figcaption></figure>';
    }
    return $out.'</div>';
}
function student_test_capture_controls(int $testId,string $kind,string $cameraLabel,string $fileLabel): string {
    $csrf=csrf_token('student-test-'.$testId);$base='<input type="hidden" name="_csrf" value="'.h($csrf).'"><input type="hidden" name="id" value="'.$testId.'"><input type="hidden" name="action" value="upload"><input type="hidden" name="media_kind" value="'.h($kind).'">';
    return '<div class="student-photo-actions"><form method="post" enctype="multipart/form-data" class="student-capture-form">'.$base.'<label class="student-capture-button"><span data-upload-label>'.h($cameraLabel).'</span><input data-student-auto-upload type="file" name="image" accept="image/*" capture="environment" required></label><noscript><button class="student-button" type="submit">Enviar foto</button></noscript></form><form method="post" enctype="multipart/form-data" class="student-capture-form">'.$base.'<label class="student-capture-button is-secondary"><span data-upload-label>'.h($fileLabel).'</span><input data-student-auto-upload type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label><noscript><button class="student-button" type="submit">Enviar arquivo</button></noscript></form></div>';
}
