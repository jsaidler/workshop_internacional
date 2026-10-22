<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $exists=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$exists)return;
    $columns=$db->query('PRAGMA table_info(cms_pages)')->fetchAll(PDO::FETCH_ASSOC);
    $names=array_map(static fn(array $row): string=>(string)$row['name'],$columns);
    if(!in_array('access_cohort_id',$names,true))$db->exec('ALTER TABLE cms_pages ADD COLUMN access_cohort_id INTEGER NULL');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_cms_pages_access_cohort ON cms_pages(activity_id,access_level,access_cohort_id)');
};
