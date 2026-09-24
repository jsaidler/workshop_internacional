<?php
declare(strict_types=1);
function handbook_fail(string $message): never {fwrite(STDERR,"handbook-email-integrity: $message\n");exit(1);}
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db->exec("CREATE TABLE cms_pages(id INTEGER PRIMARY KEY AUTOINCREMENT,activity_id INTEGER,locale TEXT,slug TEXT,title TEXT,nav_title TEXT,status TEXT,show_in_nav INTEGER,draft_document_json TEXT,published_document_json TEXT,draft_revision INTEGER,published_revision INTEGER,draft_updated_at TEXT,published_at TEXT,updated_at TEXT,access_level TEXT DEFAULT 'public');");
$db->exec("CREATE TABLE course_lessons(id INTEGER PRIMARY KEY AUTOINCREMENT,activity_id INTEGER,lesson_key TEXT,title TEXT); INSERT INTO course_lessons(activity_id,lesson_key,title) VALUES(1,'aula-1','Aula 1'),(1,'aula-2','Aula 2'),(1,'aula-3','Aula 3');");
$db->exec("CREATE TABLE course_page_sections(page_id INTEGER,section_key TEXT,lesson_id INTEGER,created_at TEXT,updated_at TEXT,PRIMARY KEY(page_id,section_key));");
$db->exec("CREATE TABLE student_private_media(id INTEGER PRIMARY KEY AUTOINCREMENT,asset_uuid TEXT UNIQUE,activity_id INTEGER,page_id INTEGER,title TEXT,original_name TEXT,mime_type TEXT,byte_size INTEGER,storage_path TEXT,checksum TEXT,created_at TEXT,updated_at TEXT);");
$seed=json_encode(['version'=>2,'theme'=>'auto','meta'=>[],'html'=>'<section data-cms-section="seed"><p>seed</p></section>'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
$q=$db->prepare("INSERT INTO cms_pages(activity_id,locale,slug,title,nav_title,status,show_in_nav,draft_document_json,published_document_json,draft_revision,published_revision,draft_updated_at,published_at,updated_at,access_level) VALUES(1,'pt-BR','caderno-positivo-direto','x','x','published',1,?,?,1,1,'x','x','x','public')");$q->execute([$seed,$seed]);
(require __DIR__.'/../migrations/050_protected_handbook_media_slots.php')($db);
(require __DIR__.'/../migrations/051_handbook_document_lessons.php')($db);
(require __DIR__.'/../migrations/052_remove_handbook_specific_visual_system.php')($db);
(require __DIR__.'/../migrations/053_rebuild_handbook_from_email_sources.php')($db);
(require __DIR__.'/../migrations/054_complete_handbook_source_details.php')($db);
$page=$db->query("SELECT * FROM cms_pages WHERE slug='caderno-positivo-direto'")->fetch();if(!$page)handbook_fail('page missing');
$html=(string)(json_decode((string)$page['published_document_json'],true)['html']??'');
$mustHave=[
 'A ideia não é transformar isso numa bula.',
 'O Fuji Super HR-U é um filme ortocromático.',
 'A cada EV abaixo temos metade da luz:',
 'Usando uma pinhole f/256 como referência, podemos ver o tamanho da diferença:',
 'tempo corrigido = tempo calculado elevado a 1,3854',
 'O filme não muda. A exposição muda.',
 'Ag⁺ + elétron → Ag⁰',
 'O cloreto férrico e a amônia, portanto, são dois processos independentes.',
 'Trabalhamos com quatro parâmetros que interferem diretamente na revelação: concentração, temperatura, tempo e agitação.',
 'Não como uma receita definitiva, mas como um possível desenvolvimento normal.',
 'Não precisa virar um relatório da NASA.'
];
foreach($mustHave as $needle)if(!str_contains($html,$needle))handbook_fail('source detail missing: '.$needle);
$mustNotHave=['Qual o tamanho do suporte','Qual o endereço para envio','08/10','me convidem como colaborador'];
foreach($mustNotHave as $needle)if(str_contains($html,$needle))handbook_fail('cohort message leaked: '.$needle);
$slots=['filme-ortocromatico','dupla-emulsao-positivo','energia-positivo','reciprocidade-energia','ei-zonas','imagem-latente-prata','negativo-positivo','branqueamentos-rotas','parametros-revelacao'];
foreach($slots as $slot)if(!str_contains($html,'data-private-media-slot="'.$slot.'"'))handbook_fail('slot missing: '.$slot);
foreach(['<style','<svg',' style=','cms-document','cms-lesson'] as $needle)if(str_contains(mb_strtolower($html,'UTF-8'),mb_strtolower($needle,'UTF-8')))handbook_fail('page-exclusive visual system leaked: '.$needle);
$map=$db->query("SELECT s.section_key,l.lesson_key FROM course_page_sections s JOIN course_lessons l ON l.id=s.lesson_id ORDER BY s.section_key")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach(['caderno-04-energia'=>'aula-1','caderno-10-imagem-latente'=>'aula-2','caderno-21-leitura-resultados'=>'aula-3'] as $section=>$lesson)if(($map[$section]??'')!==$lesson)handbook_fail('wrong lesson mapping: '.$section);
echo "handbook-email-integrity: ok\n";
