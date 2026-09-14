<?php
declare(strict_types=1);

function v3_expect(bool $ok,string $message):void{if(!$ok){fwrite(STDERR,"test-cms-v3: $message\n");exit(1);}}
function activity_slug(string $value): string {$s=strtolower(trim(preg_replace('~[^a-z0-9]+~i','-',$value)??''));return trim($s,'-')?:'item';}
require __DIR__.'/../app/public_locale.php';
require __DIR__.'/../app/cms_pages.php';
require __DIR__.'/../app/cms_settings.php';
require __DIR__.'/../app/cms_blocks.php';
$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);$db->exec('PRAGMA foreign_keys=ON');
$db->exec("CREATE TABLE activities(id INTEGER PRIMARY KEY,admin_name TEXT,public_title TEXT,slug TEXT,status TEXT,is_root INTEGER,created_at TEXT,updated_at TEXT);INSERT INTO activities VALUES(1,'Workshop','Workshop','workshop','active',1,'now','now');CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,page_uuid TEXT,activity_id INTEGER,locale TEXT,slug TEXT,title TEXT,nav_title TEXT,status TEXT,is_home INTEGER,show_in_nav INTEGER,sort_order INTEGER,draft_document_json TEXT,published_document_json TEXT,draft_revision INTEGER,published_revision INTEGER,draft_updated_at TEXT,published_at TEXT,created_at TEXT,updated_at TEXT);CREATE TABLE media_assets(id INTEGER PRIMARY KEY,asset_uuid TEXT,kind TEXT,title TEXT,default_alt TEXT DEFAULT '',original_name TEXT,mime_type TEXT,byte_size INTEGER,width INTEGER,height INTEGER,duration REAL,checksum TEXT,processing_status TEXT,active_version_id INTEGER,archived_at TEXT,created_at TEXT,updated_at TEXT);");
$migration=require __DIR__.'/../migrations/015_professional_cms_foundation.php';$migration($db);$blocksMigration=require __DIR__.'/../migrations/017_reusable_blocks.php';$blocksMigration($db);
$tables=array_column($db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(),'name');
foreach(['cms_site_settings','cms_design_settings','cms_page_revisions','media_tags','media_asset_tags','cms_reusable_blocks'] as $table)v3_expect(in_array($table,$tables,true),"missing table $table");
$columns=array_column($db->query('PRAGMA table_info(media_assets)')->fetchAll(),'name');foreach(['caption','description','focal_x','focal_y'] as $column)v3_expect(in_array($column,$columns,true),"missing media column $column");

$site=cms_settings_save($db,'site',1,PUBLIC_LOCALE_PT_BR,[
    'wordmark'=>'João Saidler',
    'navigation'=>['items'=>[
        ['type'=>'page','pageId'=>1,'label'=>'Início'],
        ['type'=>'custom','label'=>'Substack','url'=>'https://example.com','newTab'=>true],
    ]],
    'footer'=>['line1'=>'Linha 1','line2'=>'Linha 2','links'=>[['label'=>'Instagram','url'=>'https://example.com']]],
]);
v3_expect($site['wordmark']==='João Saidler','site wordmark save failed');v3_expect(count($site['footer']['links'])===1&&$site['footer']['links'][0]['label']==='Instagram','footer link list was lost');v3_expect(count($site['navigation']['items'])===2,'navigation builder items were lost');v3_expect($site['navigation']['items'][1]['newTab']===true,'navigation new-tab option was lost');

$fonts=cms_design_font_defaults();$defaults=cms_design_defaults();v3_expect($defaults['type']['bodyFont']===$fonts['body'],'default body font should expose the approved portable stack');v3_expect($defaults['type']['displayFont']===$fonts['display'],'default display font should expose the approved portable stack');
$design=cms_settings_save($db,'design',1,PUBLIC_LOCALE_PT_BR,['type'=>['displayLineHeight'=>1.05,'bodyFont'=>'var(--sans)'],'layout'=>['maxWidth'=>1600],'advanced'=>['customCss'=>'.custom-test{display:block}']]);$css=cms_design_css($design);v3_expect($design['type']['bodyFont']===$fonts['body'],'legacy body font variable should normalize when Design is saved');v3_expect(str_contains($css,'--body:'.$fonts['body'].';'),'portable body font token missing');v3_expect(str_contains($css,'--cms-display-lh:1.05'),'display line-height token missing');v3_expect(str_contains($css,'--max:1600px'),'max width token missing');v3_expect(str_contains($css,'prefers-color-scheme:dark'),'auto dark mode missing');v3_expect(str_contains($css,'[data-cms-span="2"]'),'grid span support missing');v3_expect(str_contains($css,'.custom-test{display:block}'),'custom CSS missing');

$pageDoc=json_encode(['version'=>2,'theme'=>'auto','meta'=>[],'html'=>'<section data-cms-section="test"><p>Teste</p></section>']);$db->prepare('INSERT INTO cms_pages(id,page_uuid,activity_id,locale,slug,title,nav_title,status,is_home,show_in_nav,sort_order,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,created_at,updated_at) VALUES(1,?,?,?,?,?,?,?,1,1,0,?,?,2,1,?,?,?,?)')->execute(['uuid',1,PUBLIC_LOCALE_PT_BR,'','Home','Home','active',$pageDoc,$pageDoc,'now','now','now','now']);$page=$db->query('SELECT * FROM cms_pages WHERE id=1')->fetch();cms_revision_store($db,$page,'draft');v3_expect((int)$db->query('SELECT COUNT(*) FROM cms_page_revisions')->fetchColumn()===1,'revision history failed');

$block=cms_block_save($db,1,PUBLIC_LOCALE_PT_BR,'CTA Workshop','<section data-cms-section="cta"><h2>Inscreva-se</h2><script>alert(1)</script></section>','cta');v3_expect((int)$block['id']>0,'reusable block was not created');v3_expect(!str_contains($block['html'],'<script'),'reusable block HTML was not sanitized');v3_expect(count(cms_blocks($db,1,PUBLIC_LOCALE_PT_BR))===1,'reusable block listing failed');$renamed=cms_block_rename($db,1,(int)$block['id'],'CTA principal');v3_expect($renamed['name']==='CTA principal','reusable block rename failed');cms_block_delete($db,1,(int)$block['id']);v3_expect(cms_blocks($db,1,PUBLIC_LOCALE_PT_BR)===[],'reusable block delete failed');

echo "Professional CMS foundation tests passed\n";
