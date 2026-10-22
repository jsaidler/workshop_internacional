<?php
declare(strict_types=1);

/** An optional sender identity configured by an administrator in the CMS.
 * Provider credentials stay in the server environment; they are never copied
 * into the database, HTML or diagnostics. The PHP transport remains usable
 * without a third-party provider.
 */
return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS communication_sender_settings (
    id INTEGER PRIMARY KEY CHECK(id=1),
    sender_name TEXT NOT NULL DEFAULT '',
    sender_email TEXT NOT NULL DEFAULT '',
    updated_at TEXT NOT NULL
);
SQL);
};
