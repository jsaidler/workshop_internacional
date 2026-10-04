<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();

function student_process_template_href(int $id,int $testId=0,int $stepId=0,string $intent=''): string {
    $url='/aluno/processamentos.php?id='.$id;
    if($testId>0)$url.='&test='.$testId;
    if($intent==='live')$url.='&intent=live';
    if($stepId>0)$url.='&step='.$stepId;
    return $url;
}

function student_process_render_step_form(array $catalog,array $developers,array $preparations,string $csrf,int $templateId,int $testId,?array $step=null): void {
    $payload=$step?student_process_json_array((string)$step['payload_json']):[];
    $stageKey=(string)($step['stage_key']??'first_development');
    $customLabel=(string)($payload['custom_label']??'');
    $developerKey=(string)($payload['developer_key']??array_key_first($developers)??'');
    $savedPreparationId=(int)($payload['saved_preparation_id']??0);
    $reuseSource=(string)($payload['reuse_source_stage_key']??'');
    $profile=student_process_lab_agitation_profile([
        'payload_json'=>json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        'agitation_interval'=>(string)($step['agitation_interval']??$payload['agitation_interval']??''),
    ]);
    $action=$step?'update_step':'add_step';
    ?>
    <form method="post" class="student-form-grid student-process-step-form" data-process-template-step-form>
      <input type="hidden" name="_csrf" value="<?=h($csrf)?>">
      <input type="hidden" name="action" value="<?=h($action)?>">
      <input type="hidden" name="template_id" value="<?=$templateId?>">
      <input type="hidden" name="test_id" value="<?=$testId?>">
      <?php if($step):?><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><?php endif;?>
      <label class="form-field student-span-2">Etapa
        <select name="stage_key" data-process-template-stage>
          <?php foreach($catalog as $key=>$stage):?><option value="<?=h((string)$key)?>"<?=$stageKey===(string)$key?' selected':''?>><?=h((string)$stage['label'])?></option><?php endforeach;?>
        </select>
      </label>
      <label class="form-field student-span-2" data-process-template-custom<?=$stageKey==='custom'?'':' hidden'?>>Nome da etapa
        <input name="custom_label" maxlength="120" value="<?=h($customLabel)?>">
      </label>
      <div class="student-span-2 student-form-grid" data-process-template-development<?=in_array($stageKey,['first_development','second_development'],true)?'':' hidden'?>>
        <label class="form-field">Revelador
          <select name="developer_key"><?php foreach($developers as $key=>$developer):?><option value="<?=h($key)?>"<?=$developerKey===(string)$key?' selected':''?>><?=h((string)$developer['label'])?></option><?php endforeach;?></select>
        </label>
        <label class="form-field">Predefinição de revelação
          <select name="saved_preparation_id"><option value="">Nenhuma</option><?php foreach($preparations as $prep):?><option value="<?=(int)$prep['id']?>"<?=$savedPreparationId===(int)$prep['id']?' selected':''?>><?=h((string)$prep['label'])?></option><?php endforeach;?></select>
        </label>
        <label class="form-field">Revelador ou solução estoque (ml)<input type="number" min="0" step="0.1" name="developer_amount" value="<?=h((string)($payload['developer_amount']??''))?>"></label>
        <label class="form-field">Água (ml)<input type="number" min="0" step="0.1" name="water_amount" value="<?=h((string)($payload['water_amount']??''))?>"></label>
        <label class="form-field student-span-2">Outro revelador<input name="developer_name" value="<?=h((string)($payload['developer_name']??''))?>"></label>
      </div>
      <label class="student-process-reuse student-span-2" data-process-template-reuse<?=$stageKey==='second_development'?'':' hidden'?>>
        <input type="checkbox" name="reuse_source_stage_key" value="first_development"<?=$reuseSource==='first_development'?' checked':''?>>
        <span><strong>Reutilizar o banho da primeira revelação</strong><small>Marque quando a segunda revelação usa a mesma solução já preparada. Isso não representa uma nova preparação nem um novo consumo.</small></span>
      </label>
      <label class="form-field">Tempo<input name="duration" value="<?=h((string)($step['duration']??''))?>" placeholder="07:00"></label>
      <label class="form-field">Temperatura<input name="temperature" value="<?=h((string)($payload['temperature']??''))?>" placeholder="26 °C"></label>
      <label class="form-field student-span-2">Controle de agitação
        <select name="agitation_mode" data-process-agitation-mode>
          <option value="none"<?=$profile['mode']==='none'?' selected':''?>>Sem temporização</option>
          <option value="periodic"<?=$profile['mode']==='periodic'?' selected':''?>>Periódica</option>
          <option value="continuous"<?=$profile['mode']==='continuous'?' selected':''?>>Contínua</option>
        </select>
      </label>
      <div class="student-span-2 student-form-grid" data-process-agitation-periodic<?=$profile['mode']==='periodic'?'':' hidden'?>>
        <label class="form-field">Duração de cada agitação<input name="agitation_duration" value="<?=h((string)$profile['duration'])?>" placeholder="00:10"></label>
        <label class="form-field">Intervalo entre inícios<input name="agitation_interval" value="<?=h((string)$profile['interval'])?>" placeholder="01:00"></label>
      </div>
      <label class="form-field student-span-2">Observação de agitação<input name="agitation" value="<?=h((string)($payload['agitation']??''))?>" placeholder="Ex.: inversões suaves"></label>
      <label class="form-field student-span-2">Anotações<textarea name="notes" rows="2" maxlength="3000"><?=h((string)($payload['notes']??''))?></textarea></label>
      <div class="student-actions student-span-2">
        <button class="button button-primary" type="submit"><?=$step?'Salvar etapa':'Adicionar etapa'?></button>
        <?php if($step):?><a class="button button-secondary" href="<?=h(student_process_template_href($templateId,$testId))?>">Cancelar</a><?php endif;?>
      </div>
    </form>
    <?php
}

