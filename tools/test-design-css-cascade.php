<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/public_locale.php';
require dirname(__DIR__).'/app/cms_settings.php';

function design_css_expect(bool $ok,string $message): void {
    if(!$ok){fwrite(STDERR,"test-design-css-cascade: $message\n");exit(1);}
}

$design=cms_design_defaults();
$fonts=cms_design_font_defaults();
design_css_expect($design['type']['bodyFont']===$fonts['body'],'body font default must expose the real CSS stack');
design_css_expect($design['type']['displayFont']===$fonts['display'],'display font default must expose the real CSS stack');
design_css_expect($design['type']['monoFont']===$fonts['mono'],'mono font default must expose the real CSS stack');
design_css_expect(!str_contains(implode('|',[$design['type']['bodyFont'],$design['type']['displayFont'],$design['type']['monoFont']]),'var(--'),'font form defaults must never expose CSS variable names');
$custom='.cascade-probe{color:rgb(7,8,9)}';
$design['advanced']['customCss']=$custom;
$system=cms_design_system_css($design);
$additional=cms_design_custom_css($design);
$combined=cms_design_css($design);

design_css_expect(str_contains($system,'--body:'.$fonts['body'].';'),'generated design CSS must drive the legacy body token consumed by the template');
design_css_expect(str_contains($system,'--title:'.$fonts['display'].';'),'generated design CSS must drive the legacy title token consumed by the template');
design_css_expect(str_contains($system,'--mono:'.$fonts['mono'].';'),'generated design CSS must drive the legacy mono token consumed by the template');
design_css_expect(str_contains($system,'--cms-body-font:'.$fonts['body'].';'),'generated design CSS must retain the professional body-font token');
$legacyDesign=cms_design_defaults();$legacyDesign['type']['bodyFont']='var(--sans)';$legacyDesign['type']['displayFont']='var(--title)';$legacyDesign['type']['monoFont']='var(--mono)';$legacyCss=cms_design_system_css($legacyDesign);
design_css_expect(str_contains($legacyCss,'--body:'.$fonts['body'].';')&&str_contains($legacyCss,'--title:'.$fonts['display'].';')&&str_contains($legacyCss,'--mono:'.$fonts['mono'].';'),'legacy indirect font values must normalize before CSS generation');
$fontImport=cms_design_font_import_css();
design_css_expect(str_contains($fontImport,'fonts.googleapis.com')&&str_contains($fontImport,'IBM+Plex+Sans')&&str_contains($fontImport,'IBM+Plex+Mono'),'approved non-local fonts must be loaded as web fonts');
design_css_expect(str_contains($fontImport,'layer(cms-system)'),'web font stylesheet must stay inside the system layer');
design_css_expect(!str_contains($system,$custom),'system CSS must not contain additional CSS');
design_css_expect($additional===$custom,'additional CSS must be preserved as its own payload');
design_css_expect(str_ends_with($combined,$custom),'legacy combined helper must still end with additional CSS');
design_css_expect(!str_contains($system,'!important'),'generated visual design controls must not outrank additional CSS with !important');

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE cms_design_settings (activity_id INTEGER NOT NULL, locale TEXT NOT NULL, settings_json TEXT NOT NULL, updated_at TEXT NOT NULL, PRIMARY KEY(activity_id,locale));');
$pt=cms_design_defaults();$pt['advanced']['customCss']=$custom;
cms_settings_save($db,'design',7,PUBLIC_LOCALE_PT_BR,$pt);
$enRead=cms_design_settings($db,7,PUBLIC_LOCALE_EN);
design_css_expect(($enRead['advanced']['customCss']??'')===$custom,'Additional CSS saved in PT must apply to every page/locale of the same site');
$en=cms_design_defaults();$en['advanced']['customCss']='.site-wide{display:block}';
cms_settings_save($db,'design',7,PUBLIC_LOCALE_EN,$en);
$ptRead=cms_design_settings($db,7,PUBLIC_LOCALE_PT_BR);
design_css_expect(($ptRead['advanced']['customCss']??'')==='.site-wide{display:block}','Additional CSS saved in EN must replace the site-wide payload seen by PT pages');
$other=cms_design_settings($db,8,PUBLIC_LOCALE_PT_BR);
design_css_expect(($other['advanced']['customCss']??'')==='','Additional CSS must not leak to another site/activity');
$shared=$db->query("SELECT settings_json FROM cms_design_settings WHERE activity_id=7 AND locale='__site__'")->fetchColumn();
design_css_expect(is_string($shared)&&str_contains($shared,'.site-wide{display:block}'),'site-wide Additional CSS must be persisted in a dedicated site scope');

