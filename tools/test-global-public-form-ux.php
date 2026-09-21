<?php
declare(strict_types=1);

$uiCss=(string)file_get_contents(dirname(__DIR__).'/assets/cms-ui-refinements.css');
$registrationCss=(string)file_get_contents(dirname(__DIR__).'/assets/registration.css');
$formRenderer=(string)file_get_contents(dirname(__DIR__).'/app/cms_forms.php');

foreach([
    '.cms-form .cms-form-grid',
    '.cms-form .cms-field>span',
    '.cms-form .cms-field input',
    '.cms-form .cms-choice-grid',
    '.cms-form .cms-consent',
    '.cms-form button[type=submit]',
    '.cms-form button[type=submit]:hover',
    'background:var(--inverse-bg)',
    'border:1px solid var(--line-strong)',
] as $needle){
    if(!str_contains($uiCss,$needle))throw new RuntimeException('global_form_ux_missing: '.$needle);
}

foreach([
    '.interest .cms-form',
    '[data-cms-page-slug="pinhole-lambe-lambe"] .cms-form',
    '.registration-canonical-form .cms-field',
    '.registration-canonical-form .cms-choice-grid',
    '.registration-canonical-form .cms-consent',
] as $needle){
    if(str_contains($uiCss,$needle)||str_contains($registrationCss,$needle))throw new RuntimeException('form_ux_is_not_global: '.$needle);
}

if(!str_contains($formRenderer,'<form class="cms-form"'))throw new RuntimeException('cms_form_renderer_lost_shared_form_class');

echo "Global public form UX OK\n";