$db=database();$student=student_account_current($db);$next='/aluno/processamentos.php';
if(!$student){header('Location: /aluno/login.php?next='.rawurlencode($next),true,303);exit;}
$studentId=(int)$student['id'];student_tool_require($db,$studentId,'lab_timer');
$testId=(int)($_GET['test']??$_POST['test_id']??0);$test=$testId>0?student_test_for_student($db,$testId,$studentId):null;$intent=(string)($_GET['intent']??$_POST['intent']??'');if($intent!=='live')$intent='';
if($testId>0&&!$test){http_response_code(404);student_shell_start('Registro não encontrado',null,$student);?><div class="student-empty">Registro não encontrado.</div><?php student_shell_end();exit;}
$id=(int)($_GET['id']??$_POST['template_id']??0);$editStepId=(int)($_GET['step']??0);$error='';
$notice=(string)($_SESSION['student_process_template_notice']??'');unset($_SESSION['student_process_template_notice']);

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-templates',$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{
        $action=(string)($_POST['action']??'');
        if($action==='create'){
            $template=student_process_template_create($db,$studentId,$_POST);$id=(int)$template['id'];
            $_SESSION['student_process_template_notice']='Processamento criado. Agora monte a sequência de etapas.';
        }elseif($action==='copy_standard'){
            $template=student_process_standard_copy($db,$studentId,(string)($_POST['standard_key']??''));$id=(int)$template['id'];
            if($testId>0){
                student_process_plan_apply_template($db,$id,$testId,$studentId);
                $_SESSION['student_process_notice']='Roteiro associado ao registro. Nenhuma execução foi iniciada.';
                $target=$intent==='live'?'/aluno/processar.php?test='.$testId.'&intent=live':'/aluno/teste.php?id='.$testId.'&view=process';
                header('Location: '.$target,true,303);exit;
            }
            $_SESSION['student_process_template_notice']='Padrão adicionado aos seus processamentos. Você pode editar a cópia sem alterar o padrão do workshop.';
        }elseif($action==='save'){
            $id=(int)($_POST['template_id']??0);student_process_template_update($db,$id,$studentId,$_POST);$_SESSION['student_process_template_notice']='Nome e descrição salvos.';
        }elseif($action==='duplicate'){
            $copy=student_process_template_duplicate($db,(int)($_POST['template_id']??0),$studentId);$id=(int)$copy['id'];$_SESSION['student_process_template_notice']='Cópia criada. Edite o nome ou as etapas como quiser.';
        }elseif($action==='add_step'){
            $id=(int)($_POST['template_id']??0);student_process_template_add_step($db,$id,$studentId,$_POST);$_SESSION['student_process_template_notice']='Etapa adicionada ao roteiro.';
        }elseif($action==='update_step'){
            $id=(int)($_POST['template_id']??0);student_process_template_update_step($db,$id,(int)($_POST['step_id']??0),$studentId,$_POST);$_SESSION['student_process_template_notice']='Etapa atualizada.';
        }elseif($action==='delete_step'){
            $id=(int)($_POST['template_id']??0);student_process_template_delete_step($db,$id,(int)($_POST['step_id']??0),$studentId);$_SESSION['student_process_template_notice']='Etapa removida do roteiro.';
        }elseif($action==='move_step'){
            $id=(int)($_POST['template_id']??0);student_process_template_move_step($db,$id,(int)($_POST['step_id']??0),$studentId,(int)($_POST['direction']??1));$_SESSION['student_process_template_notice']='Ordem das etapas atualizada.';
        }elseif($action==='delete'){
            $deleteId=(int)($_POST['template_id']??0);student_process_template_delete($db,$deleteId,$studentId);$id=0;$_SESSION['student_process_template_notice']='Processamento excluído.';
        }elseif($action==='apply_template'){
            $id=(int)($_POST['template_id']??0);if($testId<1)throw new RuntimeException('Escolha um registro do Caderno.');
            student_process_plan_apply_template($db,$id,$testId,$studentId);
            $_SESSION['student_process_notice']='Roteiro associado ao registro. Nenhuma execução foi iniciada.';
            $target=$intent==='live'?'/aluno/processar.php?test='.$testId.'&intent=live':'/aluno/teste.php?id='.$testId.'&view=process';
            header('Location: '.$target,true,303);exit;
        }else throw new RuntimeException('Ação inválida.');
        $url=$id>0?student_process_template_href($id,$testId,0,$intent):'/aluno/processamentos.php'.($testId>0?'?test='.$testId.($intent==='live'?'&intent=live':''):'');header('Location: '.$url,true,303);exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}

