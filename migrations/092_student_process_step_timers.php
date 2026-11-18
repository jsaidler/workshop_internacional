<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_process_step_timers (
    plan_step_id INTEGER PRIMARY KEY,
    plan_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    state TEXT NOT NULL DEFAULT 'idle',
    duration_seconds INTEGER NULL,
    remaining_seconds INTEGER NULL,
    timer_started_at TEXT NULL,
    timer_ends_at TEXT NULL,
    paused_at TEXT NULL,
    completion_applied_at TEXT NULL,
    revision INTEGER NOT NULL DEFAULT 0,
    last_client_token TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(plan_step_id) REFERENCES student_process_plan_steps(id) ON DELETE CASCADE,
    FOREIGN KEY(plan_id) REFERENCES student_process_plans(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_process_step_timers_plan ON student_process_step_timers(plan_id,student_id,updated_at DESC);
SQL);
};
