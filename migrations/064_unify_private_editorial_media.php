<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $columns=array_column($db->query('PRAGMA table_info(media_assets)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('visibility',$columns,true))$db->exec("ALTER TABLE media_assets ADD COLUMN visibility TEXT NOT NULL DEFAULT 'public'");
    $db->exec("CREATE TABLE IF NOT EXISTS course_page_media_slots (
        page_id INTEGER NOT NULL,
        slot_key TEXT NOT NULL,
        media_asset_id INTEGER NOT NULL,
        created_at TEXT NOT NULL,
        updated_at TEXT NOT NULL,
        PRIMARY KEY(page_id,slot_key),
        FOREIGN KEY(page_id) REFERENCES cms_pages(id) ON DELETE CASCADE,
        FOREIGN KEY(media_asset_id) REFERENCES media_assets(id) ON DELETE RESTRICT
    );
    CREATE INDEX IF NOT EXISTS idx_course_page_media_slots_asset ON course_page_media_slots(media_asset_id);
    CREATE INDEX IF NOT EXISTS idx_media_assets_visibility ON media_assets(visibility,archived_at);");
};