$templates=student_process_templates($db,$studentId);$template=$id>0?student_process_template_for_student($db,$id,$studentId):null;
if($id>0&&!$template){$id=0;$error=$error?:'Processamento não encontrado.';}
$steps=$template?student_process_template_steps($db,(int)$template['id']):[];
$editStep=$template&&$editStepId>0?student_process_template_step_for_student($db,(int)$template['id'],$editStepId,$studentId):null;
if($editStepId>0&&!$editStep)$error=$error?:'Etapa não encontrada.';
$catalog=student_process_managed_stage_catalog($db,false);$developers=student_process_managed_developer_catalog($db,false);$preparations=student_saved_preparations($db,$studentId);$standards=student_process_standard_catalog();$csrf=csrf_token('student-process-templates');
student_shell_start('Processamentos',null,$student);
?>
<header class="student-page-heading student-process-library-heading">
  <div>
    <p class="student-kicker"><?=$test&&$intent==='live'?'Modo laboratório':'Laboratório'?></p>
    <h1 class="student-title"><?=$test&&$intent==='live'?'Escolha o roteiro':'Processamentos'?></h1>
    <?php if($test&&$intent==='live'):?>
      <p class="student-process-context">Escolha o roteiro que você vai usar agora em <strong><?=h((string)$test['title'])?></strong>.</p>
    <?php elseif($test):?>
      <p class="student-process-context">Escolha o roteiro para <strong><?=h((string)$test['title'])?></strong>.</p>
    <?php elseif(!$template):?>
      <p class="student-process-context">Salve e reutilize as sequências de processamento que você usa no laboratório.</p>
    <?php else:?>
      <p class="student-process-context">Edite este roteiro sem alterar cópias já registradas no Caderno.</p>
    <?php endif;?>
  </div>
  <?php if($template):?><a class="button button-secondary" href="/aluno/processamentos.php<?=$testId>0?'?test='.$testId.($intent==='live'?'&intent=live':''):''?>">← Meus processamentos</a><?php endif;?>
