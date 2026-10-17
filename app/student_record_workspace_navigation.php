<?php
declare(strict_types=1);

/**
 * Three peer contexts in a photographic record, not workflow stages.
 * Native fragment links provide direct URLs and browser Back/Forward; JS only
 * progressively enhances presentation by showing one section at a time.
 */
function student_record_workspace_navigation(): void {
    $items=['exposicao'=>'Exposição','processamento'=>'Processamento','resultado'=>'Resultado'];
?>
<nav class="student-record-section-links" aria-label="Partes do registro" data-record-workspace-nav>
  <?php foreach($items as $target=>$label):?>
  <a class="student-record-workspace-link" href="#<?=h($target)?>" aria-controls="<?=h($target)?>" data-record-workspace-link><?=h($label)?></a>
  <?php endforeach;?>
</nav>
<?php
}
