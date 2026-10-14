<?php
declare(strict_types=1);

function fail_handbook_placement(string $message): never {
    fwrite(STDERR,"handbook-materials-aula1: $message\n");
    exit(1);
}

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec(<<<'SQL'
CREATE TABLE cms_pages(
 id INTEGER PRIMARY KEY, locale TEXT, slug TEXT, status TEXT,
 draft_document_json TEXT, published_document_json TEXT,
 draft_revision INTEGER, published_revision INTEGER,
 draft_updated_at TEXT, published_at TEXT, updated_at TEXT
);
CREATE TABLE course_page_sections(
 page_id INTEGER, section_key TEXT, lesson_id INTEGER,
 created_at TEXT,updated_at TEXT,PRIMARY KEY(page_id,section_key)
);
CREATE TABLE course_material_sections(
 course_id INTEGER,page_id INTEGER,section_key TEXT,lesson_id INTEGER,
 created_at TEXT,updated_at TEXT,PRIMARY KEY(course_id,page_id,section_key)
);
SQL);

$makeHtml=static function(string $detail): string {
    return '<section data-cms-section="caderno-aula-1" data-cms-section-name="Aula 1" data-cms-availability="lesson" data-cms-lesson-id="11"><p>AULA 1</p></section>'."\n\n"
        .'<section data-cms-section="caderno-08-luz-seguranca" data-cms-lesson-id="11"><p>FIM AULA 1</p></section>'."\n\n"
        .'<section data-cms-section="caderno-aula-2" data-cms-lesson-id="22"><p>AULA 2</p></section>'."\n\n"
        .'<section data-cms-section="caderno-19-duas-chapas" data-cms-lesson-id="22"><p>AULA 2 INTACTA</p></section>'."\n\n"
        .'<section class="study-unit" data-cms-section="caderno-20-materiais" data-cms-section-name="Aula 2 · Onde encontrar os materiais" data-cms-availability="lesson" data-cms-lesson-id="22"><div class="format-grid"><p>'.htmlspecialchars($detail,ENT_QUOTES,'UTF-8').'</p></div></section>'."\n\n"
        .'<section data-cms-section="caderno-aula-3" data-cms-lesson-id="33"><p>AULA 3 INTACTA</p></section>';
};
$doc=static fn(string $html): string => json_encode(['version'=>2,'meta'=>['source'=>'handbook'],'html'=>$html],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$db->prepare("INSERT INTO cms_pages VALUES(1,'pt-BR','caderno-positivo-direto','published',?,?,3,3,'x','x','x')")
   ->execute([$doc($makeHtml('TEXTO EDITORIAL DO RASCUNHO')),$doc($makeHtml('TEXTO EDITORIAL PUBLICADO'))]);
foreach(['caderno-aula-1'=>11,'caderno-aula-2'=>22,'caderno-20-materiais'=>22] as $key=>$lesson){
    $db->prepare("INSERT INTO course_page_sections VALUES(1,?,?,?,?)")->execute([$key,$lesson,'x','x']);
    $db->prepare("INSERT INTO course_material_sections VALUES(7,1,?,?,?,?)")->execute([$key,$lesson,'x','x']);
}

$apply=require __DIR__.'/../migrations/099_move_handbook_materials_to_aula1.php';
$apply($db);
$row=$db->query('SELECT * FROM cms_pages WHERE id=1')->fetch(PDO::FETCH_ASSOC);
if(!$row)fail_handbook_placement('page missing');
foreach(['draft_document_json'=>'TEXTO EDITORIAL DO RASCUNHO','published_document_json'=>'TEXTO EDITORIAL PUBLICADO'] as $column=>$detail){
    $docData=json_decode((string)$row[$column],true);
    $html=(string)($docData['html']??'');
    $p1=strpos($html,'data-cms-section="caderno-08-luz-seguranca"');
    $pList=strpos($html,'data-cms-section="caderno-20-materiais"');
    $p2=strpos($html,'data-cms-section="caderno-aula-2"');
    $p3=strpos($html,'data-cms-section="caderno-aula-3"');
    if($p1===false||$pList===false||$p2===false||$p3===false||!($p1<$pList&&$pList<$p2&&$p2<$p3))
        fail_handbook_placement('material list is not at the end of Aula 1');
    if(substr_count($html,'data-cms-section="caderno-20-materiais"')!==1)
        fail_handbook_placement('section duplicated');
    if(!str_contains($html,$detail)||!str_contains($html,'AULA 2 INTACTA')||!str_contains($html,'AULA 3 INTACTA'))
        fail_handbook_placement('other lesson or editorial body changed');
    if(!str_contains($html,'data-cms-section-name="Aula 1 · Onde encontrar os materiais" data-cms-availability="lesson" data-cms-lesson-id="11"'))
        fail_handbook_placement('HTML lesson metadata incorrect');
    if(($docData['meta']['source']??null)!=='handbook')fail_handbook_placement('document meta changed');
}
foreach(['course_page_sections','course_material_sections'] as $table){
    $id=(int)$db->query("SELECT lesson_id FROM $table WHERE page_id=1 AND section_key='caderno-20-materiais'")->fetchColumn();
    if($id!==11)fail_handbook_placement('lesson mapping incorrect in '.$table);
}
$before=$db->query('SELECT draft_document_json,published_document_json,draft_revision,published_revision FROM cms_pages WHERE id=1')->fetch(PDO::FETCH_ASSOC);
$apply($db);
$after=$db->query('SELECT draft_document_json,published_document_json,draft_revision,published_revision FROM cms_pages WHERE id=1')->fetch(PDO::FETCH_ASSOC);
if($before!==$after)fail_handbook_placement('migration must be idempotent');
echo "handbook-materials-aula1: ok\n";
