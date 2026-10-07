<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();

$db=database();student_account_reconcile_confirmed_registrations($db);$student=student_account_current($db);
$id=(int)($_GET['id']??$_POST['id']??0);$next='/aluno/teste.php?id='.$id;
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];$record=student_test_for_student($db,$id,$studentId);
if(!$record){http_response_code(404);student_shell_start('Registro não encontrado',null,$student);?><div class="student-empty">Registro não encontrado.</div><?php student_shell_end();exit;}

// Links antigos continuam apontando apenas para uma parte da mesma página.
if(($_SERVER['REQUEST_METHOD']??'GET')==='GET'&&isset($_GET['view'])){
    $anchor=match((string)$_GET['view']){'process'=>'processamento','review'=>'resultado',default=>'exposicao'};
    header('Location: /aluno/teste.php?id='.$id.'#'.$anchor,true,303);exit;
}

$error='';$contextScope=(string)($record['context_scope']??'course');
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-'.$id,$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{
        $action=(string)($_POST['action']??'');$anchor='';
        if($action==='save_exposure'){
            student_test_update_exposure($db,$id,$studentId,$_POST);$anchor='exposicao';$_SESSION['student_process_notice']='Exposição salva.';
        }elseif($action==='toggle_plan_step'){
            $plan=student_process_plan_for_test($db,$id,$studentId)??throw new RuntimeException('Associe um roteiro a este registro.');
            student_process_notebook_set_completed($db,(int)$plan['id'],(int)($_POST['plan_step_id']??0),$studentId,(string)($_POST['completed']??'0')==='1','manual');
            $anchor='processamento';$_SESSION['student_process_notice']='Marcação da etapa atualizada.';
        }elseif($action==='save_plan_step'){
            $plan=student_process_plan_for_test($db,$id,$studentId)??throw new RuntimeException('Associe um roteiro a este registro.');
            student_process_notebook_update_step($db,(int)$plan['id'],(int)($_POST['plan_step_id']??0),$studentId,$_POST);
            $anchor='processamento';$_SESSION['student_process_notice']='Etapa do roteiro atualizada neste registro.';
        }elseif($action==='move_plan_step'){
            $plan=student_process_plan_for_test($db,$id,$studentId)??throw new RuntimeException('Associe um roteiro a este registro.');
            student_process_notebook_move_step($db,(int)$plan['id'],(int)($_POST['plan_step_id']??0),$studentId,(int)($_POST['direction']??0));
            $anchor='processamento';$_SESSION['student_process_notice']='Ordem do roteiro atualizada neste registro.';
        }elseif($action==='add_plan_step'){
            $plan=student_process_plan_for_test($db,$id,$studentId)??throw new RuntimeException('Associe um roteiro a este registro.');
            student_process_notebook_add_step($db,(int)$plan['id'],$studentId,$_POST);
            $anchor='processamento';$_SESSION['student_process_notice']='Etapa adicionada ao roteiro deste registro.';
        }elseif($action==='add_free_step'){
            student_process_notebook_add_free_step($db,$id,$studentId,$_POST);$anchor='processamento';$_SESSION['student_process_notice']='Etapa adicionada ao registro.';
        }elseif($action==='delete_free_step'){
            student_process_notebook_delete_free_step($db,$id,(int)($_POST['step_id']??0),$studentId);$anchor='processamento';$_SESSION['student_process_notice']='Etapa removida.';
        }elseif($action==='save_result'){
            $notes=student_workspace_text($_POST['notes']??'',4000);$db->prepare('UPDATE student_tests SET notes=?,updated_at=? WHERE id=? AND student_id=?')->execute([$notes,utc_now(),$id,$studentId]);$anchor='resultado';$_SESSION['student_process_notice']='Resultado salvo.';
        }elseif($action==='upload'){
            $phase=(string)($_POST['phase']??'result');
            if($phase==='scene')student_test_update_exposure($db,$id,$studentId,$_POST);
            else{$notes=student_workspace_text($_POST['notes']??'',4000);$db->prepare('UPDATE student_tests SET notes=?,updated_at=? WHERE id=? AND student_id=?')->execute([$notes,utc_now(),$id,$studentId]);}
            $file=$_FILES['image']??null;if(!is_array($file))throw new RuntimeException('Selecione uma imagem.');student_test_add_media_phase($db,$id,$studentId,$file,$phase);$anchor=$phase==='scene'?'exposicao':'resultado';$_SESSION['student_process_notice']='Imagem adicionada.';
        }elseif($action==='delete_media'){
            student_test_delete_media($db,(int)($_POST['media_id']??0),$studentId);$anchor=(string)($_POST['phase']??'result')==='scene'?'exposicao':'resultado';$_SESSION['student_process_notice']='Imagem removida.';
        }elseif($action==='submit'){
            if($contextScope!=='course')throw new RuntimeException('Registros pessoais não são enviados para avaliação.');student_test_submit($db,$id,$studentId);$anchor='resultado';$_SESSION['student_process_notice']='Registro enviado para avaliação.';
        }elseif($action==='message'){
            student_test_add_student_message($db,$id,$studentId,(string)($_POST['message']??''));$anchor='resultado';$_SESSION['student_process_notice']='Mensagem enviada.';
        }else throw new RuntimeException('Ação inválida.');
        header('Location: /aluno/teste.php?id='.$id.($anchor!==''?'#'.$anchor:''),true,303);exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}

