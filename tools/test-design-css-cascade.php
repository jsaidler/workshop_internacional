<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/cms_settings.php';

function design_css_expect(bool $ok,string $message): void {
    if(!$ok){fwrite(STDERR,"test-design-css-cascade: $message\n");exit(1);}
}

$design=cms_design_defaults();
$custom='.cascade-probe{color:rgb(7,8,9)}';
$design['advanced']['customCss']=$custom;
$system=cms_design_system_css($design);
$additional=cms_design_custom_css($design);
$combined=cms_design_css($design);

design_css_expect(!str_contains($system,$custom),'system CSS must not contain additional CSS');
design_css_expect($additional===$custom,'additional CSS must be preserved as its own payload');
design_css_expect(str_ends_with($combined,$custom),'legacy combined helper must still end with additional CSS');
design_css_expect(!str_contains($system,'!important'),'generated visual design controls must not outrank additional CSS with !important');

$renderer=(string)file_get_contents(dirname(__DIR__).'/app/cms_renderer.php');
$systemStyles=strpos($renderer,'id="cms-system-styles"');
$vars=strpos($renderer,'id="cms-design-vars"');
$systemControls=strpos($renderer,'id="cms-system-choice-controls"');
$customStyle=strpos($renderer,'id="cms-custom-css"');
design_css_expect($systemStyles!==false&&$vars!==false&&$systemControls!==false&&$customStyle!==false,'renderer must expose all design style stages');
design_css_expect($systemStyles<$vars&&$vars<$systemControls&&$systemControls<$customStyle,'additional CSS must be the final author style in the rendered head');
design_css_expect(str_contains($renderer,'cms_public_system_css_imports($assetVersion)'),'public system stylesheets must be imported through the canonical layered loader');
design_css_expect(str_contains($renderer,'layer(cms-system)'),'external public system CSS must live in the lower cms-system cascade layer');
design_css_expect(str_contains($renderer,'@layer cms-system{<?=cms_design_system_css($design)?>}'),'generated design CSS must live in the lower system layer');
design_css_expect(str_contains($renderer,'cms_design_custom_css($design)'),'additional CSS must remain unlayered and therefore outrank normal system declarations regardless of specificity');
$choiceStart=strpos($renderer,'id="cms-system-choice-controls"');
$choiceEnd=strpos($renderer,'</style>',$choiceStart?:0);
$choiceCss=$choiceStart!==false&&$choiceEnd!==false?substr($renderer,$choiceStart,$choiceEnd-$choiceStart):'';
design_css_expect(!str_contains($choiceCss,'!important'),'system choice geometry must not block a later intentional additional-CSS override');

$cmsCss=(string)file_get_contents(dirname(__DIR__).'/assets/cms.css');
$cmsPro=(string)file_get_contents(dirname(__DIR__).'/assets/cms-pro.css');
$responsive=(string)file_get_contents(dirname(__DIR__).'/assets/cms-responsive.css');
design_css_expect(!str_contains($cmsPro,'!important'),'professional visual controls must remain overridable inside the system layer');
design_css_expect(!str_contains($responsive,'!important'),'responsive visual controls must remain overridable inside the system layer');
$cmsWithoutHoneypot=preg_replace('/\.honeypot\{[^}]+\}/','',$cmsCss)??$cmsCss;
design_css_expect(!str_contains($cmsWithoutHoneypot,'!important'),'layout/design rules must not use !important; only the honeypot invariant may retain it here');

$adminJs=(string)file_get_contents(dirname(__DIR__).'/assets/design-admin.js');
design_css_expect(str_contains($adminJs,"generated.id='cms-live-design-vars'"),'live preview must render generated tokens in a stylesheet');
design_css_expect(str_contains($adminJs,"custom.id='cms-live-custom'"),'live preview must keep additional CSS in its own stylesheet');
design_css_expect(str_contains($adminJs,'@layer cms-system'),'live generated design tokens must use the lower system cascade layer');
design_css_expect(!str_contains($adminJs,'root.style.setProperty('),'generated design tokens must not be written as inline styles that outrank additional CSS');
$generatedAppend=strpos($adminJs,'d.head.append(generated)');
$customAppend=strpos($adminJs,'d.head.append(custom)');
design_css_expect($generatedAppend!==false&&$customAppend!==false&&$generatedAppend<$customAppend,'live preview must append generated tokens before additional CSS');
design_css_expect(str_contains($adminJs,'root.style.removeProperty(key)'),'live preview must remove stale inline token declarations left by older code');

$package=json_decode((string)file_get_contents(dirname(__DIR__).'/package.json'),true);
design_css_expect(($package['scripts']['test:browser']??'')==='playwright test tools/browser-tests','browser suite must include the design cascade regression, not only the form editor');

echo "Design additional CSS cascade tests passed\n";
