<?php
declare(strict_types=1);

function fail_integrity(string $message): never {fwrite(STDERR,"test-pinhole-page-integrity-repair: $message\n");exit(1);}
function expect_integrity(bool $value,string $message): void {if(!$value)fail_integrity($message);}

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

// Reproduce the broken production symptom: hero/project/offer survived, the
// hybrid paragraph is duplicated, and about/FAQ/interest disappeared.
$brokenHtml=<<<'HTML'
<section class="hero" data-cms-section="hero"><p class="label">Oficina on-line · construção em vídeo + encontro ao vivo</p><dl class="hero-facts"><div><dt>Formato</dt><dd>Vídeos + encontro ao vivo</dd></div></dl><div class="cms-media-placeholder"><span>Imagem ou vídeo da Pinhole Lambe-Lambe</span></div></section>
<section class="cms-support" data-cms-section="project"><h2>Uma câmera que também abriga o laboratório.</h2><div class="cms-media-placeholder"><span>Detalhe da Pinhole Lambe-Lambe</span></div></section>
<section class="format" data-cms-section="offer"><div class="format-inner"><div class="format-heading"><h2>O projeto completo. A construção em vídeo. E um encontro ao vivo comigo.</h2><p>Você recebe o projeto em PDF e a demonstração completa da construção em vídeo para montar a câmera no seu ritmo.</p></div><p>Você recebe o projeto em PDF e acompanha a construção completa em vídeo, no seu ritmo. Depois, participa de um encontro ao vivo de 60 a 90 minutos para discutir a câmera, a operação e as dúvidas que surgirem na montagem.</p><div class="cms-project-preview"><div class="cms-media-placeholder"><span>Prévia do projeto em PDF</span></div></div><div class="format-grid cols-3"><article><h3>Projeto em PDF</h3></article><article><h3>Construção em vídeo</h3></article><article><h3>Encontro ao vivo</h3></article></div></div></section>
HTML;
$doc=json_encode(['version'=>2,'theme'=>'auto','meta'=>['title'=>'Pinhole','description'=>'broken'],'html'=>$brokenHtml],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$q=$db->prepare('INSERT INTO cms_pages(locale,slug,status,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,1,1,?,?,?)');
$q->execute(['pt-BR','pinhole-lambe-lambe','active',$doc,$doc,'2026-09-21T00:00:00Z','2026-09-21T00:00:00Z','2026-09-21T00:00:00Z']);
$pageId=(int)$db->lastInsertId();
$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,social_image,canonical_url,robots,updated_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$pageId,'Pinhole','broken','Pinhole','broken','','','index,follow','2026-09-21T00:00:00Z']);

$migration=require dirname(__DIR__).'/migrations/044_repair_pinhole_page_integrity.php';
expect_integrity(is_callable($migration),'migration not callable');
$migration($db);

$page=$db->query("SELECT * FROM cms_pages WHERE id=$pageId")->fetch();
$published=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$out=(string)($published['html']??'');

$sections=['hero','project','offer','about','faq','interest'];
$last=-1;
foreach($sections as $section){
    $needle='data-cms-section="'.$section.'"';
    $pos=strpos($out,$needle);
    expect_integrity($pos!==false,'missing section: '.$section);
    expect_integrity($pos>$last,'section order broken: '.$section);
    $last=$pos;
}
expect_integrity(substr_count($out,'data-cms-section=')===6,'unexpected section count');
expect_integrity(substr_count($out,'Você recebe o projeto em PDF e a demonstração completa da construção em vídeo para montar a câmera no seu ritmo.')===1,'offer paragraph should appear once');
expect_integrity(!str_contains($out,'acompanha a construção completa em vídeo, no seu ritmo. Depois, participa'),'legacy duplicate paragraph remained');
expect_integrity(str_contains($out,'Fotógrafo, pesquisador e construtor de câmeras desde 2018.'),'authority section not restored');
expect_integrity(str_contains($out,"STRKNG Editors' Selection #88 e #89"),'STRKNG #88/#89 proof missing');
expect_integrity(str_contains($out,'fabrico para venda a NINA'),'NINA proof missing');
expect_integrity(str_contains($out,'A câmera é funcional?'),'functional-camera FAQ missing');
expect_integrity(str_contains($out,'O que fica gravado?'),'hybrid recording FAQ missing');
expect_integrity(str_contains($out,'data-cms-form-key="pinhole-interest"'),'interest form missing');
expect_integrity(substr_count($out,'data-cms-image-placeholder')===3,'media placeholders not fully restored');
expect_integrity(str_contains($out,'/assets/media/joao-portrait.webp'),'authority portrait missing');

$seo=$db->query("SELECT * FROM cms_page_seo WHERE page_id=$pageId")->fetch();
expect_integrity(str_contains((string)$seo['description'],'construção em vídeo no seu ritmo'),'SEO description not repaired');
expect_integrity(str_contains((string)$seo['social_description'],'encontro ao vivo de 60 a 90 minutos'),'social SEO description not repaired');

echo "Pinhole page integrity repair OK\n";
