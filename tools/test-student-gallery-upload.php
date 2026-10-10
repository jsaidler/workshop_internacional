<?php
declare(strict_types=1);
const STUDENT_TEST_MEDIA_MAX_FILES=6;
const STUDENT_TEST_MEDIA_MAX_BYTES=12582912;
require __DIR__.'/../app/student_test_mobile.php';
function gallery_must(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"student-gallery-upload: $message\n");exit(1);}}
function gallery_batch(int $count): array {
    return ['name'=>array_map(static fn(int $i):string=>'imagem-'.($i+1).'.jpg',range(0,$count-1)),
        'type'=>array_fill(0,$count,'image/jpeg'),
        'tmp_name'=>array_map(static fn(int $i):string=>'/tmp/fixture-'.($i+1),range(0,$count-1)),
        'error'=>array_fill(0,$count,UPLOAD_ERR_OK),
        'size'=>array_fill(0,$count,1024)];
}
$batch=gallery_batch(2);
$result=student_test_normalize_media_batch($batch);
gallery_must(count($result)===2,'two selected gallery files were not normalized');
gallery_must($result[0]['name']==='imagem-1.jpg'&&$result[1]['name']==='imagem-2.jpg','file names or order were lost');
gallery_must($result[1]['size']===1024&&$result[1]['error']===UPLOAD_ERR_OK,'metadata shape corrupted');
$failed=false;
try{student_test_normalize_media_batch(gallery_batch(7));}catch(RuntimeException $e){$failed=str_contains($e->getMessage(),'no máximo 6');}
gallery_must($failed,'more than six selected photos were accepted');
$broken=$batch;unset($broken['error'][1]);
$failed=false;
try{student_test_normalize_media_batch($broken);}catch(RuntimeException $e){$failed=str_contains($e->getMessage(),'incompleto');}
gallery_must($failed,'partially shaped multipart payload was accepted');
$db=new PDO('sqlite::memory:');
$db->exec('CREATE TABLE student_test_media (test_id INTEGER NOT NULL)');
for($i=0;$i<5;$i++)$db->exec('INSERT INTO student_test_media(test_id) VALUES(42)');
$failed=false;
try{student_test_add_media_batch_phase($db,42,1,$batch,'result');}catch(RuntimeException $e){$failed=str_contains($e->getMessage(),'apenas 1');}
gallery_must($failed,'two images were accepted with only one remaining slot');
gallery_must((int)$db->query('SELECT COUNT(*) FROM student_test_media')->fetchColumn()===5,'capacity failure modified the stored records');
$source=(string)file_get_contents(__DIR__.'/../aluno/teste.php');
$component=(string)file_get_contents(__DIR__.'/../app/student_record_media_controls.php');
gallery_must(substr_count($source,"student_record_media_controls('scene'")===1,'scene missing shared chooser');
gallery_must(substr_count($source,"student_record_media_controls('result'")===1,'result missing shared chooser');
gallery_must(str_contains($component,'data-student-action-sheet')&&str_contains($component,'data-student-sheet-open'),'one media sheet missing');
gallery_must(str_contains($component,'name="images[]"')&&str_contains($component,' multiple>'),'gallery multiple file selector missing');
gallery_must(str_contains($component,'capture="environment"'),'camera option missing');
gallery_must(str_contains($source,'student_test_add_media_batch_phase'),'batch handler disconnected');
$cadernoFixture=(string)file_get_contents(__DIR__.'/browser-fixture/student-caderno-product-audit.php');
$cadernoTemplate=(string)file_get_contents(__DIR__.'/browser-fixture/student-caderno-product-audit.template.html');
gallery_must(str_contains($cadernoFixture,"student_record_media_controls('scene'")&&str_contains($cadernoFixture,"student_record_media_controls('result'"),'Caderno visual fixture must render canonical sheet');
gallery_must(!str_contains($cadernoTemplate,'Fotografar ou anexar')&&str_contains($cadernoTemplate,'__STUDENT_MEDIA_SCENE__')&&str_contains($cadernoTemplate,'__STUDENT_MEDIA_RESULT__'),'Caderno template has stale legacy upload actions');
echo "student-gallery-upload: ok\n";
