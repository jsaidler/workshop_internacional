<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
$id=(int)($_GET['id']??$_POST['id']??0);$next='/aluno/teste.php?id='.$id;
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$test=student_test_for_student($db,$id,$studentId);
if(!$test){http_response_code(404);student_shell_start('Teste não encontrado',null,$student);?><div class="student-empty">Teste não encontrado.</div><?php student_shell_end();exit;}
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-test-'.$id,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else{
        try{
            $action=(string)($_POST['action']??'save');
            if($action==='save')student_test_update($db,$id,$studentId,$_POST);
            elseif($action==='submit')student_test_submit($db,$id,$studentId);
            elseif($action==='message')student_test_add_student_message($db,$id,$studentId,(string)($_POST['message']??''));
            elseif($action==='upload'){
                $file=$_FILES['image']??null;if(!is_array($file))throw new RuntimeException('Selecione uma imagem.');student_test_add_media($db,$id,$studentId,$file);
            }elseif($action==='delete_media')student_test_delete_media($db,(int)($_POST['media_id']??0),$studentId);
            else throw new RuntimeException('Ação inválida.');
            $_SESSION['student_test_notice']=match($action){'save'=>'Ficha salva.','submit'=>'Teste enviado para avaliação.','message'=>'Mensagem enviada.','upload'=>'Imagem adicionada.','delete_media'=>'Imagem removida.',default=>'Alteração salva.'};
            header('Location: /aluno/teste.php?id='.$id,true,303);exit;
        }catch(Throwable $e){$error=$e->getMessage();}
    }
}
$test=student_test_for_student($db,$id,$studentId)??$test;$media=student_test_media($db,$id);$messages=student_test_messages($db,$id);$notice=$_SESSION['student_test_notice']??null;unset($_SESSION['student_test_notice']);$locked=$test['status']==='reviewed';
student_shell_start((string)$test['title'].' · Teste',null,$student);?>
<a class="student-back" href="/aluno/testes.php">← Meus testes</a>
<p class="student-kicker"><?=h((string)$test['public_title'])?> · <?=h((string)$test['cohort_title'])?></p>
<div class="student-title-row"><h1 class="student-title student-title-record"><?=h((string)$test['title'])?></h1><span class="student-status student-status-<?=h((string)$test['status'])?>"><?=h(student_test_status_label((string)$test['status']))?></span></div>
<p class="student-lead">A ficha guarda exposição, revelação, imagens e a conversa sobre este teste no mesmo lugar.</p>
<?php if($notice):?><p class="student-notice"><?=h((string)$notice)?></p><?php endif;?><?php if($error):?><p class="student-error" role="alert"><?=h($error)?></p><?php endif;?>

