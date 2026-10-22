<?php
declare(strict_types=1);
/**
 * Public origin of the site is a managed CMS setting. Do not derive it from
 * incoming HTTP Host or X-Forwarded-Host headers: those can be forged, and
 * the URL appears in email sent to students.
 */
return static function(PDO $db):void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS communication_site_origin (
    id INTEGER PRIMARY KEY CHECK(id=1),
    public_url TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
SQL);
};
