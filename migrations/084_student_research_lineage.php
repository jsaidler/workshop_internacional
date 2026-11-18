<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $table=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='student_tests'")->fetchColumn();
    if(!$table)return;

    $columns=[];
    foreach($db->query('PRAGMA table_info(student_tests)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)$column['name']]=true;
    if(!isset($columns['source_test_id']))$db->exec('ALTER TABLE student_tests ADD COLUMN source_test_id INTEGER NULL REFERENCES student_tests(id) ON DELETE SET NULL');
    if(!isset($columns['research_intent']))$db->exec("ALTER TABLE student_tests ADD COLUMN research_intent TEXT NOT NULL DEFAULT ''");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_student_tests_source ON student_tests(student_id,source_test_id)');
};
