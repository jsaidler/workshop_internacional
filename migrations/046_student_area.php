<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE student_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_uuid TEXT NOT NULL UNIQUE,
    username TEXT NOT NULL COLLATE NOCASE UNIQUE,
    display_name TEXT NOT NULL,
    email TEXT NOT NULL DEFAULT '',
    password_hash TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','disabled')),
    must_change_password INTEGER NOT NULL DEFAULT 1,
    session_version INTEGER NOT NULL DEFAULT 1,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    last_login_at TEXT NULL
);
CREATE INDEX idx_student_users_status ON student_users(status, username);
CREATE UNIQUE INDEX idx_student_users_email_nonempty ON student_users(lower(email)) WHERE email!='';

CREATE TABLE student_enrollments (
    user_id INTEGER NOT NULL,
    activity_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    PRIMARY KEY(user_id, activity_id),
    FOREIGN KEY(user_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE
);
CREATE INDEX idx_student_enrollments_activity ON student_enrollments(activity_id, user_id);

CREATE TABLE student_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_uuid TEXT NOT NULL UNIQUE,
    activity_id INTEGER NOT NULL,
    slug TEXT NOT NULL,
    title TEXT NOT NULL,
    html_content TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','archived')),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    published_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE,
    UNIQUE(activity_id, slug)
);
CREATE INDEX idx_student_materials_activity ON student_materials(activity_id, status, title);

CREATE TABLE student_login_attempts (
    key_hash TEXT PRIMARY KEY,
    window_started_at TEXT NOT NULL,
    attempt_count INTEGER NOT NULL DEFAULT 0,
    updated_at TEXT NOT NULL
);
SQL);
};
