<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();
student_private_headers();

$db=database();
student_account_reconcile_confirmed_registrations($db);
$student=student_account_current($db);
$id=(int)($_GET['id']??$_POST['id']??0);
$next='/aluno/teste.php?id='.$id;
if(!$student){
    header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);
    exit;
}
$studentId=(int)$student['id'];
$record=student_test_for_student($db,$id,$studentId);
if(!$record){
    http_response_code(404);
    student_shell_start('Registro não encontrado',null,$student);
    ?><div class="student-empty">Registro não encontrado.</div><?php
    student_shell_end();
    exit;
}

// Compatibilidade com links antigos do Caderno em formato de etapas.
if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'&&isset($_GET['view'])){
    $legacyView=(string)$_GET['view'];
    $anchor=match($legacyView){'process'=>'processamento','review'=>'resultado',default=>'exposicao'};
    header('Location: /aluno/teste.php?id='.$id.'#'.$anchor,true,303);
    exit;
}

$error='';
$contextScope=(string)($record['context_scope']??'course');
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-'.$id,$_POST['_csrf']??null)){
        $error='Solicitação inválida.';
    }else{
        try{
            $action=(string)($_POST['action']??'');
            $anchor='';
            if($action==='save_exposure'){
                student_test_update_exposure($db,$id,$studentId,$_POST);
                $anchor='exposicao';
                $_SESSION['student_process_notice']='Exposição salva.';
            }elseif($action==='add_step'){
                student_process_add_guided_step($db,$id,$studentId,$_POST);
                $anchor='processamento';
                $_SESSION['student_process_notice']='Etapa registrada.';
            }elseif($action==='delete_step'){
                student_process_delete_from_step($db,$id,(int)($_POST['step_id']??0),$studentId);
                $anchor='processamento';
                $_SESSION['student_process_notice']='Última etapa desfeita.';
            }elseif($action==='save_result'){
                $notes=student_workspace_text($_POST['notes']??'',4000);
                $db->prepare('UPDATE student_tests SET notes=?,updated_at=? WHERE id=? AND student_id=?')->execute([$notes,utc_now(),$id,$studentId]);
                $anchor='resultado';
                $_SESSION['student_process_notice']='Resultado salvo.';
            }elseif($action==='upload'){
                $phase=(string)($_POST['phase']??'result');
                if($phase==='scene')student_test_update_exposure($db,$id,$studentId,$_POST);
                else{
                    $notes=student_workspace_text($_POST['notes']??'',4000);
                    $db->prepare('UPDATE student_tests SET notes=?,updated_at=? WHERE id=? AND student_id=?')->execute([$notes,utc_now(),$id,$studentId]);
                }
                $file=$_FILES['image']??null;
                if(!is_array($file))throw new RuntimeException('Selecione uma imagem.');
                student_test_add_media_phase($db,$id,$studentId,$file,$phase);
                $anchor=$phase==='scene'?'exposicao':'resultado';
                $_SESSION['student_process_notice']='Imagem adicionada.';
            }elseif($action==='delete_media'){
                student_test_delete_media($db,(int)($_POST['media_id']??0),$studentId);
                $anchor=(string)($_POST['phase']??'result')==='scene'?'exposicao':'resultado';
                $_SESSION['student_process_notice']='Imagem removida.';
            }elseif($action==='submit'){
                if($contextScope!=='course')throw new RuntimeException('Registros pessoais não são enviados para avaliação.');
                student_test_submit($db,$id,$studentId);
                $anchor='resultado';
                $_SESSION['student_process_notice']='Registro enviado para avaliação.';
            }elseif($action==='message'){
                student_test_add_student_message($db,$id,$studentId,(string)($_POST['message']??''));
                $anchor='resultado';
                $_SESSION['student_process_notice']='Mensagem enviada.';
            }else throw new RuntimeException('Ação inválida.');
            header('Location: /aluno/teste.php?id='.$id.($anchor!==''?'#'.$anchor:''),true,303);
            exit;
        }catch(Throwable $e){
            $error=$e->getMessage();
        }
    }
}