$migrationDb=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$migrationDb->exec('CREATE TABLE cms_design_settings (activity_id INTEGER NOT NULL, locale TEXT NOT NULL, settings_json TEXT NOT NULL, updated_at TEXT NOT NULL, PRIMARY KEY(activity_id,locale));');
$legacyPt=json_encode(['advanced'=>['customCss'=>'.legacy-global{color:red}']],JSON_UNESCAPED_SLASHES);
$legacyEn=json_encode(['advanced'=>['customCss'=>'']],JSON_UNESCAPED_SLASHES);
$migrationDb->prepare('INSERT INTO cms_design_settings(activity_id,locale,settings_json,updated_at) VALUES(?,?,?,?)')->execute([3,PUBLIC_LOCALE_PT_BR,$legacyPt,'2026-09-12T12:00:00Z']);
$migrationDb->prepare('INSERT INTO cms_design_settings(activity_id,locale,settings_json,updated_at) VALUES(?,?,?,?)')->execute([3,PUBLIC_LOCALE_EN,$legacyEn,'2026-09-13T12:00:00Z']);
$siteCssMigration=require dirname(__DIR__).'/migrations/027_site_wide_additional_css.php';$siteCssMigration($migrationDb);
$migrated=$migrationDb->query("SELECT settings_json FROM cms_design_settings WHERE activity_id=3 AND locale='__site__'")->fetchColumn();
design_css_expect(is_string($migrated)&&str_contains($migrated,'.legacy-global{color:red}'),'migration must preserve an existing non-empty legacy Additional CSS payload when creating site scope');

$fontMigrationDb=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$fontMigrationDb->exec('CREATE TABLE cms_design_settings (activity_id INTEGER NOT NULL, locale TEXT NOT NULL, settings_json TEXT NOT NULL, updated_at TEXT NOT NULL, PRIMARY KEY(activity_id,locale));');
$legacyFonts=json_encode(['type'=>['bodyFont'=>'var(--sans)','displayFont'=>'var(--title)','monoFont'=>'var(--mono)'],'advanced'=>['customCss'=>'.keep-me{display:block}']],JSON_UNESCAPED_SLASHES);
$fontMigrationDb->prepare('INSERT INTO cms_design_settings(activity_id,locale,settings_json,updated_at) VALUES(?,?,?,?)')->execute([9,PUBLIC_LOCALE_PT_BR,$legacyFonts,'2026-09-13T12:00:00Z']);
$fontMigration=require dirname(__DIR__).'/migrations/029_normalize_design_font_values.php';$fontMigration($fontMigrationDb);
$fontRow=$fontMigrationDb->query("SELECT settings_json FROM cms_design_settings WHERE activity_id=9 AND locale='pt-BR'")->fetchColumn();$fontSettings=json_decode((string)$fontRow,true);
design_css_expect(($fontSettings['type']['bodyFont']??'')===$fonts['body']&&($fontSettings['type']['displayFont']??'')===$fonts['display']&&($fontSettings['type']['monoFont']??'')===$fonts['mono'],'font migration must replace legacy variable references with portable stacks');
design_css_expect(($fontSettings['advanced']['customCss']??'')==='.keep-me{display:block}','font migration must preserve unrelated design settings');

