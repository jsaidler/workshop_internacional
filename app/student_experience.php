<?php
declare(strict_types=1);

function student_experience_count(int $count,string $singular,string $plural): string {
    return $count.' '.($count===1?$singular:$plural);
}

function student_experience_process_complete(array $steps): bool {
    if(!$steps)return false;
    $last=$steps[array_key_last($steps)]??null;
    return is_array($last)&&(string)($last['stage_key']??'')==='dry';
}

function student_experience_next_choices(array $steps): array {
    if(student_experience_process_complete($steps))return [];
    return student_process_next_choices($steps);
}

function student_experience_process_view(array $record,array $steps): string {
    if(student_experience_process_complete($steps))return 'review';
    if($steps)return 'process';
    foreach(['film','lot','iso_reference','aperture','calculated_time','reciprocity_time','light_condition','tonal_range'] as $key){
        if(trim((string)($record[$key]??''))!=='')return 'process';
    }
    return 'exposure';
}

function student_experience_process_state(array $record,array $steps): array {
    if(student_experience_process_complete($steps))return ['label'=>'Processamento concluído','view'=>'review','action'=>'Registrar resultado'];
    if($steps){
        $last=$steps[array_key_last($steps)];
        $name=trim((string)($last['chemical_name']??''));
        if($name==='')$name=trim((string)($last['label']??''));
        return ['label'=>$name!==''?'Última etapa: '.$name:'Processamento em andamento','view'=>'process','action'=>'Continuar processamento'];
    }
    $view=student_experience_process_view($record,$steps);
    return $view==='process'
        ?['label'=>'Exposição registrada','view'=>'process','action'=>'Iniciar processamento']
        :['label'=>'Exposição ainda não registrada','view'=>'exposure','action'=>'Registrar exposição'];
}

function student_experience_recipe_notes(): array {
    return [
        'parodinal'=>[
            'label'=>'Parodinal',
            'preparation'=>'A ficha de preparo deve apresentar, junto das quantidades calculadas, a sequência de mistura, a maturação e o armazenamento descritos na pesquisa do curso.',
            'source'=>'Pesquisa João Saidler · Químicos - Receitas',
        ],
        'brewed-caffenol'=>[
            'label'=>'Brewed Caffenol',
            'preparation'=>'A ficha deve mostrar o preparo do café separado da solução dos demais ingredientes, a filtração e a complementação do volume final, conforme a pesquisa do curso.',
            'source'=>'Pesquisa João Saidler · Químicos - Receitas',
        ],
        'peracetic'=>[
            'label'=>'Solução peroxiacética',
            'preparation'=>'A ficha deve manter a ordem de mistura e o período de maturação registrados na pesquisa, com aviso de segurança visível antes do preparo.',
            'source'=>'Pesquisa João Saidler · Químicos - Receitas',
        ],
        'ferric'=>[
            'label'=>'Cloreto férrico',
            'preparation'=>'A ficha deve apresentar o preparo da solução de trabalho usada no processo do workshop, sem obrigar o aluno a procurar a explicação em outra página.',
            'source'=>'Processo do workshop',
        ],
        'ammonia'=>[
            'label'=>'Amônia',
            'preparation'=>'A ficha deve apresentar a diluição de trabalho usada no processo do workshop e um aviso de ventilação e incompatibilidades.',
            'source'=>'Processo do workshop',
        ],
    ];
}
