<?php
declare(strict_types=1);
return static function(PDO $db): void {
    $now=gmdate('c');
    $db->exec("CREATE TABLE activities (id INTEGER PRIMARY KEY AUTOINCREMENT, admin_name TEXT NOT NULL, public_title TEXT NOT NULL, slug TEXT NOT NULL UNIQUE, status TEXT NOT NULL DEFAULT 'active', is_root INTEGER NOT NULL DEFAULT 0, current_draft_id INTEGER NULL, published_content_id INTEGER NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL);
CREATE TABLE media_assets (id INTEGER PRIMARY KEY AUTOINCREMENT, asset_uuid TEXT NOT NULL UNIQUE, kind TEXT NOT NULL, title TEXT NOT NULL, default_alt TEXT NOT NULL DEFAULT '', original_name TEXT NOT NULL, mime_type TEXT NOT NULL, byte_size INTEGER NOT NULL, width INTEGER NULL, height INTEGER NULL, duration REAL NULL, checksum TEXT NOT NULL, processing_status TEXT NOT NULL, active_version_id INTEGER NULL, archived_at TEXT NULL, created_at TEXT NOT NULL, updated_at TEXT NOT NULL);
CREATE TABLE media_versions (id INTEGER PRIMARY KEY AUTOINCREMENT, asset_id INTEGER NOT NULL, version_uuid TEXT NOT NULL UNIQUE, original_path TEXT NOT NULL, mime_type TEXT NOT NULL, byte_size INTEGER NOT NULL, width INTEGER NULL, height INTEGER NULL, duration REAL NULL, checksum TEXT NOT NULL, poster_timestamp REAL NULL, processing_status TEXT NOT NULL, error_message TEXT NULL, created_at TEXT NOT NULL, FOREIGN KEY(asset_id) REFERENCES media_assets(id));
CREATE TABLE media_derivatives (id INTEGER PRIMARY KEY AUTOINCREMENT, version_id INTEGER NOT NULL, derivative_kind TEXT NOT NULL, width INTEGER NULL, height INTEGER NULL, format TEXT NOT NULL, path TEXT NOT NULL, byte_size INTEGER NOT NULL DEFAULT 0, created_at TEXT NOT NULL, UNIQUE(version_id,derivative_kind,width,format), FOREIGN KEY(version_id) REFERENCES media_versions(id));
CREATE INDEX idx_activities_slug ON activities(slug); CREATE INDEX idx_media_versions_asset ON media_versions(asset_id); CREATE INDEX idx_media_derivatives_version ON media_derivatives(version_id);");
    $db->prepare('INSERT INTO activities(admin_name,public_title,slug,status,is_root,created_at,updated_at) VALUES(?,?,?,?,1,?,?)')->execute(['Direct Positive Workshop','Direct Positive X-Ray Film','direct-positive-workshop','active',$now,$now]);
    $activityId=(int)$db->lastInsertId();
    $columns=array_column($db->query('PRAGMA table_info(content_documents)')->fetchAll(),'name');
    if(!in_array('activity_id',$columns,true)) $db->exec('ALTER TABLE content_documents ADD COLUMN activity_id INTEGER NULL');
    $db->prepare('UPDATE content_documents SET activity_id=? WHERE activity_id IS NULL')->execute([$activityId]);
    $interestColumns=array_column($db->query('PRAGMA table_info(interest_submissions)')->fetchAll(),'name');
    if(!in_array('activity_id',$interestColumns,true)) { $db->exec('ALTER TABLE interest_submissions ADD COLUMN activity_id INTEGER NULL'); $db->prepare('UPDATE interest_submissions SET activity_id=? WHERE activity_id IS NULL')->execute([$activityId]); }
    $db->exec('CREATE INDEX IF NOT EXISTS idx_content_activity ON content_documents(activity_id); CREATE INDEX IF NOT EXISTS idx_interest_activity ON interest_submissions(activity_id);');
    $seed=$db->query("SELECT 1 FROM content_documents WHERE activity_id=$activityId LIMIT 1")->fetchColumn();
    if(!$seed){$doc=json_encode(canonical_content(),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$db->prepare('INSERT INTO content_documents(document_key,activity_id,schema_version,draft_json,published_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at) VALUES(?,?,?,?,?,1,1,?,?,?,?,?)')->execute(['activity-'.$activityId,$activityId,1,$doc,$doc,$now,$now,$now,$now]);}
};