$renderer=(string)file_get_contents(dirname(__DIR__).'/app/cms_renderer.php');
$systemStyles=strpos($renderer,'id="cms-system-styles"');
$vars=strpos($renderer,'id="cms-design-vars"');
$systemControls=strpos($renderer,'id="cms-system-choice-controls"');
$customStyle=strpos($renderer,'id="cms-custom-css"');
design_css_expect($systemStyles!==false&&$vars!==false&&$systemControls!==false&&$customStyle!==false,'renderer must expose all design style stages');
design_css_expect($systemStyles<$vars&&$vars<$systemControls&&$systemControls<$customStyle,'additional CSS must be the final author style in the rendered head');
design_css_expect(str_contains($renderer,'cms_public_system_css_imports($assetVersion)'),'public system stylesheets must be imported through the canonical layered loader');
design_css_expect(str_contains($renderer,'cms_design_font_import_css()'),'public renderer must load the canonical web-font stylesheet before local system CSS');
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

$publicJs=(string)file_get_contents(dirname(__DIR__).'/assets/public.js');
design_css_expect(str_contains($publicJs,'@import url("/assets/registration.css") layer(cms-system);'),'registration.css must be dynamically loaded inside the lower cms-system layer');
design_css_expect(str_contains($publicJs,'@import url("/assets/cms-responsive.css") layer(cms-system);'),'responsive fallback CSS must also stay inside cms-system');
design_css_expect(str_contains($publicJs,"document.querySelector('[data-cms-responsive]')"),'public JS must recognize the renderer style marker instead of appending a duplicate stylesheet');
design_css_expect(!str_contains($publicJs,"registration.rel='stylesheet'"),'registration.css must never be appended as an unlayered link after Additional CSS');

$adminJs=(string)file_get_contents(dirname(__DIR__).'/assets/design-admin.js');
design_css_expect(str_contains($adminJs,"generated.id='cms-live-design-vars'"),'live preview must render generated tokens in a stylesheet');
design_css_expect(str_contains($adminJs,"custom.id='cms-live-custom'"),'live preview must keep additional CSS in its own stylesheet');
design_css_expect(str_contains($adminJs,'@layer cms-system'),'live generated design tokens must use the lower system cascade layer');
design_css_expect(str_contains($adminJs,"'--body':bodyFont")&&str_contains($adminJs,"'--title':displayFont")&&str_contains($adminJs,"'--mono':monoFont"),'live preview must update the actual font tokens consumed by the template');
design_css_expect(!str_contains($adminJs,'root.style.setProperty('),'generated design tokens must not be written as inline styles that outrank additional CSS');
$generatedAppend=strpos($adminJs,'d.head.append(generated)');
$customAppend=strpos($adminJs,'d.head.append(custom)');
design_css_expect($generatedAppend!==false&&$customAppend!==false&&$generatedAppend<$customAppend,'live preview must append generated tokens before additional CSS');
design_css_expect(str_contains($adminJs,'root.style.removeProperty(key)'),'live preview must remove stale inline token declarations left by older code');

$designAdmin=(string)file_get_contents(dirname(__DIR__).'/admin/design.php');
design_css_expect(!str_contains($designAdmin,'<code>var(--sans)</code>')&&!str_contains($designAdmin,'<code>var(--title)</code>')&&!str_contains($designAdmin,'<code>var(--mono)</code>'),'Design form guidance must not expose implementation-variable names as font values');
design_css_expect(str_contains($designAdmin,'pilhas CSS reais'),'Design form must explain that the editable values are real font stacks');

$package=json_decode((string)file_get_contents(dirname(__DIR__).'/package.json'),true);
design_css_expect(($package['scripts']['test:browser']??'')==='playwright test tools/browser-tests','browser suite must include the design cascade regression, not only the form editor');

echo "Design typography, additional CSS cascade and site-scope tests passed\n";