$record=student_test_for_student($db,$id,$studentId)??$record;
if($contextScope==='course'){student_feedback_mark_seen($db,$id,$studentId);$record=student_test_for_student($db,$id,$studentId)??$record;}
$feedbackState=student_feedback_state($record);$steps=student_process_steps($db,$id);$media=student_test_media($db,$id);$sceneMedia=student_test_media_by_phase($media,'scene');$resultMedia=student_test_media_by_phase($media,'result');$messages=student_test_messages($db,$id);
$notice=(string)($_SESSION['student_process_notice']??'');unset($_SESSION['student_process_notice']);$locked=(string)$record['status']==='reviewed';
$plan=student_process_plan_for_test($db,$id,$studentId);$planSteps=$plan?student_process_plan_steps($db,(int)$plan['id']):[];$planCompleted=0;foreach($planSteps as $planStep)if((string)$planStep['status']==='completed')$planCompleted++;
$editPlanStepId=(int)($_GET['edit_plan_step']??0);$editPlanStep=null;if($plan&&$editPlanStepId>0){foreach($planSteps as $candidate)if((int)$candidate['id']===$editPlanStepId){$editPlanStep=$candidate;break;}}
$stageCatalog=student_process_stage_catalog();$firstDevelopment=null;$bleachNames=[];
foreach($steps as $step){$stageKey=(string)$step['stage_key'];if($firstDevelopment===null&&$stageKey==='first_development')$firstDevelopment=$step;if(in_array($stageKey,['ferric','peracetic','dichromate','permanganate'],true)&&trim((string)$step['chemical_name'])!=='')$bleachNames[]=(string)$step['chemical_name'];}
$bleachNames=array_values(array_unique($bleachNames));

student_shell_start((string)$record['title'].' · Caderno',null,$student);?>
<header class="student-record-header">
  <div class="student-record-context"><a class="student-back" href="/aluno/caderno.php">← Caderno</a><span class="student-status student-status-<?=h((string)$record['status'])?>"><?=h(student_test_status_label((string)$record['status']))?></span></div>
  <p class="student-kicker"><?=$contextScope==='personal'?'Registro pessoal':h((string)$record['public_title'].' · '.$record['cohort_title'])?></p>
  <h1 class="student-title student-title-record"><?=h((string)$record['title'])?></h1>
  <p class="student-record-purpose">Este é um registro único. Exposição, processamento e resultado podem ser preenchidos, corrigidos ou deixados em aberto independentemente.</p>
  <nav class="student-actions student-record-section-links" aria-label="Ir para uma parte do registro"><a class="button button-secondary button-compact" href="#exposicao">Exposição</a><a class="button button-secondary button-compact" href="#processamento">Processamento</a><a class="button button-secondary button-compact" href="#resultado">Resultado</a></nav>
</header>
<?php if($notice!==''):?><p class="ui-alert ui-alert-notice"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<?php if($locked):?><p class="ui-alert ui-alert-notice">Este registro já foi avaliado. Você pode consultá-lo e continuar a conversa com o professor.</p><?php endif;?>

