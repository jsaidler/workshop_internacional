<?php
declare(strict_types=1);
function content_json(array $payload,int $status=200): never {http_response_code($status);header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');echo json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function content_api_input(): array {if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||!str_contains(strtolower($_SERVER['CONTENT_TYPE']??''),'application/json'))content_json(['error'=>['code'=>'invalid_request']],415);if((int)($_SERVER['CONTENT_LENGTH']??0)>500000)content_json(['error'=>['code'=>'document_too_large']],413);try{return json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);}catch(Throwable){content_json(['error'=>['code'=>'invalid_json']],400);}}
function content_api_guard(string $scope): array {require_admin();$input=content_api_input();if(!verify_csrf($scope,$input['csrf']??null))content_json(['error'=>['code'=>'csrf_invalid']],403);return $input;}
