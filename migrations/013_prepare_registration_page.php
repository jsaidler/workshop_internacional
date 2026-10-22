<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $hasActivities=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='activities'")->fetchColumn();
    $hasPages=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    $hasForms=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_forms'")->fetchColumn();
    if(!$hasActivities||!$hasPages||!$hasForms||!function_exists('workshop_cms_setup_activity'))return;

    $activityIds=$db->query("SELECT id FROM activities WHERE status='active' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
    foreach($activityIds as $activityId)workshop_cms_setup_activity($db,(int)$activityId);
};