<section class="student-section">
  <div class="student-section-heading"><div><p class="student-kicker">Registro</p><h2 class="student-subtitle">Dados do teste</h2></div><?php if($locked):?><p>Este registro foi marcado como revisado. Os dados ficam preservados; dúvidas ainda podem ser enviadas abaixo.</p><?php else:?><p>Salve quantas vezes precisar antes ou depois de enviar para avaliação.</p><?php endif;?></div>
  <form method="post" class="student-form-grid">
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="save">
    <h3 class="student-form-heading">Identificação</h3>
    <label class="student-field student-span-2">Título<input name="title" value="<?=h((string)$test['title'])?>" required maxlength="180"<?=$locked?' disabled':''?>></label>
    <label class="student-field">Data<input type="date" name="test_date" value="<?=h((string)($test['test_date']??''))?>"<?=$locked?' disabled':''?>></label>
    <label class="student-field">Filme<input name="film" value="<?=h((string)$test['film'])?>" placeholder="Filme e formato"<?=$locked?' disabled':''?>></label>
    <label class="student-field">Lote<input name="lot" value="<?=h((string)$test['lot'])?>"<?=$locked?' disabled':''?>></label>

    <h3 class="student-form-heading">Exposição</h3>
    <label class="student-field">ISO usado como referência<input name="iso_reference" value="<?=h((string)$test['iso_reference'])?>"<?=$locked?' disabled':''?>></label>
    <label class="student-field">Diafragma<input name="aperture" value="<?=h((string)$test['aperture'])?>"<?=$locked?' disabled':''?>></label>
    <label class="student-field">Tempo inicialmente calculado<input name="calculated_time" value="<?=h((string)$test['calculated_time'])?>"<?=$locked?' disabled':''?>></label>
    <label class="student-field">Tempo corrigido pela reciprocidade<input name="reciprocity_time" value="<?=h((string)$test['reciprocity_time'])?>"<?=$locked?' disabled':''?>></label>
    <label class="student-field student-span-2">Condição da luz<textarea name="light_condition" rows="4"<?=$locked?' disabled':''?>><?=h((string)$test['light_condition'])?></textarea></label>
    <label class="student-field student-span-2">Diferença entre regiões claras e sombras que quer preservar<textarea name="tonal_range" rows="4"<?=$locked?' disabled':''?>><?=h((string)$test['tonal_range'])?></textarea></label>

    <h3 class="student-form-heading">Revelação</h3>
    <label class="student-field">Revelador<input name="developer" value="<?=h((string)$test['developer'])?>"<?=$locked?' disabled':''?>></label>
    <label class="student-field">Diluição<input name="dilution" value="<?=h((string)$test['dilution'])?>"<?=$locked?' disabled':''?>></label>
    <label class="student-field">Temperatura<input name="temperature" value="<?=h((string)$test['temperature'])?>"<?=$locked?' disabled':''?>></label>
    <label class="student-field">Tempo de revelação<input name="development_time" value="<?=h((string)$test['development_time'])?>"<?=$locked?' disabled':''?>></label>
    <label class="student-field student-span-2">Movimentação<textarea name="agitation" rows="3"<?=$locked?' disabled':''?>><?=h((string)$test['agitation'])?></textarea></label>
    <label class="student-field student-span-2">Observações<textarea name="notes" rows="7" placeholder="O que aconteceu, o que chamou atenção, o que pretende alterar no próximo teste"<?=$locked?' disabled':''?>><?=h((string)$test['notes'])?></textarea></label>
    <?php if(!$locked):?><div class="student-actions student-span-2"><button class="student-button" type="submit">Salvar ficha</button></div><?php endif;?>
  </form>
  <?php if(!$locked):?><form method="post" class="student-submit-review"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="submit"><button class="student-button student-button-secondary" type="submit"><?=$test['status']==='submitted'?'Reenviar para avaliação':'Enviar para avaliação'?></button><p>O envio avisa, pelo estado do registro, que este teste está pronto para ser avaliado. Você continua podendo corrigir a ficha até ele ser marcado como revisado.</p></form><?php endif;?>
</section>

<section class="student-section">
  <div class="student-section-heading"><div><p class="student-kicker">Imagem</p><h2 class="student-subtitle">Resultados visuais</h2></div><p>Até <?=STUDENT_TEST_MEDIA_MAX_FILES?> imagens por teste · JPEG, PNG ou WebP · máximo 12 MB cada.</p></div>
  <?php if($media):?><div class="student-media-grid"><?php foreach($media as $item):$src='/aluno/teste-media.php?id='.(int)$item['id'];?><figure class="student-media-card"><a href="<?=h($src)?>" target="_blank" rel="noopener"><img src="<?=h($src)?>" alt="<?=h((string)$item['original_name'])?>" loading="lazy"></a><figcaption><span><?=h((string)$item['original_name'])?></span><?php if(!$locked):?><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="delete_media"><input type="hidden" name="media_id" value="<?=(int)$item['id']?>"><button class="student-link" type="submit">remover</button></form><?php endif;?></figcaption></figure><?php endforeach;?></div><?php endif;?>
  <?php if(!$locked&&count($media)<STUDENT_TEST_MEDIA_MAX_FILES):?><form method="post" enctype="multipart/form-data" class="student-upload"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="upload"><label class="student-field">Adicionar imagem<input type="file" name="image" accept="image/jpeg,image/png,image/webp" required></label><button class="student-button" type="submit">Enviar imagem</button></form><?php endif;?>
</section>

<section class="student-section">
  <div class="student-section-heading"><div><p class="student-kicker">Acompanhamento</p><h2 class="student-subtitle">Avaliação e dúvidas</h2></div><p>Use este histórico para responder à avaliação ou perguntar sobre qualquer ponto deste teste.</p></div>
  <?php if(!$messages):?><div class="student-empty">Ainda não há mensagens neste teste.</div><?php else:?><div class="student-thread"><?php foreach($messages as $message):?><article class="student-message <?=$message['author_role']==='admin'?'student-message-admin':'student-message-student'?>"><header><strong><?=$message['author_role']==='admin'?'João · avaliação':h((string)$student['name'])?></strong><span><?=h(student_test_message_date((string)$message['created_at']))?></span></header><p><?=nl2br(h((string)$message['body']))?></p></article><?php endforeach;?></div><?php endif;?>
  <form method="post" class="student-message-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="action" value="message"><label class="student-field">Nova dúvida ou comentário<textarea name="message" rows="5" required></textarea></label><button class="student-button" type="submit">Enviar mensagem</button></form>
</section>
<?php student_shell_end();

function student_test_message_date(string $value): string {$ts=strtotime($value);return $ts===false?$value:date('d/m/Y H:i',$ts);}
