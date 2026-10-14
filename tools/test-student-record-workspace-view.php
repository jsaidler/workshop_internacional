<?php
declare(strict_types=1);
function record_workspace_assert(bool $ok,string $reason): void{if(!$ok){fwrite(STDERR,"student-record-workspace-view: $reason\n");exit(1);}}
function h(mixed $v): string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string{return 'synthetic-record-csrf';}
function student_test_status_label(string $status): string{return $status==='reviewed'?'Avaliação concluída':'Rascunho';}
function student_test_message_date(string $date): string{return '14/10/2026';}
define('STUDENT_TEST_MEDIA_MAX_FILES',6);
require __DIR__.'/../app/student_record_media_controls.php';
require __DIR__.'/../app/student_record_workspace_navigation.php';
require __DIR__.'/../app/student_process_stage_catalog.php';
require __DIR__.'/../app/student_experience.php';
require __DIR__.'/../app/student_record_workspace_view.php';
$record=['id'=>7,'title'=>'Teste <script>','status'=>'draft','context_scope'=>'personal','public_title'=>'','cohort_title'=>'','test_date'=>'2026-10-11','film'=>'Fuji HR-U','lot'=>'L1','iso_reference'=>'200','aperture'=>'f/64','calculated_time'=>'04 s','reciprocity_time'=>'07 s','light_condition'=>'Luz <teste>','tonal_range'=>'sombras agrupadas','notes'=>'Notas <teste>'];
$data=['record'=>$record,'id'=>7,'contextScope'=>'personal','recordDefaultPart'=>'exposicao','notice'=>'','error'=>'','locked'=>false,'sceneMedia'=>[],'media'=>[],'plan'=>null,'planCompleted'=>0,'planSteps'=>[],'editPlanStep'=>null,'stageCatalog'=>student_process_stage_catalog(),'steps'=>[],'firstDevelopment'=>null,'bleachNames'=>[],'resultMedia'=>[],'feedbackState'=>['kind'=>'none','label'=>''],'messages'=>[]];
ob_start();student_record_render_workspace($data);$html=(string)ob_get_clean();
record_workspace_assert(substr_count($html,'data-record-workspace-panel')===3&&str_contains($html,'data-record-workspace-nav'),'record must keep three independent contexts');
record_workspace_assert(str_contains($html,'Teste &lt;script&gt;')&&!str_contains($html,'Teste <script>')&&str_contains($html,'Luz &lt;teste&gt;')&&str_contains($html,'Notas &lt;teste&gt;'),'record fields must be escaped');
record_workspace_assert(str_contains($html,'name="action" value="save_exposure"')&&str_contains($html,'name="action" value="save_result"')&&str_contains($html,'name="action" value="add_free_step"'),'real exposure, result, independent step forms absent');
record_workspace_assert(str_contains($html,'name="_csrf" value="synthetic-record-csrf"')&&str_contains($html,'id="student-image-sheet-scene"')&&str_contains($html,'id="student-image-sheet-result"'),'CSRF or real shared image picker missing');
record_workspace_assert(str_contains($html,'href="/aluno/registro-roteiro.php?test=7"')&&str_contains($html,'Movimentar estoque'),'unassociated process choices absent');
record_workspace_assert(!str_contains($html,'O processamento já aconteceu?')&&!str_contains($html,'Em andamento'),'no artificial sequence state permitted');
record_workspace_assert(str_contains($html,'Registre o resultado quando quiser. Ele não depende de nenhuma etapa anterior.'),'result independence is mandatory');
$data['record']['status']='reviewed';$data['locked']=true;$data['contextScope']='course';$data['recordDefaultPart']='resultado';$data['feedbackState']=['kind'=>'reviewed','label'=>'Avaliação concluída'];
$data['messages']=[['author_role'=>'admin','body'=>'Professor <retorno>','created_at'=>'2026-10-14 10:00:00']];
ob_start();student_record_render_workspace($data);$locked=(string)ob_get_clean();
record_workspace_assert(!str_contains($locked,'>Salvar exposição</button>')&&!str_contains($locked,'>Salvar resultado</button>')&&!str_contains($locked,'>Enviar para avaliação</button>')&&!str_contains($locked,'data-student-capture-controls'),'reviewed record should not allow mutation');
record_workspace_assert(str_contains($locked,'Avaliação concluída')&&str_contains($locked,'Professor &lt;retorno&gt;')&&!str_contains($locked,'Professor <retorno>'),'reviewed feedback or escaping absent');
$data['record']['status']='needs_revision';$data['locked']=false;$data['feedbackState']=['kind'=>'revision','label'=>'Revisão solicitada'];$data['messages']=[];
ob_start();student_record_render_workspace($data);$revision=(string)ob_get_clean();
record_workspace_assert(str_contains($revision,'>Enviar revisão para avaliação</button>'),'revision submit path is missing');
$route=(string)file_get_contents(__DIR__.'/../aluno/teste.php');
record_workspace_assert(str_contains($route,'student_record_render_workspace(compact(')&&str_contains($route,'student_test_for_student(')&&str_contains($route,"verify_csrf('student-process-'")&&str_contains($route,"student_process_notebook_set_completed("),'route must own authorization, POST and processing facts');
echo "student-record-workspace-view: ok\n";
