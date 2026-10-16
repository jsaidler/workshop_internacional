<?php
declare(strict_types=1);
require_once __DIR__.'/../../app/cms_settings.php';
/** Production questions view and shell with controlled projections only. No authentication, persistence, or authorization is simulated. */
require_once __DIR__.'/../../app/student_shell.php';
require_once __DIR__.'/../../app/student_question_annotations.php';
function h(mixed $v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function database(): PDO {static $db=null;return $db??=new PDO('sqlite::memory:');}
function student_account_current(PDO $db): ?array {return ['id'=>1,'name'=>'Aluno'];}
function student_tools_for_student(PDO $db,int $studentId): array {return [];}
function csrf_token(string $scope): string {return 'fixture-only-token';}
function student_enrollment_pages_for_enrollment(PDO $db,array $enrollment): array {return [['locale'=>'pt-BR']];}
function cms_page_url(array $activity,array $page,string $locale): string {return (int)($page['id']??0)===102?'/material/processo-quimico':'/material/exposicao';}
function student_enrollment_cohort_status_label(string $status): string {return $status==='closed'?'Turma encerrada':'Em andamento';}
function student_test_message_date(string $date): string {return '01/10/2026';}
function student_feedback_state(array $row): array {return ['label'=>'Revisão solicitada'];}
function student_experience_count(int $n,string $singular,string $plural): string {return $n.' '.($n===1?$singular:$plural);}
$cohort=(string)($_GET['cohort']??'cohort-a');
$cohort=in_array($cohort,['cohort-a','cohort-b'],true)?$cohort:'cohort-a';
$state=(string)($_GET['state']??'list');
$questionId=(int)($_GET['id']??0);
if($state==='thread'&&$questionId===0)$questionId=1;
$_SERVER['REQUEST_URI']='/aluno/duvidas.php?cohort='.rawurlencode($cohort).($questionId>0?'&id='.$questionId:'');
$db=database();
$student=['id'=>1,'name'=>'Aluno'];
$studentId=1;
$enrollment=['cohort_uuid'=>$cohort,'cohort_id'=>1,'course_title'=>$cohort==='cohort-a'?'Positivo direto em filme de raio-X':'O Fazer Intuitivo','cohort_title'=>$cohort==='cohort-a'?'Turma Outubro 2026':'Turma Setembro 2026','cohort_status'=>'active'];
$enrollments=[$enrollment];
$activity=['id'=>1];
if($state==='no-enrollment'){$enrollment=null;$enrollments=[['cohort_uuid'=>'cohort-a','course_title'=>'Positivo direto em filme de raio-X','cohort_title'=>'Turma Outubro 2026'],['cohort_uuid'=>'cohort-b','course_title'=>'O Fazer Intuitivo','cohort_title'=>'Turma Setembro 2026']];}
$error='';
$failedQuestionCreate=$state==='failed-create';
$failedQuestionReply=$state==='failed-reply'&&$questionId>0;
if($failedQuestionCreate)$error='Não foi possível publicar a dúvida. Revise os dados e tente novamente.';
if($failedQuestionReply)$error='Não foi possível enviar a resposta. O texto foi mantido.';
$validQuestionTopics=['Exposição','Filme','Química','Revelação','Material','Equipamento','Outro'];
$sourceAnnotation=$state==='source-draft'?['id'=>5,'page_id'=>102,'quote_exact'=>'Trecho marcado da segunda revelação.','body'=>'Por que o banho pode ser reutilizado?']:null;
$questionDraftTopic=$failedQuestionCreate?'Química':($sourceAnnotation?'Material':'Revelação');
$questionDraftVisibility=$failedQuestionCreate?'course':'private';
$questionDraftTitle=$failedQuestionCreate?'Dúvida sobre o banho':'';
$questionDraftBody=$failedQuestionCreate?'Meu rascunho sobre diluição e amônia precisa permanecer no formulário.':($sourceAnnotation?(string)$sourceAnnotation['body']:'');
$questionDraftRecord=$failedQuestionCreate?23:0;
$questionReplyDraft=$failedQuestionReply?'Minha resposta sobre segunda revelação continua aqui após a falha.':'';
$records=[['id'=>23,'title'=>'Registro de exposição do aluno']];
$evaluationThreads=$state==='evaluations'?[['id'=>17,'title'=>'Registro experimental avaliado','message_count'=>3]]:[];
$questions=[
 ['id'=>1,'status'=>'open','visibility'=>'cohort','title'=>'Como avaliar as sombras no positivo?','student_name'=>'Aluno','topic'=>'Exposição'],
 ['id'=>2,'status'=>'resolved','visibility'=>'private','title'=>'Dúvida sobre o banho de amônia','student_name'=>'Aluno','topic'=>'Química'],
 ['id'=>3,'status'=>'open','visibility'=>'course','title'=>'Exposição com filme ortocromático','student_name'=>'Aluno','topic'=>'Exposição'],
];
if($state==='empty')$questions=[];
if($state==='many')for($i=0;$i<28;$i++)$questions[]=['id'=>4+$i,'status'=>'open','visibility'=>'cohort','title'=>'Dúvida experimental longa '.str_pad((string)($i+1),2,'0',STR_PAD_LEFT),'student_name'=>'Aluno','topic'=>'Exposição'];
$question=$questionId>0?['id'=>$questionId,'student_id'=>1,'cohort_id'=>1,'status'=>'open','topic'=>'Revelação','title'=>'Segunda revelação','body'=>'Posso reutilizar o banho da primeira revelação?']:null;
$questionSource=in_array($state,['source-thread','foreign-source','page-note'],true)?['page_id'=>$state==='foreign-source'?901:102,'quote_exact'=>$state==='page-note'?'':'Trecho marcado da segunda revelação.']:null;
$sourceMaterialUrl='';
$fixturePages=$cohort==='cohort-a'?[['id'=>101,'locale'=>'pt-BR'],['id'=>102,'locale'=>'pt-BR']]:[['id'=>201,'locale'=>'pt-BR']];
$fixtureMaterialPage=student_question_source_page($questionSource??$sourceAnnotation,$fixturePages);
if($fixtureMaterialPage!==null)$sourceMaterialUrl=cms_page_url($activity,$fixtureMaterialPage,'pt-BR').'?cohort='.rawurlencode($cohort);
$messages=$question?[['author_role'=>'admin','student_name'=>'Professor','body'=>'Sim. Neste processo, a segunda revelação reutiliza o mesmo banho preparado para a primeira.','created_at'=>'2026-10-01T00:00:00Z']]:[];
$linkedTestUrl=$state==='linked-private'?'':($state==='linked-shared'?'/aluno/teste-compartilhado.php?id=103':'/aluno/teste.php?id=101');
student_shell_start('Dúvidas',null,$student);
require __DIR__.'/../../app/student_questions_view.php';
student_shell_end();
if($state==='new'):?><script>document.addEventListener('DOMContentLoaded',()=>document.querySelector('[data-question-new-toggle]')?.click());</script><?php endif;
