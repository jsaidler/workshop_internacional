<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_process_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_process_templates_student ON student_process_templates(student_id,updated_at DESC,id DESC);

CREATE TABLE IF NOT EXISTS student_process_template_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    template_id INTEGER NOT NULL,
    position INTEGER NOT NULL DEFAULT 0,
    stage_key TEXT NOT NULL,
    label TEXT NOT NULL,
    duration TEXT NOT NULL DEFAULT '',
    agitation_interval TEXT NOT NULL DEFAULT '',
    payload_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(template_id) REFERENCES student_process_templates(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_process_template_steps_template ON student_process_template_steps(template_id,position,id);

CREATE TABLE IF NOT EXISTS student_process_plans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plan_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    test_id INTEGER NOT NULL UNIQUE,
    source_template_id INTEGER NULL,
    source_name TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'planned',
    started_at TEXT NULL,
    completed_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(test_id) REFERENCES student_tests(id) ON DELETE CASCADE,
    FOREIGN KEY(source_template_id) REFERENCES student_process_templates(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_process_plans_student ON student_process_plans(student_id,status,updated_at DESC);

CREATE TABLE IF NOT EXISTS student_process_plan_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    plan_id INTEGER NOT NULL,
    position INTEGER NOT NULL DEFAULT 0,
    stage_key TEXT NOT NULL,
    label TEXT NOT NULL,
    duration TEXT NOT NULL DEFAULT '',
    agitation_interval TEXT NOT NULL DEFAULT '',
    payload_json TEXT NOT NULL DEFAULT '{}',
    status TEXT NOT NULL DEFAULT 'planned',
    actual_step_id INTEGER NULL,
    started_at TEXT NULL,
    completed_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(plan_id) REFERENCES student_process_plans(id) ON DELETE CASCADE,
    FOREIGN KEY(actual_step_id) REFERENCES student_process_steps(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_process_plan_steps_plan ON student_process_plan_steps(plan_id,position,id);
SQL);

    $now=gmdate('c');
    $q=$db->prepare("UPDATE student_tools SET label='Processamentos',description='Crie roteiros de laboratório com várias etapas e execute-os com temporização guiada.',updated_at=? WHERE tool_key='lab_timer'");
    $q->execute([$now]);
};
