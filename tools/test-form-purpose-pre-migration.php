<?php
declare(strict_types=1);
function fail_pre_purpose(string $message): never {fwrite(STDERR,"form-purpose-pre-migration: $message\n");exit(1);}
function must_pre_purpose(bool $condition,string $message): void {if(!$condition)fail_pre_purpose($message);}
function utc_now(): string {return '2026-10-17T00:00:00Z';}
function cms_form_by_id(PDO $db,int $id): ?array {
    $q=$db->prepare('SELECT * FROM cms_forms WHERE id=?');
    $q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;
}
require dirname(__DIR__).'/app/form_purpose.php';
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE cms_forms(id INTEGER PRIMARY KEY,activity_id INTEGER,form_key TEXT,status TEXT,updated_at TEXT)");
$db->exec("INSERT INTO cms_forms VALUES(1,1,'registration','active','now'),(2,1,'interest','active','now')");
$early=cms_form_set_purpose($db,1,'enrollment');
must_pre_purpose(($early['purpose']??'')==='enrollment','pre-069 migration seeding did not retain inferred purpose');
must_pre_purpose(count($db->query('PRAGMA table_info(cms_forms)')->fetchAll())===5,'pre-069 helper mutated the historical schema');
$migration=require dirname(__DIR__).'/migrations/069_form_purpose_authority.php';$migration($db);
must_pre_purpose((string)$db->query('SELECT purpose FROM cms_forms WHERE id=1')->fetchColumn()==='enrollment','migration 069 did not backfill the registration purpose');
must_pre_purpose((string)$db->query('SELECT purpose FROM cms_forms WHERE id=2')->fetchColumn()==='interest','migration 069 did not backfill the interest purpose');
$updated=cms_form_set_purpose($db,2,'registration');
must_pre_purpose(($updated['purpose']??'')==='registration','post-069 purpose was not returned');
must_pre_purpose((string)$db->query('SELECT purpose FROM cms_forms WHERE id=2')->fetchColumn()==='registration','post-069 purpose did not persist');
echo "form-purpose-pre-migration: ok\n";
