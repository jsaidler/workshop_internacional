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
CREATE TABLE cms_site_settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    activity_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    settings_json TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    UNIQUE(activity_id,locale)
);
SQL);
$db->exec('INSERT INTO activities(id,is_root) VALUES(1,1),(2,0)');
$db->exec("INSERT INTO cms_site_settings(activity_id,locale,settings_json,updated_at) VALUES(1,'pt-BR','{\"wordmark\":\"Workshop: Positivo Direto\",\"header\":{\"showLanguageSwitch\":true},\"footer\":{\"line1\":\"João Saidler\",\"line2\":\"Positivo direto\"}}','2026-09-20T00:00:00Z')");

$seed=require dirname(__DIR__).'/migrations/034_pinhole_lambe_lambe_landing.php';
if(!is_callable($seed))throw new RuntimeException('seed_migration_not_callable');
$seed($db);
$seed($db);

$rewrite=require dirname(__DIR__).'/migrations/035_pinhole_lambe_lambe_sales_rewrite.php';
if(!is_callable($rewrite))throw new RuntimeException('rewrite_migration_not_callable');
$rewrite($db);

$publicCopy=require dirname(__DIR__).'/migrations/036_pinhole_public_copy.php';
if(!is_callable($publicCopy))throw new RuntimeException('public_copy_migration_not_callable');
$publicCopy($db);

$uiux=require dirname(__DIR__).'/migrations/037_pinhole_uiux_and_site_identity.php';
if(!is_callable($uiux))throw new RuntimeException('uiux_migration_not_callable');
$uiux($db);

$page=$db->query("SELECT * FROM cms_pages WHERE activity_id=1 AND locale='pt-BR' AND slug='pinhole-lambe-lambe'")->fetch();
if(!$page)throw new RuntimeException('pinhole_page_missing');
if((int)$page['show_in_nav']!==1)throw new RuntimeException('pinhole_page_not_in_nav');
if($page['published_document_json']===null)throw new RuntimeException('pinhole_page_not_published');
if((int)$db->query("SELECT COUNT(*) FROM cms_pages WHERE slug='pinhole-lambe-lambe'")->fetchColumn()!==1)throw new RuntimeException('pinhole_page_not_idempotent');
if((int)$db->query("SELECT COUNT(*) FROM cms_pages WHERE activity_id=2")->fetchColumn()!==0)throw new RuntimeException('pinhole_page_leaked_to_non_root_activity');

$document=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$html=(string)($document['html']??'');
foreach([
    'class="hero hero--copy-only"',
    'data-cms-section="project"',
    'data-cms-section="offer"',
    'data-cms-section="about"',
    'data-cms-section="faq"',
    'data-cms-section="interest"',
    'data-cms-form-key="pinhole-interest"',
    'Uma câmera fotográfica sem lente e um pequeno laboratório no mesmo equipamento',
    'Uma câmera que também abriga o laboratório.',
    'O projeto completo da câmera — e a construção demonstrada do começo ao fim.',
    'class="format-grid cols-3"',
    'class="section-cta"',
    'class="section about section-compact"',
    'class="media-figure about-image"',
    'Receba a data e o valor.',
    '2 a 3 horas',
    '/assets/media/joao-portrait.webp',
] as $needle){
    if(!str_contains($html,$needle))throw new RuntimeException('pinhole_page_missing_content: '.$needle);
}
foreach([
    'class="hero-image"',
    'data-cms-image-placeholder',
    'protótipo',
    'em preparação',
    'entra aqui',
    'modelos preparados em diferentes estágios',
    'sem depender do tempo',
    'O material permanente',
    'As regras serão informadas',
    'porta de entrada',
    'experiência escolar',
    'Não haverá fotografia nem revelação durante a oficina',
] as $needle){
    if(stripos($html,$needle)!==false)throw new RuntimeException('pinhole_page_exposes_internal_or_wrong_ui_copy: '.$needle);
}

$form=$db->query("SELECT * FROM cms_forms WHERE activity_id=1 AND locale='pt-BR' AND form_key='pinhole-interest'")->fetch();
if(!$form)throw new RuntimeException('pinhole_interest_form_missing');
if($form['published_schema_json']===null)throw new RuntimeException('pinhole_interest_form_not_published');
$schema=json_decode((string)$form['published_schema_json'],true,512,JSON_THROW_ON_ERROR);
$fields=array_column((array)($schema['fields']??[]),null,'id');
foreach(['name','email','contact','consent'] as $field){
    if(!isset($fields[$field]))throw new RuntimeException('pinhole_interest_missing_field: '.$field);
}
if(isset($fields['main_interest']))throw new RuntimeException('pinhole_interest_form_kept_research_field');
if(($schema['submitLabel']??'')!=='Quero receber data e valor')throw new RuntimeException('pinhole_interest_submit_copy_wrong');

$siteJson=$db->query("SELECT settings_json FROM cms_site_settings WHERE activity_id=1 AND locale='pt-BR'")->fetchColumn();
$site=json_decode((string)$siteJson,true,512,JSON_THROW_ON_ERROR);
if(($site['wordmark']??'')!=='João Saidler')throw new RuntimeException('site_wordmark_not_neutral');
if(($site['siteName']??'')!=='João Saidler — Oficinas de fotografia')throw new RuntimeException('site_name_not_neutral');
if(($site['header']['showLanguageSwitch']??null)!==true)throw new RuntimeException('site_migration_overwrote_header_settings');

$renderer=(string)file_get_contents(dirname(__DIR__).'/app/cms_renderer.php');
if(!str_contains($renderer,'return $candidate?cms_page_url($activity,$candidate,$targetLocale):\'\';'))throw new RuntimeException('language_switch_still_falls_back_to_home');
if(!str_contains($renderer,'cms-ui-refinements.css'))throw new RuntimeException('ui_refinement_css_not_loaded');
if(!str_contains($renderer,'&&$langUrl!==\'\''))throw new RuntimeException('language_switch_not_hidden_without_counterpart');

$uiCss=(string)file_get_contents(dirname(__DIR__).'/assets/cms-ui-refinements.css');
foreach(['.hero--copy-only','.format-grid.cols-3','.section-compact','.interest .cms-form .button'] as $needle){
    if(!str_contains($uiCss,$needle))throw new RuntimeException('ui_refinement_missing: '.$needle);
}

echo "Pinhole Lambe-Lambe UI/UX sales page OK\n";
