<?php
declare(strict_types=1);
require __DIR__.'/../app/media_service.php';
require __DIR__.'/../app/media_admin_service.php';
require __DIR__.'/../app/media_maintenance.php';

function maintenance_expect(bool $ok,string $message):void{if(!$ok){fwrite(STDERR,"test-media-maintenance: $message\n");exit(1);}}
if(!extension_loaded('gd')){fwrite(STDOUT,"test-media-maintenance: skipped (GD unavailable)\n");exit(0);}

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE media_assets (id INTEGER PRIMARY KEY, asset_uuid TEXT, kind TEXT, title TEXT, default_alt TEXT DEFAULT "", caption TEXT DEFAULT "", description TEXT DEFAULT "", focal_x REAL DEFAULT 50, focal_y REAL DEFAULT 50, original_name TEXT, mime_type TEXT, byte_size INTEGER, width INTEGER, height INTEGER, duration REAL, checksum TEXT, processing_status TEXT, active_version_id INTEGER, archived_at TEXT, created_at TEXT, updated_at TEXT); CREATE TABLE media_versions (id INTEGER PRIMARY KEY, asset_id INTEGER, version_uuid TEXT, original_path TEXT, mime_type TEXT, byte_size INTEGER, width INTEGER, height INTEGER, duration REAL, checksum TEXT, processing_status TEXT, error_message TEXT, created_at TEXT); CREATE TABLE media_derivatives (id INTEGER PRIMARY KEY AUTOINCREMENT, version_id INTEGER, derivative_kind TEXT, width INTEGER, height INTEGER, format TEXT, mime_type TEXT, path TEXT, byte_size INTEGER, checksum TEXT, created_at TEXT);');
$uuid=str_repeat('a',32);$version=str_repeat('b',32);$root=media_upload_root().'/'.$uuid.'/'.$version.'/original';media_mkdir($root);$file=$root.'/file.png';

// Palette transparency reproduces the same class of PNG used by QR assets.
// The regression is not merely "alpha preserved": the generated file must
// still contain opaque/visible pixels after regeneration.
$image=imagecreate(400,240);$clear=imagecolorallocate($image,255,255,255);$solid=imagecolorallocate($image,12,20,30);imagefill($image,0,0,$clear);imagecolortransparent($image,$clear);imagefilledrectangle($image,120,60,280,180,$solid);imagepng($image,$file,9);imagedestroy($image);
$now=gmdate('c');$relative=$uuid.'/'.$version.'/original/file.png';
$db->prepare('INSERT INTO media_assets(id,asset_uuid,kind,title,original_name,mime_type,byte_size,width,height,checksum,processing_status,active_version_id,created_at,updated_at) VALUES(1,?,"image","alpha","alpha.png","image/png",?,400,240,?,"ready",1,?,?)')->execute([$uuid,filesize($file),hash_file('sha256',$file),$now,$now]);
$db->prepare('INSERT INTO media_versions(id,asset_id,version_uuid,original_path,mime_type,byte_size,width,height,checksum,processing_status,created_at) VALUES(1,1,?,?,?,?,?,?,?,"ready",?)')->execute([$version,$relative,'image/png',filesize($file),400,240,hash_file('sha256',$file),$now]);
try{
    $decoded=media_maintenance_decode_source($file,'image/png');
    maintenance_expect(($decoded['engine']??'')==='gd','PNG maintenance must prefer GD instead of ImageMagick when GD is available');
    media_release_image($decoded);

    $first=media_regenerate_image_version($db,1,1);
    maintenance_expect(($first['derivatives']??0)>0,'no derivatives were generated');
    maintenance_expect(!empty($first['transparencyPreserved']),'service did not detect source transparency');
    $firstPng=$db->query("SELECT path FROM media_derivatives WHERE format='png' ORDER BY width DESC LIMIT 1")->fetchColumn();
    maintenance_expect(is_string($firstPng)&&$firstPng!=='','PNG derivative missing');
    maintenance_expect(str_contains($firstPng,'/responsive-'),'regeneration must publish into a unique responsive directory');
    $firstAbsolute=media_upload_root().'/'.$firstPng;
    maintenance_expect(is_file($firstAbsolute),'first generated derivative missing on disk');
    maintenance_expect(media_maintenance_file_has_visible_pixels($firstAbsolute,'image/png'),'PNG derivative became fully transparent');
    $out=imagecreatefrompng($firstAbsolute);maintenance_expect($out instanceof GdImage,'PNG derivative unreadable');
    $transparent=imagecolorsforindex($out,imagecolorat($out,0,0));
    $visible=imagecolorsforindex($out,imagecolorat($out,(int)floor(imagesx($out)/2),(int)floor(imagesy($out)/2)));
    imagedestroy($out);
    maintenance_expect((int)($transparent['alpha']??0)>0,'transparent pixel became opaque');
    maintenance_expect((int)($visible['alpha']??127)<127,'visible source content became transparent');
    maintenance_expect((int)($visible['red']??255)<80&&(int)($visible['green']??255)<80&&(int)($visible['blue']??255)<80,'visible source content changed unexpectedly');

    $second=media_regenerate_image_version($db,1,1);
    $secondPng=$db->query("SELECT path FROM media_derivatives WHERE format='png' ORDER BY width DESC LIMIT 1")->fetchColumn();
    maintenance_expect(is_string($secondPng)&&$secondPng!=='','second PNG derivative missing');
    maintenance_expect($secondPng!==$firstPng,'successive regenerations must change derivative URL identity');
    maintenance_expect(($second['derivativeDirectory']??'')!==($first['derivativeDirectory']??''),'successive regenerations must use different derivative directories');
    maintenance_expect(is_file(media_upload_root().'/'.$secondPng),'second generated derivative missing on disk');
    maintenance_expect(media_maintenance_file_has_visible_pixels(media_upload_root().'/'.$secondPng,'image/png'),'second regeneration became fully transparent');
    maintenance_expect(!is_file($firstAbsolute),'obsolete derivative directory must be removed after database commit');
    maintenance_expect($db->query("SELECT COUNT(*) FROM media_derivatives WHERE path LIKE '%/responsive-%'")->fetchColumn()>0,'database must point at immutable regenerated derivative URLs');

    $repair=(string)file_get_contents(__DIR__.'/../migrations/028_repair_png_regeneration_with_gd.php');
    maintenance_expect(str_contains($repair,'media_regenerate_image_version'),'migration 028 must republish existing PNG derivatives through the GD-preferred regenerator');
    maintenance_expect(str_contains($repair,'DELETE FROM media_derivatives WHERE version_id=?'),'failed migration repair must invalidate bad derivative references and fall back to the original');
    fwrite(STDOUT,"Media regeneration visible-content test passed\n");
}finally{media_remove_tree(media_upload_root().'/'.$uuid);}
