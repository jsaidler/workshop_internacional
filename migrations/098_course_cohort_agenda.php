<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $exists=static fn(string $table): bool => (bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($table))->fetchColumn();
    if(!$exists('courses') || !$exists('course_cohorts') || !$exists('cohort_lesson_releases') || !$exists('course_enrollments'))return;
    $add=static function(string $table,string $name,string $definition) use($db): void {
        foreach($db->query('PRAGMA table_info('.$table.')')->fetchAll(PDO::FETCH_ASSOC) as $column)if((string)$column['name']===$name)return;
        $db->exec('ALTER TABLE '.$table.' ADD COLUMN '.$name.' '.$definition);
    };
    $add('courses','agenda_default_capacity','INTEGER NOT NULL DEFAULT 10');
    $add('course_cohorts','agenda_capacity','INTEGER NULL');
    $add('course_cohorts','agenda_enrollment_closes_at','TEXT NULL');
    $add('course_cohorts','agenda_listeners_open_at','TEXT NULL');
    $add('cohort_lesson_releases','meeting_starts_at','TEXT NULL');
    $add('cohort_lesson_releases','meeting_timezone',"TEXT NOT NULL DEFAULT 'America/Sao_Paulo'");
    $add('cohort_lesson_releases','meeting_duration_minutes','INTEGER NULL');
    $add('cohort_lesson_releases','meeting_url',"TEXT NOT NULL DEFAULT ''");
    $add('cohort_lesson_releases','meeting_notes',"TEXT NOT NULL DEFAULT ''");
    $add('cohort_lesson_releases','meeting_status',"TEXT NOT NULL DEFAULT 'scheduled'");
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS course_listener_reservations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    cohort_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'waiting' CHECK(status IN ('waiting','confirmed','cancelled')),
    requested_at TEXT NOT NULL,
    confirmed_at TEXT NULL,
    cancelled_at TEXT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(student_id,cohort_id),
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(cohort_id) REFERENCES course_cohorts(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_course_listener_queue ON course_listener_reservations(cohort_id,status,requested_at,id);
CREATE INDEX IF NOT EXISTS idx_course_listener_student ON course_listener_reservations(student_id,status,cohort_id);
CREATE INDEX IF NOT EXISTS idx_cohort_lesson_meetings ON cohort_lesson_releases(cohort_id,meeting_starts_at);
SQL);
};