</header>
<?php if($notice!==''):?><p class="ui-alert ui-alert-notice"><?=h($notice)?></p><?php endif;?>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>

<?php if(!$template):?>
<section class="student-process-section" aria-labelledby="student-process-owned-title">
  <div class="student-process-section-heading">
    <div><p class="student-kicker">Sua biblioteca</p><h2 class="student-subtitle" id="student-process-owned-title">Meus processamentos</h2></div>
    <?php if(!$test):?><p>Use um roteiro salvo como está ou abra para ajustar etapas, tempos e observações.</p><?php endif;?>
  </div>
  <?php if(!$templates):?>
    <div class="student-process-empty">
      <strong>Você ainda não tem um processamento salvo.</strong>
      <p>Escolha abaixo um dos padrões do workshop para começar.</p>
      <a class="button button-primary button-compact" href="#padroes-workshop">Ver padrões do workshop</a>
    </div>
  <?php else:?><div class="student-process-library" aria-label="Processamentos salvos">
    <?php foreach($templates as $item):$href=student_process_template_href((int)$item['id'],$testId,0,$intent);$hasSteps=(int)$item['step_count']>0;?>
      <article class="student-process-template-card">
        <a class="student-process-template-main" href="<?=h($href)?>">
          <span class="student-process-step-number"><?=str_pad((string)(int)$item['step_count'],2,'0',STR_PAD_LEFT)?></span>
          <div><h3><?=h((string)$item['name'])?></h3><p><?=h((string)($item['description']!==''?$item['description']:'Roteiro pessoal.'))?></p><div class="student-process-summary"><span><?=(int)$item['step_count']?> <?=((int)$item['step_count']===1?'etapa':'etapas')?></span><span><?=h(student_process_template_duration_summary($db,(int)$item['id']))?></span></div></div>
        </a>
        <div class="student-process-template-actions">
          <?php if($testId>0):?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="apply_template"><input type="hidden" name="template_id" value="<?=(int)$item['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="intent" value="<?=h($intent)?>"><button class="button button-primary button-compact" type="submit"<?=$hasSteps?'':' disabled'?>><?=$intent==='live'?'Usar no laboratório':'Associar ao registro'?></button></form>
          <?php elseif($hasSteps):?><a class="button button-primary button-compact" href="/aluno/processar.php?template=<?=(int)$item['id']?>">Iniciar no laboratório</a><?php else:?><span class="button button-primary button-compact is-disabled" aria-disabled="true">Adicione uma etapa</span><?php endif;?>
          <?php if($testId===0):?><a class="button button-secondary button-compact" href="<?=h($href)?>">Editar</a>
          <form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="duplicate"><input type="hidden" name="template_id" value="<?=(int)$item['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><button class="button button-secondary button-compact" type="submit">Duplicar</button></form>
          <form method="post" onsubmit="return confirm('Excluir este processamento salvo? Os planos já associados ao Caderno não serão apagados.')"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="template_id" value="<?=(int)$item['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><button class="student-danger-action" type="submit">Excluir</button></form><?php endif;?>
        </div>
      </article>
    <?php endforeach;?>
  </div><?php endif;?>
</section>

