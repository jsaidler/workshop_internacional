<?php
declare(strict_types=1);

return static function(PDO $db): void {
    $db->exec(<<<'SQL'
CREATE TABLE cms_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_uuid TEXT NOT NULL UNIQUE,
    activity_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    slug TEXT NOT NULL DEFAULT '',
    title TEXT NOT NULL,
    nav_title TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    is_home INTEGER NOT NULL DEFAULT 0,
    show_in_nav INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    draft_document_json TEXT NOT NULL,
    published_document_json TEXT NULL,
    draft_revision INTEGER NOT NULL DEFAULT 1,
    published_revision INTEGER NULL,
    draft_updated_at TEXT NOT NULL,
    published_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id)
);
CREATE UNIQUE INDEX idx_cms_pages_activity_locale_slug ON cms_pages(activity_id, locale, slug);
CREATE UNIQUE INDEX idx_cms_pages_home ON cms_pages(activity_id, locale) WHERE is_home=1 AND status!='archived';
CREATE INDEX idx_cms_pages_activity_locale_order ON cms_pages(activity_id, locale, sort_order, id);

CREATE TABLE cms_forms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_uuid TEXT NOT NULL UNIQUE,
    activity_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    form_key TEXT NOT NULL,
    title TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    draft_schema_json TEXT NOT NULL,
    published_schema_json TEXT NULL,
    draft_revision INTEGER NOT NULL DEFAULT 1,
    published_revision INTEGER NULL,
    draft_updated_at TEXT NOT NULL,
    published_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id)
);
CREATE UNIQUE INDEX idx_cms_forms_activity_locale_key ON cms_forms(activity_id, locale, form_key);
CREATE INDEX idx_cms_forms_activity_locale ON cms_forms(activity_id, locale, status, id);

CREATE TABLE cms_form_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    submission_uuid TEXT NOT NULL UNIQUE,
    form_id INTEGER NOT NULL,
    page_id INTEGER NULL,
    activity_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    payload_json TEXT NOT NULL,
    form_snapshot_json TEXT NOT NULL,
    source_url TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'new',
    notes TEXT NOT NULL DEFAULT '',
    consent INTEGER NOT NULL DEFAULT 0,
    ip_hash TEXT NOT NULL,
    user_agent_hash TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(form_id) REFERENCES cms_forms(id),
    FOREIGN KEY(page_id) REFERENCES cms_pages(id),
    FOREIGN KEY(activity_id) REFERENCES activities(id)
);
CREATE INDEX idx_cms_submissions_activity_created ON cms_form_submissions(activity_id, created_at DESC);
CREATE INDEX idx_cms_submissions_form_created ON cms_form_submissions(form_id, created_at DESC);
CREATE INDEX idx_cms_submissions_status ON cms_form_submissions(status, created_at DESC);
SQL);
};
