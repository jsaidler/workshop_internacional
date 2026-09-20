<?php
declare(strict_types=1);

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec(<<<'SQL'
CREATE TABLE activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    is_root INTEGER NOT NULL DEFAULT 0
);
CREATE TABLE cms_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_uuid TEXT NOT NULL UNIQUE,
    activity_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    slug TEXT NOT NULL DEFAULT '',
    title TEXT NOT NULL,
    nav_title TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    is_home INTEGER NOT NULL DEFAULT 0,
    show_in_nav INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    draft_document_json TEXT NOT NULL,
    published_document_json TEXT NULL,
    draft_revision INTEGER NOT NULL DEFAULT 1,
    published_revision INTEGER NULL,
    draft_updated_at TEXT NOT NULL,
    published_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_cms_pages_activity_locale_slug ON cms_pages(activity_id,locale,slug);
CREATE TABLE cms_forms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    form_uuid TEXT NOT NULL UNIQUE,
    activity_id INTEGER NOT NULL,
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
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE UNIQUE INDEX idx_cms_forms_activity_locale_key ON cms_forms(activity_id,locale,form_key);
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
$db->exec('INSERT INTO activities(id,is_root) VALUES(1,1),(2,0)');

$migration=require dirname(__DIR__).'/migrations/034_pinhole_lambe_lambe_landing.php';
if(!is_callable($migration))throw new RuntimeException('migration_not_callable');
$migration($db);
$migration($db);

$page=$db->query("SELECT * FROM cms_pages WHERE activity_id=1 AND locale='pt-BR' AND slug='pinhole-lambe-lambe'")->fetch();
if(!$page)throw new RuntimeException('pinhole_page_missing');
if((int)$page['show_in_nav']!==1)throw new RuntimeException('pinhole_page_not_in_nav');
if($page['published_document_json']===null)throw new RuntimeException('pinhole_page_not_published');
if((int)$db->query("SELECT COUNT(*) FROM cms_pages WHERE slug='pinhole-lambe-lambe'")->fetchColumn()!==1)throw new RuntimeException('pinhole_page_not_idempotent');
if((int)$db->query("SELECT COUNT(*) FROM cms_pages WHERE activity_id=2")->fetchColumn()!==0)throw new RuntimeException('pinhole_page_leaked_to_non_root_activity');

$document=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$html=(string)($document['html']??'');
foreach([
    'data-cms-section="hero"',
    'data-cms-image-placeholder',
    'data-cms-form-key="pinhole-interest"',
    'Sem pagamento agora',
    '2 a 3 horas',
    'Eu não vou fotografar nem revelar durante este encontro',
    'O PDF é o projeto da câmera, não um resumo da aula',
] as $needle){
    if(!str_contains($html,$needle))throw new RuntimeException('pinhole_page_missing_content: '.$needle);
}

$form=$db->query("SELECT * FROM cms_forms WHERE activity_id=1 AND locale='pt-BR' AND form_key='pinhole-interest'")->fetch();
if(!$form)throw new RuntimeException('pinhole_interest_form_missing');
if($form['published_schema_json']===null)throw new RuntimeException('pinhole_interest_form_not_published');
if((int)$db->query("SELECT COUNT(*) FROM cms_forms WHERE form_key='pinhole-interest'")->fetchColumn()!==1)throw new RuntimeException('pinhole_interest_form_not_idempotent');
$schema=json_decode((string)$form['published_schema_json'],true,512,JSON_THROW_ON_ERROR);
$fields=array_column((array)($schema['fields']??[]),null,'id');
foreach(['name','email','contact','main_interest','consent'] as $field){
    if(!isset($fields[$field]))throw new RuntimeException('pinhole_interest_missing_field: '.$field);
}
if(empty($fields['name']['required'])||empty($fields['email']['required'])||empty($fields['consent']['required']))throw new RuntimeException('pinhole_interest_required_fields_wrong');

$seo=$db->query('SELECT * FROM cms_page_seo WHERE page_id='.(int)$page['id'])->fetch();
if(!$seo)throw new RuntimeException('pinhole_page_seo_missing');
if(!str_contains((string)$seo['title'],'Pinhole Lambe-Lambe'))throw new RuntimeException('pinhole_page_seo_wrong');

echo "Pinhole Lambe-Lambe page seed OK\n";
