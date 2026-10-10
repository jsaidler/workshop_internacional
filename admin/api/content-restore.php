<?php
require __DIR__.'/../../app/bootstrap.php';content_api_guard('content');try{$a=admin_activity(database());$row=content_restore(database(),(int)$a['id']);content_json(['document'=>content_for_draft(database(),(int)$a['id']),'meta'=>content_metadata($row)]);}catch(Throwable $e){error_log('content.restore '.$e->getMessage());content_json(['error'=>['code'=>'restore_failed']],500);}
