<?php
declare(strict_types=1);
/** Render production authentication markup and student shell with controlled input; no real credentials or writes. */
require_once __DIR__.'/../../app/student_shell.php';
require_once __DIR__.'/../../app/student_auth_view.php';
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'fixture-csrf-not-authentication';}
function database(): PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_tools_for_student(PDO $db,int $id): array {return [];}
// student_shell_end() resolves the authenticated identity again; the fixture must
// project the same identity in both render phases, without creating a real session.
function student_account_current(PDO $db): ?array {global $mode;return $mode==='change'?['id'=>1,'name'=>'Aluno de teste']:null;}
function cms_design_defaults(): array {return ['colors'=>['bg'=>'#101010']];}
function cms_design_font_import_css(array $design): string {return '';}
function cms_design_css(array $design): string {return ':root{--bg:#101010;--surface:#171717;--surface-2:#202020;--text:#f3f1ec;--muted:#aaa49a;--line:#383633;--line-strong:#d5d0c7;--focus:#73d2ac;--inverse-bg:#f3f1ec;--inverse:#101010;--body:Arial,sans-serif;--title:Arial,sans-serif;--mono:"Courier New",monospace;--ux-space-1:4px;--ux-space-2:8px;--ux-space-3:12px;--ux-space-4:16px;--ux-space-5:24px;--ux-space-6:32px;--ux-space-7:48px}';}
$mode=(string)($_GET['mode']??'login');
if(!in_array($mode,['login','activation','change','login-error','activation-error','password-error'],true)){http_response_code(404);exit;}
$error=str_ends_with($mode,'-error')?'Falha de teste sem dados reais.':'';
$first=in_array($mode,['activation','password-error'],true);
$next='/aluno/';
if(in_array($mode,['login','login-error','activation-error'],true)){
    $_SERVER['REQUEST_URI']='/aluno/login.php';
    student_shell_start('Entrar · Área do aluno',null,null);
    student_auth_render_login($error,'',$mode==='activation-error'?'activate':'login',$next);
}else{
    $_SERVER['REQUEST_URI']='/aluno/senha.php'.($first?'?primeiro=1':'');
    $student=$first?null:['id'=>1,'name'=>'Aluno de teste'];
    student_shell_start(($first?'Ativar conta':'Alterar senha').' · Área do aluno',null,$student);
    student_auth_render_password($first,$error,$next);
}
student_shell_end();
