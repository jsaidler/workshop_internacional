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

foreach([
    '034_pinhole_lambe_lambe_landing.php',
    '035_pinhole_lambe_lambe_sales_rewrite.php',
    '036_pinhole_public_copy.php',
    '037_pinhole_uiux_and_site_identity.php',
    '038_restore_pinhole_media_and_global_form_ux.php',
    '039_pinhole_camera_authority.php',
    '040_pinhole_strkng_89.php',
    '041_pinhole_authority_refinement.php',
    '042_pinhole_hybrid_format.php',
] as $migrationFile){
    $migration=require dirname(__DIR__).'/migrations/'.$migrationFile;
    if(!is_callable($migration))throw new RuntimeException('migration_not_callable: '.$migrationFile);
    $migration($db);
    if($migrationFile==='034_pinhole_lambe_lambe_landing.php')$migration($db);
}

$page=$db->query("SELECT * FROM cms_pages WHERE activity_id=1 AND locale='pt-BR' AND slug='pinhole-lambe-lambe'")->fetch();
if(!$page)throw new RuntimeException('pinhole_page_missing');
if((int)$page['show_in_nav']!==1)throw new RuntimeException('pinhole_page_not_in_nav');
if($page['published_document_json']===null)throw new RuntimeException('pinhole_page_not_published');
if((int)$db->query("SELECT COUNT(*) FROM cms_pages WHERE slug='pinhole-lambe-lambe'")->fetchColumn()!==1)throw new RuntimeException('pinhole_page_not_idempotent');
if((int)$db->query("SELECT COUNT(*) FROM cms_pages WHERE activity_id=2")->fetchColumn()!==0)throw new RuntimeException('pinhole_page_leaked_to_non_root_activity');

$document=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$html=(string)($document['html']??'');
foreach([
    'class="hero"',
    'class="hero-image"',
    'data-cms-image-placeholder',
    'Imagem ou vídeo da Pinhole Lambe-Lambe',
    'Detalhe da Pinhole Lambe-Lambe',
    'Prévia do projeto em PDF',
    '/assets/media/joao-portrait.webp',
    'data-cms-section="project"',
    'data-cms-section="offer"',
    'data-cms-section="about"',
    'data-cms-section="faq"',
    'data-cms-section="interest"',
    'data-cms-form-key="pinhole-interest"',
    'Uma câmera fotográfica sem lente e um pequeno laboratório no mesmo equipamento',
    'Uma câmera que também abriga o laboratório.',
    'Oficina on-line · conteúdo em vídeo + encontro ao vivo',
    'Vídeo + encontro ao vivo',
    '60 a 90 minutos',
    'O projeto completo. A construção em vídeo. E um encontro ao vivo comigo.',
    'Construção em vídeo',
    'Como funciona o conteúdo em vídeo?',
    'O encontro ao vivo fica gravado?',
    'class="format-grid cols-3"',
    'class="section-cta"',
    'class="section about section-compact"',
    'Receba a data e o valor.',
] as $needle){
    if(!str_contains($html,$needle))throw new RuntimeException('pinhole_page_missing_content: '.$needle);
}
if(substr_count($html,'data-cms-image-placeholder')<3)throw new RuntimeException('pinhole_page_missing_visual_placeholders');
foreach([
    'hero--copy-only',
    'about--single',
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
    '2 a 3 horas',
    '1 encontro ao vivo',
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
foreach(['.format-grid.cols-3','.section-compact','.hero-image>.cms-media-placeholder','.cms-project-preview'] as $needle){
    if(!str_contains($uiCss,$needle))throw new RuntimeException('ui_refinement_missing: '.$needle);
}

echo "Pinhole Lambe-Lambe hybrid UI/UX sales page OK\n";
