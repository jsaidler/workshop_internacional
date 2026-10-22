<?php
declare(strict_types=1);
/** Optional template selection for existing drafts and future messages. */
return static function(PDO $db):void {
    $has=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='communication_campaigns'")->fetchColumn();
    if(!$has)return;
    $columns=array_column($db->query('PRAGMA table_info(communication_campaigns)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('template_key',$columns,true))
        $db->exec("ALTER TABLE communication_campaigns ADD COLUMN template_key TEXT NOT NULL DEFAULT 'minimal'");
};
