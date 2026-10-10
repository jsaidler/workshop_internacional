<?php
declare(strict_types=1);

$db=new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec(<<<'SQL'
CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,locale TEXT NOT NULL,slug TEXT NOT NULL,status TEXT NOT NULL,draft_document_json TEXT,published_document_json TEXT,draft_revision INTEGER NOT NULL DEFAULT 0,published_revision INTEGER NOT NULL DEFAULT 0,draft_updated_at TEXT,published_at TEXT,updated_at TEXT);
CREATE TABLE course_page_sections(page_id INTEGER NOT NULL,section_key TEXT NOT NULL,lesson_id INTEGER NOT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL,PRIMARY KEY(page_id,section_key));
CREATE TABLE course_material_sections(course_id INTEGER NOT NULL,page_id INTEGER NOT NULL,section_key TEXT NOT NULL,lesson_id INTEGER NOT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL,PRIMARY KEY(course_id,page_id,section_key));
CREATE TABLE course_page_media_slots(page_id INTEGER NOT NULL,slot_key TEXT NOT NULL,media_asset_id INTEGER NOT NULL,created_at TEXT NOT NULL,updated_at TEXT NOT NULL,PRIMARY KEY(page_id,slot_key));
SQL);
$oldHtml=<<<'HTML'
<section id="caderno-aula-2" class="format study-chapter" data-cms-section="caderno-aula-2"><div class="format-inner"><div class="format-heading"><div><p class="section-label">Aula 02</p><h2>Processos químicos para positivos</h2></div></div></div></section>
<section class="study-unit" data-cms-section="caderno-20-materiais"><p class="section-label">Fim da Aula 2</p><div class="statement-grid"><div><h2>Materiais para o laboratório</h2></div><div class="statement-copy"><p data-audit-synthetic-transition>Trecho sintético de teste: o fim completo da Aula 2 não está representado nesta fixture. O documento publicado no CMS precisa de auditoria separada.</p></div></div></section>
<section id="caderno-aula-3" class="format study-chapter" data-cms-section="caderno-aula-3"><div class="format-inner"><div class="format-heading"><div><p class="section-label">Aula 03</p><h2>Revisão de resultados</h2></div></div></div></section>
<section class="study-unit" data-cms-section="caderno-21-leitura-resultados"><p>Conteúdo antigo 21</p></section>
<section class="study-unit" data-cms-section="caderno-22-registro"><p>Conteúdo antigo 22</p></section>
HTML;
$doc=json_encode(['version'=>2,'theme'=>'auto','meta'=>['audit'=>true],'html'=>$oldHtml],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$stmt=$db->prepare('INSERT INTO cms_pages(id,locale,slug,status,draft_document_json,published_document_json,draft_revision,published_revision,updated_at) VALUES(1,?,?,?,?,?,?,?,?)');
$stmt->execute(['pt-BR','caderno-positivo-direto','active',$doc,$doc,10,10,'old']);
foreach(['caderno-aula-3','caderno-21-leitura-resultados','caderno-22-registro'] as $key){
    $db->prepare("INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(1,?,3,'old','old')")->execute([$key]);
    $db->prepare("INSERT INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(1,1,?,3,'old','old')")->execute([$key]);
}
foreach(['caderno-aula-2','caderno-20-materiais'] as $key){
    $db->prepare("INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(1,?,2,'old','old')")->execute([$key]);
    $db->prepare("INSERT INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(1,1,?,2,'old','old')")->execute([$key]);
}
$db->exec("INSERT INTO course_page_media_slots(page_id,slot_key,media_asset_id,created_at,updated_at) VALUES(1,'aula3-caderno',99,'old','old')");
(require __DIR__.'/../../migrations/085_aula3_student_area_research_guide.php')($db);
(require __DIR__.'/../../migrations/087_aula2_practice_bridge.php')($db);
(require __DIR__.'/../../migrations/089_restore_study_material_pedagogical_pattern.php')($db);
(require __DIR__.'/../../migrations/090_reframe_aula3_as_practice.php')($db);
(require __DIR__.'/../../migrations/091_practice_infographic_placeholders.php')($db);
$page=$db->query('SELECT published_document_json FROM cms_pages WHERE id=1')->fetchColumn();
$published=json_decode((string)$page,true,512,JSON_THROW_ON_ERROR);$html=(string)$published['html'];
?><!doctype html>
<html lang="pt-BR" data-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<style>:root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Georgia,serif;--mono:"Courier New",monospace;--reading-width:840px;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px;--ux-space-8:64px}</style>
<link rel="stylesheet" href="/template/page.css"><link rel="stylesheet" href="/assets/ui-core.css"><link rel="stylesheet" href="/assets/cms-core.css"><link rel="stylesheet" href="/assets/cms-responsive.css"><link rel="stylesheet" href="/assets/cms-header.css"><link rel="stylesheet" href="/assets/cms-editorial.css"><link rel="stylesheet" href="/assets/cms-study.css"><link rel="stylesheet" href="/assets/cms-student-notes.css"><link rel="stylesheet" href="/assets/student-area.css">
<title>Prática após Aula 2 — fixture de geometria e conteúdo sem mídia final</title>
</head>
<body class="cms-public cms-student-material" data-audit-data-source="migration-fixture-not-live-cms">
<header class="topbar cms-topbar cms-topbar-static" data-cms-public-header>
  <a class="brand" href="/aluno/">JSaidler Fotografia</a>
  <nav class="student-desktop-nav" aria-label="Área do aluno"><a href="/aluno/">Início</a><a href="/aluno/cursos.php" aria-current="page">Cursos</a><a href="/aluno/caderno.php">Caderno</a><a href="/aluno/ferramentas.php">Laboratório</a></nav>
  <div class="cms-topbar-actions"><a class="cms-student-access" href="/aluno/perfil.php">Conta</a></div>
</header>
<div class="cms-student-reading" data-student-scroll-root>
 <main id="main" data-cms-page-main>
  <aside class="cms-student-study-context" aria-label="Contexto de estudo" data-testid="study-context">
    <div class="cms-student-study-head"><div><a href="/aluno/cursos.php?cohort=auditoria">← Curso</a><strong>Positivo direto em filme de raio-X</strong><small>Turma de auditoria (dados sintéticos)</small></div><a class="cms-student-study-questions" href="/aluno/duvidas.php?cohort=auditoria">Dúvidas</a></div>
  </aside>
  <article class="study-material" data-testid="aula3-material-audit"><?=$html?></article>
  <details class="student-notes-panel" data-testid="notes-panel"><summary>Anotações <b>0</b></summary>
    <div class="student-notes-sheet"><header class="student-notes-head"><div><span>Material</span><h2>Suas anotações</h2><p>Estado vazio demonstrativo. A criação, edição e remoção são testadas na fixture dedicada de anotações, não nesta amostra de leitura.</p></div></header>
      <div class="student-notes-empty">Nenhuma anotação nesta amostra.</div>
    </div>
  </details>
 </main>
</div>
<nav class="student-mobile-nav" aria-label="Área do aluno"><a href="/aluno/">Início</a><a href="/aluno/cursos.php" aria-current="page">Cursos</a><a href="/aluno/caderno.php">Caderno</a><a href="/aluno/ferramentas.php">Laboratório</a></nav>
</body></html>
