<?php
declare(strict_types=1);

function handbook_workflow_fail(string $message): never {
    fwrite(STDERR,"student-handbook-workflow-guide: $message\n");
    exit(1);
}
function handbook_workflow_remove_tree(string $path): void {
    if(!is_dir($path))return;
    $items=scandir($path)?:[];
    foreach($items as $item){
        if($item==='.'||$item==='..')continue;
        $child=$path.'/'.$item;
        if(is_dir($child))handbook_workflow_remove_tree($child);else @unlink($child);
    }
    @rmdir($path);
}

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec(<<<'SQL'
CREATE TABLE cms_pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    locale TEXT NOT NULL,
    slug TEXT NOT NULL,
    status TEXT NOT NULL,
    draft_document_json TEXT NOT NULL,
    published_document_json TEXT NOT NULL,
    draft_revision INTEGER NOT NULL DEFAULT 1,
    published_revision INTEGER NOT NULL DEFAULT 1,
    draft_updated_at TEXT,
    published_at TEXT,
    updated_at TEXT
);
CREATE TABLE courses (id INTEGER PRIMARY KEY AUTOINCREMENT,title TEXT);
CREATE TABLE course_lessons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER,
    lesson_key TEXT NOT NULL,
    title TEXT
);
CREATE TABLE course_material_pages (
    course_id INTEGER NOT NULL,
    page_id INTEGER NOT NULL,
    sort_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(course_id,page_id)
);
CREATE TABLE course_material_sections (
    course_id INTEGER NOT NULL,
    page_id INTEGER NOT NULL,
    section_key TEXT NOT NULL,
    lesson_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(course_id,page_id,section_key)
);
CREATE TABLE media_assets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    asset_uuid TEXT NOT NULL UNIQUE,
    kind TEXT NOT NULL,
    title TEXT NOT NULL,
    default_alt TEXT NOT NULL DEFAULT '',
    original_name TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    byte_size INTEGER NOT NULL,
    width INTEGER,
    height INTEGER,
    duration REAL,
    checksum TEXT NOT NULL,
    processing_status TEXT NOT NULL,
    active_version_id INTEGER,
    archived_at TEXT,
    visibility TEXT NOT NULL DEFAULT 'public',
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE TABLE media_versions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    asset_id INTEGER NOT NULL,
    version_uuid TEXT NOT NULL UNIQUE,
    original_path TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    byte_size INTEGER NOT NULL,
    width INTEGER,
    height INTEGER,
    duration REAL,
    checksum TEXT NOT NULL,
    poster_timestamp REAL,
    processing_status TEXT NOT NULL,
    error_message TEXT,
    created_at TEXT NOT NULL
);
CREATE TABLE course_page_media_slots (
    page_id INTEGER NOT NULL,
    slot_key TEXT NOT NULL,
    media_asset_id INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    PRIMARY KEY(page_id,slot_key)
);
SQL);

