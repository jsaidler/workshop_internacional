<?php
declare(strict_types=1);
require __DIR__.'/app/bootstrap.php';
header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=300');
echo cms_sitemap_xml(database());
