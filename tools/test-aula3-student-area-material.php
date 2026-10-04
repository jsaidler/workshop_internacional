<?php
declare(strict_types=1);

function fail_aula3(string $message): never {fwrite(STDERR,"aula3-student-area-material: $message\n");exit(1);}
function must_aula3(bool $ok,string $message): void {if(!$ok)fail_aula3($message);}

$db=new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec(<<<'SQL'
CREATE TABLE cms_pages(
  id INTEGER PRIMARY KEY,
  locale TEXT NOT NULL,
  slug TEXT NOT NULL,
  status TEXT NOT NULL,
  draft_document_json TEXT NULL,
  published_document_json TEXT NULL,
  draft_revision INTEGER NOT NULL DEFAULT 0,
  published_revision INTEGER NOT NULL DEFAULT 0,
  draft_updated_at TEXT NULL,
  published_at TEXT NULL,
  updated_at TEXT NULL
);
CREATE TABLE course_page_sections(
  page_id INTEGER NOT NULL,
  section_key TEXT NOT NULL,
  lesson_id INTEGER NOT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  PRIMARY KEY(page_id,section_key)
);
CREATE TABLE course_material_sections(
  course_id INTEGER NOT NULL,
  page_id INTEGER NOT NULL,
  section_key TEXT NOT NULL,
  lesson_id INTEGER NOT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  PRIMARY KEY(course_id,page_id,section_key)
);
SQL);

$oldHtml=<<<'HTML'
<section class="format" data-cms-section="caderno-indice"><div class="format-grid">
<a class="format-card" href="#caderno-aula-1"><span class="number">01</span><h3 data-cms-editable>Filme e exposição</h3></a>
<a class="format-card" href="#caderno-aula-2"><span class="number">02</span><h3 data-cms-editable>Processos químicos para positivos</h3></a>
<a class="format-card" href="#caderno-aula-3"><span class="number">03</span><h3 data-cms-editable>Revisão de resultados</h3></a>
</div></section>
<section data-cms-section="caderno-aula-1"><h2>Aula 1 preservada</h2></section>
<section data-cms-section="caderno-aula-2"><h2>Aula 2 preservada</h2></section>
<section id="caderno-aula-3" class="format" data-cms-section="caderno-aula-3" data-cms-section-name="Aula 3 — Revisão de resultados"><h2>Revisão de resultados</h2></section>
<section class="section" data-cms-section="caderno-21-leitura-resultados"><p>Conteúdo antigo 21</p></section>
<section class="section" data-cms-section="caderno-22-registro"><p>Conteúdo antigo 22</p></section>
HTML;
$doc=json_encode(['version'=>2,'theme'=>'auto','meta'=>['test'=>true],'html'=>$oldHtml],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$stmt=$db->prepare('INSERT INTO cms_pages(id,locale,slug,status,draft_document_json,published_document_json,draft_revision,published_revision,updated_at) VALUES(1,?,?,?,?,?,?,?,?)');
$stmt->execute(['pt-BR','caderno-positivo-direto','active',$doc,$doc,10,10,'old']);
foreach(['caderno-aula-3','caderno-21-leitura-resultados','caderno-22-registro'] as $key){
    $db->prepare('INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(1,?,3,\'old\',\'old\')')->execute([$key]);
    $db->prepare('INSERT INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(1,1,?,3,\'old\',\'old\')')->execute([$key]);
}

$migration=require __DIR__.'/../migrations/085_aula3_student_area_research_guide.php';
$migration($db);
$page=$db->query('SELECT * FROM cms_pages WHERE id=1')->fetch();
$published=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$draft=json_decode((string)$page['draft_document_json'],true,512,JSON_THROW_ON_ERROR);
$html=(string)$published['html'];

must_aula3(str_contains($html,'Aula 1 preservada')&&str_contains($html,'Aula 2 preservada'),'migration replaced material outside Aula 3');
must_aula3(str_contains($html,'Área do aluno: revisão, avaliação e continuidade da pesquisa'),'new Aula 3 title missing');
must_aula3(str_contains($html,'Área do aluno e continuidade'),'index was not updated');
must_aula3(!str_contains($html,'Conteúdo antigo 21')&&!str_contains($html,'Conteúdo antigo 22'),'old generic Aula 3 sections remain in document');
must_aula3(str_contains($html,'Roteiro associado e processamento realizado são duas coisas diferentes.'),'association-versus-execution rule missing');
must_aula3(str_contains($html,'ela não escolhe qual delas “causou” o resultado'),'comparison is not explicitly non-causal');
must_aula3(str_contains($html,'Use a bancada para calcular e organizar. Use o Caderno para dizer o que aconteceu.'),'tool-versus-record authority boundary missing');
must_aula3(str_contains($html,'Reciprocidade')&&str_contains($html,'Exposição equivalente')&&str_contains($html,'Processamentos')&&str_contains($html,'Receitas e preparo')&&str_contains($html,'Inventário')&&str_contains($html,'Predefinições e calibração'),'Aula 3 does not teach the available student tools');
must_aula3(str_contains($html,'filme e lote')&&str_contains($html,'EI usado como referência')&&str_contains($html,'movimentação'),'minimum experiment record was not preserved');

$slots=['aula3-caderno','aula3-exposicao','aula3-processamentos','aula3-processamento-realizado','aula3-avaliacao','aula3-comparacao','aula3-continuidade','aula3-ferramentas'];
foreach($slots as $slot)must_aula3(str_contains($html,'data-private-media-slot="'.$slot.'"'),'missing screenshot slot '.$slot);

$keys=['caderno-aula-3','caderno-21-fluxo-pesquisa','caderno-22-exposicao','caderno-23-processamento','caderno-24-resultado-avaliacao','caderno-25-comparacao','caderno-26-continuar','caderno-27-ferramentas','caderno-28-rotina'];
foreach($keys as $key){
    must_aula3(str_contains($html,'data-cms-section="'.$key.'"'),'document missing section '.$key);
    must_aula3(str_contains($html,'data-cms-section="'.$key.'"')&&str_contains($html,'data-cms-lesson-id="3"'),'lesson availability marker missing for '.$key);
    $q=$db->prepare('SELECT lesson_id FROM course_page_sections WHERE page_id=1 AND section_key=?');$q->execute([$key]);must_aula3((int)$q->fetchColumn()===3,'legacy lesson mapping missing for '.$key);
    $q=$db->prepare('SELECT lesson_id FROM course_material_sections WHERE course_id=1 AND page_id=1 AND section_key=?');$q->execute([$key]);must_aula3((int)$q->fetchColumn()===3,'course lesson mapping missing for '.$key);
}
must_aula3((int)$db->query("SELECT COUNT(*) FROM course_page_sections WHERE section_key IN ('caderno-21-leitura-resultados','caderno-22-registro')")->fetchColumn()===0,'old lesson mappings remain');
must_aula3((int)$db->query("SELECT COUNT(*) FROM course_material_sections WHERE section_key IN ('caderno-21-leitura-resultados','caderno-22-registro')")->fetchColumn()===0,'old course material mappings remain');
must_aula3((int)$page['draft_revision']===11&&(int)$page['published_revision']===11,'page revision was not advanced');
must_aula3($draft['meta']['test']===true&&$published['meta']['test']===true,'document metadata was not preserved');

$before=(string)$page['published_document_json'];
$migration($db);
$after=(string)$db->query('SELECT published_document_json FROM cms_pages WHERE id=1')->fetchColumn();
must_aula3($before===$after,'migration is not idempotent after Aula 3 replacement');

echo "aula3-student-area-material: ok\n";
