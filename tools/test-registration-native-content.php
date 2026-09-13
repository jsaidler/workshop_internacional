<?php
declare(strict_types=1);

function fail_native(string $message): never { fwrite(STDERR,"registration-native-content: $message\n"); exit(1); }
function expect_native(bool $condition,string $message): void { if(!$condition) fail_native($message); }
if(!function_exists('h')){function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}}

require __DIR__.'/../app/public_locale.php';
require __DIR__.'/../app/interest_repository.php';
require __DIR__.'/../app/activity_repository.php';
require __DIR__.'/../app/cms_pages.php';
require __DIR__.'/../app/cms_forms.php';
require __DIR__.'/../app/workshop_registration_content.php';
require __DIR__.'/../app/workshop_cms_setup.php';

$db=new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec("CREATE TABLE activities (id INTEGER PRIMARY KEY AUTOINCREMENT,admin_name TEXT NOT NULL,public_title TEXT NOT NULL,slug TEXT NOT NULL UNIQUE,status TEXT NOT NULL DEFAULT 'active',is_root INTEGER NOT NULL DEFAULT 0,created_at TEXT NOT NULL,updated_at TEXT NOT NULL); CREATE TABLE submission_rate_limits (id INTEGER PRIMARY KEY AUTOINCREMENT,key_hash TEXT NOT NULL UNIQUE,window_started_at TEXT NOT NULL,attempt_count INTEGER NOT NULL,updated_at TEXT NOT NULL);");
$now=utc_now();
$db->prepare('INSERT INTO activities(admin_name,public_title,slug,status,is_root,created_at,updated_at)VALUES(?,?,?,?,1,?,?)')->execute(['Workshop','Direct Positive X-Ray Film','workshop','active',$now,$now]);
$activityId=(int)$db->lastInsertId();
$migration=require __DIR__.'/../migrations/011_cms_pages_forms.php';$migration($db);
cms_forms_seed($db,$activityId);cms_pages_seed($db,$activityId);workshop_cms_setup_activity($db,$activityId);

// Recreate the production shape that migration 022 has to upgrade: fields in the
// form schema, but editorial program/payment/terms still embedded in the page.
$legacyForm=cms_form_by_key($db,$activityId,PUBLIC_LOCALE_PT_BR,'registration');
expect_native($legacyForm!==null,'registration form missing before migration');
foreach(['draft_schema_json','published_schema_json'] as $column){
    $schema=json_decode((string)$legacyForm[$column],true);unset($schema['settings']['contentBlocks']);
    $db->prepare("UPDATE cms_forms SET $column=? WHERE id=?")->execute([json_encode($schema,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),(int)$legacyForm['id']]);
}
$beforePage=cms_page_by_slug($db,$activityId,PUBLIC_LOCALE_PT_BR,'inscricao');
expect_native($beforePage!==null,'registration page missing before migration');
foreach(['draft_document_json','published_document_json'] as $column){
    $doc=json_decode((string)$beforePage[$column],true);
    $legacy='<section data-registration-program><p>Programa legado</p></section><div data-registration-payment-source><p>Pagamento legado</p></div><section data-registration-terms><p>Termos legados</p></section>';
    $doc['html']=str_replace('<div data-cms-form-key="registration"></div>','<div data-cms-form-key="registration"></div>'.$legacy,(string)$doc['html']);
    $db->prepare("UPDATE cms_pages SET $column=? WHERE id=?")->execute([json_encode(cms_page_document($doc),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),(int)$beforePage['id']]);
}
$beforePage=cms_page_by_id($db,(int)$beforePage['id']);
$beforeHtml=cms_page_doc($beforePage,true)['html'];
expect_native(str_contains($beforeHtml,'data-registration-payment-source'),'legacy payment source expected before migration');
expect_native(cms_form_content_blocks(cms_form_schema(cms_form_by_id($db,(int)$legacyForm['id']),true))===[],'legacy form should not already contain native blocks');

$upgrade=require __DIR__.'/../migrations/022_registration_native_editorial_content.php';$upgrade($db);

$form=cms_form_by_key($db,$activityId,PUBLIC_LOCALE_PT_BR,'registration');
expect_native($form!==null,'registration form missing after migration');
$schema=cms_form_schema($form,true);
$blocks=cms_form_content_blocks($schema);
expect_native(count($blocks)>=7,'expected native editorial content blocks');
$byId=[];foreach($blocks as $block)$byId[$block['id']]=$block;
expect_native(isset($byId['payment_pix']),'Pix content block missing');
expect_native(isset($byId['payment_card']),'card content block missing');
expect_native(isset($byId['program']),'program content block missing');
expect_native(isset($byId['terms_copy']),'terms content block missing');
expect_native(($byId['payment_pix']['condition']['source']??'')==='payment_method','Pix condition source missing');
expect_native(($byId['payment_pix']['condition']['value']??'')==='pix','Pix condition value missing');
expect_native(($byId['payment_card']['condition']['operator']??'')==='contains','card condition operator missing');
expect_native(str_contains((string)$byId['payment_pix']['html'],'data-cms-image'),'Pix QR must be a CMS image target');
expect_native(str_contains((string)$byId['payment_pix']['html'],'data-pix-copy-value'),'Pix copy code must remain editable content');

$editorHtml=cms_render_form($form,$schema,1,PUBLIC_LOCALE_PT_BR,[],[],false,true);
expect_native(str_contains($editorHtml,'data-cms-form-content-id="payment_pix"'),'Pix block missing from editor render');
expect_native(str_contains($editorHtml,'data-cms-form-content-id="payment_card"'),'card block missing from editor render');
expect_native(!str_contains($editorHtml,'data-cms-condition-field="payment_method"'),'editor must keep conditional editorial blocks visible');

$publicHtml=cms_render_form($form,$schema,1,PUBLIC_LOCALE_PT_BR,[],[],false,false);
expect_native(str_contains($publicHtml,'data-cms-condition-field="payment_method"'),'public render must expose conditional editorial rule');
expect_native(str_contains($publicHtml,'data-cms-condition-operator="contains"'),'public card condition missing');

$page=cms_page_by_slug($db,$activityId,PUBLIC_LOCALE_PT_BR,'inscricao');
expect_native($page!==null,'registration page missing after migration');
$pageHtml=cms_page_doc($page,true)['html'];
expect_native(!str_contains($pageHtml,'data-registration-payment-source'),'legacy payment HTML must leave the page document');
expect_native(!str_contains($pageHtml,'data-registration-program'),'legacy program HTML must leave the page document');
expect_native(!str_contains($pageHtml,'data-registration-terms'),'legacy terms HTML must leave the page document');
expect_native(str_contains($pageHtml,'data-cms-form-key="registration"'),'registration page must retain the form placeholder');

$draft=cms_form_schema($form,false);
foreach($draft['settings']['contentBlocks'] as &$block){if(($block['id']??'')==='payment_pix')$block['html']=str_replace('R$ 698,00','R$ 700,00',$block['html']);}
unset($block);
$saved=cms_form_save($db,(int)$form['id'],$draft,(int)$form['draft_revision']);
$reloaded=cms_form_schema($saved,false);
$pix=array_values(array_filter(cms_form_content_blocks($reloaded),fn($block)=>($block['id']??'')==='payment_pix'))[0]??null;
expect_native(is_array($pix)&&str_contains((string)$pix['html'],'R$ 700,00'),'editorial content did not persist through canonical form save');

echo "Registration native content tests passed\n";
