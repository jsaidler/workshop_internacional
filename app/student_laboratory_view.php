<?php
declare(strict_types=1);
/**
 * Operational Laboratory presentation. Only released tools from $byKey are rendered.
 * Calculations/recipe changes remain in student mechanics JS and domain services.
 */
?>
<div class="student-v2-laboratory" data-student-laboratory>
  <header class="student-v2-lab-heading">
    <h1 class="student-title">Laboratório</h1>
  </header>
  <?php if(!$tools):?>
    <section class="student-v2-lab-empty" data-lab-empty>
      <h2>Nenhuma ferramenta disponível</h2>
      <p>Os recursos aparecem aqui conforme forem liberados para sua conta.</p>
    </section>
  <?php else:?>
    <section class="student-v2-lab-section" aria-labelledby="student-lab-operacoes" data-lab-operations>
      <div class="student-v2-lab-section-title">
        <h2 id="student-lab-operacoes">Ferramentas</h2>
      </div>
      <div class="student-v2-lab-links" data-lab-links>
        <?php if(isset($byKey['lab_timer'])):?>
          <a class="student-v2-lab-link is-featured" href="/aluno/processamentos.php" data-lab-link="processamentos">
            <span class="student-v2-lab-link-icon" aria-hidden="true"><?=student_shell_nav_icon('laboratory')?></span>
            <span class="student-v2-lab-link-copy"><strong>Processamentos</strong><small>Roteiros, etapas e temporização</small></span>
            <span class="student-v2-lab-link-arrow" aria-hidden="true">↗</span>
          </a>
        <?php endif;?>
        <?php if(isset($byKey['lab_inventory'])):?>
          <a class="student-v2-lab-link" href="/aluno/inventario.php" data-lab-link="inventario">
            <span class="student-v2-lab-link-icon" aria-hidden="true"><?=student_shell_nav_icon('notebook')?></span>
            <span class="student-v2-lab-link-copy"><strong>Inventário</strong><small>Produtos, soluções e movimentações</small></span>
            <span class="student-v2-lab-link-arrow" aria-hidden="true">↗</span>
          </a>
        <?php endif;?>
        <a class="student-v2-lab-link" href="/aluno/preparos.php" data-lab-link="preparos">
          <span class="student-v2-lab-link-icon" aria-hidden="true"><?=student_shell_nav_icon('notebook')?></span>
          <span class="student-v2-lab-link-copy"><strong>Predefinições de revelação</strong><small>Fórmulas de trabalho salvas</small></span>
          <span class="student-v2-lab-link-arrow" aria-hidden="true">↗</span>
        </a>
        <?php if(isset($byKey['calibration'])):?>
          <a class="student-v2-lab-link" href="/aluno/calibracao.php" data-lab-link="calibracao">
            <span class="student-v2-lab-link-icon" aria-hidden="true"><?=student_shell_nav_icon('agenda')?></span>
            <span class="student-v2-lab-link-copy"><strong>Referências de calibração</strong><small>Medições e referências registradas</small></span>
            <span class="student-v2-lab-link-arrow" aria-hidden="true">↗</span>
          </a>
        <?php endif;?>
      </div>
    </section>
    <?php if(isset($byKey['reciprocity'])||isset($byKey['exposure'])||isset($byKey['solution_prep'])):?>
    <section class="student-v2-lab-section" aria-labelledby="student-lab-calculos" data-lab-calculations>
      <div class="student-v2-lab-section-title">
        <h2 id="student-lab-calculos">Cálculos e receitas</h2>
      </div>
      <div class="student-v2-lab-calculators">
      <?php if(isset($byKey['reciprocity'])):?>
        <details class="student-bench-tool student-v2-lab-calculator" id="reciprocidade" data-lab-calculator>
          <summary><span><strong>Reciprocidade</strong><small>Compensação de tempo de exposição</small></span><span class="student-v2-lab-expand" aria-hidden="true">+</span></summary>
          <div class="student-v2-lab-calculator-body" data-quick-reciprocity>
            <div class="student-form-grid">
              <label class="form-field">Tempo sem reciprocidade<input data-quick-reciprocity-source placeholder="4 s, 02:30 ou 00:00:04"></label>
              <label class="form-field">Tempo com reciprocidade<input data-quick-reciprocity-target readonly></label>
            </div>
          </div>
        </details>
      <?php endif;?>
      <?php if(isset($byKey['exposure'])):?>
        <details class="student-bench-tool student-v2-lab-calculator" id="exposicao" data-lab-calculator>
          <summary><span><strong>Exposição equivalente</strong><small>Recalcule abertura, EI e tempo</small></span><span class="student-v2-lab-expand" aria-hidden="true">+</span></summary>
          <div class="student-v2-lab-calculator-body" data-exposure-tool>
            <div class="student-form-grid">
              <label class="form-field">Tempo inicial<input type="number" min="0" step="0.001" value="1" data-exposure-time></label>
              <label class="form-field">Abertura inicial<input type="number" min="0.1" step="0.1" value="8" data-exposure-f-start></label>
              <label class="form-field">EI inicial<input type="number" min="1" step="1" value="100" data-exposure-ei-start></label>
              <label class="form-field">Abertura desejada<input type="number" min="0.1" step="0.1" value="8" data-exposure-f-target></label>
              <label class="form-field">EI desejado<input type="number" min="1" step="1" value="100" data-exposure-ei-target></label>
              <label class="form-field">Compensação (EV)<input type="number" step="0.1" value="0" data-exposure-ev></label>
            </div>
            <div class="student-tool-result"><span>Tempo equivalente</span><strong data-exposure-result>1 s</strong><small data-exposure-reciprocity></small></div>
          </div>
        </details>
      <?php endif;?>
      <?php if(isset($byKey['solution_prep'])):?>
        <details class="student-bench-tool student-v2-lab-calculator" id="receitas" data-lab-calculator>
          <summary><span><strong>Receitas</strong><small>Quantidades por volume e modo de preparo</small></span><span class="student-v2-lab-expand" aria-hidden="true">+</span></summary>
          <div class="student-v2-lab-calculator-body" data-solution-tool>
            <div class="student-form-grid">
              <label class="form-field">Receita<select data-solution-select>
                <?php foreach($formulas as $key=>$formula):?><option value="<?=h($key)?>"><?=h((string)$formula['label'])?></option><?php endforeach;?>
              </select></label>
              <label class="form-field">Volume desejado (ml)<input type="number" min="1" step="1" value="250" data-solution-volume></label>
            </div>
            <?php foreach($formulas as $key=>$formula):$note=$recipeNotes[$key]??null;?>
              <article class="student-solution-formula" data-solution-formula="<?=h($key)?>" data-base-volume="<?=h((string)$formula['base_volume'])?>"<?=$key!==array_key_first($formulas)?' hidden':''?>>
                <div class="student-record-context"><h3><?=h((string)$formula['label'])?></h3><?php if(!empty($formula['fresh'])):?><span class="student-status">Uso imediato</span><?php endif;?></div>
                <div class="student-solution-ingredients">
                  <?php foreach($formula['ingredients'] as $ingredient):?><div><span><?=h((string)$ingredient['name'])?></span><strong data-solution-amount="<?=h((string)$ingredient['amount'])?>" data-unit="<?=h((string)$ingredient['unit'])?>"><?=h((string)$ingredient['amount'].' '.$ingredient['unit'])?></strong></div><?php endforeach;?>
                </div>
                <?php if($note):?><div class="student-recipe-preparation"><strong>Modo de preparo</strong><p><?=h((string)$note['preparation'])?></p><?php if(!empty($note['warning'])):?><p class="student-recipe-warning"><strong>Segurança</strong> <?=h((string)$note['warning'])?></p><?php endif;?></div><?php endif;?>
              </article>
            <?php endforeach;?>
          </div>
        </details>
      <?php endif;?>
      </div>
    </section>
    <?php endif;?>
    <section class="student-v2-lab-end" aria-label="Organização do laboratório">
      <h2>Organização do laboratório</h2>
      <p>Os registros de exposição, processamento e resultado continuam no <a href="/aluno/caderno.php">Caderno</a>.</p>
    </section>
  <?php endif;?>
</div>
