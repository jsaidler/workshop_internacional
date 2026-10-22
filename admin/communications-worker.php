<?php
declare(strict_types=1);
/** Execute from hosting cron (CLI only); no publicly reachable HTTP worker. */
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
$limit=30;
foreach($argv as $arg)if(preg_match('/^--limit=(\d{1,2})$/',$arg,$m))$limit=max(1,min(50,(int)$m[1]));
$lock=@fopen(dirname(__DIR__).'/storage/communications-worker.lock','c+');
if(!$lock || !flock($lock,LOCK_EX|LOCK_NB)){fwrite(STDERR,"Communications worker: outra execução em andamento.\n");exit(1);}
try{
    $db=database();
    communication_worker_record($db,true);
    $staff=admin_student_notice_dispatch($db,$limit);
    fwrite(STDOUT,'Student alerts: '.json_encode($staff,JSON_UNESCAPED_SLASHES)."\n");
    $queue=(int)$db->query("SELECT COUNT(*) FROM communication_deliveries WHERE status IN ('pending','processing')")->fetchColumn();
    if($queue>0){
        $result=communication_dispatch($db,$limit);
        fwrite(STDOUT,'Communications: '.json_encode($result,JSON_UNESCAPED_SLASHES)."\n");
    }
    communication_worker_record($db,false);
}catch(Throwable $e){
    if(isset($db))communication_worker_record($db,false,'O envio apresentou uma falha. Consulte os registros do servidor.');
    fwrite(STDERR,'Communications worker: '.$e->getMessage()."\n");exit(1);
}finally{flock($lock,LOCK_UN);fclose($lock);}
