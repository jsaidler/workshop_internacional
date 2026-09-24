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
foreach(['050','051','052','053','054','055','056','057','058'] as $n){
    $matches=glob(__DIR__.'/../migrations/'.$n.'_*.php');
    if(!$matches||count($matches)!==1)handbook_fail('migration not uniquely resolved: '.$n);
    (require $matches[0])($db);
}
$page=$db->query("SELECT * FROM cms_pages WHERE slug='caderno-positivo-direto'")->fetch();if(!$page)handbook_fail('page missing');
$html=(string)(json_decode((string)$page['published_document_json'],true)['html']??'');
$mustHave=[
 'Receitas, materiais, exposição e algumas referências para os testes',
 'A ideia não é transformar isso numa bula. Principalmente porque, como vimos na aula, boa parte do processo depende da relação entre exposição, revelação, temperatura, diluição e movimento.',
 'ALGUMAS COISAS IMPORTANTES SOBRE O FILME DE RAIO-X',
 'O Fuji Super HR-U é um filme ortocromático.',
 'EXPOSIÇÃO E ENERGIA',
 'FALHA DE RECIPROCIDADE',
 'DUAS COISAS DIFERENTES, UM MESMO PROBLEMA DE ENERGIA',
 '>EI<',
 'O filme não muda. A exposição muda.',
 'Isso nos leva a um conceito que eu deveria ter apresentado durante a aula e acabei deixando passar: o EI, ou Índice de Exposição.',
 'Foi justamente o que observamos.',
 'Para entender por que isso acontece, precisamos voltar um pouco e olhar o que estamos realmente revelando.',
 'Agora podemos olhar para os químicos com mais clareza.',
 'Na segunda aula fizemos duas fotografias em condições diferentes e o processo completo até o positivo.',
 'A primeira foi exposta em EI 200 e revelada com 10 ml de Parodinal + 550 ml de água, durante 7 minutos, a 26 °C e com agitação leve.',
 'Na segunda passamos para EI 400 e 20 ml de Parodinal + 550 ml de água, mantendo os mesmos 7 minutos, 26 °C e a mesma agitação.',
 'Ag⁺ + elétron → Ag⁰',
 'O cloreto férrico e a amônia, portanto, são dois processos independentes.',
 'Agora podemos voltar às duas fotografias da aula.',
 'Foi exatamente o que fizemos nas duas chapas da aula.',
 'Trabalhamos com quatro parâmetros que interferem diretamente na revelação: concentração, temperatura, tempo e agitação.',
 'Não como uma receita definitiva, mas como um possível desenvolvimento normal.',
 'Não precisa virar um relatório da NASA.'
];
foreach($mustHave as $needle)if(!str_contains($html,$needle))handbook_fail('source detail missing: '.$needle);
$mustNotHave=[
 'Qual o tamanho do suporte','Qual o endereço para envio','08/10','me convidem como colaborador',
 'Três aulas, um único processo',
 'O conteúdo foi reorganizado editorialmente',
 'Pretos absolutos. Altas luzes na transparência da base. E fotografia entre os dois extremos.',
 'A câmera fará uma única exposição para uma cena inteira que contém quantidades muito diferentes de luz.',
 'Quantidade de luz e tempo deixam de ser perfeitamente intercambiáveis nas exposições longas.',
 'Registro preservado literalmente do segundo e-mail:'
];
foreach($mustNotHave as $needle)if(str_contains($html,$needle))handbook_fail('non-source or cohort copy leaked: '.$needle);
$slots=['filme-ortocromatico','dupla-emulsao-positivo','energia-positivo','reciprocidade-energia','ei-zonas','imagem-latente-prata','negativo-positivo','branqueamentos-rotas','parametros-revelacao'];
foreach($slots as $slot)if(!str_contains($html,'data-private-media-slot="'.$slot.'"'))handbook_fail('slot missing: '.$slot);
foreach(['<style','<svg',' style=','cms-document','cms-lesson'] as $needle)if(str_contains(mb_strtolower($html,'UTF-8'),mb_strtolower($needle,'UTF-8')))handbook_fail('page-exclusive visual system leaked: '.$needle);
foreach(['editorial-cover','editorial-index','editorial-chapter','editorial-unit'] as $class)if(str_contains($html,$class))handbook_fail('landing-page editorial component leaked into study material: '.$class);
foreach(['study-material','study-cover','study-index','study-chapter','study-unit'] as $class)if(!str_contains($html,$class))handbook_fail('global study component missing: '.$class);
$map=$db->query("SELECT s.section_key,l.lesson_key FROM course_page_sections s JOIN course_lessons l ON l.id=s.lesson_id ORDER BY s.section_key")->fetchAll(PDO::FETCH_KEY_PAIR);
foreach(['caderno-04-energia'=>'aula-1','caderno-10-imagem-latente'=>'aula-2','caderno-21-leitura-resultados'=>'aula-3'] as $section=>$lesson)if(($map[$section]??'')!==$lesson)handbook_fail('wrong lesson mapping: '.$section);
$css=(string)file_get_contents(__DIR__.'/../assets/cms-ui-refinements.css');
foreach(['.study-material','.study-cover','.study-index','.study-chapter','.study-unit'] as $selector)if(!str_contains($css,$selector))handbook_fail('global study CSS missing: '.$selector);
foreach(['caderno-positivo-direto','caderno-aula-','caderno-04-energia'] as $needle)if(str_contains($css,$needle))handbook_fail('page slug/section leaked into global study CSS: '.$needle);
$material=(string)file_get_contents(__DIR__.'/../app/student_material.php');
foreach(['if(!current_admin())return','cms-media-placeholder','Infográfico pendente','slot: '] as $needle)if(!str_contains($material,$needle))handbook_fail('admin private-media placeholder contract missing: '.$needle);
echo "handbook-email-integrity: ok\n";
