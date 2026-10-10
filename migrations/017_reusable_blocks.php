<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS cms_reusable_blocks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    block_uuid TEXT NOT NULL UNIQUE,
    activity_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    name TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'custom',
    html TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id)
);
CREATE INDEX IF NOT EXISTS idx_cms_blocks_activity_locale ON cms_reusable_blocks(activity_id, locale, name);
SQL);
};
