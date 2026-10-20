<?php
declare(strict_types=1);
/** Execute from hosting cron (CLI only); no publicly reachable HTTP worker. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
$limit=30;
foreach($argv as $arg)if(preg_match('/^--limit=(\d{1,2})$/',$arg,$m))$limit=max(1,min(50,(int)$m[1]));
try{
    $result=communication_dispatch(database(),$limit);
    fwrite(STDOUT,'Communications: '.json_encode($result,JSON_UNESCAPED_SLASHES)."\n");
}catch(Throwable $e){
    fwrite(STDERR,'Communications worker: '.$e->getMessage()."\n");exit(1);
}
