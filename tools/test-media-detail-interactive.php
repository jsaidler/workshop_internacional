<?php
declare(strict_types=1);

function must(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"media-detail-interactive: $message\n");exit(1);}}
function media_asset(PDO $db,int $id): array {return ['id'=>$id,'active_version_id'=>7,'kind'=>'image','mime_type'=>'image/png','title'=>'Asset','url'=>'/uploads/original.png','derivatives'=>[['src'=>'/uploads/validated-768.png','format'=>'png']], 'versions'=>[['id'=>7,'mimeType'=>'image/png','url'=>'/uploads/original.png','derivatives'=>[['src'=>'/uploads/validated-768.png','format'=>'png']]]]];}
function media_asset_tags(PDO $db,int $id): array {return ['workshop'];}
require dirname(__DIR__).'/app/media_detail_service.php';

$db=new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec('CREATE TABLE activities(id INTEGER PRIMARY KEY,admin_name TEXT,slug TEXT);CREATE TABLE content_documents(activity_id INTEGER,draft_json TEXT,published_json TEXT);CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER,title TEXT,slug TEXT,locale TEXT,status TEXT,draft_document_json TEXT,published_document_json TEXT)');
$db->exec("INSERT INTO activities VALUES(1,'Workshop','workshop')");
for($i=0;$i<300;$i++){
    $payload=json_encode(['images'=>['hero'=>['mediaAssetId'=>999,'mediaVersionId'=>1]]]);
    $q=$db->prepare('INSERT INTO content_documents(activity_id,draft_json,published_json) VALUES(1,?,?)');$q->execute([$payload,$payload]);
    $page=json_encode(['html'=>'<img data-media-asset-id="999">']);
    $q=$db->prepare("INSERT INTO cms_pages(activity_id,title,slug,locale,status,draft_document_json,published_document_json) VALUES(1,'Irrelevante','x','pt-BR','published',?,?)");$q->execute([$page,$page]);
}
$legacy=json_encode(['images'=>['hero'=>['mediaAssetId'=>10,'mediaVersionId'=>7]]]);
$q=$db->prepare('INSERT INTO content_documents(activity_id,draft_json,published_json) VALUES(1,?,?)');$q->execute([$legacy,$legacy]);
$cms=json_encode(['html'=>'<section><img data-media-asset-id="10"></section>']);
$q=$db->prepare("INSERT INTO cms_pages(activity_id,title,slug,locale,status,draft_document_json,published_document_json) VALUES(1,'Home','home','pt-BR','published',?,?)");$q->execute([$cms,$cms]);

$started=microtime(true);$item=media_detail_admin($db,10);$elapsed=microtime(true)-$started;
must($item['tags']===['workshop'],'detail must keep asset tags');
must(count($item['uses'])===4,'detail must return only the matching draft/published legacy and CMS references');
must(count($item['derivatives'])===1,'PNG detail preview must expose validated responsive derivatives');
must(count($item['versions'][0]['derivatives']??[])===1,'PNG version detail must expose validated derivatives to preview UI');
must($item['url']==='/uploads/original.png','PNG detail must keep the original URL as fallback');
must($elapsed<1.0,'targeted detail lookup must stay interactive on irrelevant document volume');

$stream=(string)file_get_contents(dirname(__DIR__).'/media-stream.php');
$close=strpos($stream,'session_write_close()');$query=strpos($stream,'$assetId=');
must($close!==false&&$query!==false&&$close<$query,'media stream must release the PHP session lock before resolving/streaming the file');

$endpoint=(string)file_get_contents(dirname(__DIR__).'/admin/api/media-detail.php');
must(str_contains($endpoint,'media_detail_admin'),'interactive drawer must use the targeted detail service');
must(str_contains($endpoint,'session_write_close()'),'media detail endpoint must release its session lock before database work');

echo "Interactive media detail tests passed\n";
