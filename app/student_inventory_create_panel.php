<?php
declare(strict_types=1);
/**
 * Shared actual inventory creation form. Error recovery uses the same renderer as
 * the student route, and never treats a failed POST as a completed stock entry.
 */
function student_inventory_create_panel(array $draft,string $error,string $csrf): void {
    $value=static function(string $key,string $default='') use($draft):string {
        $raw=$draft[$key]??$default;
        return is_scalar($raw)?substr((string)$raw,0,2000):$default;
    };
    $kind=$value('item_kind','raw')==='solution'?'solution':'raw';
    $unit=in_array($value('unit','ml'),['ml','l','g','kg','un'],true)?$value('unit','ml'):'ml';
    $failed=$error!=='';
    ?>
<section class="student-section" id="student-inventory-new" data-inventory-new-panel<?=$failed?'':' hidden'?>>
  <div class="student-stock-section-heading"><div><p class="student-kicker">Entrada</p><h2 class="student-subtitle">Novo item</h2></div><p>Escolha <strong>Insumo</strong> para um produto armazenado. Use <strong>Solução preparada</strong> somente quando o banho já existir fisicamente.</p></div>
  <?php if($failed):?><p class="ui-alert ui-alert-error student-inventory-create-error" role="alert" tabindex="-1" data-inventory-error-focus><?=h($error)?></p><?php endif;?>
  <form method="post" class="student-form-grid" data-ui-validate data-student-local-form data-local-refresh="inventory-stock inventory-history" data-local-success="reset">
    <input type="hidden" name="_csrf" value="<?=h($csrf)?>">
    <input type="hidden" name="action" value="create">
    <fieldset class="choice-field student-span-2"><legend>O que está entrando no estoque?</legend><div class="choice-row">
      <label class="choice-option"><input type="radio" name="item_kind" value="raw"<?=$kind==='raw'?' checked':''?>><span>Insumo</span></label>
      <label class="choice-option"><input type="radio" name="item_kind" value="solution"<?=$kind==='solution'?' checked':''?>><span>Solução preparada</span></label>
    </div></fieldset>
    <label class="form-field student-span-2">Nome<input name="name" required value="<?=h($value('name'))?>"></label>
    <label class="form-field">Quantidade disponível<input type="number" min="0" step="0.001" name="quantity" value="<?=h($value('quantity','0'))?>"></label>
    <label class="form-field">Unidade<select name="unit"><?php foreach(['ml','l','g','kg','un'] as $option):?><option value="<?=h($option)?>"<?=$unit===$option?' selected':''?>><?=h($option)?></option><?php endforeach;?></select></label>
    <label class="form-field">Categoria<input name="category" value="<?=h($value('category'))?>"></label>
    <label class="form-field">Lote ou identificação<input name="lot_code" value="<?=h($value('lot_code'))?>"></label>
    <label class="form-field">Data de entrada<input type="date" name="acquired_or_prepared_at" value="<?=h($value('acquired_or_prepared_at'))?>"></label>
    <label class="form-field">Validade<input type="date" name="expires_at" value="<?=h($value('expires_at'))?>"></label>
    <label class="form-field">Avisar estoque baixo em<input type="number" min="0" step="0.001" name="minimum_quantity" value="<?=h($value('minimum_quantity'))?>"></label>
    <label class="form-field">Onde está guardado?<input name="location" value="<?=h($value('location'))?>"></label>
    <details class="student-optional-fields student-span-2"<?=$failed&&trim($value('notes'))!==''?' open':''?>><summary>Anotações</summary><label class="form-field"><textarea name="notes" rows="3" aria-label="Anotações do item"><?=h($value('notes'))?></textarea></label></details>
    <div class="student-actions student-span-2"><button class="button button-primary" type="submit">Adicionar ao inventário</button><button class="button button-secondary" type="button" data-inventory-new-cancel>Cancelar</button></div>
  </form>
</section>
<?php
}