$existingAula2='Texto técnico existente da Aula 2 que deve permanecer intacto.';
$existingRead='Um teste ruim, mas bem anotado, continua sendo um teste e pode nos dizer exatamente onde mexer.';
$existingRecord='Não precisa virar um relatório da NASA. Mas tentem registrar pelo menos:';
$html=<<<HTML
<div class="study-material">
<section data-cms-section="caderno-aula-2"><h2>Aula 02</h2></section>
<section data-cms-section="caderno-20-materiais"><p>{$existingAula2}</p></section>
<section data-cms-section="caderno-aula-3"><h2>Aula 03</h2></section>
<section data-cms-section="caderno-21-leitura-resultados"><p>{$existingRead}</p></section>
<section data-cms-section="caderno-22-registro"><p>{$existingRecord}</p></section>
</div>
HTML;
$document=json_encode(['version'=>2,'theme'=>'auto','meta'=>[],'html'=>$html],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
$now=gmdate('c');
$q=$db->prepare("INSERT INTO cms_pages(locale,slug,status,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at) VALUES('pt-BR','caderno-positivo-direto','published',?,?,1,1,?,?,?)");
$q->execute([$document,$document,$now,$now,$now]);
$pageId=(int)$db->lastInsertId();
$db->exec("INSERT INTO courses(id,title) VALUES(1,'Positivo direto');");
$db->prepare('INSERT INTO course_lessons(course_id,lesson_key,title) VALUES(1,?,?)')->execute(['aula-2','Aula 2']);
$db->prepare('INSERT INTO course_lessons(course_id,lesson_key,title) VALUES(1,?,?)')->execute(['aula-3','Aula 3']);
$db->prepare('INSERT INTO course_material_pages(course_id,page_id,sort_order,created_at,updated_at) VALUES(1,?,0,?,?)')->execute([$pageId,$now,$now]);

$assetUuids=[];
foreach(['aula2-registro','aula3-caderno','aula3-avaliacao','aula3-comparacao'] as $slot)$assetUuids[]=md5('student-handbook-workflow:'.$slot);
$mediaRoot=dirname(__DIR__).'/uploads/media';
register_shutdown_function(static function() use($mediaRoot,$assetUuids): void {
    foreach($assetUuids as $uuid)handbook_workflow_remove_tree($mediaRoot.'/'.$uuid);
});

$migration=require __DIR__.'/../migrations/085_student_handbook_workflow_guide.php';
$migration($db);

$page=$db->query("SELECT * FROM cms_pages WHERE id=$pageId")->fetch();
if(!$page)handbook_workflow_fail('page missing after migration');
$updated=(string)(json_decode((string)$page['published_document_json'],true)['html']??'');

foreach([$existingAula2,$existingRead,$existingRecord] as $needle){
    if(!str_contains($updated,$needle))handbook_workflow_fail('existing handbook content was not preserved: '.$needle);
}
foreach([
    'data-cms-section="caderno-aula2-proprios-testes"',
    'Registrem o que aconteceu, não o que deveria ter acontecido.',
    'data-cms-section="caderno-aula3-area-aluno"',
    'data-cms-section="caderno-aula3-avaliacao"',
    'data-cms-section="caderno-aula3-comparacao"',
    'data-cms-section="caderno-aula3-continuar"',
    'Uma diferença registrada não é, por si só, uma explicação.',
    'a próxima fotografia herda um ponto de partida, não uma conclusão.',
] as $needle){
    if(!str_contains($updated,$needle))handbook_workflow_fail('new handbook content missing: '.$needle);
}
foreach(['aula2-registro','aula3-caderno','aula3-avaliacao','aula3-comparacao'] as $slot){
    if(!str_contains($updated,'data-private-media-slot="'.$slot.'"'))handbook_workflow_fail('screenshot slot missing: '.$slot);
}

$posAula2=strpos($updated,'data-cms-section="caderno-aula2-proprios-testes"');
$posChapter=strpos($updated,'data-cms-section="caderno-aula-3"');
$posGuide=strpos($updated,'data-cms-section="caderno-aula3-area-aluno"');
$posExisting=strpos($updated,'data-cms-section="caderno-21-leitura-resultados"');
if($posAula2===false||$posChapter===false||$posGuide===false||$posExisting===false||!($posAula2<$posChapter&&$posChapter<$posGuide&&$posGuide<$posExisting)){
    handbook_workflow_fail('Aula 2/Aula 3 insertion order is wrong');
}

$mapping=$db->query('SELECT s.section_key,l.lesson_key FROM course_material_sections s JOIN course_lessons l ON l.id=s.lesson_id ORDER BY s.section_key')->fetchAll(PDO::FETCH_KEY_PAIR);
$expected=[
    'caderno-aula2-proprios-testes'=>'aula-2',
    'caderno-aula3-area-aluno'=>'aula-3',
    'caderno-aula3-avaliacao'=>'aula-3',
    'caderno-aula3-comparacao'=>'aula-3',
    'caderno-aula3-continuar'=>'aula-3',
];
foreach($expected as $section=>$lesson){
    if(($mapping[$section]??null)!==$lesson)handbook_workflow_fail('wrong lesson mapping: '.$section);
}

$slots=$db->query('SELECT slot_key FROM course_page_media_slots ORDER BY slot_key')->fetchAll(PDO::FETCH_COLUMN);
sort($slots,SORT_STRING);
$expectedSlots=['aula2-registro','aula3-avaliacao','aula3-caderno','aula3-comparacao'];
if($slots!==$expectedSlots)handbook_workflow_fail('private screenshot slots were not bound exactly once');
if((int)$db->query("SELECT COUNT(*) FROM media_assets WHERE visibility='private'")->fetchColumn()!==4)handbook_workflow_fail('screenshots are not four private media assets');

$versions=$db->query('SELECT a.asset_uuid,a.mime_type,a.width,a.height,v.original_path,v.byte_size FROM media_assets a JOIN media_versions v ON v.id=a.active_version_id ORDER BY a.id')->fetchAll(PDO::FETCH_ASSOC);
if(count($versions)!==4)handbook_workflow_fail('active screenshot versions missing');
foreach($versions as $version){
    if((string)$version['mime_type']!=='image/webp')handbook_workflow_fail('screenshot mime is not WebP');
    if((int)$version['width']<600||(int)$version['height']<500)handbook_workflow_fail('screenshot dimensions are unexpectedly small');
    $file=$mediaRoot.'/'.ltrim((string)$version['original_path'],'/');
    if(!is_file($file)||(int)filesize($file)!==(int)$version['byte_size'])handbook_workflow_fail('imported screenshot file missing or size mismatch');
    $deny=$mediaRoot.'/'.(string)$version['asset_uuid'].'/.htaccess';
    if(!is_file($deny)||!str_contains((string)file_get_contents($deny),'Require all denied'))handbook_workflow_fail('private screenshot storage is not denied');
}

$revision=(int)$page['published_revision'];
$migration($db);
$page2=$db->query("SELECT * FROM cms_pages WHERE id=$pageId")->fetch();
$html2=(string)(json_decode((string)$page2['published_document_json'],true)['html']??'');
if((int)$page2['published_revision']!==$revision)handbook_workflow_fail('second migration run changed handbook revision');
foreach(array_keys($expected) as $section){
    if(substr_count($html2,'data-cms-section="'.$section.'"')!==1)handbook_workflow_fail('section duplicated on second run: '.$section);
}
if((int)$db->query('SELECT COUNT(*) FROM media_assets')->fetchColumn()!==4||(int)$db->query('SELECT COUNT(*) FROM course_page_media_slots')->fetchColumn()!==4)handbook_workflow_fail('media duplicated on second run');

echo "student-handbook-workflow-guide: ok\n";
