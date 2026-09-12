<?php
declare(strict_types=1);

const PUBLIC_LOCALE_PT_BR='pt-BR';
const PUBLIC_LOCALE_EN='en';
function cms_pages_seed(PDO $db,int $activityId): void {}
function cms_page_seo(PDO $db,int $pageId): array {return ['canonicalUrl'=>'','robots'=>$pageId===3?'noindex,follow':'index,follow'];}
function cms_page_url(array $activity,array $page,string $locale): string {$lang=$locale===PUBLIC_LOCALE_PT_BR?'pt-br':'en';if((int)$page['is_home']===1)return '/?lang='.$lang;return '/?page='.rawurlencode((string)$page['slug']).'&lang='.$lang;}
function cms_absolute_url(string $url): string {return str_starts_with($url,'http')?$url:'https://example.test'.$url;}
function cms_page_home(PDO $db,int $activityId,string $locale): ?array {$q=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND locale=? AND is_home=1 AND status!='archived' LIMIT 1");$q->execute([$activityId,$locale]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function cms_page_by_slug(PDO $db,int $activityId,string $locale,string $slug): ?array {$q=$db->prepare("SELECT * FROM cms_pages WHERE activity_id=? AND locale=? AND slug=? AND status!='archived' LIMIT 1");$q->execute([$activityId,$locale,$slug]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function fail_discovery(string $message): never {fwrite(STDERR,"test-cms-discovery: $message\n");exit(1);}
function expect_discovery(bool $ok,string $message): void {if(!$ok)fail_discovery($message);}
require __DIR__.'/../app/cms_discovery.php';

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE activities(id INTEGER PRIMARY KEY,status TEXT,is_root INTEGER,slug TEXT);CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER,locale TEXT,slug TEXT,status TEXT,published_document_json TEXT,is_home INTEGER,sort_order INTEGER,published_at TEXT,updated_at TEXT);");
$db->exec("INSERT INTO activities(id,status,is_root,slug) VALUES(1,'active',1,'workshop');");
$insert=$db->prepare('INSERT INTO cms_pages(id,activity_id,locale,slug,status,published_document_json,is_home,sort_order,published_at,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?)');
$insert->execute([1,1,PUBLIC_LOCALE_PT_BR,'','active','{}',1,1,'2026-09-12T12:00:00Z','2026-09-12T12:00:00Z']);
$insert->execute([2,1,PUBLIC_LOCALE_EN,'','active','{}',1,1,'2026-09-12T13:00:00Z','2026-09-12T13:00:00Z']);
$insert->execute([3,1,PUBLIC_LOCALE_PT_BR,'privada','active','{}',0,2,'2026-09-12T14:00:00Z','2026-09-12T14:00:00Z']);
$insert->execute([4,1,PUBLIC_LOCALE_PT_BR,'inscricao','active','{}',0,3,'2026-09-12T15:00:00Z','2026-09-12T15:00:00Z']);

$xml=cms_sitemap_xml($db);
expect_discovery(str_contains($xml,'https://example.test/?lang=pt-br'),'PT home missing from sitemap');
expect_discovery(str_contains($xml,'https://example.test/?lang=en'),'EN home missing from sitemap');
expect_discovery(!str_contains($xml,'privada'),'noindex page leaked into sitemap');
expect_discovery(str_contains($xml,'page=inscricao&amp;lang=pt-br'),'query parameters were not XML escaped');
expect_discovery(str_contains($xml,'hreflang="pt-BR"')&&str_contains($xml,'hreflang="en"'),'language alternates missing');
$robots=cms_robots_text();expect_discovery(str_contains($robots,'Disallow: /admin/')&&str_contains($robots,'Sitemap: https://example.test/sitemap.xml'),'robots policy incomplete');
echo "CMS discovery tests passed\n";
