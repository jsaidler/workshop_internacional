<?php
declare(strict_types=1);

function fail_authority(string $message): never {fwrite(STDERR,"test-pinhole-camera-authority: $message\n");exit(1);}function expect_authority(bool $value,string $message): void {if(!$value)fail_authority($message);}

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
SQL);

$html='<section class="section about section-compact" data-cms-section="about" data-cms-section-name="João Saidler"><div class="about-copy"><p>texto anterior</p></div></section>';
$doc=json_encode(['version'=>2,'html'=>$html],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$q=$db->prepare('INSERT INTO cms_pages(locale,slug,status,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,1,1,?,?,?)');
$q->execute(['pt-BR','pinhole-lambe-lambe','active',$doc,$doc,'2026-09-20T00:00:00Z','2026-09-20T00:00:00Z','2026-09-20T00:00:00Z']);

$migration=require dirname(__DIR__).'/migrations/039_pinhole_camera_authority.php';
expect_authority(is_callable($migration),'migration not callable');
$migration($db);

$page=$db->query("SELECT * FROM cms_pages WHERE slug='pinhole-lambe-lambe'")->fetch();
$published=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$out=(string)($published['html']??'');
foreach([
    'Fotógrafo, pesquisador e construtor de câmeras desde 2018.',
    'câmeras que projetei e construí em casa',
    'Siena Creative Photo Awards — Commended',
    'ArtLimited Awards — Runner-up, Portraiture',
    'Siena Creative Photo Awards — Highly Commended, People',
    'The Black &amp; White Book — GOD Publishing',
    "STRKNG Editors' Selection #88 e #89",
    'fabrico para venda a NINA',
    'câmera autoral de grande formato',
    '/assets/media/joao-portrait.webp',
] as $needle)expect_authority(str_contains($out,$needle),'missing authority proof: '.$needle);
expect_authority(substr_count($out,'data-cms-section="about"')===1,'about section duplicated');

$legacyHtml=str_replace("STRKNG Editors' Selection #88 e #89","STRKNG Editors' Selection #88",$out);
$legacyDoc=json_encode(['version'=>2,'html'=>$legacyHtml],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$db->prepare('UPDATE cms_pages SET draft_document_json=?,published_document_json=? WHERE id=?')->execute([$legacyDoc,$legacyDoc,(int)$page['id']]);
$repair=require dirname(__DIR__).'/migrations/040_pinhole_strkng_89.php';
expect_authority(is_callable($repair),'repair migration not callable');
$repair($db);
$page=$db->query("SELECT * FROM cms_pages WHERE slug='pinhole-lambe-lambe'")->fetch();
$published=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$out=(string)($published['html']??'');
expect_authority(str_contains($out,"STRKNG Editors' Selection #88 e #89"),'repair migration did not add #89');

echo "Pinhole camera authority section OK\n";
