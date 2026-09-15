<?php
declare(strict_types=1);
return static function(PDO $db): void {
    $columns=array_column($db->query('PRAGMA table_info(interest_submissions)')->fetchAll(),'name');
    if(!in_array('workshop_locale',$columns,true)) $db->exec("ALTER TABLE interest_submissions ADD COLUMN workshop_locale TEXT NOT NULL DEFAULT 'en'");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_interest_activity_locale ON interest_submissions(activity_id,workshop_locale)");
};
