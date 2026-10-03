<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/admin_shell.php';
security_headers();require_admin();
$db=database();$state=admin_activity_resolution($db);$admin=current_admin();$adminId=(int)($admin['id']??0);$processId=max(0,(int)($_GET['id']??$_POST['process_id']??0));

function admin_processes_url(int $id=0): string {return '/admin/processes.php'.($id>0?'?id='.$id:'');}
function admin_process_payload(array $step): array {$p=student_process_json_array((string)($step['payload_json']??'{}'));$p['stage_key']=(string)($step['stage_key']??'');$p['duration']=(string)($step['duration']??'');$p['agitation_interval']=(string)($step['agitation_interval']??'');return $p;}

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('admin-processes',$_POST['_csrf']??null)){http_response_code(403);exit('Invalid request');}
    $action=(string)($_POST['action']??'');
    try{
        if($action==='create'){$p=student_global_process_create($db,$adminId,$_POST);$processId=(int)$p['id'];$_SESSION['admin_process_notice']='Processo criado como rascunho.';}
        elseif($action==='create_draft'){student_global_process_ensure_draft($db,$processId,$adminId);$_SESSION['admin_process_notice']='Nova versão em rascunho criada.';}
        elseif($action==='save_meta'){student_global_process_update_draft($db,(int)$_POST['version_id'],$adminId,$_POST);$_SESSION['admin_process_notice']='Rascunho salvo.';}
        elseif($action==='add_step'){student_global_process_add_step($db,(int)$_POST['version_id'],$adminId,$_POST);$_SESSION['admin_process_notice']='Etapa adicionada.';}
        elseif($action==='update_step'){student_global_process_update_step($db,(int)$_POST['version_id'],(int)$_POST['step_id'],$adminId,$_POST);$_SESSION['admin_process_notice']='Etapa salva.';}
        elseif($action==='delete_step'){student_global_process_delete_step($db,(int)$_POST['version_id'],(int)$_POST['step_id'],$adminId);$_SESSION['admin_process_notice']='Etapa removida.';}
        elseif($action==='move_step'){student_global_process_move_step($db,(int)$_POST['version_id'],(int)$_POST['step_id'],$adminId,(int)$_POST['direction']);$_SESSION['admin_process_notice']='Ordem atualizada.';}
        elseif($action==='publish'){student_global_process_publish($db,(int)$_POST['version_id'],$adminId);$_SESSION['admin_process_notice']='Versão publicada. Planos já existentes não foram alterados.';}
        elseif($action==='archive'){student_global_process_archive($db,$processId,$adminId,true);$_SESSION['admin_process_notice']='Processo arquivado para novos usos; histórico preservado.';}
        elseif($action==='restore'){student_global_process_archive($db,$processId,$adminId,false);$_SESSION['admin_process_notice']='Processo reativado.';}
        else throw new RuntimeException('Ação inválida.');
    }catch(Throwable $e){$_SESSION['admin_process_notice']='Não foi possível concluir: '.$e->getMessage();}
    header('Location: '.admin_processes_url($processId),true,303);exit;
}

$processes=student_global_processes($db,true);$selected=$processId?student_global_process_by_id($db,$processId):null;$draft=$selected?student_global_process_draft($db,$processId):null;$active=$selected&&(int)($selected['active_version_id']??0)>0?student_global_process_version($db,(int)$selected['active_version_id']):null;$versions=$selected?student_global_process_versions($db,$processId):[];$draftSteps=$draft?student_global_process_version_steps($db,(int)$draft['id']):[];$activeSteps=$active?student_global_process_version_steps($db,(int)$active['id']):[];$stageCatalog=student_process_managed_stage_catalog($db,false);$developerCatalog=student_process_managed_developer_catalog($db,false);$notice=(string)($_SESSION['admin_process_notice']??'');unset($_SESSION['admin_process_notice']);$tone=str_starts_with($notice,'Não foi possível')?'error':'success';

