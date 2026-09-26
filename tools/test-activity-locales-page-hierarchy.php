<?php
declare(strict_types=1);

function fail_identity_hierarchy(string $message): never {fwrite(STDERR,"activity-locales-page-hierarchy: $message\n");exit(1);}
function must_identity_hierarchy(bool $condition,string $message): void {if(!$condition)fail_identity_hierarchy($message);}
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function utc_now(): string {return gmdate('c');}
function canonical_content(): array {return ['schemaVersion'=>1,'texts'=>[],'forms'=>[],'media'=>[]];}

$root=dirname(__DIR__);
require $root.'/app/public_locale.php';
require $root.'/app/activity_locales.php';
require $root.'/app/cms_pages.php';
require $root.'/app/cms_page_structure.php';
require $root.'/app/activity_repository.php';

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
SQL);
(require $root.'/migrations/011_cms_pages_forms.php')($db);
$now=gmdate('c');
$db->prepare('INSERT INTO activities(admin_name,public_title,slug,status,is_root,created_at,updated_at) VALUES(?,?,?,?,?,?,?)')->execute(['Legado','Título legado','legado','active',1,$now,$now]);
$legacyId=(int)$db->lastInsertId();
$legacyPage=cms_page_create($db,$legacyId,PUBLIC_LOCALE_PT_BR,'Página principal','');
$db->prepare("UPDATE cms_pages SET is_home=1,slug='' WHERE id=?")->execute([(int)$legacyPage['id']]);
(require $root.'/migrations/068_activity_locales_page_hierarchy.php')($db);

$legacy=activity_by_id($db,$legacyId)??fail_identity_hierarchy('legacy activity missing');
must_identity_hierarchy(activity_public_title($db,$legacy,PUBLIC_LOCALE_PT_BR)==='Título legado','legacy public title was not backfilled');
activity_locale_save($db,$legacyId,PUBLIC_LOCALE_EN,'Legacy Course');
must_identity_hierarchy(activity_public_title($db,$legacy,PUBLIC_LOCALE_EN)==='Legacy Course','localized title did not take precedence');
must_identity_hierarchy((string)$db->query('SELECT public_title FROM activities WHERE id='.$legacyId)->fetchColumn()==='Título legado','localized write changed legacy public_title');
activity_update_identity($db,$legacyId,'Legado renomeado','Título localizado',PUBLIC_LOCALE_PT_BR);
must_identity_hierarchy(activity_public_title($db,activity_by_id($db,$legacyId)??$legacy,PUBLIC_LOCALE_PT_BR)==='Título localizado','identity update did not write localized title');
must_identity_hierarchy((string)$db->query('SELECT public_title FROM activities WHERE id='.$legacyId)->fetchColumn()==='Título legado','identity update still writes legacy public_title');

$blank=activity_create($db,'Curso English','English Course','english-course',null,ACTIVITY_TEMPLATE_BLANK,PUBLIC_LOCALE_EN);
$blankId=(int)$blank['id'];
must_identity_hierarchy(activity_public_title($db,$blank,PUBLIC_LOCALE_EN)==='English Course','new activity did not create localized title');
$q=$db->prepare('SELECT COUNT(*) FROM cms_pages WHERE activity_id=?');$q->execute([$blankId]);must_identity_hierarchy((int)$q->fetchColumn()===0,'blank activity unexpectedly created pages');

$home=cms_page_by_id($db,(int)$legacyPage['id'])??fail_identity_hierarchy('home missing after migration');
$child=cms_page_create($db,$legacyId,PUBLIC_LOCALE_PT_BR,'Inscrição','inscricao');
$english=cms_page_create($db,$legacyId,PUBLIC_LOCALE_EN,'Registration','registration');
$otherPt=cms_page_create($db,$legacyId,PUBLIC_LOCALE_PT_BR,'Material','material');
$otherPtChild=cms_page_create($db,$legacyId,PUBLIC_LOCALE_PT_BR,'Detalhe','detalhe');
$childSlug=(string)$child['slug'];$englishSlug=(string)$english['slug'];

cms_page_set_parent($db,(int)$child['id'],(int)$home['id']);
$child=cms_page_by_id($db,(int)$child['id'])??fail_identity_hierarchy('child missing');
must_identity_hierarchy((int)$child['parent_page_id']===(int)$home['id'],'parent_page_id not stored');
must_identity_hierarchy((string)$child['slug']===$childSlug,'hierarchy changed child slug');

$crossLocaleRejected=false;try{cms_page_set_parent($db,(int)$child['id'],(int)$english['id']);}catch(RuntimeException){$crossLocaleRejected=true;}
must_identity_hierarchy($crossLocaleRejected,'cross-locale parent was accepted');
cms_page_set_parent($db,(int)$otherPtChild['id'],(int)$otherPt['id']);
$cycleRejected=false;try{cms_page_set_parent($db,(int)$otherPt['id'],(int)$otherPtChild['id']);}catch(RuntimeException){$cycleRejected=true;}
must_identity_hierarchy($cycleRejected,'hierarchy cycle was accepted');

cms_page_set_translation_peer($db,(int)$child['id'],(int)$english['id']);
$child=cms_page_by_id($db,(int)$child['id'])??fail_identity_hierarchy('translated child missing');
$english=cms_page_by_id($db,(int)$english['id'])??fail_identity_hierarchy('translated peer missing');
$group=cms_page_translation_group_value($child);
must_identity_hierarchy($group!==''&&$group===cms_page_translation_group_value($english),'translation group was not shared');
must_identity_hierarchy((string)$child['slug']===$childSlug&&(string)$english['slug']===$englishSlug,'translation identity changed a slug');
$counterpart=cms_page_translation_counterpart($db,$child,PUBLIC_LOCALE_EN);
must_identity_hierarchy($counterpart&&(int)$counterpart['id']===(int)$english['id'],'translation counterpart still depends on matching slug');

cms_page_set_translation_peer($db,(int)$child['id'],null);
$child=cms_page_by_id($db,(int)$child['id'])??fail_identity_hierarchy('child missing after unlink');
must_identity_hierarchy(cms_page_translation_group_value($child)==='','translation unlink failed');
must_identity_hierarchy(cms_page_translation_counterpart($db,$child,PUBLIC_LOCALE_EN)===null,'legacy fallback matched a different slug unexpectedly');

$adminPages=(string)file_get_contents($root.'/admin/pages.php');
$adminActivities=(string)file_get_contents($root.'/admin/activities.php');
$renderer=(string)file_get_contents($root.'/app/cms_renderer.php');
$discovery=(string)file_get_contents($root.'/app/cms_discovery.php');
must_identity_hierarchy(str_contains($adminPages,'name="parent_page_id"')&&str_contains($adminPages,'name="translation_page_id"'),'canonical Pages admin does not expose hierarchy/equivalence');
must_identity_hierarchy(str_contains($adminActivities,'public_title_pt')&&str_contains($adminActivities,'public_title_en'),'canonical activity admin does not expose localized titles');
must_identity_hierarchy(str_contains($renderer,'cms_page_translation_counterpart'),'language switch does not use explicit translation identity');
must_identity_hierarchy(str_contains($renderer,'activity_public_title'),'public renderer does not use localized activity title fallback');
must_identity_hierarchy(str_contains($discovery,'cms_page_translation_counterpart'),'sitemap/hreflang discovery does not use explicit translation identity');

echo "activity-locales-page-hierarchy: ok\n";
