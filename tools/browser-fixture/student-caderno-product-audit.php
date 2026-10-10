<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/student_record_media_controls.php';
$template=file_get_contents(__DIR__.'/student-caderno-product-audit.template.html');
if(!is_string($template)||substr_count($template,'__STUDENT_MEDIA_SCENE__')!==1||substr_count($template,'__STUDENT_MEDIA_RESULT__')!==1){http_response_code(500);exit('Fixture do Caderno incompleta.');}
header('Content-Type: text/html; charset=utf-8');
echo str_replace(['__STUDENT_MEDIA_SCENE__','__STUDENT_MEDIA_RESULT__'],[student_record_media_controls('scene',6),student_record_media_controls('result',6)],$template);
