<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS cms_site_settings (
    activity_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    settings_json TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(activity_id, locale),
    FOREIGN KEY(activity_id) REFERENCES activities(id)
);
CREATE TABLE IF NOT EXISTS cms_design_settings (
    activity_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    settings_json TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(activity_id, locale),
    FOREIGN KEY(activity_id) REFERENCES activities(id)
);
CREATE TABLE IF NOT EXISTS cms_page_revisions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER NOT NULL,
    revision INTEGER NOT NULL,
    state TEXT NOT NULL,
    document_json TEXT NOT NULL,
    settings_json TEXT NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(page_id) REFERENCES cms_pages(id)
);
CREATE INDEX IF NOT EXISTS idx_cms_page_revisions_page ON cms_page_revisions(page_id,id DESC);
CREATE TABLE IF NOT EXISTS media_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL
);
CREATE TABLE IF NOT EXISTS media_asset_tags (
    asset_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    PRIMARY KEY(asset_id,tag_id),
    FOREIGN KEY(asset_id) REFERENCES media_assets(id),
    FOREIGN KEY(tag_id) REFERENCES media_tags(id)
);
SQL);

    $columns=array_column($db->query('PRAGMA table_info(media_assets)')->fetchAll(),'name');
    foreach([
        'caption'=>"ALTER TABLE media_assets ADD COLUMN caption TEXT NOT NULL DEFAULT ''",
        'description'=>"ALTER TABLE media_assets ADD COLUMN description TEXT NOT NULL DEFAULT ''",
        'focal_x'=>"ALTER TABLE media_assets ADD COLUMN focal_x REAL NOT NULL DEFAULT 50",
        'focal_y'=>"ALTER TABLE media_assets ADD COLUMN focal_y REAL NOT NULL DEFAULT 50",
    ] as $column=>$sql)if(!in_array($column,$columns,true))$db->exec($sql);
};
