<?php
declare(strict_types=1);
date_default_timezone_set('UTC');foreach(['config','database','public_content','form_definition','form_validator','interest_repository','csrf','auth','response'] as $f)require_once __DIR__."/$f.php";$config=app_config();session_name('workshop_session');session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')]);if(session_status()!==PHP_SESSION_ACTIVE)session_start();database();
