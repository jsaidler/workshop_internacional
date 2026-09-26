<?php
declare(strict_types=1);

function fail_pages_admin_ux(string $message): never {fwrite(STDERR,"pages-admin-ux: $message\n");exit(1);}
function must_pages_admin_ux(bool $condition,string $message): void {if(!$condition)fail_pages_admin_ux($message);}

$root=dirname(__DIR__);
$pages=(string)file_get_contents($root.'/admin/pages.php');
$css=(string)file_get_contents($root.'/admin/cms-admin.css');

must_pages_admin_ux(str_contains($pages,'pages-locale-group'),'pages are not grouped by locale');
must_pages_admin_ux(str_contains($pages,'page-tree-row')&&str_contains($pages,'style="--page-depth:'),'hierarchy depth is not represented visually');
must_pages_admin_ux(!str_contains($pages,"str_repeat('— ',\$depth)"),'hierarchy still relies on punctuation in page titles');
must_pages_admin_ux(str_contains($pages,'page-hierarchy-label">Página superior'),'parent relationship is not visible in the reading path');
must_pages_admin_ux(str_contains($pages,'page-parent-editor')&&str_contains($pages,'page-parent-popover'),'parent editing is not progressive/disclosed');
must_pages_admin_ux(str_contains($pages,'page-status-badge')&&str_contains($pages,'data-state="')&&str_contains($pages,"'draft':'published'"),'status does not have a stable visual state hook');
must_pages_admin_ux(str_contains($pages,'page-action-primary'),'primary edit action is not visually distinguishable');
must_pages_admin_ux(str_contains($pages,'$canMoveUp')&&str_contains($pages,'$canMoveDown'),'impossible sibling moves are not disabled');

must_pages_admin_ux(str_contains($css,'body.admin-section-pages .pages-locale-group'),'Pages UX is not scoped to the Pages admin');
must_pages_admin_ux(str_contains($css,'body.admin-section-pages .page-tree-row.is-child .page-tree-node:before'),'child hierarchy lacks a visual connector');
must_pages_admin_ux(str_contains($css,'body.admin-section-pages .page-parent-popover'),'parent editor has no intentional popover layout');
must_pages_admin_ux(str_contains($css,'@media(max-width:640px)'),'Pages hierarchy has no small-screen treatment');

echo "pages-admin-ux: ok\n";
