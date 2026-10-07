<?php
declare(strict_types=1);

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

$oldDoc=json_encode([
  'version'=>2,'theme'=>'auto','meta'=>['title'=>'Pinhole Lambe-Lambe','description'=>'old'],
  'html'=>'<section data-cms-section="hero">Pinhole Lambe-Lambe</section>',
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$db->prepare('INSERT INTO cms_pages(locale,slug,title,nav_title,status,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)')->execute([
  'pt-BR','pinhole-lambe-lambe','Pinhole Lambe-Lambe','Pinhole','active',$oldDoc,$oldDoc,1,1,'2026-10-06T00:00:00Z','2026-10-06T00:00:00Z','2026-10-06T00:00:00Z'
]);
$pageId=(int)$db->lastInsertId();
$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,updated_at) VALUES(?,?,?,?,?,?)')->execute([$pageId,'old','old','old','old','2026-10-06T00:00:00Z']);

$oldForm=json_encode(['version'=>1,'submitLabel'=>'Quero receber a data','fields'=>[
  ['id'=>'name','type'=>'text','label'=>'Nome','required'=>true,'width'=>'half'],
  ['id'=>'email','type'=>'email','label'=>'E-mail','required'=>true,'width'=>'half'],
  ['id'=>'consent','type'=>'consent','label'=>'Consentimento','required'=>true,'width'=>'full'],
]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$db->prepare('INSERT INTO cms_forms(locale,form_key,title,status,draft_schema_json,published_schema_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,?,1,1,?,?,?)')->execute([
  'pt-BR','pinhole-interest','Lista antiga','active',$oldForm,$oldForm,'2026-10-06T00:00:00Z','2026-10-06T00:00:00Z','2026-10-06T00:00:00Z'
]);

foreach(['093_experimental_large_format_course_page.php','094_large_format_reuse_canonical_components.php','095_large_format_restore_visual_placeholders.php'] as $file){
  $migration=require dirname(__DIR__,2).'/migrations/'.$file;
  $migration($db);
}
$page=$db->query("SELECT * FROM cms_pages WHERE id=$pageId")->fetch();
$doc=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$html=(string)($doc['html']??'');

$form=<<<'HTML'
<div class="cms-form-block">
<form class="cms-form">
  <div class="cms-form-grid">
    <label class="cms-field half"><span>Nome <span aria-hidden="true">*</span></span><input name="name"></label>
    <label class="cms-field half"><span>E-mail <span aria-hidden="true">*</span></span><input name="email" type="email"></label>
    <label class="cms-field"><span>Instagram ou WhatsApp (opcional)</span><input name="contact"></label>
    <fieldset class="cms-choice-group"><legend>O que hoje mais dificulta sua entrada no grande formato? <span aria-hidden="true">*</span></legend>
      <div class="cms-choice-grid">
        <label id="choice-hover"><input type="radio" name="entry_barrier" value="equipment-cost"><span>O custo de câmera e equipamentos para começar</span></label>
        <label><input type="radio" name="entry_barrier" value="no-camera"><span>Não ter uma câmera de grande formato</span></label>
        <label><input type="radio" name="entry_barrier" value="process"><span>Não saber como começar com filme em folha e processamento</span></label>
        <label><input type="radio" name="entry_barrier" value="experimental-entry"><span>Quero começar por um caminho mais experimental e construtivo</span></label>
        <label><input type="radio" name="entry_barrier" value="other"><span>Outro</span></label>
      </div>
    </fieldset>
    <fieldset class="cms-choice-group"><legend>O que você gostaria de conseguir fazer ao final?</legend>
      <div class="cms-choice-grid">
        <label><input type="checkbox"><span>Construir e operar minha própria câmera-laboratório</span></label>
        <label><input type="checkbox"><span>Fotografar e processar negativos em folha</span></label>
        <label><input type="checkbox"><span>Produzir positivos físicos por contato</span></label>
        <label><input type="checkbox"><span>Ter autonomia para continuar criando meus próprios experimentos</span></label>
      </div>
    </fieldset>
    <label class="cms-consent"><input type="checkbox"><span>Quero receber informações sobre a primeira turma de Fotografia Experimental em Grande Formato.</span></label>
  </div>
  <button class="button" id="form-submit" type="button">Quero receber a abertura da turma <span aria-hidden="true">↗</span></button>
</form>
</div>
HTML;
$html=str_replace('<div data-cms-form-key="pinhole-interest"></div>',$form,$html);
?>
<!doctype html>
<html lang="pt-BR" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Fotografia Experimental em Grande Formato — visual QA</title>
<style data-cms-responsive>
@import url("/template/page.css") layer(cms-system);
@import url("/assets/cms-core.css") layer(cms-system);
@import url("/assets/cms-pro.css") layer(cms-system);
@import url("/assets/cms-responsive.css") layer(cms-system);
@import url("/assets/cms-header.css") layer(cms-system);
@import url("/assets/cms-editorial.css") layer(cms-system);
@import url("/assets/cms-study.css") layer(cms-system);
@import url("/assets/cms-student-notes.css") layer(cms-system);
</style>
<style>@layer cms-system{:root{--cms-body-size:17px;--cms-body-lh:1.55;--cms-display-lh:1.02;--cms-h1:clamp(70px,9.2vw,154px);--cms-h2:clamp(46px,6.4vw,108px);--cms-h3:28px;--cms-lead:24px;--cms-small:13px;--cms-button-bg:#0b0c0d;--cms-button-text:#fff;--cms-button-border:#0b0c0d;--cms-button-height:54px;--cms-button-padding-x:22px}}</style>
</head>
<body class="cms-public" data-cms-page-slug="pinhole-lambe-lambe">
<header class="topbar cms-topbar"><a class="brand" href="#">Direct Positive Workshop</a><nav><a href="#">Workshop</a><a aria-current="page" href="#">Grande Formato Experimental</a></nav><div class="cms-topbar-actions"></div></header>
<main id="main"><?=$html?></main>
<footer class="cms-footer"><p>João Saidler · Petrópolis, Brasil</p><p>Fotografia experimental · processos fotográficos</p></footer>
</body>
</html>
