<?php
declare(strict_types=1);

function fail_large_format(string $message): never {fwrite(STDERR,"test-experimental-large-format-course-page: $message\n");exit(1);}
function expect_large_format(bool $value,string $message): void {if(!$value)fail_large_format($message);}

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec(<<<'SQL'
CREATE TABLE cms_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    locale TEXT NOT NULL,
    slug TEXT NOT NULL,
    title TEXT NOT NULL,
    nav_title TEXT NOT NULL,
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
    title TEXT NOT NULL,
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
    title TEXT NOT NULL DEFAULT '',
    description TEXT NOT NULL DEFAULT '',
    social_title TEXT NOT NULL DEFAULT '',
    social_description TEXT NOT NULL DEFAULT '',
    social_image TEXT NOT NULL DEFAULT '',
    canonical_url TEXT NOT NULL DEFAULT '',
    robots TEXT NOT NULL DEFAULT 'index,follow',
    updated_at TEXT NOT NULL
);
SQL);

$oldHtml='<section data-cms-section="hero">Pinhole Lambe-Lambe</section><section data-cms-section="offer">Materiais alternativos — R$ 98 · Madeira — R$ 98 · As duas — R$ 168</section><section data-cms-section="interest"><div data-cms-form-key="pinhole-interest"></div></section>';
$oldDoc=json_encode(['version'=>2,'theme'=>'auto','meta'=>['title'=>'Pinhole Lambe-Lambe','description'=>'old'],'html'=>$oldHtml],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$db->prepare('INSERT INTO cms_pages(locale,slug,title,nav_title,status,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
    'pt-BR','pinhole-lambe-lambe','Pinhole Lambe-Lambe','Pinhole','active',$oldDoc,$oldDoc,1,1,'2026-10-06T00:00:00Z','2026-10-06T00:00:00Z','2026-10-06T00:00:00Z'
]);
$pageId=(int)$db->lastInsertId();
$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,updated_at) VALUES(?,?,?,?,?,?)')->execute([$pageId,'old','old','old','old','2026-10-06T00:00:00Z']);

$oldForm=json_encode(['version'=>1,'submitLabel'=>'Quero receber a data','fields'=>[
    ['id'=>'name','type'=>'text','label'=>'Nome','required'=>true,'width'=>'half'],
    ['id'=>'email','type'=>'email','label'=>'E-mail','required'=>true,'width'=>'half'],
    ['id'=>'product_version','type'=>'radio','label'=>'Qual opção?','required'=>true,'width'=>'full','options'=>[
        ['value'=>'alternative','label'=>'Materiais alternativos — R$ 98'],
        ['value'=>'wood','label'=>'Madeira — R$ 98'],
        ['value'=>'both','label'=>'As duas — R$ 168'],
    ]],
    ['id'=>'consent','type'=>'consent','label'=>'Consentimento','required'=>true,'width'=>'full'],
]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$db->prepare('INSERT INTO cms_forms(locale,form_key,title,status,draft_schema_json,published_schema_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,?,1,1,?,?,?)')->execute([
    'pt-BR','pinhole-interest','Lista antiga','active',$oldForm,$oldForm,'2026-10-06T00:00:00Z','2026-10-06T00:00:00Z','2026-10-06T00:00:00Z'
]);
$formId=(int)$db->lastInsertId();

$migration=require dirname(__DIR__).'/migrations/091_experimental_large_format_course_page.php';
expect_large_format(is_callable($migration),'migration not callable');
$migration($db);

$page=$db->query("SELECT * FROM cms_pages WHERE id=$pageId")->fetch();
expect_large_format((string)$page['slug']==='pinhole-lambe-lambe','existing public slug must be preserved');
expect_large_format((string)$page['title']==='Fotografia Experimental em Grande Formato','page title not updated');
expect_large_format((string)$page['nav_title']==='Grande Formato Experimental','nav title not updated');

$published=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$html=(string)($published['html']??'');
foreach([
    'Fotografia experimental',
    '8 encontros de 1h30',
    'R$ 1.290 no Pix',
    'R$ 1.490',
    'Uma câmera que já nasce como laboratório.',
    'filme de raio-X como material acessível',
    'Cianotipia',
    'Imagem em clorofila',
    'data-cms-form-key="pinhole-interest"',
    'Você quer experimentar grande formato',
] as $needle)expect_large_format(str_contains($html,$needle),'missing new course content: '.$needle);

foreach([
    'Materiais alternativos — R$ 98',
    'Madeira — R$ 98',
    'As duas — R$ 168',
    'crédito',
    'Positivo Direto em Filme de Raio-X',
    'data-cms-image-placeholder',
] as $needle)expect_large_format(!str_contains($html,$needle),'legacy or forbidden content remained: '.$needle);

foreach(['hero','diagnosis','camera-lab','journey','meetings','positive-processes','construction','offer','about','faq','interest'] as $section){
    expect_large_format(str_contains($html,'data-cms-section="'.$section.'"'),'missing section: '.$section);
}

$form=$db->query("SELECT * FROM cms_forms WHERE id=$formId")->fetch();
expect_large_format((string)$form['form_key']==='pinhole-interest','existing form key must be preserved');
expect_large_format((string)$form['title']==='Lista de interesse — Fotografia Experimental em Grande Formato','form title not updated');
$schema=json_decode((string)$form['published_schema_json'],true,512,JSON_THROW_ON_ERROR);
$fields=array_column((array)($schema['fields']??[]),null,'id');
foreach(['name','email','contact','entry_barrier','desired_outcome','consent'] as $field){
    expect_large_format(isset($fields[$field]),'missing field: '.$field);
}
expect_large_format(!isset($fields['product_version']),'legacy product_version field remained');
expect_large_format(($fields['entry_barrier']['type']??'')==='radio','entry_barrier must be radio');
expect_large_format(!empty($fields['entry_barrier']['required']),'entry_barrier must be required');
expect_large_format(($fields['desired_outcome']['type']??'')==='checkbox-group','desired_outcome must be checkbox-group');
expect_large_format(($schema['submitLabel']??'')==='Quero receber a abertura da turma','submit label not updated');

$seo=$db->query("SELECT * FROM cms_page_seo WHERE page_id=$pageId")->fetch();
expect_large_format(str_contains((string)$seo['title'],'Fotografia Experimental em Grande Formato'),'SEO title not updated');
expect_large_format(str_contains((string)$seo['description'],'8 encontros ao vivo'),'SEO description missing course format');
expect_large_format(str_contains((string)$seo['social_description'],'R$ 1.290 no Pix'),'social description missing launch price');

echo "Experimental large-format course page OK\n";
