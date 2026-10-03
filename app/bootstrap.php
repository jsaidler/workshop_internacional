<?php
declare(strict_types=1);
foreach(['installation','config','brand','database','public_locale','workshop_settings','public_content','form_definition','form_validator','interest_repository','interest_presentation','cms_forms','form_purpose','form_workflow','cms_pages','cms_page_structure','analytics_repository','workshop_registration_content','cms_settings','cms_seo','cms_blocks','cms_renderer','cms_discovery','workshop_cms_setup','workshop_copy_refinements','csrf','auth','student_auth','student_accounts','courses','workshop_courses','student_enrollments','student_workspace','student_test_mobile','student_lifecycle','student_sharing','student_material','student_workbench','student_process_catalogs','student_process_ux','student_process_templates','student_global_processes','student_process_standards','student_process_recording','student_process_execution','student_auxiliary_ux','student_workbench_hardening','student_experience','student_notes_experience','student_question_annotations','cms_access','student_shell','response','public_errors','content/content_validator','content/content_repository','content/content_service','content/content_response','activity_locales','activity_repository','media_service','media_privacy','media_admin_service','media_maintenance','cms_media_video'] as $f)require_once __DIR__."/$f.php";
require_installed_application();
$config=app_config();
date_default_timezone_set($config['timezone']??'UTC');
session_name('workshop_session');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')]);
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
database();
student_enrollment_install_reconciliation_hook();