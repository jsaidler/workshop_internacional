<?php
declare(strict_types=1);
/** Single source for legacy record stage labels; no database access or fixture-only copies. */
function student_process_stage_catalog(): array {
    return [
        'first_development'=>['label'=>'Primeira revelação','type'=>'development'],
        'wash_after_first'=>['label'=>'Lavagem com água','type'=>'wash'],
        'stop_after_first'=>['label'=>'Banho interruptor','type'=>'chemical'],
        'fixer'=>['label'=>'Fixação / hipossulfito','type'=>'chemical'],
        'peracetic'=>['label'=>'Solução peroxiacética','type'=>'chemical'],
        'ferric'=>['label'=>'Cloreto férrico','type'=>'chemical'],
        'dichromate'=>['label'=>'Dicromato','type'=>'chemical'],
        'permanganate'=>['label'=>'Permanganato','type'=>'chemical'],
        'wash_after_bleach'=>['label'=>'Lavagem','type'=>'wash'],
        'ammonia'=>['label'=>'Banho de amônia','type'=>'chemical'],
        'wash_after_ammonia'=>['label'=>'Lavagem','type'=>'wash'],
        'clearing'=>['label'=>'Banho de limpeza','type'=>'chemical'],
        'wash_after_clearing'=>['label'=>'Lavagem','type'=>'wash'],
        'second_development'=>['label'=>'Segunda revelação','type'=>'development'],
        'final_wash'=>['label'=>'Lavagem final','type'=>'wash'],
        'dry'=>['label'=>'Secagem','type'=>'dry'],
        'custom'=>['label'=>'Outra etapa','type'=>'custom'],
    ];
}
