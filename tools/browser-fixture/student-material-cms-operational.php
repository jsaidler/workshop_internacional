<?php
declare(strict_types=1);
/*
 * CMS reading fixture: same cms_render_public_page(), enrollment context, release map,
 * section filter and global student navigation as production. All rows are synthetic.
 * It does not replace index.php's authenticated entry point or constitute hosted QA.
 */
const PUBLIC_LOCALE_PT_BR='pt-BR';
const PUBLIC_LOCALE_EN='en';
require_once __DIR__.'/../../app/cms_settings.php';
function public_locale_query(string $locale):string{return $locale==='pt-BR'?'pt-BR':'en';}
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function utc_now():string{return gmdate('c');}
function database():PDO {
 static $db=null;if($db instanceof PDO)return $db;
 $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
 $db->exec(<<<'SQL'
 CREATE TABLE courses(id INTEGER PRIMARY KEY,activity_id INTEGER,title TEXT,public_page_id INTEGER,status TEXT);
 CREATE TABLE course_cohorts(id INTEGER PRIMARY KEY,course_id INTEGER,activity_id INTEGER,workshop_page_id INTEGER,title TEXT,cohort_uuid TEXT,status TEXT);
 CREATE TABLE cms_site_settings(activity_id INTEGER,locale TEXT,settings_json TEXT,updated_at TEXT,PRIMARY KEY(activity_id,locale));
 CREATE TABLE cms_design_settings(activity_id INTEGER,locale TEXT,settings_json TEXT,updated_at TEXT,PRIMARY KEY(activity_id,locale));
 CREATE TABLE course_enrollments(id INTEGER PRIMARY KEY,student_id INTEGER,cohort_id INTEGER,status TEXT,confirmed_at TEXT);
 CREATE TABLE cms_pages(id INTEGER PRIMARY KEY,activity_id INTEGER,locale TEXT,title TEXT,slug TEXT,is_home INTEGER,status TEXT,sort_order INTEGER,draft_document_json TEXT,published_document_json TEXT);
 CREATE TABLE course_material_pages(course_id INTEGER,page_id INTEGER,sort_order INTEGER,PRIMARY KEY(course_id,page_id));
 CREATE TABLE course_lessons(id INTEGER PRIMARY KEY,course_id INTEGER,activity_id INTEGER,title TEXT,lesson_key TEXT,sort_order INTEGER);
 CREATE TABLE course_material_sections(course_id INTEGER,page_id INTEGER,section_key TEXT,lesson_id INTEGER);
 CREATE TABLE student_material_annotations(id INTEGER PRIMARY KEY,student_id INTEGER,page_id INTEGER,anchor_type TEXT,section_key TEXT,block_key TEXT,quote_exact TEXT,quote_prefix TEXT,quote_suffix TEXT,start_offset INTEGER,end_offset INTEGER,source_block_hash TEXT,body TEXT,created_at TEXT);
 CREATE TABLE cohort_lesson_releases(cohort_id INTEGER,lesson_id INTEGER,released_at TEXT,created_at TEXT,updated_at TEXT,meeting_starts_at TEXT,meeting_timezone TEXT,meeting_duration_minutes INTEGER,meeting_url TEXT,meeting_notes TEXT,meeting_status TEXT,PRIMARY KEY(cohort_id,lesson_id));
 SQL);
 $db->exec("INSERT INTO courses VALUES(12,1,'Positivo direto em filme de raio-X',NULL,'active');
 INSERT INTO course_cohorts VALUES(7,12,1,NULL,'Turma Outubro 2026','cohort-a','active');
 INSERT INTO course_enrollments VALUES(1,99,7,'active','2026-10-01T00:00:00Z');
 INSERT INTO course_lessons VALUES(1,12,1,'Exposição pensando no positivo','exposure',1),
 (2,12,1,'Processo químico completo','chemistry',2),
 (3,12,1,'Segunda revelação e acabamento','finish',3),
 (4,12,1,'Conteúdo não liberado','blocked',4);
 INSERT INTO cohort_lesson_releases(cohort_id,lesson_id,released_at,created_at,updated_at)
 VALUES(7,1,'2026-09-30T00:00:00Z','now','now'),
 (7,2,'2026-10-01T00:00:00Z','now','now'),
 (7,3,'2026-10-02T00:00:00Z','now','now'),
 (7,4,NULL,'now','now')");
 $db->exec("INSERT INTO student_material_annotations VALUES(1,99,101,'page','pagina','','','','',NULL,NULL,'','Comparar EI 200 e EI 400 mantendo os parâmetros da revelação','2026-10-01T00:00:00Z'),(2,99,101,'page','pagina','','','','',NULL,NULL,'','Conferir lavagem depois do branqueamento','2026-10-02T00:00:00Z')");
 $pages=[
  [100,'exposicao','Exposição pensando no positivo','<section class="study-unit" data-cms-section="exposure"><h2>Exposição pensando no positivo</h2><p>Exposição é uma decisão independente dos processos de revelação.</p></section>',1],
  [101,'processo-quimico-completo','Processo químico completo',<<<'HTML'
<article class="study-material" data-testid="editorial-material">
<header class="study-cover"><p class="section-label">Segundo encontro</p><div class="statement-grid"><div><h1>Processo químico completo</h1></div><p class="study-subtitle">Do primeiro desenvolvimento à obtenção do positivo no próprio filme de raio-X.</p></div></header>
<section class="study-unit" data-cms-section="primeiro-desenvolvimento" data-student-note-context="primeiro-desenvolvimento"><p class="section-label">01 · Desenvolvimento</p><div class="statement-grid"><div><h2>Primeiro desenvolvimento</h2></div><div class="statement-copy"><p>O primeiro desenvolvimento forma a imagem negativa que servirá de base ao positivo. O controle aqui é feito em relação à exposição realmente usada e à resposta do conjunto filme–revelador.</p><p data-student-anchor-block>Ao comparar resultados, mantenha as demais variáveis constantes sempre que a intenção for observar o efeito de uma mudança de exposição ou de desenvolvimento.</p></div></div></section>
<section class="study-unit" data-cms-section="branqueamento" data-student-note-context="branqueamento"><p class="section-label">02 · Branqueamento</p><div class="statement-grid"><div><h2>Branqueamento</h2></div><div class="statement-copy"><p>O branqueamento remove a prata metálica formada no primeiro desenvolvimento. A sequência de lavagem e limpeza precisa ser concluída antes da reexposição.</p><div class="technical-note study-instruction">Registre tempo, temperatura e qualquer diferença observada entre a sequência prevista e o que foi realmente feito.</div></div></div></section>
<section class="study-unit" data-cms-section="aula-nao-liberada"><div class="statement-grid"><h2>Conteúdo ainda não liberado</h2><p>Esta seção não pode ser enviada ao aluno.</p></div></section>
<section class="study-unit"><div class="statement-grid"><div><h2>Notas de bancada</h2></div><div class="statement-copy"><p>Use suas anotações para relacionar o resultado final às condições de exposição e aos valores efetivamente usados no processamento.</p></div></div></section>
</article>
HTML,2],
  [102,'segunda-revelacao','Segunda revelação e acabamento','<section class="study-unit" data-cms-section="finish"><h2>Segunda revelação e acabamento</h2><p>O tempo é registrado antes de confirmar a etapa.</p></section>',3],
  [103,'material-nao-liberado','Conteúdo não liberado','<section class="study-unit" data-cms-section="blocked"><h2>Somente depois da liberação</h2></section>',4],
 ];
 $insert=$db->prepare('INSERT INTO cms_pages(id,activity_id,locale,title,slug,is_home,status,sort_order,draft_document_json,published_document_json) VALUES(?,1,?,?,?,?,?,?,?,?)');
 foreach($pages as [$id,$slug,$title,$html,$lesson]){
   // Reproducible long-form reading position for nested-scroll History tests.
   if($id===101&&($_GET['long']??'')==='1'){
     $longText=str_repeat('<p>Continue a leitura deste material. A exposição e a revelação são registradas separadamente, mantendo os dados de cada experiência acessíveis para consulta posterior.</p>',22);
     $html=str_replace('</article>',$longText.'</article>',$html);
   }
   $document=json_encode(['version'=>2,'theme'=>'auto','meta'=>['title'=>$title],'html'=>$html],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
   $insert->execute([$id,'pt-BR',$title,$slug,0,'active',$id,$document,$document]);
   $db->prepare('INSERT INTO course_material_pages VALUES(12,?,?)')->execute([$id,$id]);
   foreach(['100'=>'exposure','101'=>'primeiro-desenvolvimento','102'=>'finish','103'=>'blocked'] as $fileId=>$key)if((int)$fileId===$id)$db->prepare('INSERT INTO course_material_sections VALUES(12,?,?,?)')->execute([$id,$key,$lesson]);
   if($id===101){
    $db->exec("INSERT INTO course_material_sections VALUES(12,101,'branqueamento',2)");
    $db->exec("INSERT INTO course_material_sections VALUES(12,101,'aula-nao-liberada',4)");
   }
 }
 $site=['wordmark'=>'JSaidler Fotografia','header'=>['sticky'=>false,'showLanguageSwitch'=>false,'showThemeSwitch'=>false], 'navigation'=>['items'=>[['type'=>'custom','label'=>'Início','url'=>'/aluno/']]],'footer'=>['line1'=>'','line2'=>'','links'=>[]]];
 $db->prepare('INSERT INTO cms_site_settings(activity_id,locale,settings_json,updated_at) VALUES(1,?,?,?)')->execute(['pt-BR',json_encode($site,JSON_UNESCAPED_UNICODE),'2026-10-01']);
 if((string)($_GET['design']??'')==='custom'){
   $design=['type'=>['displayFont'=>'Oswald','bodyFont'=>'Inter','monoFont'=>'JetBrains Mono'],'colors'=>['accent'=>'#345678']];
   $db->prepare('INSERT INTO cms_design_settings(activity_id,locale,settings_json,updated_at) VALUES(1,?,?,?)')->execute(['pt-BR',json_encode($design,JSON_UNESCAPED_UNICODE),'2026-10-01']);
 }
 return $db;
}
function course_cohort_by_id(PDO $db,int $id):?array{$q=$db->prepare('SELECT * FROM course_cohorts WHERE id=?');$q->execute([$id]);return $q->fetch(PDO::FETCH_ASSOC)?:null;}
function cms_page_seo(PDO $db,int $pageId):array{return ['title'=>'','description'=>'','socialTitle'=>'','socialDescription'=>'','socialImage'=>'','canonicalUrl'=>'','robots'=>'noindex,nofollow'];}
function cms_absolute_url(string $url):string{return $url;}
function cms_page_translation_counterpart(PDO $db,array $page,string $targetLocale):?array{return null;}
function media_resolve_cms_html(PDO $db,string $html):string{return $html;}
function media_resolve_cms_video_html(PDO $db,string $html):string{return $html;}
function csrf_token(string $scope):string{return 'fixture-cms-csrf-no-write';}
function student_workspace_text(mixed $value,int $max=120):string{return substr(trim((string)$value),0,$max);}
function activity_slug(string $raw):string{return trim((string)preg_replace('/[^a-z0-9-]+/','-',strtolower($raw)),'-');}
function student_material_notes_for_page(PDO $db,int $studentId,int $pageId):array{return [];}
function student_account_current(PDO $db):?array{
 return match((string)($_GET['actor']??'member')){'none'=>null,'other'=>['id'=>98],default=>['id'=>99]};
}
require_once __DIR__.'/../../app/cms_pages.php';
require_once __DIR__.'/../../app/courses.php';
require_once __DIR__.'/../../app/student_enrollments.php';
require_once __DIR__.'/../../app/cms_access.php';
require_once __DIR__.'/../../app/student_notes_experience.php';
require_once __DIR__.'/../../app/student_shell.php';
require_once __DIR__.'/../../app/cms_renderer.php';
$db=database();
$activity=['id'=>1,'slug'=>'workshop','is_root'=>1,'public_title'=>'Positivo direto'];
$pageId=match((string)($_GET['page']??'current')){'previous'=>100,'next'=>102,'blocked'=>103,default=>101};
$page=cms_page_by_id($db,$pageId);
$student=student_account_current($db);
$cohort=(string)($_GET['cohort']??'cohort-a');
$_GET['cohort']=$cohort;
if(!$page||!$student||!cms_access_page_allowed($db,$activity,$page,$student)
   ||!student_enrollment_material_context($db,$student,$page,$cohort)){
 http_response_code(403);header('Content-Type: text/plain; charset=UTF-8');exit('Acesso não autorizado à página do curso.');
}
if(($_SERVER['REQUEST_METHOD']??'GET')!=='GET'){http_response_code(405);exit('Fixture: somente leitura');}
$context=student_enrollment_material_context($db,$student,$page,$cohort);
$document=cms_page_doc($page,true);
$document['html']=cms_access_filter_html($db,$activity,(string)$document['html'],$student,false,null,$context);
$document=student_material_render_notebook($db,$student,$page,$document);
cms_render_public_page($activity,$page,$document,false,true);
