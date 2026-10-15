<?php
declare(strict_types=1);
/** Read-only shared notebook record using the production renderer and controlled synthetic input; no permissions simulated or writes. */
require_once __DIR__.'/../../app/student_shell.php';
require_once __DIR__.'/../../app/student_shared_record_view.php';
function h(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'synthetic-sharing-csrf';}
function database(): PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_account_current(PDO $db): ?array {return ['id'=>2,'name'=>'Visitante de teste'];}
function student_tools_for_student(PDO $db,int $id): array {return [];}
function cms_design_defaults(): array {return ['colors'=>['bg'=>'#101010']];}
function cms_design_font_import_css(array $design): string {return '';}
function cms_design_css(array $design): string {return ':root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Arial,sans-serif;--mono:"Courier New",monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px}';}
function student_test_visibility_label(string $v): string {return $v==='cohort'?'Compartilhado com a turma':'Privado';}
function student_review_value(mixed $v): string {$text=trim((string)$v);return h($text===''?'—':$text);}
function student_process_path_label(array $steps): string {return implode(' → ',array_map(static fn(array $step): string => (string)($step['chemical_name']??$step['label']),$steps));}
function student_test_message_date(string $v): string {return '14/10/2026';}
$mode=(string)($_GET['mode']??'filled');
if(!in_array($mode,['filled','empty','conversation','unsafe'],true)){http_response_code(404);exit;}
$id=103;
$record=[
  'id'=>$id,'student_id'=>3,'student_name'=>'Fotógrafa de teste','activity_id'=>1,'visibility'=>'cohort',
  'cohort_title'=>'Turma Outubro 2026','title'=>'Comparação de positivo direto — EI 400',
  'film'=>'Fuji Super H-RU','lot'=>'RX-24','iso_reference'=>'400','aperture'=>'f/64',
  'calculated_time'=>'04 s','reciprocity_time'=>'07 s',
  'light_condition'=>'Janela lateral · luz difusa','tonal_range'=>'Sombras agrupadas',
  'developer'=>'Parodinal','dilution'=>'10+550','temperature'=>'26 °C','development_time'=>'07:00',
  'bleach'=>'Cloreto férrico','notes'=>'Positivo sobre filme de raio-X. Discussão das diferenças observadas.'
];
$activity=['public_title'=>'Positivo direto em filme de raio-X'];
$steps=$mode==='empty'?[]:[
 ['position'=>1,'label'=>'Primeira revelação','chemical_name'=>'Parodinal','calculated_dilution'=>'10+550','temperature'=>'26 °C','duration'=>'07:00','notes'=>'Agitação leve'],
 ['position'=>2,'label'=>'Branqueamento','chemical_name'=>'Cloreto férrico','calculated_dilution'=>'','temperature'=>'','duration'=>'02:00','notes'=>''],
 ['position'=>3,'label'=>'Segunda revelação','chemical_name'=>'Parodinal','calculated_dilution'=>'10+550','temperature'=>'26 °C','duration'=>'05:00','notes'=>'']
];
$sceneMedia=[];$resultMedia=[];
$messages=$mode==='empty'?[]:[
 ['author_role'=>'admin','body'=>'Sua anotação sobre a exposição foi recebida.','created_at'=>'2026-10-14 12:00:00'],
 ['author_role'=>'student','body'=>'Agradeço a observação; vou comparar o resultado.','created_at'=>'2026-10-14 13:00:00']
];
if($mode==='unsafe'){
  $record['title']='Título <script>alert(1)</script>';
  $record['student_name']='Nome <img src=x onerror=alert(1)>';
  $record['notes']='Resultado <script>alert(2)</script>';
  $messages[0]['body']='Resposta <svg onload=alert(3)>';
}
$_SERVER['REQUEST_URI']='/aluno/teste-compartilhado.php?id=103&cohort=cohort-2026';
student_shell_start((string)$record['title'].' · Compartilhado',null,['id'=>2,'name'=>'Visitante de teste']);
student_shared_record_view($record,$activity,$steps,$sceneMedia,$resultMedia,$messages,$id);
student_shell_end();
