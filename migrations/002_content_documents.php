<?php
declare(strict_types=1);
return static function(PDO $db): void {$db->exec('CREATE TABLE content_documents (id INTEGER PRIMARY KEY AUTOINCREMENT,document_key TEXT NOT NULL UNIQUE,schema_version INTEGER NOT NULL,draft_json TEXT NULL,published_json TEXT NULL,draft_revision INTEGER NOT NULL DEFAULT 0,published_revision INTEGER NULL,draft_updated_at TEXT NULL,published_at TEXT NULL,published_by INTEGER NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL,FOREIGN KEY(published_by) REFERENCES admin_users(id))');};
