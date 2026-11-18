<?php
declare(strict_types=1);

/** Separate necessary course notices from opt-in promotional email. */
return static function(PDO $db): void {
    $exists=static fn(string $name):bool=>(bool)$db->query(
        "SELECT 1 FROM sqlite_master WHERE type='table' AND name=".$db->quote($name)
    )->fetchColumn();
    if(!$exists('student_users')||!$exists('courses')||!$exists('activities'))return;
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS communication_preferences (
    student_id INTEGER PRIMARY KEY,
    newsletter_opt_in INTEGER NOT NULL DEFAULT 0 CHECK(newsletter_opt_in IN (0,1)),
    changed_at TEXT NOT NULL,
    source TEXT NOT NULL DEFAULT 'profile',
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS communication_campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_id INTEGER NOT NULL,
    category TEXT NOT NULL CHECK(category IN ('operational','newsletter')),
    course_id INTEGER NULL,
    cohort_id INTEGER NULL,
    subject TEXT NOT NULL,
    body_text TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','queued','completed')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    queued_at TEXT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id),
    FOREIGN KEY(course_id) REFERENCES courses(id),
    FOREIGN KEY(cohort_id) REFERENCES course_cohorts(id)
);
CREATE INDEX IF NOT EXISTS idx_communication_campaigns_scope ON communication_campaigns(activity_id,created_at,id);
CREATE TABLE IF NOT EXISTS communication_deliveries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending','processing','sent','skipped','failed')),
    attempts INTEGER NOT NULL DEFAULT 0,
    provider_id TEXT NULL,
    error_code TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    delivered_at TEXT NULL,
    UNIQUE(campaign_id,student_id),
    FOREIGN KEY(campaign_id) REFERENCES communication_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_communication_deliveries_queue ON communication_deliveries(status,attempts,id);
SQL);
};
