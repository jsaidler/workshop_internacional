<?php
require __DIR__.'/../../app/bootstrap.php';require_admin();
header('Content-Type: application/json; charset=UTF-8');header('Cache-Control: no-store');$row=content_current(database());echo json_encode(['document'=>content_for_draft(database()),'meta'=>content_metadata($row),'csrf'=>csrf_token('content')],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
