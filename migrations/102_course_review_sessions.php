<?php
declare(strict_types=1);

/**
 * Complementary review meetings are course events, not course_lessons:
 * never manufacture curricular releases, progress or cohort enrollment.
 */
return static function(PDO $db): void {
    $exists=static fn(string $name): bool => (bool)$db->query(
        "SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name)
    )->fetchColumn();
    if(!$exists('courses')||!$exists('course_cohorts')||!$exists('student_users'))return;
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS course_review_sessions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    cohort_id INTEGER NULL,
    title TEXT NOT NULL,
    meeting_starts_at TEXT NOT NULL,
    meeting_timezone TEXT NOT NULL DEFAULT 'America/Sao_Paulo',
    meeting_duration_minutes INTEGER NULL CHECK(meeting_duration_minutes BETWEEN 1 AND 1440),
    meeting_url TEXT NOT NULL DEFAULT '',
    meeting_notes TEXT NOT NULL DEFAULT '',
    meeting_status TEXT NOT NULL DEFAULT 'scheduled'
      CHECK(meeting_status IN ('scheduled','completed','cancelled')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY(cohort_id) REFERENCES course_cohorts(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_course_review_sessions_course ON course_review_sessions(course_id,meeting_starts_at,id);
CREATE INDEX IF NOT EXISTS idx_course_review_sessions_cohort ON course_review_sessions(cohort_id,meeting_starts_at,id);
SQL);
};
