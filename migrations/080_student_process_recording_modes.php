<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_process_recording_meta (
    test_id INTEGER PRIMARY KEY,
    student_id INTEGER NOT NULL,
    entry_mode TEXT NOT NULL DEFAULT 'live',
    performed_on TEXT NULL,
    notes TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(test_id) REFERENCES student_tests(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_process_recording_meta_student ON student_process_recording_meta(student_id,updated_at DESC);
SQL);
};