<section class="student-process-section student-process-standards" id="padroes-workshop" aria-labelledby="student-process-standards-title">
  <div class="student-process-section-heading">
    <div><p class="student-kicker">Padrões do workshop</p><h2 class="student-subtitle" id="student-process-standards-title">Roteiros conhecidos</h2></div>
    <?php if(!$test):?><p>Escolha o EI e a rota de branqueamento. Nos quatro padrões, a segunda revelação reutiliza o mesmo banho de Parodinal da primeira.</p><?php endif;?>
  </div>
  <div class="student-process-standard-grid">
    <?php foreach($standards as $standardKey=>$standard):$ei=str_contains((string)$standardKey,'ei400')?'EI 400':'EI 200';$route=str_contains((string)$standardKey,'ferric-ammonia')?'FeCl₃ + amônia':'Peracética';?>
      <article class="student-process-standard-card">
        <div class="student-process-standard-meta"><span><?=h($ei)?></span><span>Parodinal</span><span><?=h($route)?></span></div>
        <h3><?=h((string)$standard['name'])?></h3>
        <?php if(!$test):?><p><?=h((string)$standard['description'])?></p><?php endif;?>
        <div class="student-process-standard-footer"><span><?=count((array)$standard['steps'])?> etapas · <?=h(student_process_standard_duration_summary((array)$standard['steps']))?></span>
          <form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="copy_standard"><input type="hidden" name="standard_key" value="<?=h((string)$standardKey)?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="intent" value="<?=h($intent)?>"><button class="button button-primary button-compact" type="submit"><?=$testId>0?($intent==='live'?'Usar no laboratório':'Associar ao registro'):'Adicionar aos meus'?></button></form>
        </div>
      </article>
    <?php endforeach;?>
  </div>
</section>

<details class="student-process-create">
  <summary><span><strong>Criar um processamento do zero</strong><small>Use esta opção quando o roteiro não parte de um padrão do workshop.</small></span><span aria-hidden="true">＋</span></summary>
  <form method="post" class="student-form-grid"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="create"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="intent" value="<?=h($intent)?>"><label class="form-field student-span-2">Nome<input name="name" required maxlength="160" placeholder="Ex.: Positivo direto — teste de branqueamento"></label><label class="form-field student-span-2">Descrição<textarea name="description" rows="2" maxlength="1000" placeholder="O que diferencia este roteiro?"></textarea></label><div class="student-actions student-span-2"><button class="button button-primary" type="submit">Criar e montar roteiro</button></div></form>
</details>

