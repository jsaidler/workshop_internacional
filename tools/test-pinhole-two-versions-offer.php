<?php
declare(strict_types=1);

function fail_versions(string $message): never {fwrite(STDERR,"test-pinhole-two-versions-offer: $message\n");exit(1);}
function expect_versions(bool $value,string $message): void {if(!$value)fail_versions($message);}

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec(<<<'SQL'
CREATE TABLE cms_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    locale TEXT NOT NULL,
    slug TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    draft_document_json TEXT NOT NULL,
    published_document_json TEXT NULL,
    draft_revision INTEGER NOT NULL DEFAULT 1,
    published_revision INTEGER NULL,
    draft_updated_at TEXT NOT NULL,
    published_at TEXT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE cms_forms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    locale TEXT NOT NULL,
    form_key TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    draft_schema_json TEXT NOT NULL,
    published_schema_json TEXT NULL,
    draft_revision INTEGER NOT NULL DEFAULT 1,
    published_revision INTEGER NULL,
    draft_updated_at TEXT NOT NULL,
    published_at TEXT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE cms_page_seo (
    page_id INTEGER PRIMARY KEY,
    description TEXT NOT NULL DEFAULT '',
    social_description TEXT NOT NULL DEFAULT '',
    updated_at TEXT NOT NULL
);
SQL);

$oldHtml='<section data-cms-section="hero">Pinhole antiga</section><section data-cms-section="project">papel paraná, alumínio de lata e fita isolante</section><section data-cms-section="offer">oferta antiga</section><section data-cms-section="about">fabrico para venda a NINA · STRKNG Editors\' Selection #88 e #89</section><section data-cms-section="faq">FAQ antiga</section><section data-cms-section="interest"><div data-cms-form-key="pinhole-interest"></div></section>';
$doc=json_encode(['version'=>2,'theme'=>'auto','meta'=>['title'=>'Pinhole','description'=>'antiga'],'html'=>$oldHtml],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$db->prepare('INSERT INTO cms_pages(locale,slug,status,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,1,1,?,?,?)')->execute(['pt-BR','pinhole-lambe-lambe','active',$doc,$doc,'2026-09-21T00:00:00Z','2026-09-21T00:00:00Z','2026-09-21T00:00:00Z']);
$pageId=(int)$db->lastInsertId();
$db->prepare('INSERT INTO cms_page_seo(page_id,description,social_description,updated_at) VALUES(?,?,?,?)')->execute([$pageId,'old','old','2026-09-21T00:00:00Z']);

$oldForm=json_encode(['version'=>1,'submitLabel'=>'Quero receber data e valor','fields'=>[
    ['id'=>'name','type'=>'text','label'=>'Nome','required'=>true,'width'=>'half'],
    ['id'=>'email','type'=>'email','label'=>'E-mail','required'=>true,'width'=>'half'],
    ['id'=>'contact','type'=>'text','label'=>'Contato','required'=>false,'width'=>'full'],
    ['id'=>'consent','type'=>'consent','label'=>'Consentimento','required'=>true,'width'=>'full'],
]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$db->prepare('INSERT INTO cms_forms(locale,form_key,status,draft_schema_json,published_schema_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,1,1,?,?,?)')->execute(['pt-BR','pinhole-interest','active',$oldForm,$oldForm,'2026-09-21T00:00:00Z','2026-09-21T00:00:00Z','2026-09-21T00:00:00Z']);

$migration=require dirname(__DIR__).'/migrations/045_pinhole_two_versions_offer.php';
expect_versions(is_callable($migration),'migration not callable');
$migration($db);

$page=$db->query("SELECT * FROM cms_pages WHERE id=$pageId")->fetch();
$published=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$html=(string)($published['html']??'');

foreach([
    'duas versões · construção em vídeo + encontro ao vivo',
    'Materiais alternativos · R$ 98',
    'Madeira · R$ 98',
    'As duas · R$ 168',
    'Container plástico, pasta de escritório',
    'encomendar o corte nas medidas do projeto',
    'R$ 28 de economia',
    'R$ 98 do valor pago aqui viram crédito na inscrição',
    'No combo, o crédito máximo também é R$ 98',
    'Escolha sua versão e receba a data.',
    'data-cms-form-key="pinhole-interest"',
    'fabrico para venda a NINA',
    "STRKNG Editors' Selection #88 e #89",
] as $needle)expect_versions(str_contains($html,$needle),'missing offer copy: '.$needle);

foreach(['hero','versions','offer','about','faq','interest'] as $section)expect_versions(str_contains($html,'data-cms-section="'.$section.'"'),'missing section: '.$section);
expect_versions(substr_count($html,'data-cms-image-placeholder')===3,'expected three media placeholders');
expect_versions(!str_contains($html,'Receba a data e o valor.'),'old interest headline remained');
expect_versions(!str_contains($html,'papel paraná, alumínio de lata e fita isolante'),'old single-version material promise remained');

$form=$db->query("SELECT * FROM cms_forms WHERE form_key='pinhole-interest'")->fetch();
$schema=json_decode((string)$form['published_schema_json'],true,512,JSON_THROW_ON_ERROR);
$fields=array_column((array)($schema['fields']??[]),null,'id');
expect_versions(isset($fields['product_version']),'product_version choice missing');
expect_versions(($fields['product_version']['type']??'')==='radio','product_version must be radio');
expect_versions(!empty($fields['product_version']['required']),'product_version must be required');
$options=array_column((array)($fields['product_version']['options']??[]),'label','value');
expect_versions(($options['alternative']??'')==='Materiais alternativos — R$ 98','alternative option wrong');
expect_versions(($options['wood']??'')==='Madeira — R$ 98','wood option wrong');
expect_versions(($options['both']??'')==='As duas — R$ 168','combo option wrong');
expect_versions(($schema['submitLabel']??'')==='Quero receber a data','submit label wrong');
expect_versions(!isset($fields['main_interest']),'legacy research field returned');

$seo=$db->query("SELECT * FROM cms_page_seo WHERE page_id=$pageId")->fetch();
expect_versions(str_contains((string)$seo['description'],'R$ 98'),'SEO price missing');
expect_versions(str_contains((string)$seo['social_description'],'crédito de até R$ 98'),'SEO credit missing');

echo "Pinhole two-version offer OK\n";
