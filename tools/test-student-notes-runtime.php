<?php
declare(strict_types=1);
function fail_notes_runtime(string $message): never {fwrite(STDERR,"student-notes-runtime: $message\n");exit(1);}
function must_notes_runtime(bool $ok,string $message): void {if(!$ok)fail_notes_runtime($message);}
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'csrf-'.$scope;}
function activity_slug(string $value): string {$value=mb_strtolower(trim($value),'UTF-8');$value=preg_replace('/[^a-z0-9]+/u','-',$value)??'';return trim($value,'-');}
function student_workspace_text(mixed $value,int $max=5000): string {$value=trim((string)$value);return mb_strlen($value)>$max?mb_substr($value,0,$max):$value;}
function student_uuid(): string {return 'annotation-test-uuid';}
function utc_now(): string {return '2026-10-01T00:00:00Z';}
function student_material_notes_for_page(PDO $db,int $studentId,int $pageId): array {return ['introducao'=>['section_key'=>'introducao','body'=>'Minha observação anterior']];}
function student_enrollment_material_context(PDO $db,array $student,array $page,string $cohortUuid=''): ?array {return ['cohort_id'=>3,'cohort_uuid'=>$cohortUuid!==''?$cohortUuid:'turma-1'];}
function student_question_create(PDO $db,int $studentId,int $cohortId,array $input): array {return ['id'=>99,'student_id'=>$studentId,'cohort_id'=>$cohortId,'title'=>(string)($input['title']??''),'body'=>(string)($input['body']??''),'visibility'=>(string)($input['visibility']??'private'),'status'=>'open'];}
require dirname(__DIR__).'/app/student_notes_experience.php';
require dirname(__DIR__).'/app/student_question_annotations.php';

$db=new PDO('sqlite::memory:');$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE,PDO::FETCH_ASSOC);
$db->exec("CREATE TABLE student_material_annotations(id INTEGER PRIMARY KEY AUTOINCREMENT,annotation_uuid TEXT,student_id INTEGER,page_id INTEGER,lesson_id INTEGER,section_key TEXT,anchor_type TEXT,body TEXT,quote_exact TEXT,quote_prefix TEXT,quote_suffix TEXT,block_key TEXT,start_offset INTEGER,end_offset INTEGER,source_block_hash TEXT,source_page_revision TEXT,created_at TEXT,updated_at TEXT)");
$db->exec("CREATE TABLE student_questions(id INTEGER PRIMARY KEY AUTOINCREMENT,student_id INTEGER,source_annotation_id INTEGER)");
$db->exec("INSERT INTO student_material_annotations(annotation_uuid,student_id,page_id,section_key,anchor_type,body,quote_exact,quote_prefix,quote_suffix,block_key,start_offset,end_offset,source_block_hash,source_page_revision,created_at,updated_at) VALUES('a1',7,11,'introducao','selection','Nota incorporada','Conteúdo didático.','','','introducao:p:1',0,18,'abc','rev-1','2026-10-01','2026-10-01')");
$_SERVER['REQUEST_URI']='/?page=material&cohort=turma-1';$_GET=['page'=>'material','cohort'=>'turma-1'];
$document=['html'=>'<section data-cms-section="introducao"><h2>Introdução</h2><p>Conteúdo didático.</p></section><section data-cms-section="processo"><h2>Processo</h2><p>Outro conteúdo.</p></section>'];
$out=student_material_render_notebook($db,['id'=>7],['id'=>11,'updated_at'=>'rev-1'],$document);$html=(string)$out['html'];
$panelPos=strpos($html,'class="student-notes-panel"');$lastSectionPos=strrpos($html,'</section>');$entryPos=strpos($html,'class="student-notes-entry"');$firstSectionPos=strpos($html,'<section');
must_notes_runtime($panelPos!==false,'notebook panel was not rendered');
must_notes_runtime($entryPos!==false&&$firstSectionPos!==false&&$entryPos<$firstSectionPos,'notes have no discoverable page-level entry point');
must_notes_runtime($lastSectionPos!==false&&$panelPos>$lastSectionPos,'notebook editor was injected inside editorial sections');
must_notes_runtime(str_contains($html,'data-student-anchor-block="introducao:p:1"'),'readable text blocks do not receive stable annotation anchors');
must_notes_runtime(str_contains($html,'data-inline-note-compose')&&str_contains($html,'value="create_selection"'),'selection annotation composer is missing');
must_notes_runtime(str_contains($html,'value="create_page"'),'page-level annotation form is missing');
must_notes_runtime(!str_contains($html,'<select name="section_key">'),'new annotations still require a manual section selector');
must_notes_runtime(str_contains($html,'Nota incorporada')&&str_contains($html,'Conteúdo didático.'),'selection annotation is not rendered in the notebook');
must_notes_runtime(str_contains($html,'data-student-annotation-data'),'client anchor payload is missing');
must_notes_runtime(str_contains($html,'Minha observação anterior'),'legacy annotations were lost during the migration');
must_notes_runtime(str_contains($html,'Também é uma dúvida?')&&str_contains($html,'Salvar e publicar dúvida')&&str_contains($html,'name="question_title"'),'new annotation cannot publish a question from the same two-step composer');
must_notes_runtime(str_contains($html,'Transformar em dúvida')&&str_contains($html,'name="annotation_action" value="question"'),'existing annotation cannot become a question in place');
must_notes_runtime(!str_contains($html,'>Virar dúvida</a>'),'annotation still forces navigation to a distant question screen');
must_notes_runtime(!str_contains($html,'<section data-cms-section="introducao" data-student-note-context="introducao" id="nota-trecho-introducao"><h2 data-student-anchor-block="introducao:h2:0">Introdução</h2><p data-student-anchor-block="introducao:p:1">Conteúdo didático.</p><form'),'note form is still glued to the section body');
$index=(string)file_get_contents(dirname(__DIR__).'/index.php');$publicJs=(string)file_get_contents(dirname(__DIR__).'/assets/public.js');$annotationJs=(string)file_get_contents(dirname(__DIR__).'/assets/student-inline-annotations.js');$endpoint=(string)file_get_contents(dirname(__DIR__).'/aluno/material-anotacao.php');
must_notes_runtime(!str_contains($index,'student-inline-annotations.js'),'annotation runtime is injected into CMS HTML and will be stripped by the sanitizer');
must_notes_runtime(str_contains($publicJs,"document.querySelector('[data-student-notes-panel]')")&&str_contains($publicJs,'student-inline-annotations.js'),'public shell does not load the annotation runtime for material pages');
must_notes_runtime(str_contains($annotationJs,'event.preventDefault()')&&str_contains($annotationJs,"Accept:'application/json'")&&str_contains($annotationJs,'fetch(form.action'),'annotation save still depends on browser navigation');
must_notes_runtime(str_contains($annotationJs,'restoreReadingOrigin')&&!str_contains($annotationJs,'student.annotation.return.v1'),'annotation continuity still depends on reload/sessionStorage instead of an in-place mutation');
must_notes_runtime(str_contains($endpoint,'$wantsJson')&&str_contains($endpoint,"['ok'=>true")&&str_contains($endpoint,"Content-Type: application/json"),'annotation endpoint has no JSON contract for local updates');
must_notes_runtime(str_contains($endpoint,'student_question_create_from_annotation')&&str_contains($endpoint,"'question_url'"),'annotation endpoint cannot create and return a linked question in place');
echo "student-notes-runtime: ok\n";
