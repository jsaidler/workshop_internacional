<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_process_calibrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    calibration_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    label TEXT NOT NULL,
    film TEXT NOT NULL DEFAULT '',
    developer TEXT NOT NULL DEFAULT '',
    preparation TEXT NOT NULL DEFAULT '',
    temperature TEXT NOT NULL DEFAULT '',
    prebath TEXT NOT NULL DEFAULT '',
    white_time TEXT NOT NULL DEFAULT '',
    notes TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_process_calibrations_student ON student_process_calibrations(student_id,updated_at DESC);
SQL);
};
