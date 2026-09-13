<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/media_list_service.php';
function must(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"media-list-service: $message\n");exit(1);}}
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec('CREATE TABLE media_tags(id INTEGER PRIMARY KEY,name TEXT);CREATE TABLE media_asset_tags(asset_id INTEGER,tag_id INTEGER);CREATE TABLE content_documents(draft_json TEXT,published_json TEXT);CREATE TABLE cms_pages(status TEXT,draft_document_json TEXT,published_document_json TEXT)');
$db->exec("INSERT INTO media_tags VALUES(1,'retrato'),(2,'workshop');INSERT INTO media_asset_tags VALUES(10,1),(10,2),(20,2)");
$legacy=json_encode(['images'=>['hero'=>['mediaAssetId'=>10], 'other'=>['mediaAssetId'=>20]]]);
$q=$db->prepare('INSERT INTO content_documents(draft_json,published_json) VALUES(?,?)');$q->execute([$legacy,$legacy]);
$cms=json_encode(['html'=>'<img data-media-asset-id="10"><img data-media-asset-id="10"><img data-media-asset-id="20">']);
$q=$db->prepare("INSERT INTO cms_pages(status,draft_document_json,published_document_json) VALUES('published',?,?)");$q->execute([$cms,$cms]);
$tags=media_list_tags_bulk($db,[10,20]);$counts=media_list_usage_counts($db,[10,20,30]);
must($tags[10]===['retrato','workshop'],'bulk tags must preserve ordered tags');
must($tags[20]===['workshop'],'bulk tags must include second asset');
must($counts[10]===4,'asset 10 should count once per legacy/CMS document state');
must($counts[20]===4,'asset 20 should count once per legacy/CMS document state');
must($counts[30]===0,'unused asset must stay at zero');
echo "Media list service tests passed\n";
