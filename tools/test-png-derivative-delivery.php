<?php
declare(strict_types=1);

function must_png_delivery(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"png-derivative-delivery: $message\n");exit(1);}}

$fixtureKind='png';
$derivativeCalls=0;
function media_asset(PDO $db,int $id): array {
    global $fixtureKind;
    return $fixtureKind==='png'
        ? ['id'=>$id,'kind'=>'image','active_version_id'=>7,'mime_type'=>'image/png','url'=>'/uploads/media/a/v/original/file.png','focal_x'=>50,'focal_y'=>50]
        : ['id'=>$id,'kind'=>'image','active_version_id'=>8,'mime_type'=>'image/jpeg','url'=>'/uploads/media/b/v/original/file.jpg','focal_x'=>50,'focal_y'=>50];
}
function media_image_sources(PDO $db,?int $assetId,?int $versionId,string $fallback): array {
    global $derivativeCalls,$fixtureKind;
    $derivativeCalls++;
    if($fixtureKind==='png')return ['src'=>'/uploads/media/a/v/responsive/1024.png','srcset'=>'/uploads/media/a/v/responsive/480.png 480w, /uploads/media/a/v/responsive/1024.png 1024w'];
    return ['src'=>'/uploads/media/b/v/responsive/1024.webp','srcset'=>'/uploads/media/b/v/responsive/480.webp 480w, /uploads/media/b/v/responsive/1024.webp 1024w'];
}
require dirname(__DIR__).'/app/media_admin_service.php';

$db=new PDO('sqlite::memory:');
$png='<img src="/uploads/media/a/v/original/file.png" data-media-asset-id="10" data-media-version-id="7" data-media-version-mode="pinned" alt="QR">';
$out=media_resolve_cms_html($db,$png);
must_png_delivery(str_contains($out,'src="/uploads/media/a/v/responsive/1024.png"'),'PNG must use its validated responsive derivative');
must_png_delivery(str_contains($out,'srcset="/uploads/media/a/v/responsive/480.png 480w, /uploads/media/a/v/responsive/1024.png 1024w"'),'PNG must expose responsive srcset');
must_png_delivery($derivativeCalls===1,'PNG resolution must use the canonical responsive source selector');

$fixtureKind='jpeg';
$jpeg='<img src="/uploads/media/b/v/original/file.jpg" data-media-asset-id="20" data-media-version-id="8" data-media-version-mode="pinned" alt="Foto">';
$out=media_resolve_cms_html($db,$jpeg);
must_png_delivery(str_contains($out,'src="/uploads/media/b/v/responsive/1024.webp"'),'JPEG should keep responsive derivative delivery');
must_png_delivery(str_contains($out,'srcset="/uploads/media/b/v/responsive/480.webp 480w, /uploads/media/b/v/responsive/1024.webp 1024w"'),'JPEG should keep responsive srcset');
must_png_delivery($derivativeCalls===2,'JPEG must use the same canonical responsive source selector');

$renderer=(string)file_get_contents(dirname(__DIR__).'/app/cms_renderer.php');
$expandPos=strpos($renderer,'$body=cms_expand_forms((string)$document[\'html\']');
$imagePos=strpos($renderer,'$body=media_resolve_cms_html($db,$body)');
must_png_delivery($expandPos!==false&&$imagePos!==false&&$expandPos<$imagePos,'form content must be expanded before media resolution so QR/media inside form contentBlocks is resolved');

echo "PNG derivative delivery tests passed\n";
