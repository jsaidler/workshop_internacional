<?php
declare(strict_types=1);
return static function(PDO $db): void {
    $columns=array_column($db->query('PRAGMA table_info(student_tests)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('visibility',$columns,true))$db->exec("ALTER TABLE student_tests ADD COLUMN visibility TEXT NOT NULL DEFAULT 'private'");
    $db->exec("UPDATE student_tests SET visibility='private' WHERE visibility IS NULL OR visibility NOT IN ('private','cohort','course')");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_student_tests_visibility ON student_tests(activity_id,visibility,cohort_id,updated_at)');
};
