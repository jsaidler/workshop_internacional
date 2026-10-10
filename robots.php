<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=300');
echo cms_robots_text();
