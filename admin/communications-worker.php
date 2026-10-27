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
    $runToken=communication_platform_available($db)?communication_worker_run_start($db):'';
    $runCounts=['claimed'=>0,'sent'=>0,'skipped'=>0,'failed'=>0];
    if(communication_platform_available($db)){
        $lifecycle=communication_journey_scan_lifecycle($db,$limit);
        fwrite(STDOUT,'Journey triggers: '.json_encode($lifecycle,JSON_UNESCAPED_SLASHES)."\n");
        $journeys=communication_journey_process($db,$limit);
        fwrite(STDOUT,'Journeys: '.json_encode($journeys,JSON_UNESCAPED_SLASHES)."\n");
        $runCounts['failed']+=(int)$journeys['failed'];
        communication_worker_run_heartbeat($db,$runToken);
    }
    $staff=admin_student_notice_dispatch($db,$limit);
    fwrite(STDOUT,'Student alerts: '.json_encode($staff,JSON_UNESCAPED_SLASHES)."\n");
    $scheduled=communication_queue_due_campaigns($db,null,$limit);
    if($scheduled>0)fwrite(STDOUT,'Scheduled campaigns queued: '.$scheduled."\n");
    $queue=(int)$db->query("SELECT COUNT(*) FROM communication_deliveries WHERE status IN ('pending','processing')")->fetchColumn();
    if($queue>0){
        $result=['claimed'=>0,'sent'=>0,'skipped'=>0,'failed'=>0];
        for($i=0;$i<$limit;$i++){
            // Claim one at a time so an administrator can cancel pending jobs
            // between provider requests even while the CLI cron holds its lock.
            $batch=communication_dispatch($db,1);
            foreach($result as $key=>$_)$result[$key]+=(int)($batch[$key]??0);
            if(array_sum($batch)===0)break;
            if($runToken!=='')communication_worker_run_heartbeat($db,$runToken);
        }
        fwrite(STDOUT,'Communications: '.json_encode($result,JSON_UNESCAPED_SLASHES)."\n");
        foreach($runCounts as $key=>$_)$runCounts[$key]+=(int)($result[$key]??0);
    }
    communication_worker_record($db,false);
    if($runToken!=='')communication_worker_run_finish($db,$runToken,$runCounts);
}catch(Throwable $e){
    if(isset($db))communication_worker_record($db,false,'O envio apresentou uma falha. Consulte os registros do servidor.');
    if(isset($db,$runToken)&&$runToken!=='')communication_worker_run_finish($db,$runToken,$runCounts??[],'O processamento apresentou uma falha.');
    fwrite(STDERR,'Communications worker: '.$e->getMessage()."\n");exit(1);
}finally{flock($lock,LOCK_UN);fclose($lock);}
