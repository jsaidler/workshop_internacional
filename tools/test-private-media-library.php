<?php
declare(strict_types=1);
function fail_private_media(string $message): never {fwrite(STDERR,"private-media-library: $message\n");exit(1);}
$root=dirname(__DIR__);
$studentArea=(string)file_get_contents($root.'/admin/student-area.php');$adminJs=(string)file_get_contents($root.'/assets/admin.js');$mediaPage=(string)file_get_contents($root.'/admin/media.php');$privacyJs=(string)file_get_contents($root.'/assets/admin-media-privacy.js');$material=(string)file_get_contents($root.'/app/student_material.php');$htaccess=(string)file_get_contents($root.'/.htaccess');$mediaFile=(string)file_get_contents($root.'/media-file.php');
if(!str_contains($adminJs,"textContent.trim()==='Imagens privadas'")||!str_contains($adminJs,'Mídia protegida'))fail_private_media('legacy protected-media uploader is not replaced in the admin UI');
if(!str_contains($adminJs,'/admin/api/protected-media-slots.php'))fail_private_media('protected slots do not use canonical asset binding API');
if(!str_contains($mediaPage,'admin-media-privacy.js')||!str_contains($privacyJs,"<option value=\"private\">Privada</option>"))fail_private_media('media manager does not expose public/private access');
if(str_contains($material,'function student_private_media_bind_slot'))fail_private_media('student material still owns a second editorial upload writer');
if(!str_contains($material,'media_page_slot_binding'))fail_private_media('protected material does not resolve canonical media assets');
if(!str_contains($htaccess,'RewriteRule ^uploads/media/(.+)$ media-file.php?path=$1'))fail_private_media('direct media URLs are not visibility-gated');
if(!str_contains($mediaFile,'media_is_private($asset)&&!current_admin()'))fail_private_media('private static media can bypass authorization gate');

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON; CREATE TABLE media_assets(id INTEGER PRIMARY KEY,asset_uuid TEXT,kind TEXT,title TEXT,default_alt TEXT,original_name TEXT,archived_at TEXT,updated_at TEXT); CREATE TABLE cms_pages(id INTEGER PRIMARY KEY);');
(require $root.'/migrations/064_unify_private_editorial_media.php')($db);
$cols=array_column($db->query('PRAGMA table_info(media_assets)')->fetchAll(PDO::FETCH_ASSOC),'name');if(!in_array('visibility',$cols,true))fail_private_media('media visibility column missing');
$tables=$db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);if(!in_array('course_page_media_slots',$tables,true))fail_private_media('canonical slot binding table missing');

$GLOBALS['pm_page']=['id'=>1,'activity_id'=>4,'access_level'=>'enrolled'];$GLOBALS['pm_asset']=['id'=>7,'asset_uuid'=>'abc','kind'=>'image','title'=>'Info','default_alt'=>'','original_name'=>'info.png','visibility'=>'private','archived_at'=>null];
function student_private_media_slot_key(string $v): string{return trim($v);}function cms_page_by_id(PDO $db,int $id): ?array{return $GLOBALS['pm_page'];}function student_page_is_protected(array $page): bool{return ($page['access_level']??'')==='enrolled';}function cms_page_doc(array $page,bool $published=false): array{return ['html'=>''];}function student_page_private_media_slots_from_document(array $doc): array{return ['slot-a'=>['key'=>'slot-a','alt'=>'A']];}function media_asset(PDO $db,int $id): array{return $GLOBALS['pm_asset'];}function utc_now(): string{return '2026-09-25T00:00:00Z';}
require_once $root.'/app/media_privacy.php';
media_page_slot_bind($db,4,1,'slot-a',7);$binding=$db->query('SELECT * FROM course_page_media_slots')->fetch();if(!$binding||(int)$binding['media_asset_id']!==7)fail_private_media('private media binding failed');
$GLOBALS['pm_asset']['visibility']='public';try{media_page_slot_bind($db,4,1,'slot-a',7);fail_private_media('public media was accepted in a protected slot');}catch(RuntimeException $e){if(!str_contains($e->getMessage(),'privada'))throw $e;}

echo "private-media-library: ok\n";
