<?php
declare(strict_types=1);

/** Premium communications orchestration, governance and observable delivery. */
return static function(PDO $db):void {
    $tables=array_fill_keys(array_map('strval',$db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN)),true);
    if(!isset($tables['communication_campaigns'],$tables['communication_deliveries'],$tables['student_users']))return;

    $add=static function(PDO $db,string $table,string $column,string $definition):void {
        $columns=array_column($db->query('PRAGMA table_info('.$table.')')->fetchAll(PDO::FETCH_ASSOC),'name');
        if(!in_array($column,$columns,true))$db->exec('ALTER TABLE '.$table.' ADD COLUMN '.$column.' '.$definition);
    };
    $add($db,'communication_campaigns','approval_status',"TEXT NOT NULL DEFAULT 'draft' CHECK(approval_status IN ('draft','pending','approved','rejected'))");
    $add($db,'communication_campaigns','created_by','INTEGER NULL');
    $add($db,'communication_campaigns','approved_by','INTEGER NULL');
    $add($db,'communication_campaigns','approved_at','TEXT NULL');
    $add($db,'communication_campaigns','approval_note',"TEXT NOT NULL DEFAULT ''");
    $add($db,'communication_campaigns','priority','INTEGER NOT NULL DEFAULT 50 CHECK(priority BETWEEN 0 AND 100)');
    $add($db,'communication_campaigns','segment_id','INTEGER NULL');
    $add($db,'communication_campaigns','template_id','INTEGER NULL');
    $add($db,'communication_campaigns','journey_id','INTEGER NULL');
    $add($db,'communication_campaigns','content_source',"TEXT NOT NULL DEFAULT 'legacy' CHECK(content_source IN ('legacy','builder'))");
    $add($db,'communication_campaigns','rendered_html',"TEXT NOT NULL DEFAULT ''");
    $add($db,'communication_campaigns','rendered_text',"TEXT NOT NULL DEFAULT ''");
    $add($db,'communication_campaigns','content_hash',"TEXT NOT NULL DEFAULT ''");
    $add($db,'communication_campaigns','rendered_at','TEXT NULL');
    $add($db,'communication_campaigns','goal_key',"TEXT NOT NULL DEFAULT ''");

    $add($db,'communication_deliveries','priority','INTEGER NOT NULL DEFAULT 50 CHECK(priority BETWEEN 0 AND 100)');
    $add($db,'communication_deliveries','available_at','TEXT NULL');
    $add($db,'communication_deliveries','locked_at','TEXT NULL');
    $add($db,'communication_deliveries','lock_token','TEXT NULL');
    $add($db,'communication_deliveries','last_error_at','TEXT NULL');
    $add($db,'communication_deliveries','dead_letter_at','TEXT NULL');
    $add($db,'communication_deliveries','idempotency_key','TEXT NULL');

    $db->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS communication_segments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    match_mode TEXT NOT NULL DEFAULT 'all' CHECK(match_mode IN ('all','any')),
    rules_json TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','archived')),
    created_by INTEGER NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id),
    FOREIGN KEY(created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_communication_segments_activity ON communication_segments(activity_id,status,name,id);

CREATE TABLE IF NOT EXISTS communication_segment_snapshots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    segment_id INTEGER NULL,
    campaign_id INTEGER NOT NULL,
    rules_json TEXT NOT NULL,
    member_ids_json TEXT NOT NULL,
    member_count INTEGER NOT NULL,
    snapshot_hash TEXT NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY(segment_id) REFERENCES communication_segments(id) ON DELETE SET NULL,
    FOREIGN KEY(campaign_id) REFERENCES communication_campaigns(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_communication_segment_snapshots_campaign ON communication_segment_snapshots(campaign_id,id);

CREATE TABLE IF NOT EXISTS communication_templates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'operational' CHECK(category IN ('operational','newsletter')),
    subject TEXT NOT NULL DEFAULT '',
    preheader TEXT NOT NULL DEFAULT '',
    document_version INTEGER NOT NULL DEFAULT 1,
    mjml_source TEXT NOT NULL,
    media_refs_json TEXT NOT NULL DEFAULT '{}',
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','archived')),
    created_by INTEGER NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id),
    FOREIGN KEY(created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_communication_templates_activity ON communication_templates(activity_id,status,name,id);

CREATE TABLE IF NOT EXISTS communication_journeys (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    trigger_key TEXT NOT NULL CHECK(trigger_key IN ('manual','enrollment_activated','course_completed','newsletter_opt_in','student_inactive')),
    trigger_config_json TEXT NOT NULL DEFAULT '{}',
    segment_id INTEGER NULL,
    status TEXT NOT NULL DEFAULT 'draft' CHECK(status IN ('draft','active','paused','archived')),
    created_by INTEGER NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id),
    FOREIGN KEY(segment_id) REFERENCES communication_segments(id) ON DELETE SET NULL,
    FOREIGN KEY(created_by) REFERENCES admin_users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_communication_journeys_activity ON communication_journeys(activity_id,status,id);

CREATE TABLE IF NOT EXISTS communication_journey_steps (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    journey_id INTEGER NOT NULL,
    position INTEGER NOT NULL,
    delay_minutes INTEGER NOT NULL DEFAULT 0 CHECK(delay_minutes BETWEEN 0 AND 525600),
    condition_json TEXT NOT NULL DEFAULT '{}',
    template_id INTEGER NULL,
    category TEXT NOT NULL CHECK(category IN ('operational','newsletter')),
    subject TEXT NOT NULL,
    preheader TEXT NOT NULL DEFAULT '',
    body_text TEXT NOT NULL DEFAULT '',
    body_html TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(journey_id,position),
    FOREIGN KEY(journey_id) REFERENCES communication_journeys(id) ON DELETE CASCADE,
    FOREIGN KEY(template_id) REFERENCES communication_templates(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS communication_journey_enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    journey_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    event_key TEXT NOT NULL,
    current_position INTEGER NOT NULL DEFAULT 1,
    status TEXT NOT NULL DEFAULT 'active' CHECK(status IN ('active','completed','cancelled','failed')),
    next_run_at TEXT NULL,
    context_json TEXT NOT NULL DEFAULT '{}',
    last_error TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(journey_id,student_id,event_key),
    FOREIGN KEY(journey_id) REFERENCES communication_journeys(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_communication_journey_due ON communication_journey_enrollments(status,next_run_at,id);

CREATE TABLE IF NOT EXISTS communication_channel_preferences (
    student_id INTEGER NOT NULL,
    category TEXT NOT NULL CHECK(category IN ('operational','newsletter')),
    channel TEXT NOT NULL CHECK(channel IN ('email','inbox')),
    enabled INTEGER NOT NULL DEFAULT 1 CHECK(enabled IN (0,1)),
    frequency TEXT NOT NULL DEFAULT 'immediate' CHECK(frequency IN ('immediate','daily','weekly')),
    changed_at TEXT NOT NULL,
    source TEXT NOT NULL,
    PRIMARY KEY(student_id,category,channel),
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS communication_consent_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    purpose TEXT NOT NULL CHECK(purpose IN ('newsletter','operational_email')),
    decision TEXT NOT NULL CHECK(decision IN ('granted','revoked')),
    source TEXT NOT NULL,
    evidence_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_communication_consent_student ON communication_consent_events(student_id,purpose,id DESC);

CREATE TABLE IF NOT EXISTS communication_audit_log (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_id INTEGER NOT NULL,
    admin_id INTEGER NULL,
    action TEXT NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id INTEGER NULL,
    details_json TEXT NOT NULL DEFAULT '{}',
    created_at TEXT NOT NULL,
    FOREIGN KEY(activity_id) REFERENCES activities(id),
    FOREIGN KEY(admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS idx_communication_audit_activity ON communication_audit_log(activity_id,id DESC);

CREATE TABLE IF NOT EXISTS communication_worker_runs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    run_token TEXT NOT NULL UNIQUE,
    started_at TEXT NOT NULL,
    heartbeat_at TEXT NOT NULL,
    finished_at TEXT NULL,
    status TEXT NOT NULL CHECK(status IN ('running','completed','failed')),
    claimed_count INTEGER NOT NULL DEFAULT 0,
    sent_count INTEGER NOT NULL DEFAULT 0,
    skipped_count INTEGER NOT NULL DEFAULT 0,
    failed_count INTEGER NOT NULL DEFAULT 0,
    error_summary TEXT NULL
);

CREATE TABLE IF NOT EXISTS communication_domain_diagnostics (
    id INTEGER PRIMARY KEY CHECK(id=1),
    domain TEXT NOT NULL DEFAULT '',
    dkim_selector TEXT NOT NULL DEFAULT '',
    spf_status TEXT NOT NULL DEFAULT 'unknown',
    dkim_status TEXT NOT NULL DEFAULT 'unknown',
    dmarc_status TEXT NOT NULL DEFAULT 'unknown',
    details_json TEXT NOT NULL DEFAULT '{}',
    checked_at TEXT NULL,
    checked_by INTEGER NULL,
    FOREIGN KEY(checked_by) REFERENCES admin_users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS communication_conversions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL,
    delivery_id INTEGER NULL,
    student_id INTEGER NOT NULL,
    goal_key TEXT NOT NULL,
    event_key TEXT NOT NULL UNIQUE,
    value_cents INTEGER NULL,
    occurred_at TEXT NOT NULL,
    metadata_json TEXT NOT NULL DEFAULT '{}',
    FOREIGN KEY(campaign_id) REFERENCES communication_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY(delivery_id) REFERENCES communication_deliveries(id) ON DELETE SET NULL,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_communication_conversions_campaign ON communication_conversions(campaign_id,goal_key,occurred_at,id);

CREATE UNIQUE INDEX IF NOT EXISTS idx_communication_delivery_idempotency
ON communication_deliveries(idempotency_key) WHERE idempotency_key IS NOT NULL;
CREATE INDEX IF NOT EXISTS idx_communication_delivery_ready
ON communication_deliveries(status,available_at,priority DESC,id);
SQL);
};