$record=student_test_for_student($db,$id,$studentId)??$record;
if($contextScope==='course'){
    student_feedback_mark_seen($db,$id,$studentId);
    $record=student_test_for_student($db,$id,$studentId)??$record;
}
$feedbackState=student_feedback_state($record);
$steps=student_process_steps($db,$id);
$complete=student_experience_process_complete($steps);
$nextChoices=student_experience_next_choices($steps);
$media=student_test_media($db,$id);
$sceneMedia=student_test_media_by_phase($media,'scene');
$resultMedia=student_test_media_by_phase($media,'result');
$inventory=student_inventory_items($db,$studentId);
$preparations=student_saved_preparations($db,$studentId);
$messages=student_test_messages($db,$id);
$notice=(string)($_SESSION['student_process_notice']??'');
unset($_SESSION['student_process_notice']);
$locked=(string)$record['status']==='reviewed';
$developers=student_process_developer_catalog();
$lastStep=$steps?($steps[array_key_last($steps)]??null):null;
$plan=student_process_plan_for_test($db,$id,$studentId);
$planSteps=$plan?student_process_plan_steps($db,(int)$plan['id']):[];
$planNext=$plan?student_process_plan_next_step($db,$plan):null;
$planCompleted=0;
foreach($planSteps as $planStep)if((string)$planStep['status']==='completed')$planCompleted++;
$planStarted=$plan&&((string)$plan['status']!=='planned'||$planCompleted>0);
$firstDevelopment=null;
$bleachNames=[];
foreach($steps as $step){
    $stageKey=(string)$step['stage_key'];
    if($firstDevelopment===null&&$stageKey==='first_development')$firstDevelopment=$step;
    if(in_array($stageKey,['ferric','peracetic','dichromate','permanganate'],true)&&trim((string)$step['chemical_name'])!=='')$bleachNames[]=(string)$step['chemical_name'];
}
$bleachNames=array_values(array_unique($bleachNames));

student_shell_start((string)$record['title'].' · Caderno',null,$student);
?>
<header class="student-record-header">
  <div class="student-record-context">
    <a class="student-back" href="/aluno/caderno.php">← Caderno</a>
    <span class="student-status student-status-<?=h((string)$record['status'])?>"><?=h(student_test_status_label((string)$record['status']))?></span>
  </div>
  <p class="student-kicker"><?=$contextScope==='personal'?'Registro pessoal':h((string)$record['public_title'].' · '.$record['cohort_title'])?></p>
  <h1 class="student-title student-title-record"><?=h((string)$record['title'])?></h1>
  <p class="student-record-purpose">Exposição, processamento e resultado pertencem ao mesmo registro. Você pode trabalhar em qualquer uma das partes e voltar depois.</p>
  <nav class="student-actions student-record-section-links" aria-label="Ir para uma parte do registro">
    <a class="button button-secondary button-compact" href="#exposicao">Exposição</a>
    <a class="button button-secondary button-compact" href="#processamento">Processamento</a>
    <a class="button button-secondary button-compact" href="#resultado">Resultado</a>
  </nav>
</header>

<?php if($notice!==''):?><p class="ui-alert ui-alert-notice"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<?php if($locked):?><p class="ui-alert ui-alert-notice">Este registro já foi avaliado e está preservado. Você ainda pode consultar todas as partes e continuar a conversa com o professor.</p><?php endif;?>

