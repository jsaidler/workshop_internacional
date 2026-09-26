<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $columns=[];
    foreach($db->query('PRAGMA table_info(cms_forms)')->fetchAll(PDO::FETCH_ASSOC) as $column)$columns[(string)$column['name']]=true;
    if(!isset($columns['purpose']))$db->exec("ALTER TABLE cms_forms ADD COLUMN purpose TEXT NOT NULL DEFAULT 'common'");

    // Preserve the behavior that existed before purpose became explicit. These
    // keys are used only for one-time migration/backfill, never as runtime
    // business rules after this migration.
    $db->exec("UPDATE cms_forms SET purpose='enrollment' WHERE purpose='common' AND form_key='registration'");
    $db->exec("UPDATE cms_forms SET purpose='interest' WHERE purpose='common' AND form_key='interest'");

    $db->exec('CREATE INDEX IF NOT EXISTS idx_cms_forms_activity_purpose ON cms_forms(activity_id,purpose,status)');
};
