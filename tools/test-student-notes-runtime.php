<?php
declare(strict_types=1);
function fail_notes_runtime(string $message): never {fwrite(STDERR,"student-notes-runtime: $message\n");exit(1);}
function must_notes_runtime(bool $ok,string $message): void {if(!$ok)fail_notes_runtime($message);}
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'csrf-'.$scope;}
function activity_slug(string $value): string {$value=mb_strtolower(trim($value),'UTF-8');$value=preg_replace('/[^a-z0-9]+/u','-',$value)??'';return trim($value,'-');}
function student_material_notes_for_page(PDO $db,int $studentId,int $pageId): array {return ['introducao'=>['section_key'=>'introducao','body'=>'Minha observação']];}
require dirname(__DIR__).'/app/student_notes_experience.php';
$_SERVER['REQUEST_URI']='/?page=material&cohort=turma-1';
$document=['html'=>'<section data-cms-section="introducao"><h2>Introdução</h2><p>Conteúdo didático.</p></section><section data-cms-section="processo"><h2>Processo</h2><p>Outro conteúdo.</p></section>'];
$out=student_material_render_notebook(new PDO('sqlite::memory:'),['id'=>7],['id'=>11],$document);
$html=(string)$out['html'];
$panelPos=strpos($html,'class="student-notes-panel"');$lastSectionPos=strrpos($html,'</section>');$entryPos=strpos($html,'class="student-notes-entry"');$firstSectionPos=strpos($html,'<section');
must_notes_runtime($panelPos!==false,'notebook panel was not rendered');
must_notes_runtime($entryPos!==false&&$firstSectionPos!==false&&$entryPos<$firstSectionPos,'notes have no discoverable page-level entry point');
must_notes_runtime(str_contains($html,'aria-controls="anotacoes"'),'notes entry does not point to the notebook');
must_notes_runtime($lastSectionPos!==false&&$panelPos>$lastSectionPos,'notebook editor was injected inside editorial sections');
must_notes_runtime(substr_count($html,'action="/aluno/material-anotacao.php"')===2,'expected one existing-note editor and one new-note form');
must_notes_runtime(str_contains($html,'Página inteira'),'page-level annotation context is missing');
must_notes_runtime(str_contains($html,'value="processo"'),'free section is not available as annotation context');
must_notes_runtime(!str_contains($html,'<section data-cms-section="introducao"><h2>Introdução</h2><p>Conteúdo didático.</p><form'),'note form is still glued to the section body');
must_notes_runtime(str_contains($html,'anotacoes=1')&&str_contains($html,'#anotacoes'),'note save does not return to the open notebook');
echo "student-notes-runtime: ok\n";
