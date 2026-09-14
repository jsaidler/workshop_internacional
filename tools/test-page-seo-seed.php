<?php
declare(strict_types=1);

function seo_seed_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"test-page-seo-seed: $message\n");exit(1);}}

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE activities(id INTEGER PRIMARY KEY,is_root INTEGER NOT NULL,status TEXT NOT NULL);CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,slug TEXT NOT NULL,title TEXT NOT NULL,status TEXT NOT NULL,is_home INTEGER NOT NULL,sort_order INTEGER NOT NULL,draft_document_json TEXT NOT NULL,published_document_json TEXT NULL);CREATE TABLE cms_page_seo(page_id INTEGER PRIMARY KEY,title TEXT NOT NULL DEFAULT '',description TEXT NOT NULL DEFAULT '',social_title TEXT NOT NULL DEFAULT '',social_description TEXT NOT NULL DEFAULT '',social_image TEXT NOT NULL DEFAULT '',canonical_url TEXT NOT NULL DEFAULT '',robots TEXT NOT NULL DEFAULT 'index,follow',updated_at TEXT NOT NULL);");
$db->exec("INSERT INTO activities VALUES(1,1,'active'),(2,0,'active')");
$insert=$db->prepare('INSERT INTO cms_pages(id,activity_id,locale,slug,title,status,is_home,sort_order,draft_document_json,published_document_json) VALUES(?,?,?,?,?,?,?,?,?,?)');
$doc=function(string $title,string $description): string {return json_encode(['version'=>2,'theme'=>'auto','meta'=>['title'=>$title,'description'=>$description],'html'=>'<section><h1>'.$title.'</h1></section>'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);};
$insert->execute([1,1,'pt-BR','','Home PT','active',1,0,$doc('Título antigo PT','Descrição antiga PT'),$doc('Título antigo PT','Descrição antiga PT')]);
$insert->execute([2,1,'en','','Home EN','active',1,0,$doc('Old EN title','Old EN description'),$doc('Old EN title','Old EN description')]);
$insert->execute([3,1,'pt-BR','inscricao','Inscrição','active',0,1,$doc('Inscrição antiga','Descrição antiga'),$doc('Inscrição antiga','Descrição antiga')]);
$insert->execute([4,1,'pt-BR','sobre','Sobre o processo','active',0,2,$doc('Sobre o processo','Descrição editorial específica da página.'),$doc('Sobre o processo','Descrição editorial específica da página.')]);
$insert->execute([5,2,'pt-BR','','Outra atividade','active',1,0,$doc('Outra atividade','Não pertence ao site raiz.'),$doc('Outra atividade','Não pertence ao site raiz.')]);
$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,social_image,canonical_url,robots,updated_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([1,'SEO antigo','SEO antigo','Social antigo','Social antigo','/old.webp','https://example.com/old','noindex,nofollow','old']);
$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,social_image,canonical_url,robots,updated_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([4,'Título SEO preservado','','','','','https://example.com/sobre','noindex,follow','old']);

$migration=require dirname(__DIR__).'/migrations/031_fill_page_seo.php';$migration($db);
$rows=[];foreach($db->query('SELECT * FROM cms_page_seo ORDER BY page_id')->fetchAll() as $row)$rows[(int)$row['page_id']]=$row;

seo_seed_expect(($rows[1]['title']??'')==='Workshop: Positivo Direto em Filme de Raio X | João Saidler','PT home search title must use the curated workshop intent');
seo_seed_expect(($rows[1]['description']??'')==='Workshop online e ao vivo de João Saidler sobre positivo direto em filme de raio X: exposição, revelação por reversão, química e análise de resultados.','PT home description must explain the process without keyword stuffing');
seo_seed_expect(($rows[1]['social_image']??'')==='/assets/media/hero-medusa.webp','PT home must use the strongest existing process image for sharing');
seo_seed_expect(($rows[1]['canonical_url']??'')==='','curated canonical field must stay empty so the renderer uses the actual public host and locale URL');
seo_seed_expect(($rows[1]['robots']??'')==='index,follow','PT home must be indexable');
seo_seed_expect(($rows[2]['title']??'')==='Direct Positive X-Ray Film Workshop | João Saidler','EN home must target the direct-positive X-ray film workshop intent');
seo_seed_expect(str_contains((string)($rows[2]['description']??''),'reversal processing'),'EN home description must cover the central process');
seo_seed_expect(str_contains((string)($rows[2]['social_description']??''),'interest list'),'EN social copy must accurately reflect that the international page is still an interest list');
seo_seed_expect(($rows[3]['title']??'')==='Inscrição — Workshop de Positivo Direto em Filme de Raio X','registration page must target transactional registration intent');
seo_seed_expect(str_contains((string)($rows[3]['description']??''),'próxima turma'),'registration description must state the current enrollment purpose');
seo_seed_expect(($rows[4]['title']??'')==='Título SEO preservado','unknown/editor-created pages must keep an existing deliberate SEO title');
seo_seed_expect(($rows[4]['description']??'')==='Descrição editorial específica da página.','unknown/editor-created pages must fill missing description from page metadata');
seo_seed_expect(($rows[4]['canonical_url']??'')==='https://example.com/sobre','unknown/editor-created pages must keep an existing deliberate canonical');
seo_seed_expect(($rows[4]['robots']??'')==='noindex,follow','unknown/editor-created pages must keep an existing deliberate robots choice');
seo_seed_expect(!isset($rows[5]),'SEO migration must not alter secondary activities when optimizing the public root site');

echo "Workshop page SEO seed tests passed\n";
