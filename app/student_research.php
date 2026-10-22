<?php
declare(strict_types=1);

function student_research_schema_available(PDO $db): bool {
    try{
        $columns=[];
        foreach($db->query('PRAGMA table_info(student_tests)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)$column['name']]=true;
        return isset($columns['source_test_id'],$columns['research_intent']);
    }catch(Throwable){return false;}
}

function student_research_normalize(mixed $value): string {
    $text=trim((string)$value);
    $text=preg_replace('/\s+/u',' ',$text)??$text;
    return mb_strtolower($text);
}

function student_research_display(mixed $value): string {
    $text=trim((string)$value);
    $text=preg_replace('/\s+/u',' ',$text)??$text;
    return $text===''?'—':$text;
}

function student_research_exposure_fields(): array {
    return [
        'test_date'=>'Data',
        'film'=>'Filme',
        'lot'=>'Lote',
        'iso_reference'=>'EI',
        'aperture'=>'Abertura',
        'calculated_time'=>'Tempo sem reciprocidade',
        'reciprocity_time'=>'Tempo com reciprocidade',
        'light_condition'=>'Condição de luz',
        'tonal_range'=>'Faixa tonal e intenção',
    ];
}

function student_research_exposure_rows(array $a,array $b): array {
    $rows=[];
    foreach(student_research_exposure_fields() as $key=>$label){
        $av=student_research_display($a[$key]??'');$bv=student_research_display($b[$key]??'');
        $rows[]=['group'=>'Exposição','label'=>$label,'a'=>$av,'b'=>$bv,'different'=>student_research_normalize($a[$key]??'')!==student_research_normalize($b[$key]??'')];
    }
    return $rows;
}

function student_research_step_name(?array $step): string {
    if(!$step)return '—';
    $chemical=trim((string)($step['chemical_name']??''));
    $label=trim((string)($step['label']??''));
    return $chemical!==''?$chemical:($label!==''?$label:'Etapa');
}

function student_research_step_value(?array $step,string $key): string {
    if(!$step)return '—';
    if($key==='name')return student_research_step_name($step);
    if($key==='volume'){
        $volume=trim((string)($step['total_volume']??''));
        if($volume==='')return '—';
        $unit=trim((string)($step['amount_unit']??''));
        return $volume.($unit!==''?' '.$unit:'');
    }
    return student_research_display($step[$key]??'');
}

function student_research_process_rows(array $stepsA,array $stepsB): array {
    $rows=[];$count=max(count($stepsA),count($stepsB));
    $attributes=[
        'name'=>'Etapa / químico',
        'calculated_dilution'=>'Diluição',
        'volume'=>'Volume total',
        'temperature'=>'Temperatura',
        'duration'=>'Tempo',
        'agitation'=>'Movimentação',
    ];
    for($i=0;$i<$count;$i++){
        $a=$stepsA[$i]??null;$b=$stepsB[$i]??null;$position=$i+1;
        foreach($attributes as $key=>$label){
            $av=student_research_step_value($a,$key);$bv=student_research_step_value($b,$key);
            if($key!=='name'&&$av==='—'&&$bv==='—')continue;
            $different=student_research_normalize($av)!==student_research_normalize($bv);
            $rows[]=['group'=>'Processamento','label'=>'Etapa '.$position.' · '.$label,'a'=>$av,'b'=>$bv,'different'=>$different,'position'=>$position,'attribute'=>$key];
        }
    }
    return $rows;
}

function student_research_difference_rows(array $a,array $b,array $stepsA,array $stepsB): array {
    return array_values(array_filter(array_merge(student_research_exposure_rows($a,$b),student_research_process_rows($stepsA,$stepsB)),static fn(array $row): bool=>(bool)$row['different']));
}

function student_research_source(PDO $db,array $record,int $studentId): ?array {
    if(!student_research_schema_available($db))return null;
    $sourceId=(int)($record['source_test_id']??0);if($sourceId<1)return null;
    return student_test_for_student($db,$sourceId,$studentId);
}

function student_research_fork(PDO $db,int $sourceId,int $studentId,string $intent): array {
    $source=student_test_for_student($db,$sourceId,$studentId)??throw new RuntimeException('Registro de origem não encontrado.');
    if(!student_research_schema_available($db))throw new RuntimeException('A continuidade da pesquisa ainda não está disponível neste banco.');
    $intent=student_workspace_text($intent,1200);if($intent==='')throw new RuntimeException('Descreva o que você pretende mudar na próxima tentativa.');
    $payload=[
        'title'=>'Variação de '.student_workspace_text((string)$source['title'],150),
        'test_date'=>'',
        'film'=>(string)($source['film']??''),
        'lot'=>(string)($source['lot']??''),
        'iso_reference'=>(string)($source['iso_reference']??''),
        'aperture'=>(string)($source['aperture']??''),
        'calculated_time'=>(string)($source['calculated_time']??''),
        'reciprocity_time'=>(string)($source['reciprocity_time']??''),
        'light_condition'=>(string)($source['light_condition']??''),
        'tonal_range'=>(string)($source['tonal_range']??''),
        'developer'=>'','dilution'=>'','temperature'=>'','development_time'=>'','agitation'=>'','notes'=>'',
        'context_scope'=>(string)($source['context_scope']??'course'),
        'context_cohort_id'=>(int)($source['context_cohort_id']??0),
    ];
    $record=student_process_create_record($db,$studentId,$payload);
    $db->prepare('UPDATE student_tests SET source_test_id=?,research_intent=?,updated_at=? WHERE id=? AND student_id=?')->execute([$sourceId,$intent,utc_now(),(int)$record['id'],$studentId]);
    return student_test_for_student($db,(int)$record['id'],$studentId)??throw new RuntimeException('Não foi possível criar a continuação da pesquisa.');
}
