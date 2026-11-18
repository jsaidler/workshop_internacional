<?php
declare(strict_types=1);
return static function(PDO $db): void {
    if(!$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='student_users'")->fetchColumn())return;
    if(!$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='student_questions'")->fetchColumn())return;
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_home_notice_reads (
 student_id INTEGER NOT NULL,
 question_id INTEGER NOT NULL,
 message_id INTEGER NOT NULL DEFAULT 0,
 seen_at TEXT NOT NULL,
 PRIMARY KEY(student_id,question_id),
 FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
 FOREIGN KEY(question_id) REFERENCES student_questions(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_home_notice_reader ON student_home_notice_reads(student_id,message_id);
SQL);
};