<section class="student-section student-record-section" id="exposicao">
  <div class="student-workflow-heading">
    <div><p class="student-kicker">Exposição</p><h2 class="student-subtitle">Como expus</h2></div>
    <p>Registre ou corrija as condições usadas na fotografia.</p>
  </div>
  <form method="post" enctype="multipart/form-data" class="student-mobile-form student-record-form" data-ui-validate>
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>">
    <input type="hidden" name="id" value="<?=$id?>">
    <input type="hidden" name="phase" value="scene">
    <input type="hidden" name="action" value="save_exposure" data-process-action>
    <div class="student-capture-block">
      <div><span class="student-capture-number">A</span><div><h3>Referência da cena</h3><p>Opcional. Serve para lembrar a situação fotografada; não é a imagem do resultado.</p></div></div>
      <?php if(!$locked&&count($media)<STUDENT_TEST_MEDIA_MAX_FILES):?><label class="button button-secondary student-capture-button">Fotografar ou anexar<input type="file" name="image" accept="image/jpeg,image/png,image/webp" capture="environment" onchange="this.form.querySelector('[data-process-action]').value='upload';this.form.submit()"></label><?php endif;?>
    </div>
    <?php if($sceneMedia):?><div class="student-media-strip"><?php foreach($sceneMedia as $item):?><figure class="student-media-card"><img src="/aluno/teste-media.php?id=<?=(int)$item['id']?>" alt="Cena registrada" loading="lazy"><?php if(!$locked):?><figcaption><button class="student-link" type="submit" name="media_id" value="<?=(int)$item['id']?>" onclick="this.form.querySelector('[data-process-action]').value='delete_media'">Remover</button></figcaption><?php endif;?></figure><?php endforeach;?></div><?php endif;?>
    <div class="student-form-group">
      <h3>Dados da exposição</h3>
      <p class="student-form-group-intro">Preencha apenas o que quiser documentar neste momento.</p>
      <div class="student-form-grid">
        <label class="form-field student-span-2">Título<input name="title" value="<?=h((string)$record['title'])?>" required maxlength="180"<?=$locked?' disabled':''?>></label>
        <label class="form-field">Data<input type="date" name="test_date" value="<?=h((string)($record['test_date']??''))?>"<?=$locked?' disabled':''?>></label>
        <label class="form-field">Filme<input name="film" value="<?=h((string)$record['film'])?>"<?=$locked?' disabled':''?>></label>
        <label class="form-field">EI<input name="iso_reference" value="<?=h((string)$record['iso_reference'])?>"<?=$locked?' disabled':''?>></label>
        <label class="form-field">Abertura<input name="aperture" value="<?=h((string)$record['aperture'])?>" placeholder="f/64"<?=$locked?' disabled':''?>></label>
        <label class="form-field">Tempo sem reciprocidade<input name="calculated_time" value="<?=h((string)$record['calculated_time'])?>" data-reciprocity-source<?=$locked?' disabled':''?>></label>
        <label class="form-field">Tempo com reciprocidade<input name="reciprocity_time" value="<?=h((string)$record['reciprocity_time'])?>" data-reciprocity-target readonly></label>
        <details class="student-optional-fields student-span-2"><summary>Dados opcionais</summary><div class="student-form-grid"><label class="form-field">Lote<input name="lot" value="<?=h((string)$record['lot'])?>"<?=$locked?' disabled':''?>></label><label class="form-field student-span-2">Condição de luz<textarea name="light_condition" rows="2"<?=$locked?' disabled':''?>><?=h((string)$record['light_condition'])?></textarea></label><label class="form-field student-span-2">Faixa tonal e intenção<textarea name="tonal_range" rows="2"<?=$locked?' disabled':''?>><?=h((string)$record['tonal_range'])?></textarea></label></div></details>
      </div>
    </div>
    <?php if(!$locked):?><div class="student-actions"><button class="button button-primary" type="submit">Salvar exposição</button></div><?php endif;?>
  </form>
</section>

