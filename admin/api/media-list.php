<?php
require __DIR__.'/../../app/bootstrap.php';require_admin();header('Content-Type: application/json; charset=UTF-8');echo json_encode(['items'=>media_list(database()),'capabilities'=>media_capabilities(),'csrf'=>csrf_token('media')],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
