<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $columns=[];
    foreach($db->query('PRAGMA table_info(course_cohorts)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)$column['name']]=true;
    if(!isset($columns['notes']))$db->exec("ALTER TABLE course_cohorts ADD COLUMN notes TEXT NOT NULL DEFAULT ''");

    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_import_batches (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    import_uuid TEXT NOT NULL UNIQUE,
    cohort_id INTEGER NOT NULL,
    original_name TEXT NOT NULL,
    total_rows INTEGER NOT NULL DEFAULT 0,
    imported_rows INTEGER NOT NULL DEFAULT 0,
    existing_rows INTEGER NOT NULL DEFAULT 0,
    error_rows INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    FOREIGN KEY(cohort_id) REFERENCES course_cohorts(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_import_batches_cohort ON student_import_batches(cohort_id,id DESC);

CREATE TABLE IF NOT EXISTS student_import_rows (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    batch_id INTEGER NOT NULL,
    row_number INTEGER NOT NULL,
    student_id INTEGER NULL,
    name TEXT NOT NULL DEFAULT '',
    email TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL,
    message TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    FOREIGN KEY(batch_id) REFERENCES student_import_batches(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_import_rows_batch ON student_import_rows(batch_id,row_number);

CREATE TABLE IF NOT EXISTS student_tests (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    test_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    cohort_id INTEGER NOT NULL,
    title TEXT NOT NULL DEFAULT '',
    test_date TEXT NULL,
    film TEXT NOT NULL DEFAULT '',
    lot TEXT NOT NULL DEFAULT '',
    iso_reference TEXT NOT NULL DEFAULT '',
    aperture TEXT NOT NULL DEFAULT '',
    calculated_time TEXT NOT NULL DEFAULT '',
    reciprocity_time TEXT NOT NULL DEFAULT '',
    light_condition TEXT NOT NULL DEFAULT '',
    tonal_range TEXT NOT NULL DEFAULT '',
    developer TEXT NOT NULL DEFAULT '',
    dilution TEXT NOT NULL DEFAULT '',
    temperature TEXT NOT NULL DEFAULT '',
    development_time TEXT NOT NULL DEFAULT '',
    agitation TEXT NOT NULL DEFAULT '',
    notes TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'draft',
    submitted_at TEXT NULL,
    reviewed_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(cohort_id) REFERENCES course_cohorts(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_tests_student ON student_tests(student_id,updated_at DESC,id DESC);
CREATE INDEX IF NOT EXISTS idx_student_tests_cohort_status ON student_tests(cohort_id,status,submitted_at DESC,id DESC);

CREATE TABLE IF NOT EXISTS student_test_media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    media_uuid TEXT NOT NULL UNIQUE,
    test_id INTEGER NOT NULL,
    original_name TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    byte_size INTEGER NOT NULL,
    storage_path TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL,
    FOREIGN KEY(test_id) REFERENCES student_tests(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_test_media_test ON student_test_media(test_id,id);

CREATE TABLE IF NOT EXISTS student_test_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    message_uuid TEXT NOT NULL UNIQUE,
    test_id INTEGER NOT NULL,
    author_role TEXT NOT NULL,
    student_id INTEGER NULL,
    body TEXT NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(test_id) REFERENCES student_tests(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_student_test_messages_test ON student_test_messages(test_id,id);
SQL);
};