<section class="student-workflow-panel student-record-section" id="processamento">
  <div class="student-workflow-heading">
    <div><p class="student-kicker">Processamento</p><h2 class="student-subtitle">Como revelei</h2></div>
    <p>Use um roteiro no laboratório ou registre diretamente o que foi feito.</p>
  </div>

  <?php if($plan):?>
    <section class="student-caderno-plan-card" aria-labelledby="student-plan-title">
      <div class="student-caderno-plan-head">
        <div><p class="student-kicker"><?=$complete?'Processamento registrado':($planStarted?'Em andamento':'Roteiro associado')?></p><h3 id="student-plan-title"><?=h((string)($plan['source_name']??'Processamento'))?></h3></div>
        <span class="student-plan-progress"><?=$planCompleted?> / <?=count($planSteps)?> etapas</span>
      </div>
      <?php if($planNext&&!$complete):?><div class="student-caderno-plan-next"><span>Próxima etapa do roteiro</span><strong><?=h((string)$planNext['label'])?></strong><?php if((string)$planNext['duration']!==''):?><small><?=h(student_process_seconds_label(student_process_time_seconds((string)$planNext['duration'])))?></small><?php endif;?></div><?php endif;?>
      <?php if(!$locked):?><div class="student-actions student-plan-resume-actions">
        <?php if(!$complete):?><a class="button button-primary" href="/aluno/processar.php?test=<?=$id?>&amp;intent=live"><?=$planStarted?'Continuar laboratório':'Abrir laboratório'?></a><?php endif;?>
        <a class="button button-secondary" href="/aluno/registro-roteiro.php?test=<?=$id?>"><?=$plan?'Alterar roteiro':'Associar roteiro'?></a>
        <a class="button button-secondary" href="/aluno/processamento-realizado.php?test=<?=$id?>">Registrar manualmente</a>
      </div><?php endif;?>
    </section>
  <?php elseif(!$locked):?>
    <div class="student-process-path-choice">
      <div class="student-process-choice-intro"><h3>Como quer registrar o processamento?</h3><p>As duas opções pertencem ao mesmo registro e podem ser retomadas depois.</p></div>
      <div class="student-actions">
        <a class="button button-primary" href="/aluno/registro-roteiro.php?test=<?=$id?>">Usar um roteiro</a>
        <a class="button button-secondary" href="/aluno/processamento-realizado.php?test=<?=$id?>">Registrar manualmente</a>
      </div>
    </div>
  <?php endif;?>

  <?php if(!$locked&&!$plan&&!$complete):?>
    <details class="student-manual-process" id="student-manual-process"<?=$steps?' open':''?>>
      <summary><span><strong>Adicionar uma etapa aqui</strong><small>Registro rápido, etapa por etapa.</small></span><span>＋</span></summary>
      <form method="post" class="student-process-step-form" data-process-step-form>
        <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>">
        <input type="hidden" name="action" value="add_step">
        <fieldset class="choice-field"><legend>Etapa</legend><div class="choice-row student-process-choice-row"><?php foreach($nextChoices as $key=>$choice):?><label class="choice-option"><input type="radio" name="stage_key" value="<?=h($key)?>"<?=$key===array_key_first($nextChoices)?' checked':''?> data-stage-choice><span><?=h((string)$choice['label'])?></span></label><?php endforeach;?></div></fieldset>
        <div data-stage-fields="development" class="student-process-stage-fields"><div class="student-form-grid"><label class="form-field student-span-2">Revelador<select name="developer_key" data-developer-select><?php foreach($developers as $key=>$developer):?><option value="<?=h($key)?>" data-preparation-mode="<?=h((string)$developer['mode'])?>"><?=h((string)$developer['label'])?></option><?php endforeach;?></select></label><label class="form-field student-span-2" data-custom-developer hidden>Outro revelador<input name="developer_name"></label><label class="form-field student-span-2">Predefinição de revelação<select name="saved_preparation_id" data-saved-preparation><option value="">Nenhum</option><?php foreach($preparations as $prep):?><option value="<?=(int)$prep['id']?>" data-developer-key="<?=h((string)$prep['developer_key'])?>" data-mode="<?=h((string)$prep['preparation_mode'])?>" data-developer-amount="<?=h((string)$prep['developer_amount'])?>" data-water-amount="<?=h((string)$prep['water_amount'])?>" data-temperature="<?=h((string)$prep['temperature'])?>" data-duration="<?=h((string)$prep['duration'])?>" data-agitation="<?=h((string)$prep['agitation'])?>"><?=h((string)$prep['label'])?></option><?php endforeach;?></select></label><div class="student-span-2 student-developer-mix" data-developer-mix><label class="form-field">Revelador ou solução estoque (ml)<input type="number" min="0" step="0.1" name="developer_amount" data-developer-amount></label><label class="form-field">Água (ml)<input type="number" min="0" step="0.1" name="water_amount" data-water-amount></label><output class="student-dilution-output student-span-2" data-dilution-output></output></div><label class="form-field student-span-2" data-fresh-volume hidden>Volume preparado (ml)<input type="number" min="0" step="0.1" name="fresh_volume" data-fresh-volume-input></label><?=student_process_inventory_select_html($inventory,'Item do inventário')?><label class="form-field">Temperatura<input name="temperature" placeholder="26 °C"></label><label class="form-field">Tempo<input name="duration" placeholder="7 min" data-development-duration></label><label class="form-field student-span-2">Agitação<input name="agitation"></label></div></div>
        <div data-stage-fields="simple" class="student-process-stage-fields"><div class="student-form-grid"><label class="form-field">Tempo<input name="duration_simple"></label><?=student_process_inventory_select_html($inventory,'Item do inventário',true)?><label class="form-field">Quantidade utilizada<input type="number" min="0" step="0.1" name="inventory_amount_simple"></label></div></div>
        <div data-stage-fields="custom" class="student-process-stage-fields"><div class="student-form-grid"><label class="form-field student-span-2">Nome da etapa<input name="custom_label"></label><label class="form-field">Tempo<input name="duration_custom"></label></div></div>
        <input type="hidden" data-stage-duration><input type="hidden" data-stage-inventory-item><input type="hidden" data-stage-inventory-amount>
        <details class="student-optional-fields"><summary>Anotações da etapa</summary><label class="form-field"><textarea name="notes" rows="3" maxlength="3000" aria-label="Anotações da etapa"></textarea></label></details>
        <div class="student-actions"><button class="button button-primary" type="submit">Registrar etapa</button></div>
      </form>
    </details>
  <?php endif;?>

  <?php if($steps):?>
    <details class="student-process-history" open>
      <summary>Etapas registradas · <?=h(student_experience_count(count($steps),'etapa','etapas'))?></summary>
      <ol class="student-process-summary"><?php foreach($steps as $step):$detail=[];if($step['calculated_dilution']!=='')$detail[]=$step['calculated_dilution'];if($step['temperature']!=='')$detail[]=$step['temperature'];if($step['duration']!=='')$detail[]=$step['duration'];$stepName=$step['chemical_name']!==''?$step['chemical_name']:$step['label'];?><li><div><span class="student-process-step-number"><?=str_pad((string)$step['position'],2,'0',STR_PAD_LEFT)?></span><strong><?=h((string)$stepName)?></strong><?php if($detail):?><small><?=h(implode(' · ',$detail))?></small><?php endif;?><?php if($step['notes']!==''):?><p><?=h((string)$step['notes'])?></p><?php endif;?></div></li><?php endforeach;?></ol>
      <?php if(!$locked&&$lastStep&&!$plan):?><details class="student-optional-fields"><summary>Mais opções do processamento</summary><div class="student-process-history-actions"><form method="post" onsubmit="return confirm('Desfazer somente a última etapa registrada?')"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="delete_step"><input type="hidden" name="step_id" value="<?=(int)$lastStep['id']?>"><button class="button button-secondary button-compact" type="submit">Desfazer última etapa</button></form></div></details><?php endif;?>
    </details>
  <?php elseif($locked):?><p class="student-field-help">Nenhuma etapa de processamento foi registrada.</p><?php endif;?>
