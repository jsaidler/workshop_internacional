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
            'preparation'=>'Aqueça a água e desligue na ebulição. Quando chegar a cerca de 60 °C, misture primeiro o paracetamol, depois o sulfito e, por último, o hidróxido aos poucos. Mantenha o recipiente fechado por 3 dias, agitando uma vez ao dia; depois filtre e guarde em vidro âmbar.',
            'source'=>'Pesquisa João Saidler · Químicos - Receitas',
            'warning'=>'Use proteção para olhos e pele ao manipular hidróxido de sódio.',
        ],
        'brewed-caffenol'=>[
            'label'=>'Brewed Caffenol',
            'preparation'=>'Ferva metade da água com o café por 5 minutos e deixe descansar por 10. Na outra parte de água, dissolva os demais ingredientes na ordem da receita. Una as duas partes através de filtro, mantendo a borra fora da solução, e complete o volume final.',
            'source'=>'Pesquisa João Saidler · Químicos - Receitas',
            'warning'=>'Preparo para uso imediato.',
        ],
        'peracetic'=>[
            'label'=>'Solução peroxiacética',
            'preparation'=>'A pesquisa registra a ordem de mistura como água, vinagre e peróxido, com maturação da solução entre 2 e 7 dias antes do uso.',
            'source'=>'Pesquisa João Saidler · Químicos - Receitas',
            'warning'=>'Peróxido concentrado exige proteção adequada, recipiente compatível e cuidado com respingos.',
        ],
        'ferric'=>[
            'label'=>'Cloreto férrico',
            'preparation'=>'Prepare apenas o volume de trabalho necessário usando a proporção indicada na ficha. Dissolva completamente e espere a solução estabilizar antes do uso.',
            'source'=>'Processo do workshop',
            'warning'=>'O cloreto férrico é corrosivo e mancha superfícies; use recipiente compatível e proteção para olhos e pele.',
        ],
        'ammonia'=>[
            'label'=>'Amônia',
            'preparation'=>'Prepare a diluição de trabalho indicada na ficha em local bem ventilado e mantenha o recipiente fechado quando não estiver em uso.',
            'source'=>'Processo do workshop',
            'warning'=>'Evite inalar vapores e nunca misture amônia com produtos clorados.',
        ],
    ];
}