admin_shell_start('processes','Processos globais',$state,['/assets/admin-processes.css']);?>
<?php if($notice!==''):?><div class="admin-notice <?=h($tone)?>" role="<?=$tone==='error'?'alert':'status'?>"><?=h($notice)?></div><?php endif;?>

<?php if(!$selected):?>
<div class="admin-list-summary"><span><?=count($processes)?> processo<?=count($processes)===1?'':'s'?></span><span>Processos publicados são versionados e não reescrevem registros antigos.</span></div>
<?php if($processes):?><div class="admin-table-scroll"><table class="admin-data-table admin-process-list-table"><thead><tr><th>Processo</th><th>Status</th><th>Versão</th><th>Uso</th><th></th></tr></thead><tbody><?php foreach($processes as $p):?><tr><td><div class="admin-table-primary"><strong><?=h((string)$p['name'])?></strong><span><?=h((string)$p['process_key'])?></span></div></td><td data-label="Status"><?=admin_badge((string)$p['status']==='archived'?'Arquivado':'Ativo',(string)$p['status']==='archived'?'muted':'good')?></td><td data-label="Versão"><?=(int)($p['active_version_number']??0)>0?'v'.(int)$p['active_version_number']:'—'?><?php if((int)$p['draft_count']>0):?> <?=admin_badge('rascunho','attention')?><?php endif;?></td><td data-label="Planos associados"><?=(int)$p['usage_count']?></td><td><a class="admin-button secondary admin-table-action" href="<?=h(admin_processes_url((int)$p['id']))?>">Gerenciar</a></td></tr><?php endforeach;?></tbody></table></div><?php else:?><div class="admin-empty">Nenhum processo global cadastrado.</div><?php endif;?>
<details class="admin-secondary-setup admin-process-create"><summary class="admin-button">Novo processo global</summary><section class="admin-card"><form class="admin-form-grid" method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('admin-processes'))?>"><input type="hidden" name="action" value="create"><label class="admin-form-span-2">Nome<input name="name" maxlength="180" required></label><label class="admin-form-span-2">Chave estável<input name="process_key" maxlength="120" placeholder="opcional"></label><label class="admin-form-span-2">Descrição<textarea name="description" rows="3"></textarea></label><div class="admin-form-actions"><button class="admin-button" type="submit">Criar rascunho</button></div></form></section></details>

<?php else:?>
<section class="admin-card"><header><div><a class="admin-back" href="/admin/processes.php">← Processos globais</a><p class="admin-kicker">Processo global</p><h2><?=h((string)$selected['name'])?></h2><p><?=h((string)$selected['description'])?></p><div class="admin-process-meta"><?=admin_badge((string)$selected['status']==='archived'?'Arquivado':'Ativo',(string)$selected['status']==='archived'?'muted':'good')?><?php if($active):?><span>Versão publicada v<?=(int)$active['version_number']?></span><?php else:?><span>Ainda não publicado</span><?php endif;?></div></div><div class="admin-card-actions"><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('admin-processes'))?>"><input type="hidden" name="process_id" value="<?=$processId?>"><input type="hidden" name="action" value="<?=(string)$selected['status']==='archived'?'restore':'archive'?>"><button class="admin-button secondary" type="submit"><?=(string)$selected['status']==='archived'?'Reativar':'Arquivar'?></button></form></div></header></section>

