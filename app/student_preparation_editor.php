<?php
declare(strict_types=1);
/** Canonical long preparation editor for direct route and desktop contextual dialog. */
function student_preparation_editor(?array $editing,array $developers,array $ingredients,string $error,string $csrf,array $draft=[]):void {
  $isEditing=$editing!==null;
  $data=$editing??[];
  if($error!==''&&$draft)foreach(['label','developer_key','developer_name','developer_amount','water_amount','temperature','duration','agitation','coffee_amount','carbonate_amount','ascorbic_amount','fresh_volume'] as $key){
    if(isset($draft[$key])&&is_scalar($draft[$key]))$data[$key]=substr((string)$draft[$key],0,2000);
  }
  if($error!==''&&$draft){
    $ingredients=['café'=>(string)($draft['coffee_amount']??''),'carbonato'=>(string)($draft['carbonate_amount']??''),'ascórbico'=>(string)($draft['ascorbic_amount']??'')];
  }
  $ingredientAmount=static function(array $map,string $contains):string{
    foreach($map as $name=>$amount)if(str_contains($name,$contains))return $amount;
    return '';
  };
?>
<div data-student-editor class="student-long-editor student-task-editor student-preparation-editor">
<div class="student-create-dialog-head">
 <div><p class="student-kicker"><?=$isEditing?'Predefinição existente':'Nova predefinição'?></p><h2><?=$isEditing?'Editar predefinição':'Criar predefinição'?></h2></div>
 <a class="button button-secondary button-compact" href="/aluno/preparos.php" data-student-editor-close>Fechar</a>
</div>
<p class="student-editor-explainer"><strong>Você está salvando parâmetros, não preparando estoque.</strong> A predefinição guarda proporções e condições de uso, sem movimentar o Inventário.</p>
<?php if($error!==''):?><p class="ui-alert ui-alert-error student-long-editor-error" role="alert" tabindex="-1" data-student-editor-error-focus><?=h($error)?></p><?php endif;?>
<section class="student-tool-workspace">
<form method="post" action="/aluno/preparos.php?<?=$isEditing?'editar='.(int)$editing['id']:'novo=1'?>" class="student-form-grid student-task-form" data-saved-preparation-form data-student-local-form data-local-refresh="preparations-list" data-local-success="close-dialog">
 <input type="hidden" name="_csrf" value="<?=h($csrf)?>">
 <input type="hidden" name="action" value="<?=$isEditing?'update':'create'?>">
 <?php if($isEditing):?><input type="hidden" name="id" value="<?=(int)$data['id']?>"><?php endif;?>
 <div class="student-task-section-head student-span-2"><span class="student-task-eyebrow">01 · Identificação</span><h3>Qual revelador e como você o prepara?</h3></div>
 <label class="form-field student-span-2">Nome da predefinição<input name="label" required placeholder="Ex.: Parodinal 10/550 · EI 200" value="<?=h((string)($data['label']??''))?>"></label>
 <label class="form-field student-span-2">Revelador<select name="developer_key" data-developer-select><?php foreach($developers as $key=>$developer):?><option value="<?=h($key)?>" data-preparation-mode="<?=h((string)$developer['mode'])?>"<?=($data&&$data['developer_key']===$key)?' selected':''?>><?=h((string)$developer['label'])?></option><?php endforeach;?></select></label>
 <label class="form-field student-span-2" data-custom-developer<?=($data&&$data['developer_key']==='other')?'':' hidden'?>>Outro revelador<input name="developer_name" value="<?=($data&&$data['developer_key']==='other')?h((string)$data['developer_name']):''?>"></label>
 <div class="student-span-2 student-developer-mix student-task-mixture" data-developer-mix>
  <div class="student-task-mixture-label"><span>Proporção da solução</span><small>Informe os volumes em mililitros</small></div>
  <div class="student-form-grid">
   <label class="form-field">Revelador ou solução estoque (ml)<input type="number" min="0" step="0.1" inputmode="decimal" name="developer_amount" data-developer-amount value="<?=h((string)($data['developer_amount']??''))?>"></label>
   <label class="form-field">Água (ml)<input type="number" min="0" step="0.1" inputmode="decimal" name="water_amount" data-water-amount value="<?=h((string)($data['water_amount']??''))?>"></label>
   <output class="student-dilution-output student-span-2" data-dilution-output></output>
  </div>
 </div>
 <div class="student-span-2 student-caffenol-prep student-task-mixture" data-fresh-preparation hidden>
  <div class="student-task-mixture-label"><span>Ingredientes da solução</span><small>Quantidades e volume preparado</small></div>
  <div class="student-form-grid">
   <label class="form-field">Café (g)<input type="number" min="0" step="0.1" inputmode="decimal" name="coffee_amount" value="<?=h($ingredientAmount($ingredients,'café'))?>"></label>
   <label class="form-field">Carbonato (g)<input type="number" min="0" step="0.1" inputmode="decimal" name="carbonate_amount" value="<?=h($ingredientAmount($ingredients,'carbonato'))?>"></label>
   <label class="form-field">Ácido ascórbico (g)<input type="number" min="0" step="0.1" inputmode="decimal" name="ascorbic_amount" value="<?=h($ingredientAmount($ingredients,'ascórbico'))?>"></label>
   <label class="form-field">Volume preparado (ml)<input type="number" min="0" step="1" inputmode="numeric" name="fresh_volume" data-fresh-volume-input value="<?=h((string)($data['fresh_volume']??$data['developer_amount']??''))?>"></label>
  </div>
 </div>
 <div class="student-task-section-head student-span-2 student-task-section-divider"><span class="student-task-eyebrow">02 · Aplicação</span><h3>Em quais condições você utiliza?</h3></div>
 <label class="form-field">Temperatura<input name="temperature" placeholder="26 °C" value="<?=h((string)($data['temperature']??''))?>"></label>
 <label class="form-field">Tempo<input name="duration" placeholder="07:00" value="<?=h((string)($data['duration']??''))?>"></label>
 <details class="student-parameter-disclosure student-span-2"<?=trim((string)($data['agitation']??''))!==''?' open':''?>>
  <summary><span><strong>Agitação</strong><small>Descreva seu modo de agitação, se aplicável</small></span><span class="student-disclosure-icon" aria-hidden="true"></span></summary>
  <div class="student-parameter-fields"><label class="form-field">Agitação<input name="agitation" placeholder="Ex.: leve" value="<?=h((string)($data['agitation']??''))?>"></label></div>
 </details>
 <div class="student-actions student-span-2 student-task-actions"><button class="button button-primary" type="submit"><?=$isEditing?'Salvar alterações':'Salvar predefinição'?></button><a class="button button-secondary" href="/aluno/preparos.php" data-student-editor-close>Cancelar</a></div>
</form>
</section>
</div>
<?php }
