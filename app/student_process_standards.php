<?php
declare(strict_types=1);

/**
 * Public workshop standards are persisted, versioned domain data.
 * This compatibility layer keeps the student-facing API stable while removing
 * recipes and route definitions from PHP source code.
 */
function student_process_standard_catalog(): array {
    return student_global_process_catalog(database());
}

function student_process_standard_developers(): array {
    $catalog=student_process_managed_developer_catalog(database(),true);$out=[];
    foreach(['parodinal','brewed-caffenol'] as $key)if(isset($catalog[$key]))$out[$key]=$catalog[$key];
    return $out;
}

function student_process_standard_for_key(string $key): ?array {
    return student_global_process_for_key(database(),$key);
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
    $standard=student_global_process_for_key($db,$standardKey)??throw new RuntimeException('Processamento padrão não encontrado.');
    $name=(string)$standard['name'];$description=(string)$standard['description'];

    $db->beginTransaction();
    try{
        $template=student_process_template_create($db,$studentId,['name'=>$name,'description'=>$description]);
        $templateId=(int)($template['id']??0);if($templateId<1)throw new RuntimeException('Não foi possível criar o processamento padrão.');
        foreach((array)$standard['steps'] as $step){
            $input=['stage_key'=>(string)$step['stage_key'],'duration'=>(string)($step['duration']??'')];
            foreach(['developer_key','developer_name','developer_amount','water_amount','fresh_volume','temperature','agitation','agitation_interval','notes','reuse_source_stage_key','chemical_name'] as $field)if(array_key_exists($field,$step))$input[$field]=$step[$field];
            student_process_template_add_step($db,$templateId,$studentId,$input);
        }
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return student_process_template_for_student($db,$templateId,$studentId)??[];
}
