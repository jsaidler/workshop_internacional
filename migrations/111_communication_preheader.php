<?php
declare(strict_types=1);
/** Optional inbox preview text; historical campaigns retain an empty value. */
return static function(PDO $db):void {
    $exists=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='communication_campaigns'")->fetchColumn();
    if(!$exists)return;
    $columns=array_column($db->query('PRAGMA table_info(communication_campaigns)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('preheader',$columns,true))
        $db->exec("ALTER TABLE communication_campaigns ADD COLUMN preheader TEXT NOT NULL DEFAULT ''");
};
