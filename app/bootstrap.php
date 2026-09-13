<?php
declare(strict_types=1);
foreach(['installation','config','database','public_locale','workshop_settings','public_content','form_definition','form_validator','interest_repository','interest_presentation','cms_forms','form_workflow','cms_pages','workshop_registration_content','cms_settings','cms_seo','cms_blocks','cms_renderer','cms_discovery','workshop_cms_setup','workshop_copy_refinements','csrf','auth','response','public_errors','content/content_validator','content/content_repository','content/content_service','content/content_response','activity_repository','media_service','media_admin_service','media_maintenance','cms_media_video'] as $f)require_once __DIR__."/$f.php";
require_installed_application();
$config=app_config();
date_default_timezone_set($config['timezone']??'UTC');
session_name('workshop_session');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')]);
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
database();