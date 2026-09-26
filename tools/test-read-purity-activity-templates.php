<?php
declare(strict_types=1);

function fail_read_purity(string $message): never {fwrite(STDERR,"read-purity-activity-templates: $message\n");exit(1);}
function must_read_purity(bool $condition,string $message): void {if(!$condition)fail_read_purity($message);}
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function utc_now(): string {return gmdate('c');}
function canonical_content(): array {return ['schemaVersion'=>1,'texts'=>[],'forms'=>[],'media'=>[]];}

$root=dirname(__DIR__);
require $root.'/app/public_locale.php';
require $root.'/app/cms_pages.php';
require $root.'/app/cms_forms.php';
require $root.'/app/workshop_registration_content.php';
require $root.'/app/workshop_settings.php';
require $root.'/app/workshop_cms_setup.php';
require $root.'/app/workshop_copy_refinements.php';
require $root.'/app/activity_repository.php';
require $root.'/app/cms_discovery.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON');
$db->exec(<<<'SQL'
CREATE TABLE activities(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_name TEXT NOT NULL,
    public_title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    status TEXT NOT NULL DEFAULT 'active',
    is_root INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE content_documents(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    document_key TEXT NOT NULL,
    activity_id INTEGER NOT NULL,
    schema_version INTEGER NOT NULL,
    draft_json TEXT NOT NULL,
    published_json TEXT NULL,
    draft_revision INTEGER NOT NULL,
    published_revision INTEGER NULL,
    draft_updated_at TEXT NOT NULL,
    published_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE workshop_locale_settings(
    activity_id INTEGER NOT NULL,
    locale TEXT NOT NULL,
    planned_price TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(activity_id,locale)
);
SQL);
(require $root.'/migrations/011_cms_pages_forms.php')($db);

$blank=activity_create($db,'Curso vazio','Curso vazio','curso-vazio',null,ACTIVITY_TEMPLATE_BLANK);
$blankId=(int)$blank['id'];
must_read_purity((int)$db->query('SELECT COUNT(*) FROM cms_pages')->fetchColumn()===0,'blank template created CMS pages');
must_read_purity((int)$db->query('SELECT COUNT(*) FROM cms_forms')->fetchColumn()===0,'blank template created CMS forms');
must_read_purity((int)$db->query('SELECT COUNT(*) FROM content_documents')->fetchColumn()===0,'blank template created legacy content document');

must_read_purity(cms_pages($db,$blankId)===[],'empty page query did not return an empty list');
must_read_purity(cms_page_home($db,$blankId,PUBLIC_LOCALE_PT_BR)===null,'empty home query created or returned a page');
must_read_purity(cms_page_by_slug($db,$blankId,PUBLIC_LOCALE_PT_BR,'qualquer')===null,'empty slug query created or returned a page');
must_read_purity(cms_nav_pages($db,$blankId,PUBLIC_LOCALE_PT_BR)===[],'empty navigation query created pages');
must_read_purity(cms_forms($db,$blankId)===[],'empty form query did not return an empty list');
must_read_purity(cms_form_by_key($db,$blankId,PUBLIC_LOCALE_PT_BR,'registration')===null,'empty form lookup created or returned a form');
must_read_purity(cms_sitemap_entries($db)===[],'empty activity unexpectedly produced sitemap entries');
must_read_purity((int)$db->query('SELECT COUNT(*) FROM cms_pages')->fetchColumn()===0,'read path seeded pages');
must_read_purity((int)$db->query('SELECT COUNT(*) FROM cms_forms')->fetchColumn()===0,'read path seeded forms');

$direct=activity_create($db,'Positivo direto','Positivo direto','positivo-direto',null,ACTIVITY_TEMPLATE_DIRECT_POSITIVE);
$directId=(int)$direct['id'];
$q=$db->prepare("SELECT COUNT(*) FROM cms_pages WHERE activity_id=? AND status!='archived'");$q->execute([$directId]);must_read_purity((int)$q->fetchColumn()===3,'direct-positive template did not create the expected pages');
$q=$db->prepare("SELECT COUNT(*) FROM cms_forms WHERE activity_id=? AND status!='archived'");$q->execute([$directId]);must_read_purity((int)$q->fetchColumn()===2,'direct-positive template did not create the expected forms');
$q=$db->prepare('SELECT COUNT(*) FROM content_documents WHERE activity_id=?');$q->execute([$directId]);must_read_purity((int)$q->fetchColumn()===1,'direct-positive compatibility document missing');

$home=cms_page_home($db,$directId,PUBLIC_LOCALE_PT_BR)??fail_read_purity('direct-positive home missing');
$form=cms_form_by_key($db,$directId,PUBLIC_LOCALE_PT_BR,'registration')??fail_read_purity('direct-positive registration form missing');
$db->prepare('UPDATE cms_pages SET title=? WHERE id=?')->execute(['HOME EDITADA',(int)$home['id']]);
$db->prepare('UPDATE cms_forms SET title=? WHERE id=?')->execute(['FORM EDITADO',(int)$form['id']]);
workshop_cms_setup_activity($db,$directId);
must_read_purity((string)$db->query('SELECT title FROM cms_pages WHERE id='.(int)$home['id'])->fetchColumn()==='HOME EDITADA','template reapplied over edited page');
must_read_purity((string)$db->query('SELECT title FROM cms_forms WHERE id='.(int)$form['id'])->fetchColumn()==='FORM EDITADO','template reapplied over edited form');

$pagesAdmin=(string)file_get_contents($root.'/admin/pages.php');
$formsAdmin=(string)file_get_contents($root.'/admin/forms.php');
$activitiesAdmin=(string)file_get_contents($root.'/admin/activities.php');
must_read_purity(!str_contains($pagesAdmin,'cms_pages_seed('),'pages admin still seeds on read');
must_read_purity(!str_contains($formsAdmin,'cms_forms_seed('),'forms admin still seeds on read');
must_read_purity(str_contains($activitiesAdmin,'name="template"')&&str_contains($activitiesAdmin,'activity_template_options()'),'activity template is not explicit in admin');

echo "read-purity-activity-templates: ok\n";