<?php else:?>
<section class="student-process-editor">
  <div class="student-process-editor-overview">
    <div><p class="student-kicker">Roteiro salvo</p><h2><?=h((string)$template['name'])?></h2><p><?=h((string)($template['description']!==''?$template['description']:'Sem descrição. Você pode adicionar uma para lembrar o objetivo deste roteiro.'))?></p></div>
    <div class="student-process-editor-stats"><span><strong><?=count($steps)?></strong><?=count($steps)===1?'etapa':'etapas'?></span><span><strong><?=h(student_process_template_duration_summary($db,(int)$template['id']))?></strong>tempo programado</span></div>
  </div>
  <div class="student-process-editor-actions">
    <?php if($steps):?><?php if($testId>0):?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="apply_template"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="intent" value="<?=h($intent)?>"><button class="button button-primary" type="submit"><?=$intent==='live'?'Usar no laboratório':'Associar ao registro'?></button></form><?php else:?><a class="button button-primary" href="/aluno/processar.php?template=<?=(int)$template['id']?>">Iniciar no laboratório</a><?php endif;?><?php endif;?>
    <?php if($testId===0):?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="duplicate"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><button class="button button-secondary" type="submit">Duplicar processamento</button></form><?php endif;?>
  </div>

  <details class="student-process-meta-panel">
    <summary>Editar nome e descrição</summary>
    <form method="post" class="student-form-grid"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="save"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="intent" value="<?=h($intent)?>"><label class="form-field student-span-2">Nome<input name="name" required maxlength="160" value="<?=h((string)$template['name'])?>"></label><label class="form-field student-span-2">Descrição<textarea name="description" rows="2" maxlength="1000"><?=h((string)$template['description'])?></textarea></label><div class="student-actions student-span-2"><button class="button button-primary" type="submit">Salvar</button></div></form>
  </details>

  <div class="student-process-editor-heading"><div><p class="student-kicker">Sequência</p><h2 class="student-subtitle">Etapas do processamento</h2><p>Revise a ordem e os parâmetros antes de usar o roteiro. Associá-lo a um registro não inicia a execução.</p></div></div>
  <?php if(!$steps):?><div class="student-process-empty"><strong>Este roteiro ainda está vazio.</strong><p>Adicione a primeira etapa abaixo. Para um processo já trabalhado no workshop, pode ser mais simples voltar e começar por um padrão.</p></div>
  <?php else:?><ol class="student-process-step-list">
    <?php foreach($steps as $index=>$step):$payload=student_process_json_array((string)$step['payload_json']);$reuse=(string)($payload['reuse_source_stage_key']??'');$isEditing=$editStep&&(int)$editStep['id']===(int)$step['id'];?>
      <li class="student-process-step-card<?=$isEditing?' is-editing':''?>">
        <span class="student-process-step-index"><?=str_pad((string)($index+1),2,'0',STR_PAD_LEFT)?></span>
        <div class="student-process-step-body">
          <h3><?=h((string)$step['label'])?></h3>
          <?php $bits=[];if((string)($payload['developer_name']??'')!=='')$bits[]=(string)$payload['developer_name'];if((string)($payload['temperature']??'')!=='')$bits[]=(string)$payload['temperature'];if((string)$step['duration']!=='')$bits[]='Tempo '.student_process_seconds_label(student_process_time_seconds((string)$step['duration']));if((string)$step['agitation_interval']!=='')$bits[]='Agitação '.student_process_seconds_label(student_process_time_seconds((string)$step['agitation_interval']));?>
          <p><?=h($bits?implode(' · ',$bits):'Sem temporização ou parâmetros adicionais.')?></p>
          <?php if($reuse==='first_development'):?><div class="student-process-reuse-note"><strong>Mesmo banho da 1ª revelação</strong><span>Reutilize a solução já preparada; não prepare outro revelador.</span></div><?php endif;?>
          <?php if((string)($payload['notes']??'')!==''&&$reuse!=='first_development'):?><small class="student-process-step-note"><?=h((string)$payload['notes'])?></small><?php endif;?>
        </div>
        <div class="student-process-step-actions">
          <a class="button button-secondary button-compact" href="<?=h(student_process_template_href((int)$template['id'],$testId,(int)$step['id'],$intent))?>#editar-etapa">Editar</a>
          <?php if($index>0):?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="move_step"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="intent" value="<?=h($intent)?>"><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><input type="hidden" name="direction" value="-1"><button class="student-link" type="submit">Subir</button></form><?php endif;?>
          <?php if($index<count($steps)-1):?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="move_step"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="intent" value="<?=h($intent)?>"><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><input type="hidden" name="direction" value="1"><button class="student-link" type="submit">Descer</button></form><?php endif;?>
          <form method="post" onsubmit="return confirm('Remover esta etapa do roteiro?')"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="delete_step"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="intent" value="<?=h($intent)?>"><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><button class="student-danger-action" type="submit">Remover</button></form>
        </div>
        <?php if($isEditing):?><div class="student-process-step-editor" id="editar-etapa"><div class="student-process-inline-help"><strong>Editar etapa</strong><span>Altere somente o que muda neste ponto do roteiro. A ordem continua preservada.</span></div><?php student_process_render_step_form($catalog,$developers,$preparations,$csrf,(int)$template['id'],$testId,$step);?></div><?php endif;?>
      </li>
    <?php endforeach;?>
  </ol><?php endif;?>

  <details class="student-process-add"<?=$steps?'':' open'?>>
    <summary><span><strong>Adicionar etapa</strong><small>Inclua um novo banho ou operação no fim da sequência.</small></span><span aria-hidden="true">＋</span></summary>
    <?php student_process_render_step_form($catalog,$developers,$preparations,$csrf,(int)$template['id'],$testId);?>
  </details>

  <section class="student-danger-zone student-process-danger-zone">
    <h2>Excluir processamento</h2>
    <p>Exclui este roteiro da sua biblioteca. Registros do Caderno que já receberam uma cópia do plano não serão alterados.</p>
    <form method="post" onsubmit="return confirm('Excluir este processamento salvo? Esta ação não pode ser desfeita.')"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="intent" value="<?=h($intent)?>"><button class="student-danger-action" type="submit">Excluir processamento</button></form>
  </section>
</section>
<?php endif;?>
<?php student_shell_end();