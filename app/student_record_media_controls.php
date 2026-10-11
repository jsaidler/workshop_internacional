<?php
declare(strict_types=1);
/**
 * Transient media chooser, never a replacement for the record's exposure/result
 * form. Both inputs belong to that form, preserving its CSRF token and phase.
 * Call only for mutable records with available media capacity.
 */
function student_record_media_controls(string $phase,int $remaining): string {
    if(!in_array($phase,['scene','result'],true))throw new InvalidArgumentException('Fase de imagem inválida.');
    if($remaining<1)return '';
    $id='student-image-sheet-'.$phase;
    $title=$phase==='scene'?'Referência da cena':'Imagem do resultado';
    $limit='Você pode adicionar até '.$remaining.' '.($remaining===1?'imagem':'imagens').' a este registro.';
    return <<<HTML
<div class="student-capture-form" data-student-capture-controls>
  <button class="button button-secondary student-capture-trigger" type="button" data-student-sheet-open="$id" aria-haspopup="dialog" aria-controls="$id">Adicionar imagens</button>
  <dialog class="student-action-sheet" data-student-action-sheet id="$id" aria-labelledby="$id-title">
    <div class="student-action-sheet-content">
      <header class="student-action-sheet-heading">
        <div><p class="student-kicker">$title</p><h2 id="$id-title">Adicionar imagens</h2></div>
        <button class="student-action-sheet-close" type="button" data-student-sheet-close aria-label="Fechar opções de imagem">Fechar</button>
      </header>
      <p class="ui-alert ui-alert-error student-action-sheet-feedback" data-student-sheet-feedback role="alert" hidden></p>
      <div class="student-action-sheet-options">
        <label class="student-action-sheet-choice">
          <span>Escolher da galeria</span><small>Uma ou várias imagens</small>
          <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
        </label>
        <label class="student-action-sheet-choice">
          <span>Fotografar</span><small>Usar a câmera do aparelho</small>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp" capture="environment">
        </label>
      </div>
      <p class="student-action-sheet-help">$limit</p>
    </div>
  </dialog>
</div>
HTML;
}
