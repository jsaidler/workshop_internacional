<?php
declare(strict_types=1);
/** Individual recipient choices and safe rich message bodies. Existing campaigns keep their previous all-eligible behavior until edited. */
return static function(PDO $db):void {
    $has=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='communication_campaigns'")->fetchColumn();
    if(!$has)return;
    $columns=array_column($db->query('PRAGMA table_info(communication_campaigns)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('body_html',$columns,true))$db->exec("ALTER TABLE communication_campaigns ADD COLUMN body_html TEXT NOT NULL DEFAULT ''");
    if(!in_array('selection_mode',$columns,true))$db->exec("ALTER TABLE communication_campaigns ADD COLUMN selection_mode TEXT NOT NULL DEFAULT 'all'");
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS communication_campaign_targets (
    campaign_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    PRIMARY KEY(campaign_id,student_id),
    FOREIGN KEY(campaign_id) REFERENCES communication_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_communication_campaign_targets_student ON communication_campaign_targets(student_id,campaign_id);
SQL);
};
