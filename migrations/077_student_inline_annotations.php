<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS student_material_annotations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    annotation_uuid TEXT NOT NULL UNIQUE,
    student_id INTEGER NOT NULL,
    page_id INTEGER NOT NULL,
    lesson_id INTEGER NULL,
    section_key TEXT NOT NULL DEFAULT 'pagina',
    anchor_type TEXT NOT NULL DEFAULT 'page',
    body TEXT NOT NULL DEFAULT '',
    quote_exact TEXT NOT NULL DEFAULT '',
    quote_prefix TEXT NOT NULL DEFAULT '',
    quote_suffix TEXT NOT NULL DEFAULT '',
    block_key TEXT NOT NULL DEFAULT '',
    start_offset INTEGER NULL,
    end_offset INTEGER NULL,
    source_block_hash TEXT NOT NULL DEFAULT '',
    source_page_revision TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE,
    FOREIGN KEY(page_id) REFERENCES cms_pages(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_material_annotations_page
    ON student_material_annotations(student_id,page_id,updated_at DESC,id DESC);
CREATE INDEX IF NOT EXISTS idx_student_material_annotations_anchor
    ON student_material_annotations(student_id,page_id,anchor_type,block_key,id);
SQL);
};
