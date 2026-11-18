<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tables=array_column($db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC),'name');
    $has=static fn(string $name): bool=>in_array($name,$tables,true);
    $now=gmdate('c');

    if($has('student_tests')){
        $columns=array_column($db->query('PRAGMA table_info(student_tests)')->fetchAll(PDO::FETCH_ASSOC),'name');
        if(!in_array('context_scope',$columns,true))$db->exec("ALTER TABLE student_tests ADD COLUMN context_scope TEXT NOT NULL DEFAULT 'course'");
        if(!in_array('context_cohort_id',$columns,true))$db->exec('ALTER TABLE student_tests ADD COLUMN context_cohort_id INTEGER NULL');
        $db->exec("UPDATE student_tests SET context_scope='course' WHERE context_scope IS NULL OR context_scope NOT IN ('personal','course')");
        $db->exec("UPDATE student_tests SET context_cohort_id=cohort_id WHERE context_scope='course' AND context_cohort_id IS NULL");
        $db->exec('CREATE INDEX IF NOT EXISTS idx_student_tests_context ON student_tests(student_id,context_scope,context_cohort_id,updated_at DESC)');
    }

    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_process_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    step_uuid TEXT NOT NULL UNIQUE,
    test_id INTEGER NOT NULL,
    position INTEGER NOT NULL DEFAULT 0,
    stage_type TEXT NOT NULL,
    stage_key TEXT NOT NULL DEFAULT '',
    label TEXT NOT NULL DEFAULT '',
    chemical_key TEXT NOT NULL DEFAULT '',
    chemical_name TEXT NOT NULL DEFAULT '',
    inventory_item_id INTEGER NULL,
    saved_preparation_id INTEGER NULL,
    developer_amount REAL NULL,
    water_amount REAL NULL,
    amount_unit TEXT NOT NULL DEFAULT 'ml',
    calculated_dilution TEXT NOT NULL DEFAULT '',
    total_volume REAL NULL,
    temperature TEXT NOT NULL DEFAULT '',
    duration TEXT NOT NULL DEFAULT '',
    agitation TEXT NOT NULL DEFAULT '',
    notes TEXT NOT NULL DEFAULT '',
    metadata_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(test_id) REFERENCES student_tests(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_process_steps_test ON student_process_steps(test_id,position,id);
CREATE INDEX IF NOT EXISTS idx_student_process_steps_inventory ON student_process_steps(inventory_item_id,test_id);

CREATE TABLE IF NOT EXISTS student_saved_preparations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    preparation_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    developer_key TEXT NOT NULL DEFAULT '',
    developer_name TEXT NOT NULL,
    label TEXT NOT NULL,
    preparation_mode TEXT NOT NULL DEFAULT 'concentrate',
    developer_amount REAL NULL,
    water_amount REAL NULL,
    amount_unit TEXT NOT NULL DEFAULT 'ml',
    temperature TEXT NOT NULL DEFAULT '',
    duration TEXT NOT NULL DEFAULT '',
    agitation TEXT NOT NULL DEFAULT '',
    ingredients_json TEXT NOT NULL DEFAULT '[]',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_saved_preparations_student ON student_saved_preparations(student_id,developer_key,label);

CREATE TABLE IF NOT EXISTS student_inventory_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    item_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    item_kind TEXT NOT NULL DEFAULT 'raw',
    name TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT '',
    quantity REAL NOT NULL DEFAULT 0,
    unit TEXT NOT NULL DEFAULT 'ml',
    minimum_quantity REAL NULL,
    lot_code TEXT NOT NULL DEFAULT '',
    acquired_or_prepared_at TEXT NULL,
    expires_at TEXT NULL,
    location TEXT NOT NULL DEFAULT '',
    storable INTEGER NOT NULL DEFAULT 1,
    notes TEXT NOT NULL DEFAULT '',
    archived_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_inventory_items_student ON student_inventory_items(student_id,archived_at,name);

CREATE TABLE IF NOT EXISTS student_inventory_movements (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    movement_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    item_id INTEGER NOT NULL,
    test_id INTEGER NULL,
    step_id INTEGER NULL,
    movement_type TEXT NOT NULL,
    quantity_delta REAL NOT NULL,
    note TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(item_id) REFERENCES student_inventory_items(id) ON DELETE CASCADE,
    FOREIGN KEY(test_id) REFERENCES student_tests(id) ON DELETE SET NULL,
    FOREIGN KEY(step_id) REFERENCES student_process_steps(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_inventory_movements_item ON student_inventory_movements(item_id,id DESC);
CREATE INDEX IF NOT EXISTS idx_student_inventory_movements_test ON student_inventory_movements(test_id,id);

CREATE TABLE IF NOT EXISTS student_tools (
    tool_key TEXT PRIMARY KEY,
    label TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    access_mode TEXT NOT NULL DEFAULT 'course',
    sort_order INTEGER NOT NULL DEFAULT 0,
    enabled INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS student_tool_courses (
    tool_key TEXT NOT NULL,
    course_id INTEGER NOT NULL,
    PRIMARY KEY(tool_key,course_id),
    FOREIGN KEY(tool_key) REFERENCES student_tools(tool_key) ON DELETE CASCADE,
    FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_tool_courses_course ON student_tool_courses(course_id,tool_key);

CREATE TABLE IF NOT EXISTS student_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    question_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    cohort_id INTEGER NOT NULL,
    test_id INTEGER NULL,
    topic TEXT NOT NULL DEFAULT '',
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    visibility TEXT NOT NULL DEFAULT 'private',
    status TEXT NOT NULL DEFAULT 'open',
    accepted_message_id INTEGER NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(cohort_id) REFERENCES course_cohorts(id) ON DELETE CASCADE,
    FOREIGN KEY(test_id) REFERENCES student_tests(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_questions_cohort ON student_questions(cohort_id,status,updated_at DESC);
CREATE INDEX IF NOT EXISTS idx_student_questions_student ON student_questions(student_id,updated_at DESC);

CREATE TABLE IF NOT EXISTS student_question_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    message_uuid TEXT NOT NULL UNIQUE,
    question_id INTEGER NOT NULL,
    author_role TEXT NOT NULL,
    student_id INTEGER NULL,
    body TEXT NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(question_id) REFERENCES student_questions(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_question_messages_question ON student_question_messages(question_id,id);

CREATE TABLE IF NOT EXISTS student_material_notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    note_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    page_id INTEGER NOT NULL,
    section_key TEXT NOT NULL,
    lesson_id INTEGER NULL,
    body TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(student_id,page_id,section_key),
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(page_id) REFERENCES cms_pages(id) ON DELETE CASCADE,
    FOREIGN KEY(lesson_id) REFERENCES course_lessons(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_material_notes_student ON student_material_notes(student_id,updated_at DESC);
CREATE INDEX IF NOT EXISTS idx_student_material_notes_page ON student_material_notes(page_id,section_key);
SQL);

    $toolInsert=$db->prepare('INSERT OR IGNORE INTO student_tools(tool_key,label,description,access_mode,sort_order,enabled,created_at,updated_at) VALUES(?,?,?,?,1,1,?,?)');
    $tools=[
        ['reciprocity','Reciprocidade','Corrija exposições longas com a curva liberada pela matrícula.','course',10],
        ['exposure','Exposição','Relacione tempo, abertura, EI e EV.','course',20],
        ['lab_timer','Temporizador de laboratório','Acompanhe tempos e movimentação durante o processamento.','course',30],
        ['calibration','Calibração','Guarde referências pessoais de processamento.','course',40],
        ['solution_prep','Preparo de soluções','Dimensione preparos liberados pelas matrículas.','course',50],
        ['lab_inventory','Inventário do laboratório','Controle insumos, soluções armazenáveis e consumo.','all',60],
    ];
    $stmt=$db->prepare('INSERT OR IGNORE INTO student_tools(tool_key,label,description,access_mode,sort_order,enabled,created_at,updated_at) VALUES(?,?,?,?,?,?,?,?)');
    foreach($tools as [$key,$label,$description,$mode,$sort])$stmt->execute([$key,$label,$description,$mode,$sort,1,$now,$now]);

    if($has('courses')){
        $db->exec("INSERT OR IGNORE INTO student_tool_courses(tool_key,course_id)
            SELECT t.tool_key,c.id FROM student_tools t CROSS JOIN courses c
            WHERE t.access_mode='course' AND t.enabled=1 AND c.status='active'");
    }
};
