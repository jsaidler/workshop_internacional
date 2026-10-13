<?php
declare(strict_types=1);

/**
 * One saved processing route in the student's library. Content is escaped,
 * mutations still POST to the authenticated parent route with CSRF.
 */
function student_process_saved_card(array $item,string $href,string $duration,string $csrf,int $testId):void {
    $id=(int)($item['id']??0);
    $stepCount=(int)($item['step_count']??0);
    $hasSteps=$stepCount>0;
    $name=(string)($item['name']??'');
    $description=trim((string)($item['description']??''))?:'Roteiro pessoal.';
?>
<article class="student-process-template-card" data-process-saved-card>
  <a class="student-process-template-main" href="<?=h($href)?>">
    <span class="student-process-step-number"><?=str_pad((string)$stepCount,2,'0',STR_PAD_LEFT)?></span>
    <div>
      <h3><?=h($name)?></h3>
      <p><?=h($description)?></p>
      <div class="student-process-summary"><span><?=$stepCount?> <?=$stepCount===1?'etapa':'etapas'?></span><span><?=h($duration)?></span></div>
    </div>
  </a>
  <div class="student-process-template-actions">
    <?php if($testId>0):?>
      <form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="apply_template"><input type="hidden" name="template_id" value="<?=$id?>"><input type="hidden" name="test_id" value="<?=$testId?>"><button class="button button-primary button-compact" type="submit"<?=$hasSteps?'':' disabled'?>>Associar ao registro</button></form>
    <?php elseif($hasSteps):?>
      <a class="button button-primary button-compact" href="/aluno/processar.php?template=<?=$id?>">Abrir no laboratório</a>
    <?php else:?>
      <span class="button button-primary button-compact is-disabled" aria-disabled="true">Adicione uma etapa</span>
    <?php endif;?>
    <?php if($testId===0):?>
      <details class="student-process-card-options" data-process-card-options>
        <summary aria-label="Mais ações para <?=h($name)?>"><span>Mais opções</span><span class="student-process-card-chevron" aria-hidden="true"></span></summary>
        <div class="student-process-card-options-content">
          <a class="button button-secondary button-compact" href="<?=h($href)?>">Editar roteiro</a>
          <form method="post"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="duplicate"><input type="hidden" name="template_id" value="<?=$id?>"><input type="hidden" name="test_id" value="<?=$testId?>"><button class="button button-secondary button-compact" type="submit">Duplicar</button></form>
          <form method="post" onsubmit="return confirm('Excluir este processamento salvo? Os planos já associados ao Caderno não serão apagados.')"><input type="hidden" name="_csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="template_id" value="<?=$id?>"><input type="hidden" name="test_id" value="<?=$testId?>"><button class="student-danger-action" type="submit">Excluir</button></form>
        </div>
      </details>
    <?php endif;?>
  </div>
</article>
<?php
}
