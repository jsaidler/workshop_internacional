<?php
declare(strict_types=1);

function fail_hybrid(string $message): never {fwrite(STDERR,"test-pinhole-hybrid-format: $message\n");exit(1);}
function expect_hybrid(bool $value,string $message): void {if(!$value)fail_hybrid($message);}

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

$html=<<<'HTML'
<section class="hero"><p class="label">Oficina on-line e ao vivo</p><dl><div><dt>Formato</dt><dd data-cms-editable>1 encontro ao vivo</dd></div><div><dt>Duração</dt><dd data-cms-editable>2 a 3 horas</dd></div></dl><div class="cms-media-placeholder" data-cms-image-placeholder><span>Imagem ou vídeo da Pinhole Lambe-Lambe</span></div></section>
<section data-cms-section="offer"><h2>O projeto completo da câmera — e a construção demonstrada do começo ao fim.</h2><p>Antes do encontro, você recebe um PDF com a lista de materiais e as peças para imprimir, colar no papel paraná e recortar.</p><article><h3 data-cms-editable>Construção</h3><p data-cms-editable>Cada parte da câmera é mostrada e explicada até o equipamento completo.</p></article><article><h3 data-cms-editable>Operação</h3><p data-cms-editable>Como preparar e usar a câmera e o pequeno laboratório integrado.</p></article></section>
<section data-cms-section="faq"><p>Os princípios necessários para compreender a câmera são apresentados durante o encontro.</p><article class="format-card"><h3 data-cms-editable>A câmera é funcional?</h3><p data-cms-editable>Sim. O projeto foi concebido para produzir uma câmera pinhole com um pequeno laboratório integrado.</p></article><article class="format-card"><h3 data-cms-editable>O encontro fica gravado?</h3><p data-cms-editable>Não. O encontro acontece ao vivo e o projeto em PDF fica com você.</p></article><p>O encontro é dedicado à construção, à formação da imagem e à operação do equipamento.</p></section>
<section data-cms-section="about"><p>Além de projetar e construir as câmeras que uso no meu próprio trabalho, fabrico para venda a NINA.</p></section>
HTML;
$doc=json_encode(['version'=>2,'meta'=>['description'=>'Oficina on-line e ao vivo sobre a construção e a operação de uma Pinhole Lambe-Lambe autoral, com projeto completo em PDF.'],'html'=>$html],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$q=$db->prepare('INSERT INTO cms_pages(locale,slug,status,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,1,1,?,?,?)');
$q->execute(['pt-BR','pinhole-lambe-lambe','active',$doc,$doc,'2026-09-21T00:00:00Z','2026-09-21T00:00:00Z','2026-09-21T00:00:00Z']);
$pageId=(int)$db->lastInsertId();
$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,social_image,canonical_url,robots,updated_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$pageId,'Pinhole','old description','Pinhole','old social','','','index,follow','2026-09-21T00:00:00Z']);

$migration=require dirname(__DIR__).'/migrations/042_pinhole_hybrid_format.php';
expect_hybrid(is_callable($migration),'migration not callable');
$migration($db);

$page=$db->query("SELECT * FROM cms_pages WHERE id=$pageId")->fetch();
$published=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$out=(string)($published['html']??'');
foreach([
    'Oficina on-line · conteúdo em vídeo + encontro ao vivo',
    'Vídeo + encontro ao vivo',
    '60 a 90 minutos',
    'O projeto completo. A construção em vídeo. E um encontro ao vivo comigo.',
    'acompanha a construção completa em vídeo, no seu ritmo',
    'Construção em vídeo',
    'Encontro ao vivo',
    'Como funciona o conteúdo em vídeo?',
    'O encontro ao vivo fica gravado?',
] as $needle)expect_hybrid(str_contains($out,$needle),'missing hybrid copy: '.$needle);

foreach(['2 a 3 horas','1 encontro ao vivo','O encontro acontece ao vivo e o projeto em PDF fica com você.'] as $needle){
    expect_hybrid(!str_contains($out,$needle),'legacy live-only copy remained: '.$needle);
}
expect_hybrid(str_contains($out,'Imagem ou vídeo da Pinhole Lambe-Lambe'),'media slot was altered');
expect_hybrid(str_contains($out,'fabrico para venda a NINA'),'authority section was altered');
expect_hybrid(str_contains((string)($published['meta']['description']??''),'construção em vídeo e encontro ao vivo'),'document SEO description not updated');

$seo=$db->query("SELECT * FROM cms_page_seo WHERE page_id=$pageId")->fetch();
expect_hybrid(str_contains((string)$seo['description'],'construção em vídeo e encontro ao vivo'),'SEO description not updated');
expect_hybrid(str_contains((string)$seo['social_description'],'Projeto completo em PDF'),'social SEO description not updated');

echo "Pinhole hybrid format OK\n";
