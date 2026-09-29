<?php
declare(strict_types=1);
function fail_pages_admin_ux(string $message): never {fwrite(STDERR,"pages-admin-ux: $message\n");exit(1);}
function must_pages_admin_ux(bool $condition,string $message): void {if(!$condition)fail_pages_admin_ux($message);}
$root=dirname(__DIR__);$pages=(string)file_get_contents($root.'/admin/pages.php');$css=(string)file_get_contents($root.'/assets/admin-site.css');
must_pages_admin_ux(str_contains($pages,"['/assets/admin-site.css']"),'Pages does not load the Site workspace stylesheet');
must_pages_admin_ux(str_contains($pages,'site-page-group'),'pages are not grouped by locale');
must_pages_admin_ux(str_contains($pages,'site-page-row')&&str_contains($pages,'--page-depth:'),'hierarchy depth is not represented visually');
must_pages_admin_ux(str_contains($pages,'site-page-parent'),'parent relationship is not visible in the reading path');
must_pages_admin_ux(str_contains($pages,'site-page-parent-form'),'parent editing is not progressively disclosed');
must_pages_admin_ux(str_contains($pages,'admin_badge('),'page state is not using the shared badge primitive');
must_pages_admin_ux(str_contains($pages,'admin-data-toolbar')&&str_contains($pages,'name="q"')&&str_contains($pages,'name="lang"'),'Pages has no scalable search/filter surface');
must_pages_admin_ux(str_contains($pages,'$canMoveUp')&&str_contains($pages,'$canMoveDown'),'impossible sibling moves are not disabled');
must_pages_admin_ux(!str_contains($pages,'overview-hero'),'Pages still duplicates the shell title with a legacy hero');
must_pages_admin_ux(str_contains($css,'.site-page-row.is-child .site-page-node::before'),'child hierarchy lacks a visual connector');
must_pages_admin_ux(str_contains($css,'.site-page-parent-form'),'parent editor has no intentional layout');
must_pages_admin_ux(str_contains($css,'@media(max-width:650px)'),'Pages hierarchy has no small-screen treatment');
echo "pages-admin-ux: ok\n";
