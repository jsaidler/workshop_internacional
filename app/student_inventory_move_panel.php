<?php
declare(strict_types=1);
/**
 * One operational renderer for inventory movements, used by the authenticated
 * list and by the browser audit. No client-side duplicate form template.
 */
function student_inventory_move_panel(array $item,string $csrf,array $draft=[],string $error=''): void {
    $id=(int)$item['id'];
    $failed=$error!==''&&(int)($draft['item_id']??0)===$id;
    $choice=(string)($failed?($draft['direction']??'out'):'out');
    $choices=['out'=>'Saída / consumo','in'=>'Entrada','adjust_up'=>'Correção — acrescentar','adjust_down'=>'Correção — retirar'];
    if(!isset($choices[$choice]))$choice='out';
    $amount=$failed&&is_scalar($draft['amount']??null)?substr((string)$draft['amount'],0,100):'';
    $note=$failed&&is_scalar($draft['note']??null)?substr((string)$draft['note'],0,500):'';
    $submittedUuid=$failed&&is_scalar($draft['movement_uuid']??null)?(string)$draft['movement_uuid']:'';
    $uuid=preg_match('/^[a-zA-Z0-9-]{16,80}$/D',$submittedUuid)?$submittedUuid:student_uuid();
    $titleId='inventory-move-title-'.$id;
    ?>
<button class="button button-secondary button-compact" type="button" data-inventory-move-open data-inventory-item-id="<?=$id?>" aria-haspopup="dialog" aria-controls="inventory-move-<?=$id?>">Movimentar estoque</button>
<dialog class="student-create-dialog student-inventory-move-dialog" id="inventory-move-<?=$id?>" data-inventory-move-dialog data-inventory-item-id="<?=$id?>" aria-labelledby="<?=$titleId?>"<?=$failed?' data-inventory-move-reopen':''?>>
  <div class="student-create-dialog-head student-inventory-move-head">
    <div><p class="student-kicker">Laboratório · Inventário</p><h2 id="<?=$titleId?>">Movimentar estoque</h2></div>
    <button type="button" class="button button-secondary button-compact" data-inventory-move-close>Fechar</button>
  </div>
  <div class="student-inventory-move-context"><strong><?=h((string)$item['name'])?></strong><span>Saldo disponível: <?=h(student_workbench_number((float)$item['quantity']).' '.(string)$item['unit'])?></span></div>
  <?php if($failed):?><p class="ui-alert ui-alert-error student-inventory-move-error" role="alert" tabindex="-1" data-inventory-move-error-focus><?=h($error)?></p><?php endif;?>
  <form method="post" class="student-form-grid" data-student-local-form data-local-refresh="inventory-stock inventory-history" data-local-success="inventory-move">
    <input type="hidden" name="_csrf" value="<?=h($csrf)?>">
    <input type="hidden" name="action" value="move">
    <input type="hidden" name="item_id" value="<?=$id?>">
    <input type="hidden" name="movement_uuid" value="<?=h($uuid)?>">
    <label class="form-field student-span-2">Movimento
      <select name="direction" required><?php foreach($choices as $key=>$text):?><option value="<?=h($key)?>"<?=$choice===$key?' selected':''?>><?=h($text)?></option><?php endforeach;?></select>
    </label>
    <label class="form-field student-span-2">Quantidade (<?=h((string)$item['unit'])?>)
      <input type="number" min="0.001" step="0.001" name="amount" inputmode="decimal" required value="<?=h($amount)?>">
    </label>
    <label class="form-field student-span-2">Motivo <span class="student-inventory-move-note-hint">(obrigatório nas correções)</span>
      <input name="note" maxlength="500" value="<?=h($note)?>" placeholder="Ex.: consumo fora do Caderno">
    </label>
    <div class="student-actions student-span-2">
      <button class="button button-primary" type="submit">Registrar movimentação</button>
      <button class="button button-secondary" type="button" data-inventory-move-close>Cancelar</button>
    </div>
  </form>
</dialog>
<?php
}
