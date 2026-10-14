<?php
declare(strict_types=1);
/** Only presentation; authentication, ownership, POST/CSRF validation and mutation belong to the original routes. */
function student_record_render_free_step_editor(array $step, int $testId, int $position, bool $locked, string $error): void {
?>
<header class="student-record-header student-step-editor-header"><div class="student-record-context"><a class="student-back" href="/aluno/teste.php?id=<?=$testId?>#processamento">← Processamento</a><span class="student-status">Etapa <?=str_pad((string)$position,2,'0',STR_PAD_LEFT)?></span></div><p class="student-kicker">Caderno</p><h1 class="student-title student-title-record"><?=h($stepName)?></h1><p class="student-lead">Corrija os dados desta etapa. Nenhuma outra etapa é alterada por isso.</p></header>
<?php if($error!==''):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="student-workflow-panel student-step-editor">
<form method="post" class="student-process-edit-form student-form-grid" data-ui-validate>
<input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$testId))?>"><input type="hidden" name="registro" value="<?=$testId?>"><input type="hidden" name="etapa" value="<?=$position?>"><input type="hidden" name="action" value="save">
<label class="form-field student-span-2">Nome da etapa<input name="label" value="<?=h((string)$step['label'])?>" required<?=$locked?' disabled':''?>></label>
<label class="form-field student-span-2">Químico ou solução<input name="chemical_name" value="<?=h((string)$step['chemical_name'])?>"<?=$locked?' disabled':''?>></label>
<label class="form-field">Tempo<input name="duration" value="<?=h((string)$step['duration'])?>"<?=$locked?' disabled':''?>></label><label class="form-field">Temperatura<input name="temperature" value="<?=h((string)$step['temperature'])?>"<?=$locked?' disabled':''?>></label>
<label class="form-field student-span-2">Agitação<input name="agitation" value="<?=h((string)$step['agitation'])?>"<?=$locked?' disabled':''?>></label><label class="form-field student-span-2">Anotações<textarea name="notes" rows="4" maxlength="3000"<?=$locked?' disabled':''?>><?=h((string)$step['notes'])?></textarea></label>
<?php if(!$locked):?><div class="student-sticky-action student-span-2"><button class="button button-primary" type="submit">Salvar alterações</button><a class="button button-secondary" href="/aluno/teste.php?id=<?=$testId?>#processamento">Cancelar</a></div><?php endif;?>
</form>
<?php if(!$locked):?><details class="student-step-danger"><summary>Remover esta etapa</summary><div><p>Remove somente esta anotação. As outras etapas e as movimentações de estoque permanecem como foram registradas.</p><form method="post" onsubmit="return confirm('Remover somente esta etapa?')"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-process-'.$testId))?>"><input type="hidden" name="registro" value="<?=$testId?>"><input type="hidden" name="etapa" value="<?=$position?>"><input type="hidden" name="action" value="delete"><button class="button button-danger" type="submit">Remover etapa</button></form></div></details><?php endif;?>
<div class="student-actions"><a class="button button-secondary" href="/aluno/inventario.php">Movimentar estoque</a></div>
</section>

<?php
}

function student_record_render_delete_confirmation(array $record, int $id, string $error): void {
?>
<div class="student-appbar"><a class="student-back" href="/aluno/teste.php?id=<?=$id?>">← Voltar ao registro</a></div>
<p class="student-kicker">Ação permanente</p>
<h1 class="student-title student-title-record">Excluir registro</h1>
<p class="student-lead student-lead-compact">Você está prestes a excluir <strong><?=h((string)$record['title'])?></strong>. Esta ação apaga o registro, todas as fotografias anexadas e toda a conversa associada.</p>
<?php if($error):?><p class="ui-alert ui-alert-error" role="alert"><?=h($error)?></p><?php endif;?>
<section class="student-danger-zone">
  <p>Essa exclusão não pode ser desfeita.</p>
  <form method="post"><input type="hidden" name="_csrf" value="<?=h(csrf_token('student-delete-test-'.$id))?>"><input type="hidden" name="id" value="<?=$id?>"><button class="button button-danger" type="submit">Excluir registro definitivamente</button></form>
</section>

<?php
}
