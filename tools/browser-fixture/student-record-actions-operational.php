<?php
declare(strict_types=1);
/** Render actual student record action views in the canonical shell. All identities/data are synthetic; no writes. */
require_once __DIR__.'/../../app/student_shell.php';
require_once __DIR__.'/../../app/student_record_auxiliary_views.php';
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'synthetic-action-csrf';}
function database(): PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_account_current(PDO $db): ?array {return ['id'=>1,'name'=>'Aluno de teste'];}
function student_tools_for_student(PDO $db,int $id): array {return [];}
function cms_design_defaults(): array {return ['colors'=>['bg'=>'#101010']];}
function cms_design_font_import_css(array $design): string {return '';}
function cms_design_css(array $design): string {return ':root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Arial,sans-serif;--mono:"Courier New",monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px}';}
$mode=(string)($_GET['mode']??'step');
$valid=['step','step-error','step-locked','delete','delete-error'];
if(!in_array($mode,$valid,true)){http_response_code(404);exit;}
$student=['id'=>1,'name'=>'Aluno de teste'];
if(str_starts_with($mode,'step')){
    $_SERVER['REQUEST_URI']='/aluno/teste-etapa.php?registro=7&etapa=1';
    $step=['id'=>8,'label'=>'Primeira revelação','chemical_name'=>'Brewed Caffenol','duration'=>'05:00','temperature'=>'35,7 °C','agitation'=>'Inversões leves','notes'=>'Revelação realizada'];
    $error=$mode==='step-error'?'Não foi possível salvar <dados>.':'';
    $locked=$mode==='step-locked';
    student_shell_start('Editar etapa · Teste EI 400',null,$student);
    $draft=$mode==='step-error'?['label'=>'Primeira revelação corrigida','duration'=>'06:20','notes'=>'Rascunho não salvo <teste>']:[];
    student_record_render_free_step_editor($step,7,1,$locked,$error,'Brewed Caffenol',$draft);
}else{
    $_SERVER['REQUEST_URI']='/aluno/excluir-teste.php?id=7';
    $record=['id'=>7,'title'=>'Teste EI 400 · FeCl₃'];
    $error=$mode==='delete-error'?'Solicitação inválida <teste>.':'';
    student_shell_start('Excluir registro',null,$student);
    student_record_render_delete_confirmation($record,7,$error);
}
student_shell_end();
