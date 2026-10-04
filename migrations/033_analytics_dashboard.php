<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS analytics_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_id INTEGER NOT NULL,
    page_id INTEGER NULL,
    form_id INTEGER NULL,
    submission_id INTEGER NULL,
    event_type TEXT NOT NULL,
    session_hash TEXT NOT NULL,
    locale TEXT NOT NULL DEFAULT '',
    path TEXT NOT NULL DEFAULT '',
    referrer_host TEXT NOT NULL DEFAULT '',
    utm_source TEXT NOT NULL DEFAULT '',
    utm_medium TEXT NOT NULL DEFAULT '',
    utm_campaign TEXT NOT NULL DEFAULT '',
    device_type TEXT NOT NULL DEFAULT '',
    meta_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id),
    FOREIGN KEY(page_id) REFERENCES cms_pages(id),
    FOREIGN KEY(form_id) REFERENCES cms_forms(id),
    FOREIGN KEY(submission_id) REFERENCES cms_form_submissions(id)
);
CREATE INDEX IF NOT EXISTS idx_analytics_activity_created ON analytics_events(activity_id,created_at DESC);
CREATE INDEX IF NOT EXISTS idx_analytics_activity_type_created ON analytics_events(activity_id,event_type,created_at DESC);
CREATE INDEX IF NOT EXISTS idx_analytics_activity_session ON analytics_events(activity_id,session_hash,created_at);
CREATE INDEX IF NOT EXISTS idx_analytics_page_created ON analytics_events(page_id,created_at DESC);
CREATE INDEX IF NOT EXISTS idx_analytics_form_created ON analytics_events(form_id,created_at DESC);
SQL);
};
