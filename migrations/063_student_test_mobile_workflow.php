<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $columns=[];
    foreach($db->query('PRAGMA table_info(student_test_media)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)$column['name']]=true;
    if(!isset($columns['phase']))$db->exec("ALTER TABLE student_test_media ADD COLUMN phase TEXT NOT NULL DEFAULT 'result'");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_student_test_media_test_phase ON student_test_media(test_id,phase,id)");
};