<?php if(!$draft):?>
<section class="admin-card"><header><div><p class="admin-kicker"><?=$active?'Alteração segura':'Configuração inicial'?></p><h2><?=$active?'Criar nova versão':'Criar primeiro rascunho'?></h2><p><?=$active?'A versão atual permanece imutável até que uma nova seja publicada.':'Monte o roteiro antes de publicar.'?></p></div><form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('admin-processes'))?>"><input type="hidden" name="action" value="create_draft"><input type="hidden" name="process_id" value="<?=$processId?>"><button class="admin-button" type="submit">Criar rascunho</button></form></header></section>
<?php else:?>
<section class="admin-card"><header><div><p class="admin-kicker">Rascunho · v<?=(int)$draft['version_number']?></p><h2>Dados da versão</h2></div></header><form class="admin-form-grid" method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('admin-processes'))?>"><input type="hidden" name="action" value="save_meta"><input type="hidden" name="process_id" value="<?=$processId?>"><input type="hidden" name="version_id" value="<?=(int)$draft['id']?>"><label class="admin-form-span-2">Nome<input name="name" value="<?=h((string)$draft['name'])?>" required></label><label class="admin-form-span-2">Descrição<textarea name="description" rows="3"><?=h((string)$draft['description'])?></textarea></label><label class="admin-form-span-2">O que mudou<textarea name="change_note" rows="2"><?=h((string)$draft['change_note'])?></textarea></label><div class="admin-form-actions"><button class="admin-button" type="submit">Salvar</button></div></form></section>

<section class="admin-card admin-process-steps"><header><div><p class="admin-kicker">Roteiro</p><h2><?=count($draftSteps)?> etapa<?=count($draftSteps)===1?'':'s'?></h2><p>Edite o rascunho. A versão publicada e os registros dos alunos não são alterados.</p></div></header>
<?php if(!$draftSteps):?><div class="admin-empty compact">Nenhuma etapa no rascunho.</div><?php endif;?>
<ol class="admin-process-step-list"><?php foreach($draftSteps as $i=>$step):$v=admin_process_payload($step);?><li class="admin-process-step"><div class="admin-process-step-summary"><span><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><div><strong><?=h((string)$step['label'])?></strong><small><?=h(implode(' · ',array_filter([(string)($v['developer_name']??''),(string)$step['duration'],(string)($v['temperature']??'')])))?></small></div><form class="admin-inline-actions" method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('admin-processes'))?>"><input type="hidden" name="action" value="move_step"><input type="hidden" name="process_id" value="<?=$processId?>"><input type="hidden" name="version_id" value="<?=(int)$draft['id']?>"><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><button class="admin-button secondary admin-table-action" name="direction" value="-1"<?=$i===0?' disabled':''?> aria-label="Mover etapa para cima">↑</button><button class="admin-button secondary admin-table-action" name="direction" value="1"<?=$i===count($draftSteps)-1?' disabled':''?> aria-label="Mover etapa para baixo">↓</button></form></div>
<details><summary>Editar</summary><form class="admin-form-grid admin-process-step-form" method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('admin-processes'))?>"><input type="hidden" name="action" value="update_step"><input type="hidden" name="process_id" value="<?=$processId?>"><input type="hidden" name="version_id" value="<?=(int)$draft['id']?>"><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><?php admin_process_step_fields($stageCatalog,$developerCatalog,$v);?><div class="admin-form-actions admin-form-span-2"><button class="admin-button" type="submit">Salvar etapa</button></div></form><form class="admin-process-delete" method="post" data-confirm="Remover esta etapa do rascunho?"><input type="hidden" name="_csrf" value="<?=h(csrf_token('admin-processes'))?>"><input type="hidden" name="action" value="delete_step"><input type="hidden" name="process_id" value="<?=$processId?>"><input type="hidden" name="version_id" value="<?=(int)$draft['id']?>"><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><button class="admin-button danger" type="submit">Remover etapa</button></form></details></li><?php endforeach;?></ol>
<details class="admin-secondary-setup admin-process-add"><summary class="admin-button secondary">Adicionar etapa</summary><form class="admin-form-grid admin-process-step-form" method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('admin-processes'))?>"><input type="hidden" name="action" value="add_step"><input type="hidden" name="process_id" value="<?=$processId?>"><input type="hidden" name="version_id" value="<?=(int)$draft['id']?>"><?php admin_process_step_fields($stageCatalog,$developerCatalog,[]);?><div class="admin-form-actions admin-form-span-2"><button class="admin-button" type="submit">Adicionar etapa</button></div></form></details></section>
<section class="admin-card"><header><div><p class="admin-kicker">Publicação</p><h2>Publicar v<?=(int)$draft['version_number']?></h2><p>Novos registros passarão a usar esta versão. Planos existentes continuam com o snapshot que já possuem.</p></div><form method="post" data-confirm="Publicar esta versão para novos usos?"><input type="hidden" name="_csrf" value="<?=h(csrf_token('admin-processes'))?>"><input type="hidden" name="action" value="publish"><input type="hidden" name="process_id" value="<?=$processId?>"><input type="hidden" name="version_id" value="<?=(int)$draft['id']?>"><button class="admin-button" type="submit"<?=!$draftSteps?' disabled':''?>>Publicar</button></form></header></section>
<?php endif;?>

