<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tables=array_column($db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('student_questions',$tables,true)||!in_array('student_material_annotations',$tables,true))return;
    $columns=array_column($db->query('PRAGMA table_info(student_questions)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('source_annotation_id',$columns,true))$db->exec('ALTER TABLE student_questions ADD COLUMN source_annotation_id INTEGER NULL REFERENCES student_material_annotations(id) ON DELETE SET NULL');
    $db->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_student_questions_source_annotation ON student_questions(source_annotation_id) WHERE source_annotation_id IS NOT NULL');
};
