<?php
declare(strict_types=1);
return static function(PDO $db): void {
    $columns=array_column($db->query('PRAGMA table_info(interest_submissions)')->fetchAll(),'name');
    if(!in_array('status',$columns,true)) $db->exec("ALTER TABLE interest_submissions ADD COLUMN status TEXT NOT NULL DEFAULT 'new'");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_interest_activity_status ON interest_submissions(activity_id,status)");
};
