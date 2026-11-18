<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $tables=array_column($db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('student_tool_courses',$tables,true))return;

    $columns=array_column($db->query('PRAGMA table_info(student_tool_courses)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('release_lesson_id',$columns,true))$db->exec('ALTER TABLE student_tool_courses ADD COLUMN release_lesson_id INTEGER NULL');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_student_tool_courses_release ON student_tool_courses(course_id,release_lesson_id,tool_key)');

    if(in_array('course_lessons',$tables,true)){
        $db->exec(<<<'SQL'
UPDATE student_tool_courses
SET release_lesson_id=(
    SELECT l.id
    FROM course_lessons l
    WHERE l.course_id=student_tool_courses.course_id
    ORDER BY l.sort_order,l.id
    LIMIT 1
)
WHERE tool_key='solution_prep'
  AND release_lesson_id IS NULL
  AND EXISTS(
      SELECT 1 FROM course_lessons l2
      WHERE l2.course_id=student_tool_courses.course_id
  )
SQL);
    }
};
