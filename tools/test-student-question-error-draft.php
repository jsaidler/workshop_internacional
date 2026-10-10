<?php
declare(strict_types=1);

/**
 * Direct regression of the draft-normalization code executed by aluno/duvidas.php.
 * The browser fixture alone cannot certify server-side POST error handling.
 */
$source=file_get_contents(dirname(__DIR__).'/aluno/duvidas.php');
if(!is_string($source))throw new RuntimeException('Question page unavailable.');
$start=strpos($source,'$failedQuestionCreate=');
$end=$start===false?false:strpos($source,"student_shell_start('Dúvidas'",$start);
if($start===false||$end===false||$end<=$start)throw new RuntimeException('Question draft-state calculation missing.');
$stateCode=substr($source,$start,$end-$start);
$assert=static function(bool $valid,string $message): void {
    if(!$valid){fwrite(STDERR,"question-error-draft: FAIL — $message\n");exit(1);}
};
$simulate=static function(string $method,array $post,string $error,?array $sourceAnnotation)use($stateCode): array {
    $_SERVER['REQUEST_METHOD']=$method;
    $_POST=$post;
    eval($stateCode);
    return compact(
        'failedQuestionCreate','failedQuestionReply','questionDraftTopic',
        'questionDraftVisibility','questionDraftTitle','questionDraftBody',
        'questionDraftRecord','questionReplyDraft'
    );
};

$a=$simulate('GET',[],'',null);
$assert(!$a['failedQuestionCreate']&&!$a['failedQuestionReply'],'GET should not have a failed action');
$assert($a['questionDraftTopic']==='Exposição'&&$a['questionDraftVisibility']==='private','default form choices');
$assert($a['questionDraftBody']===''&&$a['questionDraftTitle']==='','new form starts empty');

$a=$simulate('GET',[],'',['body'=>'Trecho pré-preenchido']);
$assert($a['questionDraftTopic']==='Material','source annotation chooses material');
$assert($a['questionDraftBody']==='Trecho pré-preenchido','annotation body is retained');

$original='Rascunho com conteúdo detalhado, inclusive <script> e caracteres & especiais.';
$a=$simulate('POST',[
    'action'=>'create','topic'=>'Química','title'=>'Título da dúvida',
    'body'=>$original,'test_id'=>'23','visibility'=>'course'
],'Falha de publicação',null);
$assert($a['failedQuestionCreate']===true,'create error must reopen the form');
$assert($a['questionDraftTopic']==='Química','failed create must preserve topic');
$assert($a['questionDraftVisibility']==='course','failed create must preserve course sharing');
$assert($a['questionDraftTitle']==='Título da dúvida','failed create must preserve title');
$assert($a['questionDraftBody']===$original,'failed create must preserve exact text');
$assert($a['questionDraftRecord']===23,'failed create must preserve related record');

$a=$simulate('POST',['action'=>'create','topic'=>'invalid','visibility'=>'invalid','body'=>'rascunho'],'Erro',null);
$assert($a['questionDraftTopic']==='Exposição'&&$a['questionDraftVisibility']==='private','unrecognized choices must fall back safely');
$assert($a['questionDraftBody']==='rascunho','invalid choices must not clear the draft');

$a=$simulate('POST',['action'=>'reply','body'=>'Resposta que não posso perder.','question_id'=>42],'Falha de resposta',null);
$assert($a['failedQuestionReply']===true&&!$a['failedQuestionCreate'],'reply error scope');
$assert($a['questionReplyDraft']==='Resposta que não posso perder.','reply text is preserved');

$required=[
    'open create panel on error'=>'data-question-new-panel<?=$sourceAnnotation||$failedQuestionCreate',
    'expanded aria state on error'=>'aria-expanded="<?=$sourceAnnotation||$failedQuestionCreate',
    'escaped title'=>'value="<?=h($questionDraftTitle)?>"',
    'escaped body'=>'<?=h($questionDraftBody)?>',
    'escaped reply'=>'<?=h($questionReplyDraft)?>',
    'selected cohort scope'=>"$questionDraftVisibility==='cohort'",
    'selected course scope'=>"$questionDraftVisibility==='course'",
    'selected related record'=>'$questionDraftRecord===(int)$record',
    'accessible server error'=>'class="ui-alert ui-alert-error" role="alert"',
    'reply context on POST'=>"$_GET['id']??$_POST['question_id']"
];
foreach($required as $label=>$literal)$assert(str_contains($source,$literal),"template missing $label");
echo "question-error-draft: ok\n";
