<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $exists=(bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='cms_forms'")->fetchColumn();
    if(!$exists)return;
    $columns=$db->query('PRAGMA table_info(cms_forms)')->fetchAll(PDO::FETCH_ASSOC);
    $names=array_map(static fn(array $row): string=>(string)$row['name'],$columns);
    $added=!in_array('purpose',$names,true);
    if($added)$db->exec("ALTER TABLE cms_forms ADD COLUMN purpose TEXT NOT NULL DEFAULT 'common'");
    if($added){
        $db->exec("UPDATE cms_forms SET purpose='enrollment' WHERE form_key='registration'");
        $db->exec("UPDATE cms_forms SET purpose='interest' WHERE form_key='interest'");
    }
    $db->exec("UPDATE cms_forms SET purpose='common' WHERE purpose NOT IN ('common','interest','registration','enrollment') OR purpose IS NULL OR purpose=''");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_cms_forms_purpose ON cms_forms(activity_id,purpose,status,id)');
};
