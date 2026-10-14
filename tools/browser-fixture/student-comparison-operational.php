<?php
declare(strict_types=1);
/** Actual comparison PHP markup and real descriptive algorithms, synthetic owned records; no POST or persistence. */
require_once __DIR__.'/../../app/student_shell.php';
require_once __DIR__.'/../../app/student_research.php';
require_once __DIR__.'/../../app/student_research_comparison_view.php';
function h(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'controlled-comparison-csrf';}
function database(): PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_account_current(PDO $db): ?array {return ['id'=>1,'name'=>'Aluno de teste'];}
function student_tools_for_student(PDO $db,int $id): array {return [];}
function cms_design_defaults(): array {return ['colors'=>['bg'=>'#101010']];}
function cms_design_font_import_css(array $design): string {return '';}
function cms_design_css(array $design): string {return ':root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Arial,sans-serif;--mono:"Courier New",monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px}';}
$mode=(string)($_GET['mode']??'select');
if(!in_array($mode,['select','analysis','no-difference','error','insufficient'],true)){http_response_code(404);exit;}
$a=['id'=>10,'title'=>'EI 200 — FeCl₃','test_date'=>'2026-10-01','film'=>'Fuji HR-U','lot'=>'24A','iso_reference'=>'200','aperture'=>'f/64','calculated_time'=>'04 s','reciprocity_time'=>'07 s','light_condition'=>'Luz de janela','tonal_range'=>'Sombras agrupadas','context_scope'=>'personal','public_title'=>'','cohort_title'=>'','notes'=>'Positivo direto com altas luzes preservadas'];
$b=['id'=>11,'title'=>'EI 400 — FeCl₃','test_date'=>'2026-10-02','film'=>'Fuji HR-U','lot'=>'24A','iso_reference'=>'400','aperture'=>'f/64','calculated_time'=>'02 s','reciprocity_time'=>'04 s','light_condition'=>'Luz de janela','tonal_range'=>'Sombras agrupadas','context_scope'=>'personal','public_title'=>'','cohort_title'=>'','notes'=>'Sombras mais profundas e outra resposta nas altas luzes'];
$stepsA=[['label'=>'Primeira revelação','chemical_name'=>'Parodinal','calculated_dilution'=>'10+550','total_volume'=>'560','amount_unit'=>'ml','temperature'=>'26 °C','duration'=>'07:00','agitation'=>'inversões leves']];
$stepsB=[['label'=>'Primeira revelação','chemical_name'=>'Parodinal','calculated_dilution'=>'10+550','total_volume'=>'560','amount_unit'=>'ml','temperature'=>'26 °C','duration'=>'06:30','agitation'=>'inversões leves']];
if($mode==='no-difference'){
    foreach(['test_date','film','lot','iso_reference','aperture','calculated_time','reciprocity_time','light_condition','tonal_range'] as $field)$b[$field]=$a[$field];
    $stepsB=$stepsA;
}
$records=$mode==='insufficient'?[$a]:[$a,$b];
$map=[];foreach($records as $record)$map[(int)$record['id']]=$record;
$ids=in_array($mode,['analysis','no-difference','error'],true)?[10,11]:($mode==='select'?[10]:[]);
$comparison=null;
if(count($ids)===2){
    $comparison=['a'=>$a,'b'=>$b,'stepsA'=>$stepsA,'stepsB'=>$stepsB,
        'differences'=>student_research_difference_rows($a,$b,$stepsA,$stepsB),
        'exposureRows'=>student_research_exposure_rows($a,$b),
        'resultMediaA'=>[],'resultMediaB'=>[]];
}
$_SERVER['REQUEST_URI']='/aluno/comparar-processos.php';
student_shell_start('Comparar experimentações',null,['id'=>1,'name'=>'Aluno de teste']);
student_research_compare_view($records,$map,$ids,$mode==='error'?'Não foi possível criar variação <teste>.':'',$comparison);
student_shell_end();
