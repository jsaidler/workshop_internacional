<?php
declare(strict_types=1);

/**
 * Runtime access to administrable laboratory catalogs.
 *
 * Stable keys stay in historical snapshots even when an item is disabled. New
 * selection UIs should request only enabled entries; historical rendering and
 * editing can request all entries.
 */
function student_process_managed_table_exists(PDO $db,string $table): bool {
    $q=$db->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=? LIMIT 1");$q->execute([$table]);return (bool)$q->fetchColumn();
}

function student_process_managed_stage_catalog(?PDO $db=null,bool $onlyEnabled=true): array {
    $db=$db?:database();
    if(!student_process_managed_table_exists($db,'student_process_stage_catalog'))return student_process_stage_catalog();
    $sql='SELECT * FROM student_process_stage_catalog'.($onlyEnabled?' WHERE enabled=1':'').' ORDER BY sort_order,label COLLATE NOCASE,stage_key';
    $out=[];foreach($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row){
        $out[(string)$row['stage_key']]=[
            'label'=>(string)$row['label'],
            'type'=>(string)$row['stage_type'],
            'chemical_name'=>(string)$row['chemical_name'],
            'enabled'=>(int)$row['enabled']===1,
            'priority'=>(int)$row['sort_order'],
        ];
    }
    return $out;
}

function student_process_managed_developer_catalog(?PDO $db=null,bool $onlyEnabled=true): array {
    $db=$db?:database();
    if(!student_process_managed_table_exists($db,'student_process_developer_catalog'))return student_process_developer_catalog();
    $sql='SELECT * FROM student_process_developer_catalog'.($onlyEnabled?' WHERE enabled=1':'').' ORDER BY sort_order,label COLLATE NOCASE,developer_key';
    $out=[];foreach($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $row){
        $out[(string)$row['developer_key']]=[
            'label'=>(string)$row['label'],
            'mode'=>(string)$row['preparation_mode'],
            'storable'=>(int)$row['storable']===1,
            'enabled'=>(int)$row['enabled']===1,
            'priority'=>(int)$row['sort_order'],
        ];
    }
    return $out;
}

function student_process_managed_developer(string $key,string $customName='',?PDO $db=null,bool $allowDisabled=true): array {
    $catalog=student_process_managed_developer_catalog($db,!$allowDisabled);$key=trim($key);
    $entry=$catalog[$key]??null;
    if(!$entry){
        $all=student_process_managed_developer_catalog($db,false);$entry=$all['other']??['label'=>'Outro','mode'=>'custom','storable'=>true,'enabled'=>true,'priority'=>999];$key='other';
    }
    if($key==='other'&&trim($customName)!=='')$entry['label']=student_workspace_text($customName,180);
    return ['key'=>$key]+$entry;
}

function student_process_managed_stage(string $key,?PDO $db=null,bool $allowDisabled=true): ?array {
    $catalog=student_process_managed_stage_catalog($db,!$allowDisabled);return $catalog[trim($key)]??null;
}
