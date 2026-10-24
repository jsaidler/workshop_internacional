<?php
declare(strict_types=1);
/**
 * Store editable MJML independently from the legacy campaign body.
 * Not a delivery source; campaign.body_html and body_text remain unchanged.
 */
return static function(PDO $db):void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS communication_builder_drafts (
    campaign_id INTEGER PRIMARY KEY,
    document_version INTEGER NOT NULL DEFAULT 1 CHECK(document_version=1),
    mjml_source TEXT NOT NULL,
    media_refs_json TEXT NOT NULL DEFAULT '{}',
    revision INTEGER NOT NULL DEFAULT 1 CHECK(revision>=1),
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(campaign_id) REFERENCES communication_campaigns(id) ON DELETE CASCADE
);
SQL);
};
