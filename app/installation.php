<?php
declare(strict_types=1);

function app_root(): string { return dirname(__DIR__); }
function installed_lock_path(): string { return app_root().'/storage/installed.lock'; }
function application_installed(): bool { return is_file(installed_lock_path()); }
function installer_url(): string { return '/install/'; }
function is_api_request(): bool {
    $path=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)?:'';
    return str_starts_with($path,'/api/') || str_contains(strtolower($_SERVER['HTTP_ACCEPT']??''),'application/json');
}
function not_installed_response(): never {
    http_response_code(503);
    header('Content-Type: application/json; charset=UTF-8');
    header('Retry-After: 60');
    echo json_encode(['error'=>['code'=>'not_installed','message'=>'The application has not been installed.']],JSON_UNESCAPED_SLASHES);
    exit;
}
function require_installed_application(): void {
    if(application_installed()) return;
    if(is_api_request()) not_installed_response();
    header('Location: '.installer_url(),true,303);
    exit;
}