<?php if($active):?><section class="admin-card admin-process-preview"><header><div><p class="admin-kicker">Em uso · v<?=(int)$active['version_number']?></p><h2>Roteiro publicado</h2></div></header><ol class="admin-process-published-list"><?php foreach($activeSteps as $i=>$step):$v=admin_process_payload($step);?><li><span><?=str_pad((string)($i+1),2,'0',STR_PAD_LEFT)?></span><div><strong><?=h((string)$step['label'])?></strong><small><?=h(implode(' · ',array_filter([(string)($v['developer_name']??''),(string)$step['duration'],(string)($v['temperature']??'')])))?></small></div></li><?php endforeach;?></ol></section><?php endif;?>
<section class="admin-card admin-process-history"><header><div><p class="admin-kicker">Histórico</p><h2>Versões</h2></div></header><div class="admin-table-scroll"><table class="admin-data-table admin-process-history-table"><thead><tr><th>Versão</th><th>Status</th><th>Publicada</th><th>Alteração</th></tr></thead><tbody><?php foreach($versions as $v):?><tr><td>v<?=(int)$v['version_number']?></td><td data-label="Status"><?=h((string)$v['status'])?></td><td data-label="Publicada"><?=h((string)($v['published_at']??'—'))?></td><td data-label="Alteração"><?=h((string)($v['change_note']?:'—'))?></td></tr><?php endforeach;?></tbody></table></div></section>
<?php endif;?>
<script>
(()=>{
  const stageForms=[...document.querySelectorAll('.admin-process-step-form')];
  const refresh=form=>{
    const stage=form.querySelector('select[name="stage_key"]');if(!stage)return;
    const selected=stage.selectedOptions[0];const type=selected?.dataset.stageType||'';const key=stage.value;
    const developer=form.querySelector('select[name="developer_key"]');const developerMode=developer?.selectedOptions[0]?.dataset.developerMode||'';
    form.querySelectorAll('[data-process-field="developer"]').forEach(node=>node.hidden=type!=='development');
    form.querySelectorAll('[data-process-field="developer-name"]').forEach(node=>node.hidden=type!=='development'||developer?.value!=='other');
    form.querySelectorAll('[data-process-field="fresh-volume"]').forEach(node=>node.hidden=type!=='development'||developerMode!=='fresh');
    form.querySelectorAll('[data-process-field="dilution-volume"]').forEach(node=>node.hidden=type!=='development'||developerMode==='fresh');
    form.querySelectorAll('[data-process-field="chemical"]').forEach(node=>node.hidden=!['chemical','custom'].includes(type));
    form.querySelectorAll('[data-process-field="reuse"]').forEach(node=>node.hidden=key!=='second_development');
    const agitationMode=form.querySelector('select[name="agitation_mode"]');
    form.querySelectorAll('[data-process-field="agitation-periodic"]').forEach(node=>node.hidden=agitationMode?.value!=='periodic');
  };
  stageForms.forEach(form=>{
    const stage=form.querySelector('select[name="stage_key"]'),developer=form.querySelector('select[name="developer_key"]'),agitationMode=form.querySelector('select[name="agitation_mode"]');
    stage?.addEventListener('change',()=>refresh(form));developer?.addEventListener('change',()=>refresh(form));agitationMode?.addEventListener('change',()=>refresh(form));refresh(form);
  });
})();
</script>
<?php admin_shell_end();