<section class="student-section student-record-section" id="exposicao">
  <div class="student-workflow-heading"><div><p class="student-kicker">Exposição</p><h2 class="student-subtitle">Como expus</h2></div><p>Registre ou corrija o que quiser documentar sobre a exposição.</p></div>
  <form method="post" enctype="multipart/form-data" class="student-mobile-form student-record-form" data-ui-validate>
    <input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="phase" value="scene"><input type="hidden" name="action" value="save_exposure" data-process-action>
    <div class="student-capture-block"><div><span class="student-capture-number">A</span><div><h3>Referência da cena</h3><p>Opcional. Serve para lembrar a situação fotografada; não é a imagem do resultado.</p></div></div><?php if(!$locked&&count($media)<STUDENT_TEST_MEDIA_MAX_FILES):?><label class="button button-secondary student-capture-button">Fotografar ou anexar<input type="file" name="image" accept="image/jpeg,image/png,image/webp" capture="environment" onchange="this.form.querySelector('[data-process-action]').value='upload';this.form.submit()"></label><?php endif;?></div>
    <?php if($sceneMedia):?><div class="student-media-strip"><?php foreach($sceneMedia as $item):?><figure class="student-media-card"><img src="/aluno/teste-media.php?id=<?=(int)$item['id']?>" alt="Cena registrada" loading="lazy"><?php if(!$locked):?><figcaption><button class="student-link" type="submit" name="media_id" value="<?=(int)$item['id']?>" onclick="this.form.querySelector('[data-process-action]').value='delete_media'">Remover</button></figcaption><?php endif;?></figure><?php endforeach;?></div><?php endif;?>
    <div class="student-form-group"><h3>Dados da exposição</h3><p class="student-form-group-intro">Preencha apenas o que fizer sentido para este registro.</p><div class="student-form-grid">
      <label class="form-field student-span-2">Título<input name="title" value="<?=h((string)$record['title'])?>" required maxlength="180"<?=$locked?' disabled':''?>></label><label class="form-field">Data<input type="date" name="test_date" value="<?=h((string)($record['test_date']??''))?>"<?=$locked?' disabled':''?>></label><label class="form-field">Filme<input name="film" value="<?=h((string)$record['film'])?>"<?=$locked?' disabled':''?>></label><label class="form-field">EI<input name="iso_reference" value="<?=h((string)$record['iso_reference'])?>"<?=$locked?' disabled':''?>></label><label class="form-field">Abertura<input name="aperture" value="<?=h((string)$record['aperture'])?>" placeholder="f/64"<?=$locked?' disabled':''?>></label><label class="form-field">Tempo sem reciprocidade<input name="calculated_time" value="<?=h((string)$record['calculated_time'])?>" data-reciprocity-source<?=$locked?' disabled':''?>></label><label class="form-field">Tempo com reciprocidade<input name="reciprocity_time" value="<?=h((string)$record['reciprocity_time'])?>" data-reciprocity-target readonly></label>
      <details class="student-optional-fields student-span-2"><summary>Dados opcionais</summary><div class="student-form-grid"><label class="form-field">Lote<input name="lot" value="<?=h((string)$record['lot'])?>"<?=$locked?' disabled':''?>></label><label class="form-field student-span-2">Condição de luz<textarea name="light_condition" rows="2"<?=$locked?' disabled':''?>><?=h((string)$record['light_condition'])?></textarea></label><label class="form-field student-span-2">Faixa tonal e intenção<textarea name="tonal_range" rows="2"<?=$locked?' disabled':''?>><?=h((string)$record['tonal_range'])?></textarea></label></div></details>
    </div></div>
    <?php if(!$locked):?><div class="student-actions"><button class="button button-primary" type="submit">Salvar exposição</button></div><?php endif;?>
  </form>
</section>

