<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_process_plan_mutation_tokens (
    token TEXT PRIMARY KEY,
    student_id INTEGER NOT NULL,
    plan_id INTEGER NOT NULL,
    action TEXT NOT NULL,
    result_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(plan_id) REFERENCES student_process_plans(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_process_plan_mutation_tokens_plan
    ON student_process_plan_mutation_tokens(plan_id,student_id,created_at DESC);
SQL);
};
