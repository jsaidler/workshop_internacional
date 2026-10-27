<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec('CREATE TABLE IF NOT EXISTS cms_page_seo (
        page_id INTEGER PRIMARY KEY,
        title TEXT NOT NULL DEFAULT "",
        description TEXT NOT NULL DEFAULT "",
        social_title TEXT NOT NULL DEFAULT "",
        social_description TEXT NOT NULL DEFAULT "",
        social_image TEXT NOT NULL DEFAULT "",
        canonical_url TEXT NOT NULL DEFAULT "",
        robots TEXT NOT NULL DEFAULT "index,follow",
        updated_at TEXT NOT NULL,
        FOREIGN KEY(page_id) REFERENCES cms_pages(id) ON DELETE CASCADE
    )');
};
