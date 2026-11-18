<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $table=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='student_tests'")->fetchColumn();
    if(!$table)return;

    $columns=[];
    foreach($db->query('PRAGMA table_info(student_tests)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)$column['name']]=true;
    if(!isset($columns['feedback_at']))$db->exec('ALTER TABLE student_tests ADD COLUMN feedback_at TEXT NULL');
    if(!isset($columns['feedback_seen_at']))$db->exec('ALTER TABLE student_tests ADD COLUMN feedback_seen_at TEXT NULL');

    // Existing reviewed/revision records become visible once as pedagogical returns.
    $db->exec("UPDATE student_tests SET feedback_at=COALESCE(reviewed_at,updated_at) WHERE feedback_at IS NULL AND status IN ('needs_revision','reviewed')");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_student_tests_feedback ON student_tests(student_id,status,feedback_at DESC)');

    // Teacher messages are feedback events. A new event invalidates the previous read receipt.
    $db->exec(<<<'SQL'
CREATE TRIGGER IF NOT EXISTS trg_student_test_admin_feedback
AFTER INSERT ON student_test_messages
WHEN NEW.author_role='admin'
BEGIN
    UPDATE student_tests
       SET feedback_at=NEW.created_at,
           feedback_seen_at=NULL
     WHERE id=NEW.test_id;
END;

CREATE TRIGGER IF NOT EXISTS trg_student_test_student_feedback_reply
AFTER INSERT ON student_test_messages
WHEN NEW.author_role='student'
BEGIN
    UPDATE student_tests
       SET feedback_seen_at=CASE WHEN feedback_at IS NOT NULL THEN feedback_at ELSE feedback_seen_at END
     WHERE id=NEW.test_id;
END;

CREATE TRIGGER IF NOT EXISTS trg_student_test_review_feedback
AFTER UPDATE OF status ON student_tests
WHEN NEW.status IN ('needs_revision','reviewed') AND NEW.status<>OLD.status
BEGIN
    UPDATE student_tests
       SET feedback_at=NEW.updated_at,
           feedback_seen_at=NULL
     WHERE id=NEW.id;
END;

CREATE TRIGGER IF NOT EXISTS trg_student_test_revision_resubmitted
AFTER UPDATE OF status ON student_tests
WHEN NEW.status='submitted' AND OLD.status='needs_revision'
BEGIN
    UPDATE student_tests
       SET feedback_seen_at=CASE WHEN feedback_at IS NOT NULL THEN feedback_at ELSE feedback_seen_at END
     WHERE id=NEW.id;
END;
SQL);
};
