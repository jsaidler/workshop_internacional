<?php
require __DIR__.'/app/bootstrap.php'; security_headers(); require __DIR__.'/template/public.php'; render_public_page([],[],isset($_GET['success']));
