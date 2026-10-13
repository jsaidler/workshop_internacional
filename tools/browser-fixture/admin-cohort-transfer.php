<?php
declare(strict_types=1);
/* Actual admin transfer renderer with synthetic data; backend mutations
 * are tested using SQLite in tools/test-admin-cohort-transfer.php. */
function h(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope):string{return 'fixture-token-'.$scope;}
require_once dirname(__DIR__,2).'/app/admin_shell.php';
require_once dirname(__DIR__,2).'/app/admin_student_cohort_transfer_panel.php';
$mode=(string)($_GET['mode']??'manual');
if(!in_array($mode,['manual','registration','no-target'],true)){http_response_code(400);exit('Invalid scenario');}
$activity=['id'=>1,'admin_name'=>'Fotografia','is_root'=>1,'slug'=>''];
$state=['activity'=>$activity];
$selected=['student_id'=>8,'enrollment_id'=>41,'name'=>'Aluno Exemplo','email'=>'aluno@example.test','cohort_id'=>20,'cohort_title'=>'Turma de setembro','source_submission_id'=>$mode==='registration'?88:null];
$options=[['id'=>20,'title'=>'Turma de setembro','status'=>'closed'],['id'=>21,'title'=>'Turma de outubro','status'=>$mode==='no-target'?'archived':'active'],['id'=>22,'title'=>'Turma arquivada','status'=>'archived'],['id'=>23,'title'=>'Turma de novembro','status'=>$mode==='no-target'?'closed':'active']];
admin_shell_start('students','Alunos',$state,['/assets/admin-operations.css','/assets/admin-teaching.css']);
?><section class="admin-card admin-operation-detail" aria-label="Detalhe da participação">
<header><div><p class="admin-kicker">Aluno</p><h1><?=h($selected['name'])?></h1><p><?=h($selected['email'])?></p></div></header>
<dl class="admin-teaching-summary"><div><dt>Turma</dt><dd><?=h($selected['cohort_title'])?></dd></div><div><dt>Tipo de participação</dt><dd><?=$mode==='registration'?'Inscrição':'Matrícula manual/CSV'?></dd></div></dl>
<?php admin_student_cohort_transfer_panel($selected,$options,1,11,20,8); ?>
</section>
<?php admin_shell_end(); ?>
