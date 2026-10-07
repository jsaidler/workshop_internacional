<?php
declare(strict_types=1);

$uiCss=(string)file_get_contents(dirname(__DIR__).'/assets/cms-editorial.css');
$registrationCss=(string)file_get_contents(dirname(__DIR__).'/assets/registration.css');
$pageCss=(string)file_get_contents(dirname(__DIR__).'/template/page.css');
$formRenderer=(string)file_get_contents(dirname(__DIR__).'/app/cms_forms.php');

foreach([
    '.cms-form .cms-form-grid',
    '.cms-form .cms-field>span',
    '.cms-form .cms-field input',
    '.cms-form .cms-choice-grid',
    '.cms-form .cms-consent',
    '.cms-form button[type=submit]',
    '.cms-form button[type=submit]:hover',
    'background:var(--cms-form-action-bg,var(--inverse-bg))',
    'border:1px solid var(--cms-form-action-border,var(--inverse-bg))',
    'background:var(--cms-form-action-hover-bg,transparent)',
    'color:var(--cms-form-action-hover-fg,var(--text))',
    'border-color:var(--cms-form-action-hover-border,var(--line-strong))',
    'opacity:1',
    'visibility:visible',
] as $needle){
    if(!str_contains($uiCss,$needle))throw new RuntimeException('global_form_ux_missing: '.$needle);
}
if(str_contains($uiCss,'!important'))throw new RuntimeException('global_form_ux_uses_important');

foreach([
    '.interest .cms-form',
    '[data-cms-page-slug="pinhole-lambe-lambe"] .cms-form',
    '.registration-canonical-form .cms-field',
    '.registration-canonical-form .cms-choice-grid',
    '.registration-canonical-form .cms-consent',
] as $needle){
    if(str_contains($uiCss,$needle)||str_contains($registrationCss,$needle))throw new RuntimeException('form_ux_is_not_global: '.$needle);
}

foreach([
    '--cms-form-action-bg: var(--inverse);',
    '--cms-form-action-fg: var(--inverse-bg);',
    '--cms-form-action-hover-fg: var(--inverse);',
] as $needle){
    if(!str_contains($pageCss,$needle))throw new RuntimeException('inverse_form_action_token_missing: '.$needle);
}

if(!str_contains($formRenderer,'<form class="cms-form"'))throw new RuntimeException('cms_form_renderer_lost_shared_form_class');
if(!str_contains($formRenderer,'<button class="button" type="'))throw new RuntimeException('cms_form_renderer_lost_shared_submit_button');

echo "Global public form UX OK\n";