<section class="student-workflow-panel student-record-section" id="processamento">
  <div class="student-workflow-heading"><div><p class="student-kicker">Processamento</p><h2 class="student-subtitle">Roteiro e etapas</h2></div><p><?=$plan?'O roteiro é uma referência. Marque, edite ou abra qualquer etapa na ordem que quiser.':'Associe um roteiro como referência ou anote etapas livremente.'?></p></div>

  <?php if($plan):?>
  <section class="student-caderno-plan-card" aria-labelledby="student-plan-title">
    <div class="student-caderno-plan-head"><div><p class="student-kicker">Roteiro associado</p><h3 id="student-plan-title"><?=h((string)$plan['source_name'])?></h3><p>Esta é a cópia deste registro. Alterações feitas aqui não mudam o roteiro-modelo.</p></div><span class="student-plan-progress"><?=$planCompleted?> de <?=count($planSteps)?> marcadas</span></div>
    <div class="student-notebook-route-steps">
    <?php foreach($planSteps as $i=>$planStep):$done=(string)$planStep['status']==='completed';$seconds=student_process_time_seconds((string)$planStep['duration']);$routePayload=student_process_json_array((string)$planStep['payload_json']);$routeMode=(string)($routePayload['agitation_mode']??'none');if(!in_array($routeMode,['none','periodic','continuous'],true))$routeMode='none';$editing=$editPlanStep&&(int)$editPlanStep['id']===(int)$planStep['id'];?>
      <article class="student-notebook-route-step<?=$done?' is-done':''?><?=$editing?' is-editing':''?>">
        <div class="student-notebook-route-step-main"><span class="student-process-step-number"><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><strong><?=h((string)$planStep['label'])?></strong><?php if($seconds!==null):?><small><?=h(student_process_seconds_label($seconds))?></small><?php endif;?></div>
        <div class="student-actions student-notebook-route-step-actions">
          <?php if(!$locked):?><form method="post" action="/aluno/teste.php?id=<?=$id?>#processamento"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="toggle_plan_step"><input type="hidden" name="plan_step_id" value="<?=(int)$planStep['id']?>"><input type="hidden" name="completed" value="<?=$done?'0':'1'?>"><button class="button button-secondary button-compact" type="submit"><?=$done?'Desmarcar':'Marcar ✓'?></button></form><a class="button button-secondary button-compact" href="/aluno/teste.php?id=<?=$id?>&amp;edit_plan_step=<?=(int)$planStep['id']?>#processamento">Editar</a><?php endif;?>
          <a class="button button-secondary button-compact" href="/aluno/processar.php?test=<?=$id?>&amp;step=<?=(int)$planStep['id']?>"><?=$seconds!==null?'Timer':'Abrir etapa'?></a>
        </div>
        <?php if($editing&&!$locked):?>
        <div class="student-notebook-route-editor">
          <form method="post" action="/aluno/teste.php?id=<?=$id?>#processamento" class="student-form-grid"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="save_plan_step"><input type="hidden" name="plan_step_id" value="<?=(int)$planStep['id']?>">
            <label class="form-field student-span-2">Nome da etapa<input name="label" value="<?=h((string)$planStep['label'])?>"></label>
            <label class="form-field">Tempo<input name="duration" value="<?=h((string)$planStep['duration'])?>" placeholder="07:00"></label>
            <label class="form-field">Temperatura<input name="temperature" value="<?=h((string)($routePayload['temperature']??''))?>" placeholder="26 °C"></label>
            <?php if(in_array((string)$planStep['stage_key'],['first_development','second_development'],true)):?>
            <label class="form-field student-span-2">Revelador / solução<input name="developer_name" value="<?=h((string)($routePayload['developer_name']??''))?>"></label>
            <label class="form-field">Revelador / solução (ml)<input type="number" min="0" step="0.1" name="developer_amount" value="<?=h((string)($routePayload['developer_amount']??''))?>"></label>
            <label class="form-field">Água (ml)<input type="number" min="0" step="0.1" name="water_amount" value="<?=h((string)($routePayload['water_amount']??''))?>"></label>
            <?php else:?>
            <label class="form-field student-span-2">Químico / solução<input name="chemical_name" value="<?=h((string)($routePayload['chemical_name']??''))?>"></label>
            <?php endif;?>
            <label class="form-field student-span-2">Agitação<select name="agitation_mode"><option value="none"<?=$routeMode==='none'?' selected':''?>>Sem temporização</option><option value="periodic"<?=$routeMode==='periodic'?' selected':''?>>Periódica</option><option value="continuous"<?=$routeMode==='continuous'?' selected':''?>>Contínua</option></select></label>
            <label class="form-field">Duração da agitação<input name="agitation_duration" value="<?=h((string)($routePayload['agitation_duration']??''))?>" placeholder="00:10"></label>
            <label class="form-field">Intervalo entre inícios<input name="agitation_interval" value="<?=h((string)($routePayload['agitation_interval']??$planStep['agitation_interval']??''))?>" placeholder="01:00"></label>
            <label class="form-field student-span-2">Observação de agitação<input name="agitation" value="<?=h((string)($routePayload['agitation']??''))?>"></label>
            <label class="form-field student-span-2">Anotações<textarea name="notes" rows="3" maxlength="3000"><?=h((string)($routePayload['notes']??''))?></textarea></label>
            <div class="student-actions student-span-2"><button class="button button-primary" type="submit">Salvar etapa</button><a class="button button-secondary" href="/aluno/teste.php?id=<?=$id?>#processamento">Fechar</a></div>
          </form>
          <div class="student-notebook-route-order" aria-label="Alterar posição da etapa">
            <?php if($i>0):?><form method="post" action="/aluno/teste.php?id=<?=$id?>#processamento"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="move_plan_step"><input type="hidden" name="plan_step_id" value="<?=(int)$planStep['id']?>"><input type="hidden" name="direction" value="-1"><button class="student-link" type="submit">↑ Subir</button></form><?php endif;?>
            <?php if($i<count($planSteps)-1):?><form method="post" action="/aluno/teste.php?id=<?=$id?>#processamento"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="move_plan_step"><input type="hidden" name="plan_step_id" value="<?=(int)$planStep['id']?>"><input type="hidden" name="direction" value="1"><button class="student-link" type="submit">↓ Descer</button></form><?php endif;?>
          </div>
        </div>
        <?php endif;?>
      </article>
    <?php endforeach;?>
    </div>

    <?php if(!$locked):?>
    <details class="student-notebook-route-add">
      <summary><span><strong>Adicionar etapa ao roteiro</strong><small>Altera somente a cópia deste registro.</small></span><span>＋</span></summary>
      <form method="post" action="/aluno/teste.php?id=<?=$id?>#processamento" class="student-form-grid"><input type="hidden" name="id" value="<?=$id?>"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="add_plan_step">
        <label class="form-field student-span-2">Tipo de etapa<select name="stage_key"><?php foreach($stageCatalog as $key=>$stage):?><option value="<?=h($key)?>"><?=h((string)$stage['label'])?></option><?php endforeach;?></select></label>
        <label class="form-field student-span-2">Nome personalizado <small>opcional</small><input name="label"></label>
        <label class="form-field student-span-2">Químico / solução / revelador <small>opcional</small><input name="chemical_name"></label>
        <label class="form-field">Tempo<input name="duration" placeholder="07:00"></label><label class="form-field">Temperatura<input name="temperature" placeholder="26 °C"></label>
        <label class="form-field">Solução (ml)<input type="number" min="0" step="0.1" name="developer_amount"></label><label class="form-field">Água (ml)<input type="number" min="0" step="0.1" name="water_amount"></label>
        <label class="form-field student-span-2">Agitação<input name="agitation"></label><label class="form-field student-span-2">Anotações<textarea name="notes" rows="3" maxlength="3000"></textarea></label>
        <div class="student-actions student-span-2"><button class="button button-primary" type="submit">Adicionar ao roteiro</button></div>
      </form>
    </details>
    <?php endif;?>

    <div class="student-actions student-plan-resume-actions"><a class="button button-primary" href="/aluno/processar.php?test=<?=$id?>">Abrir roteiro</a><?php if(!$locked):?><a class="button button-secondary" href="/aluno/registro-roteiro.php?test=<?=$id?>">Trocar roteiro-base</a><?php endif;?><a class="button button-secondary" href="/aluno/inventario.php">Movimentar estoque</a></div>
  </section>
  <?php elseif(!$locked):?>
  <div class="student-process-path-choice"><div class="student-actions"><a class="button button-primary" href="/aluno/registro-roteiro.php?test=<?=$id?>">Associar roteiro</a><a class="button button-secondary" href="/aluno/inventario.php">Movimentar estoque</a></div></div>
  <?php endif;?>

  <?php if(!$locked&&!$plan):?>
  <details class="student-manual-process" id="adicionar-etapa">
    <summary><span><strong>Adicionar etapa</strong><small>Anote uma etapa sem associar um roteiro.</small></span><span>＋</span></summary>
    <form method="post" class="student-process-step-form student-form-grid"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="add_free_step">
      <label class="form-field student-span-2">Tipo de etapa<select name="stage_key"><?php foreach($stageCatalog as $key=>$stage):?><option value="<?=h($key)?>"><?=h((string)$stage['label'])?></option><?php endforeach;?></select></label>
      <label class="form-field student-span-2">Nome personalizado <small>use se escolher “Outra etapa”</small><input name="label"></label>
      <label class="form-field student-span-2">Químico ou solução <small>opcional</small><input name="chemical_name"></label>
      <label class="form-field">Tempo<input name="duration" placeholder="07:00"></label><label class="form-field">Temperatura<input name="temperature" placeholder="26 °C"></label>
      <label class="form-field student-span-2">Agitação<input name="agitation"></label><label class="form-field student-span-2">Anotações<textarea name="notes" rows="3" maxlength="3000"></textarea></label>
      <div class="student-actions student-span-2"><button class="button button-primary" type="submit">Adicionar ao registro</button></div>
    </form>
  </details>
  <?php endif;?>

  <?php if($steps):?>
  <details class="student-process-history" open><summary>Etapas anotadas · <?=h(student_experience_count(count($steps),'etapa','etapas'))?></summary><ol class="student-process-summary">
    <?php foreach($steps as $step):$detail=[];if($step['calculated_dilution']!=='')$detail[]=$step['calculated_dilution'];if($step['temperature']!=='')$detail[]=$step['temperature'];if($step['duration']!=='')$detail[]=$step['duration'];$stepName=$step['chemical_name']!==''?$step['chemical_name']:$step['label'];?>
    <li><div><span class="student-process-step-number"><?=str_pad((string)$step['position'],2,'0',STR_PAD_LEFT)?></span><strong><?=h((string)$stepName)?></strong><?php if($detail):?><small><?=h(implode(' · ',$detail))?></small><?php endif;?><?php if($step['notes']!==''):?><p><?=h((string)$step['notes'])?></p><?php endif;?></div><?php if(!$locked):?><div class="student-actions"><a class="student-link" href="/aluno/teste-etapa.php?registro=<?=$id?>&amp;etapa=<?=(int)$step['position']?>">Editar</a><form method="post" onsubmit="return confirm('Remover somente esta etapa do registro?')"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="delete_free_step"><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><button class="student-link" type="submit">Remover</button></form></div><?php endif;?></li>
    <?php endforeach;?>
  </ol></details>
  <?php endif;?>
