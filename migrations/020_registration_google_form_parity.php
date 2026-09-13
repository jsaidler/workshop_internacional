<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasSubmissions=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_form_submissions'")->fetchColumn();
    if($hasSubmissions){
        $columns=[];
        foreach($db->query('PRAGMA table_info(cms_form_submissions)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)$column['name']]=true;
        if(!isset($columns['payment_status']))$db->exec("ALTER TABLE cms_form_submissions ADD COLUMN payment_status TEXT NOT NULL DEFAULT 'pending'");
        if(!isset($columns['payment_confirmed_at']))$db->exec('ALTER TABLE cms_form_submissions ADD COLUMN payment_confirmed_at TEXT NULL');
        if(!isset($columns['payment_note']))$db->exec("ALTER TABLE cms_form_submissions ADD COLUMN payment_note TEXT NOT NULL DEFAULT ''");
    }

    $hasActivities=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='activities'")->fetchColumn();
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    $hasForms=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_forms'")->fetchColumn();
    if(!$hasActivities||!$hasPages||!$hasForms||!function_exists('workshop_cms_setup_activity'))return;

    $activityIds=$db->query("SELECT id FROM activities WHERE status='active' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    foreach($activityIds as $activityId)workshop_cms_setup_activity($db,(int)$activityId);
};
