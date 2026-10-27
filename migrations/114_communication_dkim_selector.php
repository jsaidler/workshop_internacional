<?php
declare(strict_types=1);
return static function(PDO $db):void {
    $exists=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='communication_domain_diagnostics'")->fetchColumn();
    if(!$exists)return;
    $columns=$db->query('PRAGMA table_info(communication_domain_diagnostics)')->fetchAll(PDO::FETCH_COLUMN,1);
    if(!in_array('dkim_selector',$columns,true))$db->exec("ALTER TABLE communication_domain_diagnostics ADD COLUMN dkim_selector TEXT NOT NULL DEFAULT ''");
};