</section>

<section class="student-section student-record-section" id="resultado">
  <div class="student-workflow-heading"><div><p class="student-kicker">Resultado</p><h2 class="student-subtitle">O que obtive</h2></div><p>Registre o resultado quando quiser. Ele não depende de nenhuma etapa anterior.</p></div>
  <details class="student-result-context">
    <summary><span>Resumo do registro</span><small>Exposição e processamento</small></summary>
    <div class="student-result-context-grid">
    <article><span>Exposição</span><dl><div><dt>Filme</dt><dd><?=h((string)($record['film']!==''?$record['film']:'—'))?></dd></div><div><dt>EI</dt><dd><?=h((string)($record['iso_reference']!==''?$record['iso_reference']:'—'))?></dd></div><div><dt>Abertura</dt><dd><?=h((string)($record['aperture']!==''?$record['aperture']:'—'))?></dd></div><div><dt>Tempo</dt><dd><?=h((string)($record['reciprocity_time']!==''?$record['reciprocity_time']:($record['calculated_time']!==''?$record['calculated_time']:'—')))?></dd></div></dl><a href="#exposicao">Ir para exposição</a></article>
    <article><span>Processamento</span><?php if($plan):?><dl><div><dt>Roteiro</dt><dd><?=h((string)$plan['source_name'])?></dd></div><div><dt>Etapas marcadas</dt><dd><?=$planCompleted?> de <?=count($planSteps)?></dd></div><?php if($steps):?><div><dt>Anotações livres</dt><dd><?=count($steps)?></dd></div><?php endif;?></dl><?php elseif($steps):?><dl><div><dt>Etapas anotadas</dt><dd><?=count($steps)?></dd></div><div><dt>1ª revelação</dt><dd><?=h($firstDevelopment?(string)($firstDevelopment['chemical_name']?:$firstDevelopment['label']):'—')?></dd></div><div><dt>Branqueamento</dt><dd><?=h($bleachNames?implode(' + ',$bleachNames):'—')?></dd></div></dl><?php else:?><p>Nenhuma etapa anotada.</p><?php endif;?><a href="#processamento">Ir para processamento</a></article>
  </div>
  </details>

  <div class="student-result-flow"><form method="post" enctype="multipart/form-data" class="student-mobile-form student-result-form"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="phase" value="result"><input type="hidden" name="action" value="save_result" data-process-action>
    <div class="student-capture-block"><div><span class="student-capture-number">B</span><div><h3>Imagem do resultado</h3><p>Fotografia da chapa processada ou outra evidência que queira guardar neste registro.</p></div></div><?php if(!$locked&&count($media)<STUDENT_TEST_MEDIA_MAX_FILES):?><label class="button button-secondary student-capture-button">Fotografar ou anexar<input type="file" name="image" accept="image/jpeg,image/png,image/webp" capture="environment" onchange="this.form.querySelector('[data-process-action]').value='upload';this.form.submit()"></label><?php endif;?></div>
    <?php if($resultMedia):?><div class="student-media-strip"><?php foreach($resultMedia as $item):?><figure class="student-media-card"><img src="/aluno/teste-media.php?id=<?=(int)$item['id']?>" alt="Resultado" loading="lazy"><?php if(!$locked):?><figcaption><button class="student-link" type="submit" name="media_id" value="<?=(int)$item['id']?>" onclick="this.form.querySelector('[data-process-action]').value='delete_media'">Remover</button></figcaption><?php endif;?></figure><?php endforeach;?></div><?php endif;?>
    <label class="form-field">Anotações sobre o resultado<textarea name="notes" rows="5"<?=$locked?' disabled':''?>><?=h((string)$record['notes'])?></textarea></label><p class="student-field-help">Anote o que observou, inclusive quando a experiência foi abandonada antes do fim do roteiro.</p><?php if(!$locked):?><div class="student-actions"><button class="button button-primary" type="submit">Salvar resultado</button></div><?php endif;?>
  </form>

  <?php if($contextScope==='course'):?><section class="student-review-thread student-review-thread-<?=h((string)$feedbackState['kind'])?>"><p class="student-kicker">Acompanhamento do curso</p><h3>Avaliação</h3>
    <?php if((string)$record['status']==='draft'):?><p class="student-review-help">Quando considerar o registro pronto para avaliação, envie-o. Não é necessário completar um roteiro para isso.</p><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="submit"><button class="button button-primary" type="submit">Enviar para avaliação</button></form>
    <?php elseif((string)$record['status']==='submitted'):?><div class="student-review-state"><strong><?=h((string)$feedbackState['label'])?></strong><p>O registro permanece aqui enquanto aguarda a avaliação. Se houver uma mensagem do professor, ela aparece na conversa abaixo.</p></div>
    <?php elseif((string)$record['status']==='needs_revision'):?><div class="student-review-state is-action"><strong>Revisão solicitada</strong><p>Leia o retorno abaixo, ajuste o registro e envie novamente.</p></div><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="submit"><button class="button button-primary" type="submit">Enviar revisão para avaliação</button></form>
    <?php else:?><div class="student-review-state is-complete"><strong>Avaliação concluída</strong><p>A conversa continua disponível se precisar retomar algum ponto com o professor.</p></div><?php endif;?>
    <details class="student-optional-fields"<?=($messages||in_array((string)$record['status'],['needs_revision','reviewed'],true))?' open':''?>><summary>Conversa<?=$messages?' · '.count($messages):''?></summary><?php if($messages):?><div class="student-message-list"><?php foreach($messages as $message):?><article><strong><?=$message['author_role']==='admin'?'Professor':'Você'?></strong><p><?=nl2br(h((string)$message['body']))?></p><small><?=h(student_test_message_date((string)$message['created_at']))?></small></article><?php endforeach;?></div><?php endif;?><form method="post" class="student-form-grid"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$id))?>"><input type="hidden" name="action" value="message"><label class="form-field student-span-2">Mensagem ao professor<textarea name="message" rows="3"></textarea></label><button class="button button-secondary" type="submit">Enviar mensagem</button></form></details>
  </section><?php endif;?>
  </div>
</section>
<div class="student-actions student-record-footer-actions"><a class="button button-secondary" href="/aluno/caderno.php">← Voltar ao Caderno</a></div>
<?php student_shell_end();