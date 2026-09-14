<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/media_list_service.php';
function media_url(string $path): string {return '/media/'.ltrim($path,'/');}
function media_video_url(int $assetId,int $versionId,string $path): string {return '/video/'.$assetId.'/'.$versionId;}
function must(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"media-list-service: $message\n");exit(1);}}
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec('CREATE TABLE media_tags(id INTEGER PRIMARY KEY,name TEXT);CREATE TABLE media_asset_tags(asset_id INTEGER,tag_id INTEGER);CREATE TABLE content_documents(draft_json TEXT,published_json TEXT);CREATE TABLE cms_pages(status TEXT,draft_document_json TEXT,published_document_json TEXT);CREATE TABLE media_assets(id INTEGER PRIMARY KEY,active_version_id INTEGER,kind TEXT,title TEXT,original_name TEXT,mime_type TEXT,byte_size INTEGER,width INTEGER,height INTEGER,archived_at TEXT);CREATE TABLE media_versions(id INTEGER PRIMARY KEY,asset_id INTEGER,original_path TEXT,version_uuid TEXT,processing_status TEXT);CREATE TABLE media_derivatives(id INTEGER PRIMARY KEY,version_id INTEGER,derivative_kind TEXT,width INTEGER,height INTEGER,format TEXT,mime_type TEXT,path TEXT,byte_size INTEGER,checksum TEXT)');
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

$db->exec("INSERT INTO media_assets(id,active_version_id,kind,title,original_name,mime_type,byte_size,width,height,archived_at) VALUES(10,100,'image','QR','qr.png','image/png',1000,1200,800,NULL);INSERT INTO media_versions VALUES(100,10,'a/original.png','v100','ready');INSERT INTO media_derivatives VALUES(1,100,'responsive',480,320,'png','image/png','a/480.png',500,'x'),(2,100,'responsive',1024,683,'png','image/png','a/1024.png',800,'y')");
$grid=media_list_grid_items($db,'active');
must(count($grid)===1,'grid should return the active asset');
must($grid[0]['title']==='QR','grid should keep asset metadata');
must(count($grid[0]['derivatives'])===2,'PNG grid/editor previews must expose validated responsive derivatives');
must(count($grid[0]['uses'])===4,'grid should expose usage count without detailed usage rows');
must($grid[0]['versions']===[],'grid must not load version history');
must($grid[0]['url']==='/media/a/original.png','grid should expose original URL as fallback');
echo "Media list service tests passed\n";
