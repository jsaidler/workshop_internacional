<?php
declare(strict_types=1);

function fail_aula3(string $message): never {fwrite(STDERR,"aula3-student-area-material: $message\n");exit(1);}
function must_aula3(bool $ok,string $message): void {if(!$ok)fail_aula3($message);}
function section_tag_aula3(string $html,string $key): string {
    if(!preg_match("~<section\\b[^>]*data-cms-section=[\"']".preg_quote($key,'~')."[\"'][^>]*>~i",$html,$m))return '';
    return (string)$m[0];
}

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
CREATE TABLE course_page_media_slots(
  page_id INTEGER NOT NULL,
  slot_key TEXT NOT NULL,
  media_asset_id INTEGER NOT NULL,
  created_at TEXT NOT NULL,
  updated_at TEXT NOT NULL,
  PRIMARY KEY(page_id,slot_key)
);
SQL);

$oldHtml=<<<'HTML'
<section class="format study-index" data-cms-section="caderno-indice"><div class="format-grid">
<a class="format-card" href="#caderno-aula-1"><span class="number">01</span><h3 data-cms-editable>Filme e exposição</h3></a>
<a class="format-card" href="#caderno-aula-2"><span class="number">02</span><h3 data-cms-editable>Processos químicos para positivos</h3></a>
<a class="format-card" href="#caderno-aula-3"><span class="number">03</span><h3 data-cms-editable>Revisão de resultados</h3></a>
</div></section>
<section class="study-unit" data-cms-section="caderno-aula-1"><h2>Aula 1 preservada</h2></section>
<section id="caderno-aula-2" class="format study-chapter" data-cms-section="caderno-aula-2"><h2>Aula 2 preservada</h2></section>
<section class="study-unit" data-cms-section="caderno-20-materiais"><h2>Conteúdo final existente da Aula 2 preservado</h2></section>
<section id="caderno-aula-3" class="format study-chapter" data-cms-section="caderno-aula-3" data-cms-section-name="Aula 3 — Revisão de resultados"><h2>Revisão de resultados</h2></section>
<section class="study-unit" data-cms-section="caderno-21-leitura-resultados"><p>Conteúdo antigo 21</p></section>
<section class="study-unit" data-cms-section="caderno-22-registro"><p>Conteúdo antigo 22</p></section>
HTML;
$doc=json_encode(['version'=>2,'theme'=>'auto','meta'=>['test'=>true],'html'=>$oldHtml],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$stmt=$db->prepare('INSERT INTO cms_pages(id,locale,slug,status,draft_document_json,published_document_json,draft_revision,published_revision,updated_at) VALUES(1,?,?,?,?,?,?,?,?)');
$stmt->execute(['pt-BR','caderno-positivo-direto','active',$doc,$doc,10,10,'old']);
foreach(['caderno-aula-3','caderno-21-leitura-resultados','caderno-22-registro'] as $key){
    $db->prepare('INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(1,?,3,\'old\',\'old\')')->execute([$key]);
    $db->prepare('INSERT INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(1,1,?,3,\'old\',\'old\')')->execute([$key]);
}
foreach(['caderno-aula-2','caderno-20-materiais'] as $key){
    $db->prepare('INSERT INTO course_page_sections(page_id,section_key,lesson_id,created_at,updated_at) VALUES(1,?,2,\'old\',\'old\')')->execute([$key]);
    $db->prepare('INSERT INTO course_material_sections(course_id,page_id,section_key,lesson_id,created_at,updated_at) VALUES(1,1,?,2,\'old\',\'old\')')->execute([$key]);
}
$db->exec("INSERT INTO course_page_media_slots(page_id,slot_key,media_asset_id,created_at,updated_at) VALUES(1,'aula3-caderno',99,'old','old')");

$aula3Migration=require __DIR__.'/../migrations/085_aula3_student_area_research_guide.php';
$aula2Migration=require __DIR__.'/../migrations/087_aula2_practice_bridge.php';
$studyPatternMigration=require __DIR__.'/../migrations/089_restore_study_material_pedagogical_pattern.php';
$aula3Migration($db);
$aula2Migration($db);
$studyPatternMigration($db);
$page=$db->query('SELECT * FROM cms_pages WHERE id=1')->fetch();
$published=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
$draft=json_decode((string)$page['draft_document_json'],true,512,JSON_THROW_ON_ERROR);
$html=(string)$published['html'];

must_aula3(str_contains($html,'Aula 1 preservada')&&str_contains($html,'Aula 2 preservada')&&str_contains($html,'Conteúdo final existente da Aula 2 preservado'),'migration replaced existing material outside the intended additions');
must_aula3(strpos($html,'Conteúdo final existente da Aula 2 preservado')<strpos($html,'Agora é a vez de vocês'),'Aula 2 bridge does not follow the existing Aula 2 ending');
must_aula3(strpos($html,'Antes do terceiro encontro')<strpos($html,'id="caderno-aula-3"'),'Aula 2 bridge was not inserted before Aula 3');
must_aula3(str_contains($html,'Registre o que aconteceu, não o que deveria ter acontecido.'),'Aula 2 practice principle missing');
must_aula3(str_contains($html,'data-private-media-slot="aula2-caderno"'),'Aula 2 screenshot slot missing');
foreach(['caderno-20a-agora-e-a-vez','caderno-20b-antes-terceiro-encontro'] as $key){
    must_aula3(str_contains($html,'data-cms-section="'.$key.'"'),'document missing Aula 2 bridge section '.$key);
    $q=$db->prepare('SELECT lesson_id FROM course_page_sections WHERE page_id=1 AND section_key=?');$q->execute([$key]);must_aula3((int)$q->fetchColumn()===2,'legacy Aula 2 mapping missing for '.$key);
    $q=$db->prepare('SELECT lesson_id FROM course_material_sections WHERE course_id=1 AND page_id=1 AND section_key=?');$q->execute([$key]);must_aula3((int)$q->fetchColumn()===2,'course Aula 2 mapping missing for '.$key);
}
$q=$db->query("SELECT media_asset_id FROM course_page_media_slots WHERE page_id=1 AND slot_key='aula2-caderno'");
must_aula3((int)$q->fetchColumn()===99,'Aula 2 Caderno screenshot did not reuse the canonical Caderno asset');

must_aula3(str_contains($html,'Área do aluno: revisão, avaliação e continuidade da pesquisa'),'new Aula 3 title missing');
must_aula3(str_contains($html,'Área do aluno e continuidade'),'index was not updated');
must_aula3(!str_contains($html,'Conteúdo antigo 21')&&!str_contains($html,'Conteúdo antigo 22'),'old generic Aula 3 sections remain in document');
must_aula3(str_contains($html,'Associar um roteiro ao registro organiza o plano; não afirma que as etapas foram executadas.'),'association-versus-execution rule missing');
must_aula3(str_contains($html,'ela não escolhe qual delas “causou” o resultado'),'comparison is not explicitly non-causal');
must_aula3(str_contains($html,'Use as ferramentas para calcular e organizar. Use o Caderno para registrar o que aconteceu.'),'tool-versus-record authority boundary missing');
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

$unitKeys=['caderno-20a-agora-e-a-vez','caderno-20b-antes-terceiro-encontro','caderno-21-fluxo-pesquisa','caderno-22-exposicao','caderno-23-processamento','caderno-24-resultado-avaliacao','caderno-25-comparacao','caderno-26-continuar','caderno-27-ferramentas','caderno-28-rotina'];
foreach($unitKeys as $key){
    $tag=section_tag_aula3($html,$key);
    must_aula3($tag!==''&&preg_match('~class="[^"]*\\bstudy-unit\\b[^"]*"~',$tag)===1,'section did not adopt study-unit: '.$key);
    must_aula3(preg_match('~class="[^"]*\\bsection\\b[^"]*"~',$tag)!==1,'section still uses landing-page section class: '.$key);
    must_aula3(!str_contains($tag,'data-layout-background=')&&!str_contains($tag,'data-layout-space='),'section still carries landing-page layout attributes: '.$key);
}
$chapterTag=section_tag_aula3($html,'caderno-aula-3');
must_aula3(preg_match('~class="[^"]*\\bstudy-chapter\\b[^"]*"~',$chapterTag)===1,'Aula 3 chapter did not adopt study-chapter');
foreach(['Registro da prática entre as aulas','Preparação para o terceiro encontro','O Caderno como registro da pesquisa','Registrar a exposição','Registrar o processamento realizado','Resultado e avaliação','Comparar duas tentativas','Criar a próxima tentativa','Ferramentas da área do aluno','Depois de cada sessão'] as $heading){
    must_aula3(str_contains($html,'<h2 data-cms-editable>'.$heading.'</h2>'),'pedagogical heading missing: '.$heading);
}

must_aula3((int)$page['draft_revision']===13&&(int)$page['published_revision']===13,'page revisions did not advance for Aula 3, Aula 2 bridge and study-pattern correction');
must_aula3($draft['meta']['test']===true&&$published['meta']['test']===true,'document metadata was not preserved');

$before=(string)$page['published_document_json'];
$studyPatternMigration($db);
$after=(string)$db->query('SELECT published_document_json FROM cms_pages WHERE id=1')->fetchColumn();
must_aula3($before===$after,'study-pattern migration is not idempotent');

echo "aula3-student-area-material: ok\n";
