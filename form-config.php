<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
security_headers();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=60, must-revalidate');
$uuid=trim((string)($_GET['uuid']??''));
if($uuid===''||strlen($uuid)>80){http_response_code(400);echo json_encode(['conditions'=>(object)[]]);exit;}
$form=cms_form_by_uuid(database(),$uuid);
if(!$form||empty($form['published_schema_json'])){http_response_code(404);echo json_encode(['conditions'=>(object)[]]);exit;}
$schema=cms_form_schema($form,true);$conditions=cms_form_conditions($schema);
echo json_encode(['conditions'=>$conditions?:new stdClass()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
