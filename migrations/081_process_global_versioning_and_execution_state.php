<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_process_stage_catalog (
    stage_key TEXT PRIMARY KEY,
    label TEXT NOT NULL,
    stage_type TEXT NOT NULL,
    chemical_name TEXT NOT NULL DEFAULT '',
    enabled INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS student_process_developer_catalog (
    developer_key TEXT PRIMARY KEY,
    label TEXT NOT NULL,
    preparation_mode TEXT NOT NULL,
    storable INTEGER NOT NULL DEFAULT 1,
    enabled INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS student_global_processes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    process_uuid TEXT NOT NULL UNIQUE,
    process_key TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'active',
    active_version_id INTEGER NULL,
    created_by_admin_id INTEGER NULL,
    archived_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_global_processes_status ON student_global_processes(status,name);

CREATE TABLE IF NOT EXISTS student_global_process_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    version_uuid TEXT NOT NULL UNIQUE,
    process_id INTEGER NOT NULL,
    version_number INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft',
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    change_note TEXT NOT NULL DEFAULT '',
    created_by_admin_id INTEGER NULL,
    published_by_admin_id INTEGER NULL,
    published_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(process_id,version_number),
    FOREIGN KEY(process_id) REFERENCES student_global_processes(id) ON DELETE CASCADE,
    FOREIGN KEY(created_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY(published_by_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_global_process_versions_process ON student_global_process_versions(process_id,version_number DESC);
CREATE INDEX IF NOT EXISTS idx_student_global_process_versions_status ON student_global_process_versions(status,published_at DESC);

CREATE TABLE IF NOT EXISTS student_global_process_version_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    version_id INTEGER NOT NULL,
    position INTEGER NOT NULL DEFAULT 0,
    stage_key TEXT NOT NULL,
    label TEXT NOT NULL,
    duration TEXT NOT NULL DEFAULT '',
    agitation_interval TEXT NOT NULL DEFAULT '',
    payload_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(version_id) REFERENCES student_global_process_versions(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_global_process_version_steps ON student_global_process_version_steps(version_id,position,id);

CREATE TABLE IF NOT EXISTS student_process_execution_sessions (
    plan_id INTEGER PRIMARY KEY,
    student_id INTEGER NOT NULL,
    plan_step_id INTEGER NULL,
    state TEXT NOT NULL DEFAULT 'idle',
    duration_seconds INTEGER NULL,
    remaining_seconds INTEGER NULL,
    timer_started_at TEXT NULL,
    timer_ends_at TEXT NULL,
    paused_at TEXT NULL,
    revision INTEGER NOT NULL DEFAULT 0,
    last_client_token TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(plan_id) REFERENCES student_process_plans(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(plan_step_id) REFERENCES student_process_plan_steps(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_process_execution_student ON student_process_execution_sessions(student_id,state,updated_at DESC);

CREATE TABLE IF NOT EXISTS student_process_change_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    change_uuid TEXT NOT NULL UNIQUE,
    actor_role TEXT NOT NULL,
    actor_admin_id INTEGER NULL,
    actor_student_id INTEGER NULL,
    entity_type TEXT NOT NULL,
    entity_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    before_json TEXT NOT NULL DEFAULT '{}',
    after_json TEXT NOT NULL DEFAULT '{}',
    note TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    FOREIGN KEY(actor_admin_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    FOREIGN KEY(actor_student_id) REFERENCES student_users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_process_change_entity ON student_process_change_log(entity_type,entity_id,id DESC);
SQL);

    $planColumns=array_column($db->query('PRAGMA table_info(student_process_plans)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('source_global_version_id',$planColumns,true))$db->exec('ALTER TABLE student_process_plans ADD COLUMN source_global_version_id INTEGER NULL');

    $now=gmdate('c');

    $stages=[
        ['first_development','Primeira revelação','development','',10],
        ['wash_after_first','Lavagem com água','wash','',20],
        ['stop_after_first','Banho interruptor','chemical','Banho interruptor',30],
        ['fixer','Fixação / hipossulfito','chemical','Hipossulfito / fixador',40],
        ['peracetic','Solução peroxiacética','chemical','Solução peroxiacética',50],
        ['ferric','Cloreto férrico','chemical','Cloreto férrico',60],
        ['dichromate','Dicromato','chemical','Dicromato',70],
        ['permanganate','Permanganato','chemical','Permanganato',80],
        ['wash_after_bleach','Lavagem','wash','',90],
        ['ammonia','Banho de amônia','chemical','Amônia',100],
        ['wash_after_ammonia','Lavagem','wash','',110],
        ['clearing','Banho de limpeza','chemical','Banho de limpeza',120],
        ['wash_after_clearing','Lavagem','wash','',130],
        ['second_development','Segunda revelação','development','',140],
        ['final_wash','Lavagem final','wash','',150],
        ['dry','Secagem','dry','',160],
        ['custom','Outra etapa','custom','',999],
    ];
    $stageInsert=$db->prepare('INSERT OR IGNORE INTO student_process_stage_catalog(stage_key,label,stage_type,chemical_name,enabled,sort_order,created_at,updated_at) VALUES(?,?,?,?,1,?,?,?)');
    foreach($stages as [$key,$label,$type,$chemical,$sort])$stageInsert->execute([$key,$label,$type,$chemical,$sort,$now,$now]);

    $developers=[
        ['parodinal','Parodinal','concentrate',1,10],
        ['brewed-caffenol','Brewed Caffenol','fresh',0,20],
        ['rodinal','Rodinal / Adonal','concentrate',1,30],
        ['d76','Kodak D-76','stock',1,40],
        ['id11','Ilford ID-11','stock',1,50],
        ['xtol','Kodak XTOL','stock',1,60],
        ['hc110','Kodak HC-110','concentrate',1,70],
        ['tmax','Kodak T-MAX Developer','concentrate',1,80],
        ['ilfosol3','Ilford ILFOSOL 3','concentrate',1,90],
        ['ddx','Ilford ILFOTEC DD-X','concentrate',1,100],
        ['microphen','Ilford MICROPHEN','stock',1,110],
        ['perceptol','Ilford PERCEPTOL','stock',1,120],
        ['other','Outro','custom',1,999],
    ];
    $developerInsert=$db->prepare('INSERT OR IGNORE INTO student_process_developer_catalog(developer_key,label,preparation_mode,storable,enabled,sort_order,created_at,updated_at) VALUES(?,?,?,?,1,?,?,?)');
    foreach($developers as [$key,$label,$mode,$storable,$sort])$developerInsert->execute([$key,$label,$mode,$storable,$sort,$now,$now]);

    $development=static function(int $ei): array {
        $amount=$ei===400?20:10;
        return ['developer_key'=>'parodinal','developer_amount'=>(string)$amount,'water_amount'=>(string)(550-$amount),'temperature'=>'26 °C','duration'=>'7:00','agitation'=>'leve'];
    };
    $caffenol=static function(): array {
        return ['developer_key'=>'brewed-caffenol','temperature'=>'35,7 °C','duration'=>'5:00','notes'=>'Brewed Caffenol preparado fresco. Receita de referência para aproximadamente 1 L: 37 g de café torrado e moído extra-forte, 54 g de carbonato de sódio e 20 g de ácido ascórbico; completar com água até 1 L.'];
    };
    $reuse=static function(array $base): array {
        $base['reuse_source_stage_key']='first_development';
        $reuse='Reutilizar o mesmo banho de revelador da primeira revelação; não preparar uma nova solução.';
        $base['notes']=trim((string)($base['notes']??''))!==''?trim((string)$base['notes']).' '.$reuse:$reuse;
        return $base;
    };
    $ei200=$development(200);$ei400=$development(400);$caf=$caffenol();
    $catalog=[
        'positive-ferric-ammonia-ei200'=>[
            'Positivo direto — Parodinal EI 200 — FeCl₃ + amônia',
            'Parodinal 10 ml + água até 550 ml, 26 °C, 7 min e agitação leve. O mesmo banho é reutilizado na segunda revelação. Lavagens de 1 min e cloreto férrico por 1 min 30 s.',
            [
                ['first_development',$ei200],['wash_after_first',['duration'=>'1:00']],['ferric',['duration'=>'1:30']],['wash_after_bleach',['duration'=>'1:00']],['ammonia',['duration'=>'']],['wash_after_ammonia',['duration'=>'1:00']],['second_development',$reuse($ei200)],['final_wash',['duration'=>'1:00']],['dry',['duration'=>'']],
            ],
        ],
        'positive-ferric-ammonia-ei400'=>[
            'Positivo direto — Parodinal EI 400 — FeCl₃ + amônia',
            'Parodinal 20 ml + água até 550 ml, 26 °C, 7 min e agitação leve. O mesmo banho é reutilizado na segunda revelação. Lavagens de 1 min e cloreto férrico por 1 min 30 s.',
            [
                ['first_development',$ei400],['wash_after_first',['duration'=>'1:00']],['ferric',['duration'=>'1:30']],['wash_after_bleach',['duration'=>'1:00']],['ammonia',['duration'=>'']],['wash_after_ammonia',['duration'=>'1:00']],['second_development',$reuse($ei400)],['final_wash',['duration'=>'1:00']],['dry',['duration'=>'']],
            ],
        ],
        'positive-peracetic-ei200'=>[
            'Positivo direto — Parodinal EI 200 — peracética',
            'Parodinal 10 ml + água até 550 ml, 26 °C, 7 min e agitação leve. O mesmo banho é reutilizado na segunda revelação. Lavagens de 1 min e branqueamento peracético por 1 min 30 s.',
            [
                ['first_development',$ei200],['wash_after_first',['duration'=>'1:00']],['peracetic',['duration'=>'1:30']],['wash_after_bleach',['duration'=>'1:00']],['second_development',$reuse($ei200)],['final_wash',['duration'=>'1:00']],['dry',['duration'=>'']],
            ],
        ],
        'positive-peracetic-ei400'=>[
            'Positivo direto — Parodinal EI 400 — peracética',
            'Parodinal 20 ml + água até 550 ml, 26 °C, 7 min e agitação leve. O mesmo banho é reutilizado na segunda revelação. Lavagens de 1 min e branqueamento peracético por 1 min 30 s.',
            [
                ['first_development',$ei400],['wash_after_first',['duration'=>'1:00']],['peracetic',['duration'=>'1:30']],['wash_after_bleach',['duration'=>'1:00']],['second_development',$reuse($ei400)],['final_wash',['duration'=>'1:00']],['dry',['duration'=>'']],
            ],
        ],
        'positive-ferric-ammonia-ei400-caffenol'=>[
            'Positivo direto — Brewed Caffenol EI 400 — FeCl₃ + amônia',
            'Brewed Caffenol fresco, EI 400, 35,7 °C e 5 min. O mesmo banho é reutilizado na segunda revelação. Lavagens de 1 min e cloreto férrico por 1 min 30 s.',
            [
                ['first_development',$caf],['wash_after_first',['duration'=>'1:00']],['ferric',['duration'=>'1:30']],['wash_after_bleach',['duration'=>'1:00']],['ammonia',['duration'=>'']],['wash_after_ammonia',['duration'=>'1:00']],['second_development',$reuse($caf)],['final_wash',['duration'=>'1:00']],['dry',['duration'=>'']],
            ],
        ],
        'positive-peracetic-ei400-caffenol'=>[
            'Positivo direto — Brewed Caffenol EI 400 — peracética',
            'Brewed Caffenol fresco, EI 400, 35,7 °C e 5 min. O mesmo banho é reutilizado na segunda revelação. Lavagens de 1 min e branqueamento peracético por 1 min 30 s.',
            [
                ['first_development',$caf],['wash_after_first',['duration'=>'1:00']],['peracetic',['duration'=>'1:30']],['wash_after_bleach',['duration'=>'1:00']],['second_development',$reuse($caf)],['final_wash',['duration'=>'1:00']],['dry',['duration'=>'']],
            ],
        ],
    ];

    $stageLabels=[];foreach($stages as [$key,$label])$stageLabels[$key]=$label;
    $findProcess=$db->prepare('SELECT id,active_version_id FROM student_global_processes WHERE process_key=? LIMIT 1');
    $insertProcess=$db->prepare("INSERT INTO student_global_processes(process_uuid,process_key,name,description,status,created_at,updated_at) VALUES(?,?,?,?, 'active',?,?)");
    $insertVersion=$db->prepare("INSERT INTO student_global_process_versions(version_uuid,process_id,version_number,status,name,description,change_note,published_at,created_at,updated_at) VALUES(?,?,1,'published',?,?,?, ?,?,?)");
    $insertStep=$db->prepare('INSERT INTO student_global_process_version_steps(version_id,position,stage_key,label,duration,agitation_interval,payload_json,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?)');
    $activate=$db->prepare('UPDATE student_global_processes SET active_version_id=?,name=?,description=?,updated_at=? WHERE id=?');

    foreach($catalog as $key=>[$name,$description,$steps]){
        $findProcess->execute([$key]);$process=$findProcess->fetch(PDO::FETCH_ASSOC);
        if($process)continue;
        $insertProcess->execute([bin2hex(random_bytes(16)),$key,$name,$description,$now,$now]);$processId=(int)$db->lastInsertId();
        $insertVersion->execute([bin2hex(random_bytes(16)),$processId,$name,$description,'Migração do catálogo operacional existente.',$now,$now,$now]);$versionId=(int)$db->lastInsertId();
        foreach($steps as $i=>[$stageKey,$raw]){
            $duration=(string)($raw['duration']??'');$payload=$raw;unset($payload['duration']);
            $insertStep->execute([$versionId,$i+1,$stageKey,$stageLabels[$stageKey]??$stageKey,$duration,(string)($raw['agitation_interval']??''),json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),$now,$now]);
        }
        $activate->execute([$versionId,$name,$description,$now,$processId]);
    }
};