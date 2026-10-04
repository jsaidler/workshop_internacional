<?php
require __DIR__.'/../../app/bootstrap.php';content_api_guard('content');try{$a=admin_activity(database());$row=content_publish(database(),(int)$a['id']);content_json(['meta'=>content_metadata($row)]);}catch(Throwable $e){error_log('content.publish '.$e->getMessage());content_json(['error'=>['code'=>'publish_failed']],500);}
