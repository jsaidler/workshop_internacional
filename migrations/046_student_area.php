<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE student_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_uuid TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    must_change_password INTEGER NOT NULL DEFAULT 1,
    auth_version INTEGER NOT NULL DEFAULT 1,
    last_login_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE INDEX idx_student_users_status_email ON student_users(status,email);

CREATE TABLE student_enrollments (
    student_id INTEGER NOT NULL,
    activity_id INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(student_id,activity_id),
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE
);
CREATE INDEX idx_student_enrollments_activity_status ON student_enrollments(activity_id,status,student_id);

CREATE TABLE student_materials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    material_uuid TEXT NOT NULL UNIQUE,
    activity_id INTEGER NOT NULL,
    locale TEXT NOT NULL DEFAULT 'pt-BR',
    slug TEXT NOT NULL,
    title TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    html_content TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id) ON DELETE CASCADE
);
CREATE UNIQUE INDEX idx_student_materials_activity_locale_slug ON student_materials(activity_id,locale,slug);
CREATE INDEX idx_student_materials_activity_status ON student_materials(activity_id,status,locale,title);

CREATE TABLE student_login_attempts (
    key_hash TEXT PRIMARY KEY,
    attempts INTEGER NOT NULL DEFAULT 0,
    first_attempt_at TEXT NOT NULL,
    last_attempt_at TEXT NOT NULL,
    blocked_until TEXT NULL
);
SQL);
};
