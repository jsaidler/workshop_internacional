<?php
declare(strict_types=1);

function fail_coherence(string $message): never {fwrite(STDERR,"test-pinhole-hybrid-coherence: $message\n");exit(1);}
function expect_coherence(bool $value,string $message): void {if(!$value)fail_coherence($message);}

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
<section class="hero" data-cms-section="hero" data-cms-section-name="Início">
  <div class="hero-copy"><div class="hero-top"><p class="label" data-cms-editable>Oficina on-line · conteúdo em vídeo + encontro ao vivo</p><h1><span>Pinhole</span><span>Lambe-Lambe</span></h1></div>
  <div class="hero-bottom"><dl class="hero-facts"><div><dt>Formato</dt><dd data-cms-editable>1 encontro ao vivo</dd></div><div><dt>Duração</dt><dd data-cms-editable>2 a 3 horas</dd></div><div><dt>Material</dt><dd data-cms-editable>Projeto completo em PDF</dd></div><div><dt>Construção</dt><dd data-cms-editable>Materiais simples</dd></div></dl></div></div>
  <figure class="hero-image" data-cms-image-block><div class="cms-media-placeholder" data-cms-image-placeholder><span>Imagem ou vídeo da Pinhole Lambe-Lambe</span></div></figure>
</section>
<section class="format" data-cms-section="offer" data-cms-section-name="A oficina">
  <div class="format-inner section-compact"><div class="format-heading"><h2 data-cms-editable>O projeto completo da câmera. A construção demonstrada do começo ao fim.</h2><p data-cms-editable>Você recebe o projeto em PDF e acompanha a construção completa em vídeo, no seu ritmo. Depois, participa de um encontro ao vivo de 60 a 90 minutos para discutir a câmera.</p></div>
  <div class="cms-project-preview"><div class="cms-media-placeholder" data-cms-image-placeholder><span>Prévia do projeto em PDF</span></div></div>
  <div class="format-grid cols-3"><article class="format-card"><span class="number">01</span><h3 data-cms-editable>Construção</h3><p data-cms-editable>Cada parte da câmera é mostrada.</p></article><article class="format-card"><span class="number">02</span><h3 data-cms-editable>Imagem e exposição</h3><p data-cms-editable>Princípio da pinhole.</p></article><article class="format-card"><span class="number">03</span><h3 data-cms-editable>Operação</h3><p data-cms-editable>Como operar a câmera.</p></article></div></div>
</section>
<section class="section about section-compact" data-cms-section="about" data-cms-section-name="João Saidler"><div class="about-copy"><p>Além de projetar e construir as câmeras que uso no meu próprio trabalho, fabrico para venda a NINA, uma câmera autoral de grande formato.</p><dl class="recognition"><div><dt>Publicações</dt><dd>STRKNG Editors' Selection #88 e #89</dd></div></dl></div><figure><img src="/assets/media/joao-portrait.webp"></figure></section>
<section class="format" data-cms-section="faq" data-cms-section-name="Perguntas frequentes"><div class="format-inner section-compact"><div class="format-grid cols-3">
  <article class="format-card"><h3 data-cms-editable>Preciso já ter uma câmera?</h3><p>Não.</p></article>
  <article class="format-card"><h3 data-cms-editable>Preciso entender fotografia?</h3><p data-cms-editable>Não. Os princípios necessários para compreender a câmera são apresentados durante o encontro.</p></article>
  <article class="format-card"><h3 data-cms-editable>Que materiais vou usar?</h3><p>Papel paraná.</p></article>
  <article class="format-card"><h3 data-cms-editable>A câmera é funcional?</h3><p>Sim.</p></article>
  <article class="format-card"><h3 data-cms-editable>O encontro fica gravado?</h3><p data-cms-editable>Não. O encontro acontece ao vivo e o projeto em PDF fica com você.</p></article>
  <article class="format-card"><h3 data-cms-editable>A oficina inclui fotografia e revelação?</h3><p>Não.</p></article>
</div></div></section>
HTML;

$doc=json_encode(['version'=>2,'meta'=>['description'=>'descrição antiga'],'html'=>$html],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$q=$db->prepare('INSERT INTO cms_pages(locale,slug,status,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES(?,?,?,?,?,1,1,?,?,?)');
$q->execute(['pt-BR','pinhole-lambe-lambe','active',$doc,$doc,'2026-09-21T00:00:00Z','2026-09-21T00:00:00Z','2026-09-21T00:00:00Z']);
$pageId=(int)$db->lastInsertId();
$db->prepare('INSERT INTO cms_page_seo(page_id,title,description,social_title,social_description,social_image,canonical_url,robots,updated_at) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$pageId,'Pinhole','old','Pinhole','old','','','index,follow','2026-09-21T00:00:00Z']);

$migration=require dirname(__DIR__).'/migrations/043_pinhole_hybrid_coherence.php';
expect_coherence(is_callable($migration),'migration not callable');
$migration($db);

$page=$db->query("SELECT * FROM cms_pages WHERE id=$pageId")->fetch();
$published=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$out=(string)($published['html']??'');
foreach([
    'Oficina on-line · construção em vídeo + encontro ao vivo',
    'Vídeos + encontro ao vivo',
    '60 a 90 minutos',
    'PDF completo da câmera',
    'No seu ritmo',
    'O projeto completo. A construção em vídeo. E um encontro ao vivo comigo.',
    'Projeto em PDF',
    'Construção em vídeo',
    'Encontro ao vivo',
    'A câmera é funcional?',
    'O que fica gravado?',
    'O encontro com João acontece ao vivo e não é gravado.',
] as $needle)expect_coherence(str_contains($out,$needle),'missing canonical hybrid copy: '.$needle);

foreach([
    '1 encontro ao vivo',
    '2 a 3 horas',
    'A construção demonstrada do começo ao fim.',
    '<h3 data-cms-editable>Construção</h3>',
    '<h3 data-cms-editable>Imagem e exposição</h3>',
    '<h3 data-cms-editable>Operação</h3>',
    'O encontro acontece ao vivo e o projeto em PDF fica com você.',
] as $needle)expect_coherence(!str_contains($out,$needle),'legacy or contradictory copy remained: '.$needle);

expect_coherence(str_contains($out,'Imagem ou vídeo da Pinhole Lambe-Lambe'),'hero media slot was lost');
expect_coherence(str_contains($out,'Prévia do projeto em PDF'),'project media slot was lost');
expect_coherence(str_contains($out,'fabrico para venda a NINA'),'authority section was altered');
expect_coherence(str_contains($out,"STRKNG Editors' Selection #88 e #89"),'recognition section was altered');
expect_coherence(str_contains((string)($published['meta']['description']??''),'construção em vídeo no seu ritmo'),'document description not normalized');

$seo=$db->query("SELECT * FROM cms_page_seo WHERE page_id=$pageId")->fetch();
expect_coherence(str_contains((string)$seo['description'],'encontro ao vivo de 60 a 90 minutos'),'SEO description not normalized');
expect_coherence(str_contains((string)$seo['social_description'],'construção em vídeo'),'social description not normalized');

echo "Pinhole hybrid coherence OK\n";
