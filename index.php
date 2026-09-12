<?php
require __DIR__.'/app/bootstrap.php'; security_headers(); require __DIR__.'/template/public.php'; try{render_public_page([],[],isset($_GET['success']));}catch(RuntimeException $e){http_response_code(404);echo 'Activity not found';}
