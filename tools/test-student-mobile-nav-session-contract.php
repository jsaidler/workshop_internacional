<?php
declare(strict_types=1);

function fail_contract(string $message): never { fwrite(STDERR,"student-mobile-session-contract: $message\n"); exit(1); }

$quality=(string)file_get_contents(__DIR__.'/../assets/student-quality-pass.css');
$area=(string)file_get_contents(__DIR__.'/../assets/student-area.css');
$index=(string)file_get_contents(__DIR__.'/../index.php');

if(!preg_match('~\.student-mobile-nav\s*\{[^}]*position:fixed[^}]*bottom:0~s',$area))fail_contract('canonical mobile navigation is not fixed to the viewport bottom');
if(preg_match('~\.student-mobile-nav\s*\{[^}]*position:static~s',$quality))fail_contract('quality pass must not downgrade mobile navigation to document flow');
if(str_contains($quality,'.student-shell{padding-bottom:20px}'))fail_contract('quality pass must not remove the navigation footprint from the shell');

$loginGuard=strpos($index,"if(!\$admin&&!\$student&&(\$pageAccess!=='public'||\$materialCourses))");
$accessGuard=strpos($index,"if(!\$admin&&!cms_access_page_allowed");
if($loginGuard===false)fail_contract('protected material login redirect guard missing');
if($accessGuard===false||$loginGuard>$accessGuard)fail_contract('login redirect must run before protected material access filtering');

echo "student-mobile-session-contract: ok\n";
