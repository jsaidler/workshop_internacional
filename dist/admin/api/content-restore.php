<?php
require __DIR__.'/../../app/bootstrap.php';content_api_guard('content');try{$row=content_restore(database());content_json(['document'=>content_for_draft(database()),'meta'=>content_metadata($row)]);}catch(Throwable $e){error_log('content.restore '.$e->getMessage());content_json(['error'=>['code'=>'restore_failed']],500);}
