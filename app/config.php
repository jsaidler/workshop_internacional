<?php
declare(strict_types=1);
function app_config(): array {
    $file=dirname(__DIR__).'/config/local.php';
    if(!is_file($file)) throw new RuntimeException('Missing config/local.php.');
    $v=require $file;
    foreach(['app_secret','ip_hash_secret'] as $k) if (!is_string($v[$k]??null)||strlen($v[$k])<32) throw new RuntimeException("Invalid configuration: $k");
    return $v+['site_name'=>'Workshop','public_url'=>'','timezone'=>'UTC','rate_limit_max_attempts'=>5,'rate_limit_window_seconds'=>3600];
}
