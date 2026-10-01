<?php
declare(strict_types=1);

function student_process_standard_catalog(): array {
    return [
        'positive-ferric-ammonia'=>[
            'name'=>'Positivo direto — FeCl₃ + amônia',
            'description'=>'Sequência-base do workshop com cloreto férrico seguido de amônia. Lavagens de 1 min e branqueamento de 1 min 30 s; revelações e banho de amônia permanecem sem tempo fixo.',
            'steps'=>[
                ['stage_key'=>'first_development','duration'=>''],
                ['stage_key'=>'wash_after_first','duration'=>'1:00'],
                ['stage_key'=>'ferric','duration'=>'1:30'],
                ['stage_key'=>'wash_after_bleach','duration'=>'1:00'],
                ['stage_key'=>'ammonia','duration'=>''],
                ['stage_key'=>'wash_after_ammonia','duration'=>'1:00'],
                ['stage_key'=>'second_development','duration'=>''],
                ['stage_key'=>'final_wash','duration'=>'1:00'],
                ['stage_key'=>'dry','duration'=>''],
            ],
        ],
        'positive-peracetic'=>[
            'name'=>'Positivo direto — peracética',
            'description'=>'Sequência-base do workshop com branqueamento peracético. Lavagens de 1 min e branqueamento de 1 min 30 s; as duas revelações permanecem sem tempo fixo.',
            'steps'=>[
                ['stage_key'=>'first_development','duration'=>''],
                ['stage_key'=>'wash_after_first','duration'=>'1:00'],
                ['stage_key'=>'peracetic','duration'=>'1:30'],
                ['stage_key'=>'wash_after_bleach','duration'=>'1:00'],
                ['stage_key'=>'second_development','duration'=>''],
                ['stage_key'=>'final_wash','duration'=>'1:00'],
                ['stage_key'=>'dry','duration'=>''],
            ],
        ],
    ];
}

function student_process_standard_developers(): array {
    $catalog=student_process_developer_catalog();$out=[];
    foreach(['parodinal','brewed-caffenol'] as $key)if(isset($catalog[$key]))$out[$key]=$catalog[$key];
    return $out;
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

function student_process_standard_copy(PDO $db,int $studentId,string $standardKey,string $developerKey): array {
    $standard=student_process_standard_for_key($standardKey)??throw new RuntimeException('Processamento padrão não encontrado.');
    $developers=student_process_standard_developers();
    if(!isset($developers[$developerKey]))throw new RuntimeException('Escolha um revelador disponível para o padrão.');
    $developer=$developers[$developerKey];
    $name=(string)$standard['name'].' · '.(string)$developer['label'];
    $description=(string)$standard['description'];

    $db->beginTransaction();
    try{
        $template=student_process_template_create($db,$studentId,['name'=>$name,'description'=>$description]);
        $templateId=(int)($template['id']??0);if($templateId<1)throw new RuntimeException('Não foi possível criar o processamento padrão.');
        foreach((array)$standard['steps'] as $step){
            $input=['stage_key'=>(string)$step['stage_key'],'duration'=>(string)($step['duration']??'')];
            if(in_array((string)$step['stage_key'],['first_development','second_development'],true))$input['developer_key']=$developerKey;
            student_process_template_add_step($db,$templateId,$studentId,$input);
        }
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_process_template_for_student($db,$templateId,$studentId)??[];
}
