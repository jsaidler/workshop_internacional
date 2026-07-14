<?php
declare(strict_types=1);
const CONTENT_DOCUMENT_KEY='main-landing-page';
function content_document(PDO $db,?int $activityId=null): ?array {$activityId??=(int)root_activity($db)['id'];$q=$db->prepare('SELECT * FROM content_documents WHERE activity_id=? LIMIT 1');$q->execute([$activityId]);return $q->fetch()?:null;}
function content_seed(PDO $db,?int $activityId=null): array {$activityId??=(int)root_activity($db)['id'];$doc=canonical_content();$json=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$q=$db->prepare('INSERT INTO content_documents(document_key,activity_id,schema_version,draft_json,published_json,draft_revision,published_revision,draft_updated_at,published_at,published_by,created_at,updated_at)VALUES(?,?,?,?,?,1,1,?,?,?,?,?)');$now=gmdate('c');$q->execute(['activity-'.$activityId,$activityId,$doc['schemaVersion'],$json,$json,$now,$now,null,$now,$now]);return content_document($db,$activityId);}
function content_current(PDO $db,?int $activityId=null): array {return content_document($db,$activityId)?:content_seed($db,$activityId);}
