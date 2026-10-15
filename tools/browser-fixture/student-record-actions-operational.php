<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/cms_settings.php';
/** Render actual student record action views in the canonical shell. All identities/data are synthetic; no writes. */
require_once __DIR__.'/../../app/student_shell.php';
require_once __DIR__.'/../../app/student_record_auxiliary_views.php';
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'synthetic-action-csrf';}
function database(): PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_account_current(PDO $db): ?array {return ['id'=>1,'name'=>'Aluno de teste'];}
function student_tools_for_student(PDO $db,int $id): array {return [];}
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
