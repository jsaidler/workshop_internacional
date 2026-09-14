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

$renderer=(string)file_get_contents(dirname(__DIR__).'/app/cms_renderer.php');
$vars=strpos($renderer,'id="cms-design-vars"');
$systemControls=strpos($renderer,'id="cms-system-choice-controls"');
$customStyle=strpos($renderer,'id="cms-custom-css"');
design_css_expect($vars!==false&&$systemControls!==false&&$customStyle!==false,'renderer must expose all design style stages');
design_css_expect($vars<$systemControls&&$systemControls<$customStyle,'additional CSS must be the final author style in the rendered head');
design_css_expect(str_contains($renderer,'cms_design_system_css($design)'),'renderer must render generated design CSS separately');
design_css_expect(str_contains($renderer,'cms_design_custom_css($design)'),'renderer must render additional CSS separately');
$choiceStart=strpos($renderer,'id="cms-system-choice-controls"');
$choiceEnd=strpos($renderer,'</style>',$choiceStart?:0);
$choiceCss=$choiceStart!==false&&$choiceEnd!==false?substr($renderer,$choiceStart,$choiceEnd-$choiceStart):'';
design_css_expect(!str_contains($choiceCss,'!important'),'system choice geometry must not block a later intentional additional-CSS override');

$adminJs=(string)file_get_contents(dirname(__DIR__).'/assets/design-admin.js');
design_css_expect(str_contains($adminJs,"id='cms-live-design-vars'")||str_contains($adminJs,"id=\'cms-live-design-vars\'")||str_contains($adminJs,"generated.id='cms-live-design-vars'"),'live preview must render generated tokens in a stylesheet');
design_css_expect(str_contains($adminJs,"custom.id='cms-live-custom'"),'live preview must keep additional CSS in its own stylesheet');
design_css_expect(!str_contains($adminJs,'root.style.setProperty('),'generated design tokens must not be written as inline styles that outrank additional CSS');
$generatedAppend=strpos($adminJs,'d.head.append(generated)');
$customAppend=strpos($adminJs,'d.head.append(custom)');
design_css_expect($generatedAppend!==false&&$customAppend!==false&&$generatedAppend<$customAppend,'live preview must append generated tokens before additional CSS');
design_css_expect(str_contains($adminJs,'root.style.removeProperty(key)'),'live preview must remove stale inline token declarations left by older code');

$package=json_decode((string)file_get_contents(dirname(__DIR__).'/package.json'),true);
design_css_expect(($package['scripts']['test:browser']??'')==='playwright test tools/browser-tests','browser suite must include the design cascade regression, not only the form editor');

echo "Design additional CSS cascade tests passed\n";
