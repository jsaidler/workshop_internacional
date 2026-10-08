<?php
declare(strict_types=1);

const PUBLIC_LOCALE_PT_BR='pt-BR';
const PUBLIC_LOCALE_EN='en';

function fail_public_nav_hierarchy(string $message): never {fwrite(STDERR,"public-navigation-hierarchy: $message\n");exit(1);}
function must_public_nav_hierarchy(bool $ok,string $message): void {if(!$ok)fail_public_nav_hierarchy($message);}
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function public_locale_query(string $locale): string {return $locale===PUBLIC_LOCALE_PT_BR?'pt-br':'en';}

$GLOBALS['public_nav_pages']=[];
function cms_page_by_id(PDO $db,int $id): ?array {return $GLOBALS['public_nav_pages'][$id]??null;}
function cms_nav_pages(PDO $db,int $activityId,string $locale): array {
    return array_values(array_filter($GLOBALS['public_nav_pages'],static fn(array $page): bool=>(int)$page['activity_id']===$activityId&&(string)$page['locale']===$locale&&(string)$page['status']!=='archived'&&!empty($page['show_in_nav'])&&!empty($page['published_document_json'])));
}

$root=dirname(__DIR__);
require $root.'/app/cms_renderer.php';

$db=new PDO('sqlite::memory:');
$activity=['id'=>1,'is_root'=>1,'slug'=>''];
$page=static fn(int $id,string $title,string $slug,?int $parent,int $sort=1): array=>[
    'id'=>$id,'activity_id'=>1,'locale'=>'pt-BR','status'=>'active','title'=>$title,'nav_title'=>$title,
    'slug'=>$slug,'is_home'=>$slug===''?1:0,'parent_page_id'=>$parent,'sort_order'=>$sort,'show_in_nav'=>1,
    'published_document_json'=>'{}'
];
$GLOBALS['public_nav_pages']=[
    1=>$page(1,'Workshop de positivo','',null,1),
    2=>$page(2,'Inscrição','inscricao',1,2),
    3=>$page(3,'Grande formato experimental','grande-formato',null,3),
    4=>$page(4,'Condições','condicoes',2,4),
];

$site=['navigation'=>['items'=>[
    ['type'=>'page','pageId'=>2,'label'=>'','newTab'=>false],
    ['type'=>'custom','label'=>'Sobre','url'=>'/#sobre','newTab'=>false],
    ['type'=>'page','pageId'=>3,'label'=>'Grande formato','newTab'=>false],
    ['type'=>'page','pageId'=>4,'label'=>'','newTab'=>false],
]]];
$tree=cms_navigation_entries($db,$activity,'pt-BR',$site);
must_public_nav_hierarchy(count($tree)===3,'root navigation should contain the synthesized workshop parent, custom link and large-format page');
must_public_nav_hierarchy((int)($tree[0]['pageId']??0)===1,'configured child did not synthesize its editorial parent');
must_public_nav_hierarchy(count($tree[0]['children']??[])===1,'registration was not nested under the workshop');
must_public_nav_hierarchy((int)$tree[0]['children'][0]['pageId']===2,'wrong first-level child under workshop');
must_public_nav_hierarchy((int)$tree[0]['children'][0]['children'][0]['pageId']===4,'nested editorial hierarchy was not preserved recursively');
must_public_nav_hierarchy(($tree[1]['type']??'')==='custom'&&($tree[1]['label']??'')==='Sobre','custom item order changed while synthesizing ancestors');
must_public_nav_hierarchy((int)($tree[2]['pageId']??0)===3&&($tree[2]['label']??'')==='Grande formato','configured page label/order was not preserved');

$html=cms_render_navigation_tree($tree,2,'pt-BR');
must_public_nav_hierarchy(str_contains($html,'data-cms-nav-parent'),'renderer does not expose submenu parents');
must_public_nav_hierarchy(str_contains($html,'data-cms-submenu-toggle'),'renderer does not expose submenu toggle');
must_public_nav_hierarchy(str_contains($html,'aria-current="page">Inscrição</a>'),'current child page is not marked correctly');
must_public_nav_hierarchy(str_contains($html,'is-current-branch'),'current child does not mark its ancestor branch');
must_public_nav_hierarchy(substr_count($html,'Inscrição')===1,'child page leaked into more than one navigation level');

$fallback=cms_navigation_entries($db,$activity,'pt-BR',['navigation'=>['items'=>[]]]);
must_public_nav_hierarchy((int)($fallback[0]['pageId']??0)===1&&count($fallback[0]['children']??[])===1,'fallback navigation ignores page hierarchy');

$migrationDb=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$migrationDb->exec("CREATE TABLE activities(id INTEGER PRIMARY KEY,status TEXT,is_root INTEGER)");
$migrationDb->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER,locale TEXT,slug TEXT,status TEXT,is_home INTEGER,parent_page_id INTEGER NULL,updated_at TEXT)");
$migrationDb->exec("INSERT INTO activities VALUES(1,'active',1)");
$migrationDb->exec("INSERT INTO cms_pages VALUES
 (10,1,'pt-BR','','active',1,NULL,'x'),
 (11,1,'pt-BR','inscricao','active',0,NULL,'x'),
 (20,1,'en','','active',1,NULL,'x'),
 (21,1,'en','registration','active',0,NULL,'x')");
(require $root.'/migrations/097_public_menu_page_hierarchy.php')($migrationDb);
must_public_nav_hierarchy((int)$migrationDb->query('SELECT parent_page_id FROM cms_pages WHERE id=11')->fetchColumn()===10,'PT registration page was not attached to the workshop home');
must_public_nav_hierarchy((int)$migrationDb->query('SELECT parent_page_id FROM cms_pages WHERE id=21')->fetchColumn()===20,'EN registration page was not attached to its workshop home');
must_public_nav_hierarchy((string)$migrationDb->query('SELECT slug FROM cms_pages WHERE id=11')->fetchColumn()==='inscricao','migration changed the registration URL');

$admin=(string)file_get_contents($root.'/admin/site.php');
$adminJs=(string)file_get_contents($root.'/assets/admin-site.js');
$headerCss=(string)file_get_contents($root.'/assets/cms-header.css');
$publicJs=(string)file_get_contents($root.'/assets/public.js');
must_public_nav_hierarchy(str_contains($admin,'A hierarquia vem de <strong>Páginas</strong>')&&str_contains($admin,'data-nav-parent-hint'),'menu admin does not explain/reflect the canonical page hierarchy');
must_public_nav_hierarchy(str_contains($adminJs,'Dentro de:')&&str_contains($adminJs,'parentTitle'),'dynamic menu rows lose parent context');
must_public_nav_hierarchy(str_contains($headerCss,'.cms-nav-submenu-wrap')&&str_contains($headerCss,'@media(max-width:980px)'),'submenu lacks responsive system styles');
must_public_nav_hierarchy(str_contains($publicJs,'data-cms-submenu-toggle')&&str_contains($publicJs,'setSubmenu'),'public runtime lacks submenu interaction');

echo "public-navigation-hierarchy: ok\n";
