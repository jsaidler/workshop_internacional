<?php
declare(strict_types=1);

/** Administrative alerts are not marketing campaigns or student messages. */
return static function(PDO $db): void {
    $tables=[];
    foreach($db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN) as $name)$tables[(string)$name]=true;
    if(!isset($tables['admin_users'],$tables['student_users'],$tables['course_cohorts']))return;
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS admin_student_notice_preferences (
    admin_id INTEGER NOT NULL,
    event_kind TEXT NOT NULL CHECK(event_kind IN ('question_created','question_replied','test_submitted','test_commented')),
    enabled INTEGER NOT NULL DEFAULT 1 CHECK(enabled IN (0,1)),
    updated_at TEXT NOT NULL,
    PRIMARY KEY(admin_id,event_kind),
    FOREIGN KEY(admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS admin_student_notice_outbox (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    cohort_id INTEGER NOT NULL,
    event_kind TEXT NOT NULL CHECK(event_kind IN ('question_created','question_replied','test_submitted','test_commented')),
    target_id INTEGER NOT NULL,
    event_key TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','processing','sent','skipped','failed')),
    attempts INTEGER NOT NULL DEFAULT 0,
    provider_id TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    sent_at TEXT NULL,
    error_code TEXT NULL,
    UNIQUE(admin_id,event_kind,event_key),
    FOREIGN KEY(admin_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(cohort_id) REFERENCES course_cohorts(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_admin_notice_dispatch ON admin_student_notice_outbox(status,attempts,id);
SQL);
};
