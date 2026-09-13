<?php
declare(strict_types=1);

function must_png(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"png-original-delivery: $message\n");exit(1);}}

$fixtureKind='png';
$derivativeCalls=0;
function media_asset(PDO $db,int $id): array {
    global $fixtureKind;
    if($fixtureKind==='png')return [
        'id'=>$id,'kind'=>'image','active_version_id'=>7,'mime_type'=>'image/png','url'=>'/uploads/media/a/v/original/file.png',
        'focal_x'=>50,'focal_y'=>50,
        'versions'=>[['id'=>7,'mimeType'=>'image/png','url'=>'/uploads/media/a/v/original/file.png']],
    ];
    return [
        'id'=>$id,'kind'=>'image','active_version_id'=>8,'mime_type'=>'image/jpeg','url'=>'/uploads/media/b/v/original/file.jpg',
        'focal_x'=>50,'focal_y'=>50,
        'versions'=>[['id'=>8,'mimeType'=>'image/jpeg','url'=>'/uploads/media/b/v/original/file.jpg']],
    ];
}
function media_image_sources(PDO $db,?int $assetId,?int $versionId,string $fallback): array {
    global $derivativeCalls;
    $derivativeCalls++;
    return ['src'=>'/uploads/media/b/v/responsive/1024.webp','srcset'=>'/uploads/media/b/v/responsive/480.webp 480w, /uploads/media/b/v/responsive/1024.webp 1024w'];
}
require dirname(__DIR__).'/app/media_admin_service.php';

$db=new PDO('sqlite::memory:');

$png='<img src="/uploads/media/a/v/responsive/1024.png" srcset="/uploads/media/a/v/responsive/480.png 480w, /uploads/media/a/v/responsive/1024.png 1024w" sizes="50vw" data-media-asset-id="10" data-media-version-id="7" data-media-version-mode="pinned" alt="QR">';
$out=media_resolve_cms_html($db,$png);
must_png(str_contains($out,'src="/uploads/media/a/v/original/file.png"'),'PNG must resolve to the untouched pinned original');
must_png(!str_contains($out,'srcset='),'PNG output must remove derivative srcset');
must_png(!str_contains($out,'sizes='),'PNG output must remove derivative sizes');
must_png($derivativeCalls===0,'PNG resolution must not invoke responsive derivative selection');

$fixtureKind='jpeg';
$jpeg='<img src="/uploads/media/b/v/original/file.jpg" data-media-asset-id="20" data-media-version-id="8" data-media-version-mode="pinned" alt="Foto">';
$out=media_resolve_cms_html($db,$jpeg);
must_png(str_contains($out,'src="/uploads/media/b/v/responsive/1024.webp"'),'JPEG should keep responsive derivative delivery');
must_png(str_contains($out,'srcset="/uploads/media/b/v/responsive/480.webp 480w, /uploads/media/b/v/responsive/1024.webp 1024w"'),'JPEG should keep responsive srcset');
must_png($derivativeCalls===1,'non-PNG image resolution should still use derivative selection');

echo "PNG original delivery tests passed\n";
