<?php
/** Shared presentation for an operational, student-owned template. The route owns auth, CSRF, writes and redirects. */
declare(strict_types=1);
function student_process_render_template_editor(array $template,array $steps,?array $editStep,array $catalog,array $developers,array $preparations,string $csrf,int $testId,string $durationSummary): void {
?>
<section class="student-process-editor" data-operational-template-editor>
  <div class="student-process-editor-overview">
    <div><p class="student-kicker">Roteiro salvo</p><h2><?=h((string)$template['name'])?></h2><p><?=h((string)($template['description']!==''?$template['description']:'Sem descrição. Você pode adicionar uma para lembrar o objetivo deste roteiro.'))?></p></div>
    <div class="student-process-editor-stats"><span><strong><?=count($steps)?></strong><?=count($steps)===1?'etapa':'etapas'?></span><span><strong><?=h($durationSummary)?></strong>tempo programado</span></div>
  </div>
  <div class="student-process-editor-actions">
    <?php if($steps):?><?php if($testId>0):?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="apply_template"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><button class="button button-primary" type="submit">Associar ao registro</button></form><?php else:?><a class="button button-primary" href="/aluno/processar.php?template=<?=(int)$template['id']?>">Abrir no laboratório</a><?php endif;?><?php endif;?>
    <?php if($testId===0):?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="duplicate"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><button class="button button-secondary" type="submit">Duplicar processamento</button></form><?php endif;?>
  </div>

  <details class="student-process-meta-panel">
    <summary>Editar nome e descrição</summary>
    <form method="post" class="student-form-grid"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="save"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><label class="form-field student-span-2">Nome<input name="name" required maxlength="160" value="<?=h((string)$template['name'])?>"></label><label class="form-field student-span-2">Descrição<textarea name="description" rows="2" maxlength="1000"><?=h((string)$template['description'])?></textarea></label><div class="student-actions student-span-2"><button class="button button-primary" type="submit">Salvar</button></div></form>
  </details>

  <div class="student-process-editor-heading"><div><p class="student-kicker">Sequência</p><h2 class="student-subtitle">Etapas do processamento</h2><p>Revise a ordem e os parâmetros antes de usar o roteiro. Associá-lo a um registro não inicia a execução.</p></div></div>
  <?php if(!$steps):?><div class="student-process-empty"><strong>Este roteiro ainda está vazio.</strong><p>Adicione a primeira etapa abaixo. Para um processo já trabalhado no workshop, pode ser mais simples voltar e começar por um padrão.</p></div>
  <?php else:?><ol class="student-process-step-list">
    <?php foreach($steps as $index=>$step):$payload=student_process_json_array((string)$step['payload_json']);$reuse=(string)($payload['reuse_source_stage_key']??'');$isEditing=$editStep&&(int)$editStep['id']===(int)$step['id'];?>
      <li data-stage="<?=str_pad((string)($index+1),2,'0',STR_PAD_LEFT)?>" data-stage-key="<?=h((string)$step['stage_key'])?>" class="student-process-step-card<?=$isEditing?' is-editing':''?>">
        <span class="student-process-step-index"><?=str_pad((string)($index+1),2,'0',STR_PAD_LEFT)?></span>
        <div class="student-process-step-body">
          <h3><?=h((string)$step['label'])?></h3>
          <?php $bits=[];if((string)($payload['developer_name']??'')!=='')$bits[]=(string)$payload['developer_name'];if((string)($payload['temperature']??'')!=='')$bits[]=(string)$payload['temperature'];if((string)$step['duration']!=='')$bits[]='Tempo '.student_process_seconds_label(student_process_time_seconds((string)$step['duration']));if((string)$step['agitation_interval']!=='')$bits[]='Agitação '.student_process_seconds_label(student_process_time_seconds((string)$step['agitation_interval']));?>
          <p><?=h($bits?implode(' · ',$bits):'Sem temporização ou parâmetros adicionais.')?></p>
          <?php if($reuse==='first_development'):?><div class="student-process-reuse-note"><strong>Mesmo banho da 1ª revelação</strong><span>Reutilize a solução já preparada; não prepare outro revelador.</span></div><?php endif;?>
          <?php if((string)($payload['notes']??'')!==''&&$reuse!=='first_development'):?><small class="student-process-step-note"><?=h((string)$payload['notes'])?></small><?php endif;?>
        </div>
        <div class="student-process-step-actions">
          <a class="button button-secondary button-compact" href="<?=h(student_process_template_href((int)$template['id'],$testId,(int)$step['id']))?>#editar-etapa">Editar etapa</a>
          <details class="student-process-step-options">
            <summary><span>Mais opções</span><span class="student-process-card-chevron" aria-hidden="true"></span></summary>
            <div class="student-process-step-options-content">
          <?php if($index>0):?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="move_step"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><input type="hidden" name="direction" value="-1"><button class="student-link" type="submit">Subir</button></form><?php endif;?>
          <?php if($index<count($steps)-1):?><form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="move_step"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><input type="hidden" name="direction" value="1"><button class="student-link" type="submit">Descer</button></form><?php endif;?>
          <form method="post" onsubmit="return confirm('Remover esta etapa do roteiro?')"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="delete_step"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><button class="student-danger-action" type="submit">Remover</button></form>
            </div>
          </details>
        </div>
        <?php if($isEditing):?><div class="student-process-step-editor" id="editar-etapa"><div class="student-process-inline-help"><strong>Editar etapa</strong><span>Altere somente o que muda neste ponto do roteiro. A ordem continua preservada.</span></div><?php student_process_render_step_form($catalog,$developers,$preparations,$csrf,(int)$template['id'],$testId,$step);?></div><?php endif;?>
      </li>
    <?php endforeach;?>
  </ol><?php endif;?>

  <details class="student-process-add"<?=$steps?'':' open'?>>
    <summary><span><strong>Adicionar etapa</strong><small>Inclua um novo banho ou operação no fim da sequência.</small></span><span aria-hidden="true">＋</span></summary>
    <?php student_process_render_step_form($catalog,$developers,$preparations,$csrf,(int)$template['id'],$testId);?>
  </details>

  <details class="student-process-retire">
    <summary>Excluir este roteiro</summary>
    <section class="student-danger-zone student-process-danger-zone">
    <h2>Exclusão permanente</h2>
    <p>Exclui este roteiro da sua biblioteca. Registros do Caderno que já receberam uma cópia do plano não serão alterados.</p>
    <form method="post" onsubmit="return confirm('Excluir este processamento salvo? Esta ação não pode ser desfeita.')"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="template_id" value="<?=(int)$template['id']?>"><input type="hidden" name="test_id" value="<?=$testId?>"><button class="student-danger-action" type="submit">Excluir processamento</button></form>
    </section>
  </details>
</section>
<?php
}
