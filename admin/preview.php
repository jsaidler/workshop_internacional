<?php
declare(strict_types=1);

require __DIR__.'/../app/bootstrap.php';
security_headers();
require_admin();
header('X-Robots-Tag: noindex, nofollow');

$documentRow=content_current(database());
$content_document_override=content_for_draft(database());
require __DIR__.'/../template/public.php';
ob_start();
render_public_page();
$page=(string)ob_get_clean();
$notice='<p style="position:fixed;z-index:99;top:8px;right:8px;margin:0;padding:6px 9px;background:#101313;color:#fff;font:12px Arial">Prévia do rascunho · revisão '.(int)$documentRow['draft_revision'].' · <a href="/editor/" style="color:#fff">Voltar ao editor</a></p>';
echo str_replace('<body>','<body>'.$notice,$page);
