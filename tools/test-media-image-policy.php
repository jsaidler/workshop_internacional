<?php
declare(strict_types=1);

require __DIR__.'/../app/media_service.php';

function media_test_expect(bool $ok,string $message):void{
    if(!$ok){fwrite(STDERR,"test-media-image-policy: $message\n");exit(1);}
}

$png=media_image_derivative_formats('image/png',false,true);
media_test_expect(in_array('png',$png,true),'PNG derivatives must include PNG.');
media_test_expect(!in_array('jpeg',$png,true),'PNG must never be converted to JPEG.');
media_test_expect(in_array('webp',$png,true),'PNG may receive a WebP optimization derivative.');
media_test_expect(media_primary_derivative_format('image/png',['jpeg','webp'])===null,'Legacy PNG assets without a PNG derivative must fall back to the untouched original.');
media_test_expect(media_primary_derivative_format('image/png',['png','webp'])==='png','PNG delivery must prefer PNG to guarantee transparency and exact graphics semantics.');

$jpeg=media_image_derivative_formats('image/jpeg',false,true);
media_test_expect($jpeg===['jpeg','webp'],'JPEG should keep a JPEG fallback and an optimized WebP derivative.');
media_test_expect(media_primary_derivative_format('image/jpeg',['jpeg','webp'])==='webp','JPEG delivery should prefer WebP when available.');

$webpAlpha=media_image_derivative_formats('image/webp',true,true);
media_test_expect(in_array('webp',$webpAlpha,true),'Transparent WebP must remain WebP-capable.');
media_test_expect(in_array('png',$webpAlpha,true),'Transparent WebP should also receive a PNG lossless fallback derivative.');
media_test_expect(!in_array('jpeg',$webpAlpha,true),'Transparent WebP must never become JPEG.');

media_test_expect(media_preserve_lossless('image/png',false),'PNG is always handled as lossless artwork even when alpha detection is inconclusive.');
media_test_expect(media_preserve_lossless('image/jpeg',true),'Any detected alpha must forbid lossy flattening.');
media_test_expect(!media_preserve_lossless('image/jpeg',false),'Opaque JPEG may use normal photographic optimization.');

echo "Media image preservation policy tests passed\n";
