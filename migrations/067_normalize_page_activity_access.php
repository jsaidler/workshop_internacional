<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $exists=$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_pages'")->fetchColumn();
    if(!$exists)return;
    $db->exec("UPDATE cms_pages SET access_level='activity' WHERE access_level='enrolled'");
};
