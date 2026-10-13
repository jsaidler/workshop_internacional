<?php
declare(strict_types=1);
function student_calibration_editor(?array $editing,string $error,string $csrf,array $draft=[]):void {
  $isEditing=$editing!==null;
  $data=$editing??[];
  if($error!==''&&$draft)foreach(['label','film','developer','preparation','temperature','prebath','white_time','notes'] as $key){
    if(isset($draft[$key])&&is_scalar($draft[$key]))$data[$key]=substr((string)$draft[$key],0,2000);
  }
?>
<div data-student-editor class="student-long-editor student-task-editor student-calibration-editor">
<div class="student-create-dialog-head">
  <div><p class="student-kicker"><?=$isEditing?'Referência existente':'Nova referência'?></p><h2><?=$isEditing?'Editar calibração':'Registrar calibração'?></h2></div>
  <a class="button button-secondary button-compact" href="/aluno/calibracao.php" data-student-editor-close>Fechar</a>
</div>
<?php if($error!==''):?><p class="ui-alert ui-alert-error student-long-editor-error" role="alert" tabindex="-1" data-student-editor-error-focus><?=h($error)?></p><?php endif;?>
<section class="student-tool-workspace">
<form method="post" action="/aluno/calibracao.php?<?=$isEditing?'editar='.(int)$editing['id']:'novo=1'?>" class="student-form-grid student-task-form" data-student-local-form data-local-refresh="calibrations-list" data-local-success="close-dialog">
<input type="hidden" name="_csrf" value="<?=h($csrf)?>">
<?php if($isEditing):?><input type="hidden" name="id" value="<?=(int)$data['id']?>"><?php endif;?>
<div class="student-task-focus student-span-2">
  <span class="student-task-eyebrow">Medição principal</span>
  <h3>Quanto tempo levou para formar o branco?</h3>
  <label class="form-field student-task-time">Tempo do branco<input name="white_time" required inputmode="text" placeholder="Ex.: 03:00" value="<?=h((string)($data['white_time']??''))?>" aria-describedby="student-white-time-help"></label>
  <p id="student-white-time-help">Registre o tempo observado no seu ensaio. Não é uma previsão automática.</p>
</div>
<label class="form-field student-span-2 student-task-name">Nome da referência<input name="label" required placeholder="Ex.: HR-U · Parodinal · 26 °C" value="<?=h((string)($data['label']??''))?>"></label>
<details class="student-parameter-disclosure student-span-2" data-calibration-conditions<?=$isEditing||$error!==''||array_filter(array_map('trim',array_map('strval',array_intersect_key($data,array_flip(['film','developer','preparation','temperature','prebath'])))))?' open':''?>>
  <summary><span><strong>Condições do ensaio</strong><small>Filme, revelador, diluição, temperatura e pré-banho</small></span><span class="student-disclosure-icon" aria-hidden="true"></span></summary>
  <div class="student-parameter-fields student-form-grid">
    <label class="form-field">Filme<input name="film" value="<?=h((string)($data['film']??''))?>"></label>
    <label class="form-field">Revelador<input name="developer" value="<?=h((string)($data['developer']??''))?>"></label>
    <label class="form-field">Preparo ou diluição<input name="preparation" value="<?=h((string)($data['preparation']??''))?>"></label>
    <label class="form-field">Temperatura<input name="temperature" value="<?=h((string)($data['temperature']??''))?>"></label>
    <label class="form-field student-span-2">Pré-banho<input name="prebath" value="<?=h((string)($data['prebath']??''))?>"></label>
  </div>
</details>
<details class="student-parameter-disclosure student-span-2"<?=trim((string)($data['notes']??''))!==''?' open':''?>>
  <summary><span><strong>Anotações</strong><small>Observações para consultar depois</small></span><span class="student-disclosure-icon" aria-hidden="true"></span></summary>
  <div class="student-parameter-fields"><label class="form-field"><textarea name="notes" rows="3" aria-label="Anotações da calibração"><?=h((string)($data['notes']??''))?></textarea></label></div>
</details>
<div class="student-actions student-span-2 student-task-actions"><button class="button button-primary" type="submit"><?=$isEditing?'Salvar alterações':'Salvar referência'?></button><a class="button button-secondary" href="/aluno/calibracao.php" data-student-editor-close>Cancelar</a></div>
</form>
</section>
</div>
<?php }
