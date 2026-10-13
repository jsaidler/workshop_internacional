<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/student_process_saved_card.php';
function h(string $value):string{return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function check_saved_card(bool $ok,string $message):void{
 if(!$ok){fwrite(STDERR,'student-process-saved-card: '.$message."\n");exit(1);}
}
$item=['id'=>14,'name'=>'Roteiro <script>','description'=>'Primeira e segunda revelação','step_count'=>9];
ob_start();student_process_saved_card($item,'/aluno/processamentos.php?id=14','19:30','token-csrf',0);$view=(string)ob_get_clean();
check_saved_card(!str_contains($view,'Roteiro <script>')&&str_contains($view,'Roteiro &lt;script&gt;'),'untrusted title must be escaped');
check_saved_card(str_contains($view,'data-process-saved-card')&&str_contains($view,'data-process-card-options'),'operational card and native disclosure are required');
check_saved_card(strpos($view,'Abrir no laboratório')<strpos($view,'Mais opções'),'runner action must precede maintenance');
foreach(['Editar roteiro','Duplicar','Excluir'] as $action)check_saved_card(str_contains($view,$action),'maintenance action lost: '.$action);
foreach(['duplicate','delete'] as $action)check_saved_card(str_contains($view,'name="action" value="'.$action.'"'),'mutation POST lost: '.$action);
check_saved_card(substr_count($view,'value="token-csrf"')===2,'mutations must retain independent CSRF tokens');
check_saved_card(str_contains($view,'onsubmit="return confirm('),'delete confirmation must remain explicit');
ob_start();student_process_saved_card($item,'/aluno/processamentos.php?id=14&test=7','19:30','token-csrf',7);$association=(string)ob_get_clean();
check_saved_card(str_contains($association,'Associar ao registro')&&str_contains($association,'name="action" value="apply_template"'),'association action must be preserved');
check_saved_card(!str_contains($association,'name="action" value="delete"')&&!str_contains($association,'data-process-card-options'),'association context must not expose management');
$empty=$item;$empty['step_count']=0;
ob_start();student_process_saved_card($empty,'/aluno/processamentos.php?id=14','Sem tempo','token-csrf',0);$noSteps=(string)ob_get_clean();
check_saved_card(str_contains($noSteps,'Adicione uma etapa')&&str_contains($noSteps,'aria-disabled="true"'),'route without steps must not start timer');
$route=(string)file_get_contents(dirname(__DIR__).'/aluno/processamentos.php');
check_saved_card(str_contains($route,"require_once __DIR__.'/../app/student_process_saved_card.php'")&&str_contains($route,'student_process_saved_card($item,$href'),'authenticated library must consume the single card renderer');
echo "student-process-saved-card: ok\n";
