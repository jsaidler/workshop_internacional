<?php
declare(strict_types=1);
const CONTENT_DOCUMENT_KEY='main-landing-page';
function content_document(PDO $db): ?array {$q=$db->prepare('SELECT * FROM content_documents WHERE document_key=?');$q->execute([CONTENT_DOCUMENT_KEY]);return $q->fetch()?:null;}
function content_seed(PDO $db): array {$doc=canonical_content();$json=json_encode($doc,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$q=$db->prepare('INSERT INTO content_documents(document_key,schema_version,draft_json,published_json,draft_revision,published_revision,draft_updated_at,published_at,published_by,created_at,updated_at)VALUES(?,?,?,?,1,1,?,?,?,?,?)');$now=gmdate('c');$q->execute([CONTENT_DOCUMENT_KEY,$doc['schemaVersion'],$json,$json,$now,$now,null,$now,$now]);return content_document($db);}
function content_current(PDO $db): array {return content_document($db)?:content_seed($db);}
