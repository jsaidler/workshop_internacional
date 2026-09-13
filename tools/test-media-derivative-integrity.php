<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/media_service.php';

function derivative_expect(bool $ok,string $message): void {
    if(!$ok){fwrite(STDERR,"media-derivative-integrity: $message\n");exit(1);}
}

if(!extension_loaded('imagick')&&!extension_loaded('gd')){
    fwrite(STDERR,"media-derivative-integrity: no image engine available\n");
    exit(1);
}

$dir=sys_get_temp_dir().'/workshop-media-integrity-'.bin2hex(random_bytes(5));
if(!mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('test_temp_failed');
$out=$dir.'/derived.png';
$source=null;

try{
    if(extension_loaded('imagick')){
        $image=new Imagick();
        $image->newImage(400,400,new ImagickPixel('transparent'),'png');
        $draw=new ImagickDraw();$draw->setFillColor(new ImagickPixel('black'));
        $draw->rectangle(40,40,360,360);$draw->setFillColor(new ImagickPixel('white'));$draw->rectangle(100,100,300,300);
        $image->drawImage($draw);
        // Reproduce the class of source metadata that caused the production
        // corruption: raster pixels are valid, but ImageMagick carries a
        // larger virtual page/canvas with an offset.
        $image->setImagePage(1600,400,300,0);
        $source=['engine'=>'imagick','image'=>$image,'width'=>400,'height'=>400,'alpha'=>true,'mime'=>'image/png'];
    }else{
        $image=imagecreatetruecolor(400,400);imagealphablending($image,false);imagesavealpha($image,true);
        $clear=imagecolorallocatealpha($image,0,0,0,127);imagefill($image,0,0,$clear);
        $black=imagecolorallocatealpha($image,0,0,0,0);$white=imagecolorallocatealpha($image,255,255,255,0);
        imagefilledrectangle($image,40,40,360,360,$black);imagefilledrectangle($image,100,100,300,300,$white);
        $source=['engine'=>'gd','image'=>$image,'width'=>400,'height'=>400,'alpha'=>true,'mime'=>'image/png'];
    }

    media_write_image($source,200,200,'png',$out);
    media_verify_image_derivative($out,'image/png',200,200,true);
    [$width,$height]=media_image_dimensions($out);
    derivative_expect($width===200&&$height===200,'PNG derivative must have the exact requested raster dimensions');

    if(extension_loaded('imagick')){
        $probe=new Imagick($out);$page=$probe->getImagePage();$pixel=$probe->getImagePixelColor(20,20)->getColor();$probe->clear();
        derivative_expect(((int)($page['x']??0))===0&&((int)($page['y']??0))===0,'PNG derivative must not retain virtual-page offsets');
        derivative_expect(in_array((int)($page['width']??0),[0,200],true)&&in_array((int)($page['height']??0),[0,200],true),'PNG derivative virtual page must match its raster');
        derivative_expect(($pixel['r']??255)<80&&($pixel['g']??255)<80&&($pixel['b']??255)<80,'resized image content must remain in the expected raster area instead of collapsing into a strip');
    }else{
        $probe=imagecreatefrompng($out);derivative_expect($probe instanceof GdImage,'generated PNG must decode');$rgba=imagecolorsforindex($probe,imagecolorat($probe,20,20));imagedestroy($probe);
        derivative_expect(($rgba['red']??255)<80&&($rgba['green']??255)<80&&($rgba['blue']??255)<80,'resized image content must remain in the expected raster area');
    }

    $migration=(string)file_get_contents(dirname(__DIR__).'/migrations/024_rebuild_png_derivatives.php');
    derivative_expect(str_contains($migration,'media_regenerate_image_version'),'legacy PNG versions must be rebuilt with the canonical writer');
    derivative_expect(str_contains($migration,'DELETE FROM media_derivatives WHERE version_id=?'),'failed legacy repair must invalidate old derivatives so delivery falls back to the original');
}finally{
    media_release_image($source);
    if(is_file($out))@unlink($out);
    @rmdir($dir);
}

echo "Media derivative integrity tests passed\n";