</section>

<section class="student-section student-record-section" id="resultado">
  <div class="student-workflow-heading">
    <div><p class="student-kicker">Resultado</p><h2 class="student-subtitle">O que obtive</h2></div>
    <p>Registre o resultado quando quiser. Exposição e processamento continuam disponíveis acima para consulta ou correção.</p>
  </div>

  <section class="student-result-context" aria-labelledby="student-result-context-title">
    <div class="student-result-context-head"><p class="student-kicker">Resumo do registro</p><h3 id="student-result-context-title">Exposição e processamento</h3></div>
    <div class="student-result-context-grid">
      <article><span>Exposição</span><dl><div><dt>Filme</dt><dd><?=h((string)($record['film']!==''?$record['film']:'—'))?></dd></div><div><dt>EI</dt><dd><?=h((string)($record['iso_reference']!==''?$record['iso_reference']:'—'))?></dd></div><div><dt>Abertura</dt><dd><?=h((string)($record['aperture']!==''?$record['aperture']:'—'))?></dd></div><div><dt>Tempo</dt><dd><?=h((string)($record['reciprocity_time']!==''?$record['reciprocity_time']:($record['calculated_time']!==''?$record['calculated_time']:'—')))?></dd></div></dl><a href="#exposicao">Ir para exposição</a></article>
      <article><span>Processamento</span><?php if($steps):?><dl><div><dt>Etapas registradas</dt><dd><?=count($steps)?></dd></div><div><dt>1ª revelação</dt><dd><?=h($firstDevelopment?(string)($firstDevelopment['chemical_name']?:$firstDevelopment['label']):'—')?></dd></div><div><dt>Branqueamento</dt><dd><?=h($bleachNames?implode(' + ',$bleachNames):'—')?></dd></div><div><dt>Estado</dt><dd><?=$complete?'Concluído':'Em andamento'?></dd></div></dl><?php elseif($plan):?><p>Roteiro associado: <strong><?=h((string)($plan['source_name']??'Processamento'))?></strong>.</p><?php else:?><p>Nenhuma etapa foi registrada.</p><?php endif;?><a href="#processamento">Ir para processamento</a></article>
    </div>
  </section>

  <div class="student-result-flow">
    <form method="post" enctype="multipart/form-data" class="student-mobile-form student-result-form">
      <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>">
      <input type="hidden" name="phase" value="result">
      <input type="hidden" name="action" value="save_result" data-process-action>
      <div class="student-capture-block"><div><span class="student-capture-number">B</span><div><h3>Imagem do resultado</h3><p>Fotografia da chapa processada para documentar o que foi obtido.</p></div></div><?php if(!$locked&&count($media)<STUDENT_TEST_MEDIA_MAX_FILES):?><label class="button button-secondary student-capture-button">Fotografar ou anexar<input type="file" name="image" accept="image/jpeg,image/png,image/webp" capture="environment" onchange="this.form.querySelector('[data-process-action]').value='upload';this.form.submit()"></label><?php endif;?></div>
      <?php if($resultMedia):?><div class="student-media-strip"><?php foreach($resultMedia as $item):?><figure class="student-media-card"><img src="/aluno/teste-media.php?id=<?=(int)$item['id']?>" alt="Resultado" loading="lazy"><?php if(!$locked):?><figcaption><button class="student-link" type="submit" name="media_id" value="<?=(int)$item['id']?>" onclick="this.form.querySelector('[data-process-action]').value='delete_media'">Remover</button></figcaption><?php endif;?></figure><?php endforeach;?></div><?php endif;?>
      <label class="form-field">Anotações sobre o resultado<textarea name="notes" rows="5"<?=$locked?' disabled':''?>><?=h((string)$record['notes'])?></textarea></label>
      <p class="student-field-help">Anote o que observou no positivo e o que manteria ou mudaria numa próxima tentativa.</p>
      <?php if(!$locked):?><div class="student-actions"><button class="button button-primary" type="submit">Salvar resultado</button></div><?php endif;?>
    </form>

    <?php if($contextScope==='course'):?>
      <section class="student-review-thread student-review-thread-<?=h((string)$feedbackState['kind'])?>">
        <p class="student-kicker">Acompanhamento do curso</p><h3>Avaliação</h3>
        <?php if((string)$record['status']==='draft'):?><p class="student-review-help">Quando considerar o registro pronto para avaliação, envie-o. As partes do Caderno continuam sendo o mesmo registro.</p><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="submit"><button class="button button-primary" type="submit">Enviar para avaliação</button></form>
        <?php elseif((string)$record['status']==='submitted'):?><div class="student-review-state"><strong><?=h((string)$feedbackState['label'])?></strong><p>O registro permanece aqui enquanto aguarda a avaliação. Se houver uma mensagem do professor, ela aparece na conversa abaixo.</p></div>
        <?php elseif((string)$record['status']==='needs_revision'):?><div class="student-review-state is-action"><strong>Revisão solicitada</strong><p>Leia o retorno abaixo, ajuste o próprio registro e envie novamente. A conversa e o histórico permanecem neste resultado.</p></div><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="submit"><button class="button button-primary" type="submit">Enviar revisão para avaliação</button></form>
        <?php else:?><div class="student-review-state is-complete"><strong>Avaliação concluída</strong><p>O registro fica preservado como foi avaliado. A conversa continua disponível se precisar retomar algum ponto com o professor.</p></div><?php endif;?>
        <details class="student-optional-fields"<?=($messages||in_array((string)$record['status'],['needs_revision','reviewed'],true))?' open':''?>><summary>Conversa<?=$messages?' · '.count($messages):''?></summary><?php if($messages):?><div class="student-message-list"><?php foreach($messages as $message):?><article><strong><?=$message['author_role']==='admin'?'Professor':'Você'?></strong><p><?=nl2br(h((string)$message['body']))?></p><small><?=h(student_test_message_date((string)$message['created_at']))?></small></article><?php endforeach;?></div><?php endif;?><form method="post" class="student-form-grid"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="message"><label class="form-field student-span-2">Mensagem ao professor<textarea name="message" rows="3"></textarea></label><button class="button button-secondary" type="submit">Enviar mensagem</button></form></details>
      </section>
    <?php endif;?>
  </div>
</section>

<div class="student-actions student-record-footer-actions"><a class="button button-secondary" href="/aluno/caderno.php">← Voltar ao Caderno</a></div>

<?php
student_shell_end();

function student_process_inventory_select_html(array $inventory,string $label,bool $simple=false): string {
    if(!$inventory)return '';
    ob_start();
    ?><label class="form-field student-span-2"<?=$simple?'':' data-developer-inventory'?>><?=h($label)?><select name="<?=$simple?'inventory_item_id_simple':'inventory_item_id'?>"><option value="">Não vincular ao inventário</option><?php foreach($inventory as $item):?><option value="<?=(int)$item['id']?>"><?=h((string)$item['name'])?> · <?=h(student_workbench_number((float)$item['quantity']).' '.$item['unit'])?></option><?php endforeach;?></select></label><?php
    return (string)ob_get_clean();
}
