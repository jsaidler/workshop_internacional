<?php
declare(strict_types=1);

function fail_material_lessons(string $message): never {fwrite(STDERR,"material-lesson-sections: $message\n");exit(1);}
function must_material_lessons(bool $condition,string $message): void {if(!$condition)fail_material_lessons($message);}

$root=dirname(__DIR__);$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,locale TEXT NOT NULL,slug TEXT NOT NULL,status TEXT NOT NULL,draft_document_json TEXT,published_document_json TEXT,draft_revision INTEGER,published_revision INTEGER,draft_updated_at TEXT,published_at TEXT,updated_at TEXT)");
$db->exec("CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,activity_id INTEGER NOT NULL,lesson_key TEXT NOT NULL,title TEXT)");
$db->exec("INSERT INTO course_lessons VALUES(11,7,'aula-1','Aula 1'),(12,7,'aula-2','Aula 2'),(13,7,'aula-3','Aula 3')");
$html=<<<'HTML'
<section data-cms-section="caderno-capa" data-cms-section-name="Capa" data-cms-availability="lesson" data-cms-lesson-id="99">Capa</section>
<section data-cms-section="caderno-indice" data-cms-section-name="Índice" data-cms-availability="lesson" data-cms-lesson-id="99">Índice</section>
<section data-cms-section="caderno-aula-1" data-cms-section-name="Aula 1 — Filme e exposição">A1</section>
<section data-cms-section="caderno-02-filme" data-cms-section-name="Aula 1 · O filme de raio-X"><figure data-private-media-slot="filme-ortocromatico"></figure></section>
<section data-cms-section="caderno-aula-2" data-cms-section-name="Aula 2 — Processos químicos para positivos">A2</section>
<section data-cms-section="caderno-10-imagem-latente" data-cms-section-name="Aula 2 · O que estamos revelando"><figure data-private-media-slot="imagem-latente-prata"></figure></section>
<section data-cms-section="caderno-aula-3" data-cms-section-name="Aula 3 — Revisão de resultados">A3</section>
<section data-cms-section="caderno-21-leitura-resultados" data-cms-section-name="Aula 3 · Como ler os resultados">Resultados</section>
HTML;
$doc=json_encode(['html'=>$html],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$q=$db->prepare("INSERT INTO cms_pages VALUES(1,7,'pt-BR','caderno-positivo-direto','published',?,?,4,4,'old','old','old')");$q->execute([$doc,$doc]);
(require $root.'/migrations/071_material_lesson_sections.php')($db);
$page=$db->query('SELECT * FROM cms_pages WHERE id=1')->fetch();$draft=json_decode((string)$page['draft_document_json'],true,512,JSON_THROW_ON_ERROR);$published=json_decode((string)$page['published_document_json'],true,512,JSON_THROW_ON_ERROR);
foreach([$draft,$published] as $index=>$document){
    $previous=libxml_use_internal_errors(true);$dom=new DOMDocument('1.0','UTF-8');$dom->loadHTML('<?xml encoding="utf-8" ?><div>'.$document['html'].'</div>',LIBXML_HTML_NOIMPLIED|LIBXML_HTML_NODEFDTD);$xpath=new DOMXPath($dom);
    $rule=function(string $key)use($xpath): array {$nodes=$xpath->query('//*[@data-cms-section="'.$key.'"]');$node=$nodes&&$nodes->length?$nodes->item(0):null;if(!$node instanceof DOMElement)return [];return ['availability'=>$node->getAttribute('data-cms-availability'),'lesson'=>$node->getAttribute('data-cms-lesson-id')];};
    foreach(['caderno-capa','caderno-indice'] as $key){$r=$rule($key);must_material_lessons(($r['availability']??'')===''&&($r['lesson']??'')==='',"$key remained lesson-gated in document $index");}
    foreach(['caderno-aula-1','caderno-02-filme'] as $key){$r=$rule($key);must_material_lessons(($r['availability']??'')==='lesson'&&($r['lesson']??'')==='11',"$key not assigned to Aula 1");}
    foreach(['caderno-aula-2','caderno-10-imagem-latente'] as $key){$r=$rule($key);must_material_lessons(($r['availability']??'')==='lesson'&&($r['lesson']??'')==='12',"$key not assigned to Aula 2");}
    foreach(['caderno-aula-3','caderno-21-leitura-resultados'] as $key){$r=$rule($key);must_material_lessons(($r['availability']??'')==='lesson'&&($r['lesson']??'')==='13',"$key not assigned to Aula 3");}
    must_material_lessons(substr_count((string)$document['html'],'data-private-media-slot=')===2,'private media slots changed');libxml_clear_errors();libxml_use_internal_errors($previous);
}
must_material_lessons((int)$page['draft_revision']===5&&(int)$page['published_revision']===5,'material revision was not advanced atomically');
$migration=(string)file_get_contents($root.'/migrations/071_material_lesson_sections.php');must_material_lessons(str_contains($migration,"['caderno-capa','caderno-indice']"),'cover/index immediate contract missing');must_material_lessons(str_contains($migration,'material_lesson_assignment_changed_media_slots'),'media-slot preservation guard missing');
echo "material-lesson-sections: ok\n";
