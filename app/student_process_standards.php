<?php
declare(strict_types=1);

function student_process_standard_development(int $ei): array {
    $developerAmount=$ei===400?20:10;
    return [
        'developer_key'=>'parodinal',
        'developer_amount'=>(string)$developerAmount,
        'water_amount'=>(string)(550-$developerAmount),
        'temperature'=>'26 °C',
        'duration'=>'7:00',
        'agitation'=>'leve',
    ];
}

function student_process_standard_catalog(): array {
    $ei200=student_process_standard_development(200);
    $ei400=student_process_standard_development(400);
    return [
        'positive-ferric-ammonia-ei200'=>[
            'name'=>'Positivo direto — Parodinal EI 200 — FeCl₃ + amônia',
            'description'=>'Parodinal 10 ml + água até 550 ml, 26 °C, 7 min e agitação leve nas duas revelações. Lavagens de 1 min e cloreto férrico por 1 min 30 s.',
            'steps'=>[
                ['stage_key'=>'first_development']+$ei200,
                ['stage_key'=>'wash_after_first','duration'=>'1:00'],
                ['stage_key'=>'ferric','duration'=>'1:30'],
                ['stage_key'=>'wash_after_bleach','duration'=>'1:00'],
                ['stage_key'=>'ammonia','duration'=>''],
                ['stage_key'=>'wash_after_ammonia','duration'=>'1:00'],
                ['stage_key'=>'second_development']+$ei200,
                ['stage_key'=>'final_wash','duration'=>'1:00'],
                ['stage_key'=>'dry','duration'=>''],
            ],
        ],
        'positive-ferric-ammonia-ei400'=>[
            'name'=>'Positivo direto — Parodinal EI 400 — FeCl₃ + amônia',
            'description'=>'Parodinal 20 ml + água até 550 ml, 26 °C, 7 min e agitação leve nas duas revelações. Lavagens de 1 min e cloreto férrico por 1 min 30 s.',
            'steps'=>[
                ['stage_key'=>'first_development']+$ei400,
                ['stage_key'=>'wash_after_first','duration'=>'1:00'],
                ['stage_key'=>'ferric','duration'=>'1:30'],
                ['stage_key'=>'wash_after_bleach','duration'=>'1:00'],
                ['stage_key'=>'ammonia','duration'=>''],
                ['stage_key'=>'wash_after_ammonia','duration'=>'1:00'],
                ['stage_key'=>'second_development']+$ei400,
                ['stage_key'=>'final_wash','duration'=>'1:00'],
                ['stage_key'=>'dry','duration'=>''],
            ],
        ],
        'positive-peracetic-ei200'=>[
            'name'=>'Positivo direto — Parodinal EI 200 — peracética',
            'description'=>'Parodinal 10 ml + água até 550 ml, 26 °C, 7 min e agitação leve nas duas revelações. Lavagens de 1 min e branqueamento peracético por 1 min 30 s.',
            'steps'=>[
                ['stage_key'=>'first_development']+$ei200,
                ['stage_key'=>'wash_after_first','duration'=>'1:00'],
                ['stage_key'=>'peracetic','duration'=>'1:30'],
                ['stage_key'=>'wash_after_bleach','duration'=>'1:00'],
                ['stage_key'=>'second_development']+$ei200,
                ['stage_key'=>'final_wash','duration'=>'1:00'],
                ['stage_key'=>'dry','duration'=>''],
            ],
        ],
        'positive-peracetic-ei400'=>[
            'name'=>'Positivo direto — Parodinal EI 400 — peracética',
            'description'=>'Parodinal 20 ml + água até 550 ml, 26 °C, 7 min e agitação leve nas duas revelações. Lavagens de 1 min e branqueamento peracético por 1 min 30 s.',
            'steps'=>[
                ['stage_key'=>'first_development']+$ei400,
                ['stage_key'=>'wash_after_first','duration'=>'1:00'],
                ['stage_key'=>'peracetic','duration'=>'1:30'],
                ['stage_key'=>'wash_after_bleach','duration'=>'1:00'],
                ['stage_key'=>'second_development']+$ei400,
                ['stage_key'=>'final_wash','duration'=>'1:00'],
                ['stage_key'=>'dry','duration'=>''],
            ],
        ],
    ];
}

function student_process_standard_developers(): array {
    $catalog=student_process_developer_catalog();
    return isset($catalog['parodinal'])?['parodinal'=>$catalog['parodinal']]:[];
}

function student_process_standard_for_key(string $key): ?array {
    $catalog=student_process_standard_catalog();return $catalog[$key]??null;
}

function student_process_standard_duration_summary(array $steps): string {
    $total=0;$untimed=false;
    foreach($steps as $step){$raw=trim((string)($step['duration']??''));$seconds=student_process_time_seconds($raw);if($raw===''||$seconds===null){$untimed=true;continue;}$total+=$seconds;}
    if($untimed)return $total>0?student_process_seconds_label($total).' + etapas livres':'Etapas livres';
    return student_process_seconds_label($total);
}

function student_process_template_duration_summary(PDO $db,int $templateId): string {
    return student_process_standard_duration_summary(student_process_template_steps($db,$templateId));
}

function student_process_standard_copy(PDO $db,int $studentId,string $standardKey,string $developerKey=''): array {
    $standard=student_process_standard_for_key($standardKey)??throw new RuntimeException('Processamento padrão não encontrado.');
    $name=(string)$standard['name'];
    $description=(string)$standard['description'];

    $db->beginTransaction();
    try{
        $template=student_process_template_create($db,$studentId,['name'=>$name,'description'=>$description]);
        $templateId=(int)($template['id']??0);if($templateId<1)throw new RuntimeException('Não foi possível criar o processamento padrão.');
        foreach((array)$standard['steps'] as $step){
            $input=['stage_key'=>(string)$step['stage_key'],'duration'=>(string)($step['duration']??'')];
            foreach(['developer_key','developer_amount','water_amount','temperature','agitation','agitation_interval'] as $field)if(array_key_exists($field,$step))$input[$field]=$step[$field];
            student_process_template_add_step($db,$templateId,$studentId,$input);
        }
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_process_template_for_student($db,$templateId,$studentId)??[];
}
