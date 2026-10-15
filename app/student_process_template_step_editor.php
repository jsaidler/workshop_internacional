<?php
declare(strict_types=1);

/** Operational renderer for template step editing; shared by the authenticated route and visual test. */
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
    $mixOpen=$developerKey==='other'||trim((string)($payload['developer_amount']??''))!==''||trim((string)($payload['water_amount']??''))!==''||trim((string)($payload['developer_name']??''))!=='';
    $extrasOpen=$profile['mode']!=='none'||trim((string)($payload['agitation']??''))!==''||trim((string)($payload['notes']??''))!=='';

    ?>
    <form method="post" class="student-form-grid student-process-step-form student-process-task-form" data-process-template-step-form>
      <input type="hidden" name="_csrf" value="<?=h($csrf)?>">
      <input type="hidden" name="action" value="<?=h($action)?>">
      <input type="hidden" name="template_id" value="<?=$templateId?>">
      <input type="hidden" name="test_id" value="<?=$testId?>">
      <?php if($step):?><input type="hidden" name="step_id" value="<?=(int)$step['id']?>"><?php endif;?>
      <label class="form-field student-span-2">Operação
        <select name="stage_key" data-process-template-stage>
          <?php foreach($catalog as $key=>$stage):?><option value="<?=h((string)$key)?>"<?=$stageKey===(string)$key?' selected':''?>><?=h((string)$stage['label'])?></option><?php endforeach;?>
        </select>
      </label>
      <label class="form-field student-span-2" data-process-template-custom<?=$stageKey==='custom'?'':' hidden'?>>Nome da etapa
        <input name="custom_label" maxlength="120" value="<?=h($customLabel)?>">
      </label>
      <div class="student-process-task-time student-span-2 student-form-grid">
        <label class="form-field">Tempo<input name="duration" value="<?=h((string)($step['duration']??''))?>" placeholder="07:00" inputmode="text"></label>
        <label class="form-field">Temperatura<input name="temperature" value="<?=h((string)($payload['temperature']??''))?>" placeholder="26 °C"></label>
      </div>
      <div class="student-span-2 student-form-grid student-process-task-development" data-process-template-development<?=in_array($stageKey,['first_development','second_development'],true)?'':' hidden'?>>
        <label class="form-field">Revelador
          <select name="developer_key" data-process-template-developer><?php foreach($developers as $key=>$developer):?><option value="<?=h($key)?>"<?=$developerKey===(string)$key?' selected':''?>><?=h((string)$developer['label'])?></option><?php endforeach;?></select>
        </label>
        <label class="form-field">Predefinição de revelação
          <select name="saved_preparation_id"><option value="">Nenhuma</option><?php foreach($preparations as $prep):?><option value="<?=(int)$prep['id']?>"<?=$savedPreparationId===(int)$prep['id']?' selected':''?>><?=h((string)$prep['label'])?></option><?php endforeach;?></select>
        </label>
        <details class="student-process-task-disclosure student-span-2"<?=$mixOpen?' open':''?>>
          <summary><span><strong>Composição da solução</strong><small>Volumes e revelador personalizado, quando necessário</small></span><span aria-hidden="true">⌄</span></summary>
          <div class="student-form-grid student-process-task-disclosure-body">
            <label class="form-field">Revelador ou solução estoque (ml)<input type="number" min="0" step="0.1" inputmode="decimal" name="developer_amount" value="<?=h((string)($payload['developer_amount']??''))?>"></label>
            <label class="form-field">Água (ml)<input type="number" min="0" step="0.1" inputmode="decimal" name="water_amount" value="<?=h((string)($payload['water_amount']??''))?>"></label>
            <label class="form-field student-span-2" data-process-template-other-developer<?=$developerKey==='other'?'':' hidden'?>>Outro revelador<input name="developer_name" value="<?=h((string)($payload['developer_name']??''))?>"></label>
          </div>
        </details>
      </div>
      <label class="student-process-reuse student-span-2" data-process-template-reuse<?=$stageKey==='second_development'?'':' hidden'?>>
        <input type="checkbox" name="reuse_source_stage_key" value="first_development"<?=$reuseSource==='first_development'?' checked':''?>>
        <span><strong>Reutilizar o banho da primeira revelação</strong><small>Use a mesma solução já preparada, sem duplicar o consumo.</small></span>
      </label>

      <details class="student-process-task-disclosure student-span-2"<?=$extrasOpen?' open':''?>>
        <summary><span><strong>Agitação e observações</strong><small>Temporização, instruções e notas de referência</small></span><span aria-hidden="true">⌄</span></summary>
        <div class="student-form-grid student-process-task-disclosure-body">
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
          <label class="form-field student-span-2">Anotações<textarea name="notes" rows="3" maxlength="3000"><?=h((string)($payload['notes']??''))?></textarea></label>
        </div>
      </details>
      <div class="student-actions student-span-2 student-process-task-actions">
        <button class="button button-primary" type="submit"><?=$step?'Salvar etapa':'Adicionar etapa'?></button>
        <?php if($step):?><a class="button button-secondary" href="<?=h(student_process_template_href($templateId,$testId))?>">Cancelar</a><?php endif;?>
      </div>
    </form>
    <?php
}
