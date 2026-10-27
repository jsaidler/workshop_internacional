<?php
declare(strict_types=1);

/** Scheduling, persistent academic inbox and provider delivery evidence. */
return static function(PDO $db): void {
    $tables=array_fill_keys(array_map('strval',$db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN)),true);
    if(!isset($tables['communication_campaigns'],$tables['communication_deliveries']))return;

    $campaignColumns=array_column($db->query('PRAGMA table_info(communication_campaigns)')->fetchAll(PDO::FETCH_ASSOC),'name');
    if(!in_array('scheduled_at',$campaignColumns,true))$db->exec('ALTER TABLE communication_campaigns ADD COLUMN scheduled_at TEXT NULL');
    if(!in_array('schedule_timezone',$campaignColumns,true))$db->exec("ALTER TABLE communication_campaigns ADD COLUMN schedule_timezone TEXT NOT NULL DEFAULT 'America/Sao_Paulo'");
    if(!in_array('schedule_error',$campaignColumns,true))$db->exec('ALTER TABLE communication_campaigns ADD COLUMN schedule_error TEXT NULL');

    $db->exec(<<<'SQL'
CREATE INDEX IF NOT EXISTS idx_communication_campaigns_schedule
ON communication_campaigns(status,scheduled_at,activity_id);

CREATE TABLE IF NOT EXISTS student_communication_inbox (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    campaign_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    read_at TEXT NULL,
    UNIQUE(campaign_id,student_id),
    FOREIGN KEY(campaign_id) REFERENCES communication_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS idx_student_communication_inbox
ON student_communication_inbox(student_id,read_at,id);

CREATE TABLE IF NOT EXISTS communication_delivery_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    channel TEXT NOT NULL CHECK(channel IN ('campaign','admin_notice')),
    delivery_id INTEGER NOT NULL,
    event_key TEXT NOT NULL,
    provider_event TEXT NOT NULL,
    provider_message_id TEXT NOT NULL,
    happened_at TEXT NOT NULL,
    reason TEXT NULL,
    created_at TEXT NOT NULL,
    UNIQUE(channel,event_key)
);
CREATE INDEX IF NOT EXISTS idx_communication_delivery_events_delivery
ON communication_delivery_events(channel,delivery_id,happened_at,id);

CREATE TABLE IF NOT EXISTS communication_webhook_settings (
    id INTEGER PRIMARY KEY CHECK(id=1),
    provider_webhook_id INTEGER NULL,
    configured_at TEXT NULL,
    last_event_at TEXT NULL,
    last_error TEXT NULL
);

CREATE TABLE IF NOT EXISTS communication_email_suppressions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_id INTEGER NOT NULL,
    email TEXT NOT NULL,
    reason TEXT NOT NULL,
    provider_message_id TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(student_id,email),
    FOREIGN KEY(student_id) REFERENCES student_users(id) ON DELETE CASCADE
);
SQL);
};