function admin_process_step_fields(array $stages,array $developers,array $v): void {
    $stageKey=(string)($v['stage_key']??'first_development');$developerKey=(string)($v['developer_key']??'');
    $profile=student_process_lab_agitation_profile([
        'payload_json'=>json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
        'agitation_interval'=>(string)($v['agitation_interval']??''),
    ]);?>
    <label class="admin-form-span-2">Etapa<select name="stage_key" required><?php foreach($stages as $key=>$stage):$enabled=(bool)($stage['enabled']??true);?><option value="<?=h($key)?>" data-stage-type="<?=h((string)($stage['type']??''))?>"<?=$key===$stageKey?' selected':''?><?=!$enabled&&$key!==$stageKey?' disabled':''?>><?=h((string)$stage['label'].(!$enabled?' · desativada':''))?></option><?php endforeach;?></select></label>
    <label data-process-field="developer">Revelador<select name="developer_key"><option value="">—</option><?php foreach($developers as $key=>$dev):$enabled=(bool)($dev['enabled']??true);?><option value="<?=h($key)?>" data-developer-mode="<?=h((string)($dev['mode']??''))?>"<?=$key===$developerKey?' selected':''?><?=!$enabled&&$key!==$developerKey?' disabled':''?>><?=h((string)$dev['label'].(!$enabled?' · desativado':''))?></option><?php endforeach;?></select></label>
    <label data-process-field="developer-name">Nome personalizado<input name="developer_name" value="<?=h((string)($v['developer_name']??''))?>"></label>
    <label data-process-field="dilution-volume">Revelador / estoque (ml)<input type="number" min="0" step="0.1" name="developer_amount" value="<?=h((string)($v['developer_amount']??''))?>"></label>
    <label data-process-field="dilution-volume">Água (ml)<input type="number" min="0" step="0.1" name="water_amount" value="<?=h((string)($v['water_amount']??''))?>"></label>
    <label data-process-field="fresh-volume">Volume fresco (ml)<input type="number" min="0" step="0.1" name="fresh_volume" value="<?=h((string)($v['fresh_volume']??''))?>"></label>
    <label>Temperatura<input name="temperature" value="<?=h((string)($v['temperature']??''))?>"></label>
    <label>Tempo<input name="duration" value="<?=h((string)($v['duration']??''))?>" placeholder="07:00"></label>
    <label class="admin-form-span-2">Controle de agitação<select name="agitation_mode"><option value="none"<?=$profile['mode']==='none'?' selected':''?>>Sem temporização</option><option value="periodic"<?=$profile['mode']==='periodic'?' selected':''?>>Periódica</option><option value="continuous"<?=$profile['mode']==='continuous'?' selected':''?>>Contínua</option></select></label>
    <div class="admin-form-span-2 admin-form-grid" data-process-field="agitation-periodic"<?=$profile['mode']==='periodic'?'':' hidden'?>>
      <label>Duração de cada agitação<input name="agitation_duration" value="<?=h((string)$profile['duration'])?>" placeholder="00:10"></label>
      <label>Intervalo entre inícios<input name="agitation_interval" value="<?=h((string)$profile['interval'])?>" placeholder="01:00"></label>
    </div>
    <label class="admin-form-span-2">Observação de agitação<input name="agitation" value="<?=h((string)($v['agitation']??''))?>" placeholder="Ex.: inversões suaves"></label>
    <label class="admin-form-span-2" data-process-field="chemical">Químico / nome livre<input name="chemical_name" value="<?=h((string)($v['chemical_name']??''))?>"></label>
    <label data-process-field="reuse">Reutilizar banho<select name="reuse_source_stage_key"><option value="">Não</option><option value="first_development"<?=((string)($v['reuse_source_stage_key']??'')==='first_development')?' selected':''?>>Primeira revelação</option></select></label>
    <label class="admin-form-span-2">Notas<textarea name="notes" rows="3"><?=h((string)($v['notes']??''))?></textarea></label>
<?php }
