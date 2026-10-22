<?php
declare(strict_types=1);

/**
 * Store encrypted provider credentials separately from editorial sender settings.
 * Encryption keys are derived from the existing installation secret in
 * config/local.php; the updater preserves that secret and the SQLite database.
 * Never put raw keys into migrations or source-controlled fixtures.
 */
return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS communication_brevo_credentials (
    id INTEGER PRIMARY KEY CHECK(id=1),
    ciphertext TEXT NOT NULL,
    nonce TEXT NOT NULL,
    auth_tag TEXT NOT NULL,
    verified_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
SQL);
};
