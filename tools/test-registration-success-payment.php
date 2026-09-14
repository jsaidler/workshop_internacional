<?php
declare(strict_types=1);

function registration_success_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"registration-success-payment: $message\n");exit(1);}}
if(!function_exists('h')){function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}}
if(!function_exists('csrf_token')){function csrf_token(string $scope): string {return 'test-csrf';}}
if(!function_exists('activity_slug')){function activity_slug(string $value): string {return trim(strtolower(preg_replace('~[^a-z0-9]+~i','-',$value)??''),'-')?:'item';}}

require __DIR__.'/../app/public_locale.php';
require __DIR__.'/../app/cms_pages.php';
require __DIR__.'/../app/cms_forms.php';
require __DIR__.'/../app/form_workflow.php';
require __DIR__.'/../app/workshop_registration_content.php';
require __DIR__.'/../app/workshop_cms_setup.php';

$schema=workshop_registration_schema();
$blocks=[];foreach(cms_form_content_blocks($schema) as $block)$blocks[(string)$block['id']]=$block;
registration_success_expect(!empty($blocks['payment_pix']['showOnSuccess']),'Pix block must remain available after successful submission');
registration_success_expect(!empty($blocks['payment_card']['showOnSuccess']),'card block must remain available after successful submission');

$context=cms_form_success_context($schema,['payment_method'=>'pix','name'=>'João','cpf'=>'09007208756','email'=>'private@example.com']);
registration_success_expect($context===['payment_method'=>'pix'],'success flash must retain only values needed to choose post-submit content');
$form=['id'=>7,'form_uuid'=>'test-form','title'=>'Inscrição — nova turma'];
$pixHtml=cms_render_form($form,$schema,1,PUBLIC_LOCALE_PT_BR,$context,[],true,false);
registration_success_expect(str_contains($pixHtml,'Inscrição recebida'),'success confirmation missing');
registration_success_expect(str_contains($pixHtml,'data-pix-copy-value'),'Pix payment code must remain visible after submit');
registration_success_expect(str_contains($pixHtml,'pix-workshop.svg'),'Pix QR must remain visible after submit');
registration_success_expect(!str_contains($pixHtml,'Mercado Pago'),'Pix confirmation must not show the card payment block');
registration_success_expect(!str_contains($pixHtml,'data-cms-condition-field="payment_method"'),'post-submit payment block must already be resolved server-side');

$cardContext=cms_form_success_context($schema,['payment_method'=>'card_installments','name'=>'João']);
$cardHtml=cms_render_form($form,$schema,1,PUBLIC_LOCALE_PT_BR,$cardContext,[],true,false);
registration_success_expect(str_contains($cardHtml,'Mercado Pago'),'card payment link must remain visible after submit');
registration_success_expect(!str_contains($cardHtml,'data-pix-copy-value'),'card confirmation must not show Pix data');

$normalHtml=cms_render_form($form,$schema,1,PUBLIC_LOCALE_PT_BR,[],[],false,false);
registration_success_expect(str_contains($normalHtml,'data-cms-condition-field="payment_method"'),'pre-submit form must keep runtime payment conditions');

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('CREATE TABLE cms_forms (id INTEGER PRIMARY KEY, locale TEXT NOT NULL, form_key TEXT NOT NULL, status TEXT NOT NULL, draft_schema_json TEXT NOT NULL, published_schema_json TEXT NOT NULL, draft_revision INTEGER NOT NULL, published_revision INTEGER NOT NULL, draft_updated_at TEXT, published_at TEXT, updated_at TEXT);');
$legacy=$schema;
$legacy['successMessage']='Sua inscrição foi recebida. A vaga é confirmada somente após a confirmação do pagamento.';
foreach($legacy['settings']['contentBlocks'] as &$block){unset($block['showOnSuccess']);if(($block['id']??'')==='payment_pix')$block['html']=str_replace('R$ 698,00','PIX EDITADO PELO CMS',$block['html']);}
unset($block);
$json=json_encode($legacy,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$db->prepare('INSERT INTO cms_forms(id,locale,form_key,status,draft_schema_json,published_schema_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(1,?,?,?,?,?,4,4,?,?,?)')->execute([PUBLIC_LOCALE_PT_BR,'registration','active',$json,$json,'old','old','old']);
$migration=require __DIR__.'/../migrations/032_keep_registration_payment_on_success.php';$migration($db);
$row=$db->query('SELECT * FROM cms_forms WHERE id=1')->fetch();
$migrated=json_decode((string)$row['published_schema_json'],true);$migratedBlocks=[];foreach($migrated['settings']['contentBlocks']??[] as $block)$migratedBlocks[(string)($block['id']??'')]=$block;
registration_success_expect(!empty($migratedBlocks['payment_pix']['showOnSuccess'])&&!empty($migratedBlocks['payment_card']['showOnSuccess']),'migration must enable post-submit visibility for both payment methods');
registration_success_expect(str_contains((string)$migratedBlocks['payment_pix']['html'],'PIX EDITADO PELO CMS'),'migration must preserve edited payment content');
registration_success_expect(($migrated['successMessage']??'')==='Recebi seus dados. Se ainda não concluiu o pagamento, use abaixo a forma escolhida. Se já pagou, basta aguardar a confirmação.','migration must update the known legacy success copy');
registration_success_expect((int)$row['draft_revision']===5&&(int)$row['published_revision']===5,'migration must advance form revision once');

$formSubmit=(string)file_get_contents(__DIR__.'/../form-submit.php');
registration_success_expect(str_contains($formSubmit,"['success'=>true,'values'=>cms_form_success_context(\$schema,\$values)]"),'submit flow must pass only success context into the confirmation render');

echo "Registration success payment tests passed\n";
