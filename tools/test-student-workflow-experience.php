<?php
declare(strict_types=1);
function fail_student_workflow(string $message): never {fwrite(STDERR,"student-workflow-experience: $message\n");exit(1);}
function must_student_workflow(bool $ok,string $message): void {if(!$ok)fail_student_workflow($message);}
$root=dirname(__DIR__);
$shell=(string)file_get_contents($root.'/app/student_shell.php');
$home=(string)file_get_contents($root.'/aluno/index.php');
$courses=(string)file_get_contents($root.'/aluno/cursos.php');
$notebook=(string)file_get_contents($root.'/aluno/caderno.php');
$record=(string)file_get_contents($root.'/aluno/teste.php');
$routePicker=(string)file_get_contents($root.'/aluno/registro-roteiro.php');
$bench=(string)file_get_contents($root.'/aluno/ferramentas.php');
$processManager=(string)file_get_contents($root.'/aluno/processamentos.php');
$processRunner=(string)file_get_contents($root.'/aluno/processar.php');
$experience=(string)file_get_contents($root.'/app/student_experience.php');
$hardening=(string)file_get_contents($root.'/app/student_workbench_hardening.php');

must_student_workflow(str_contains($shell,'>Início</a>')&&str_contains($shell,'>Curso</a>')&&str_contains($shell,'>Caderno</a>'),'primary navigation lost task-centered destinations');
must_student_workflow(str_contains($shell,'data-student-toolbox')&&str_contains($shell,'data-toolbox-open'),'simple tools are not available as an integrated toolbox');
must_student_workflow(!str_contains($home,'Escolha o que você veio fazer'),'home still asks the student to classify their intent');
must_student_workflow(str_contains($home,'student_experience_dashboard_state')&&str_contains($home,'Continuar no Caderno'),'home no longer prioritizes unfinished Caderno work');
must_student_workflow(str_contains($courses,'elseif(count($enrollments)===1)$selectedEnrollment=$enrollments[0]'),'single enrollment still requires an intermediate course click');
must_student_workflow(str_contains($notebook,'Mais ações')&&str_contains($notebook,'student-record-summary'),'notebook does not prioritize opening the record over secondary actions');
must_student_workflow(!str_contains($notebook,'student-process-compare'),'notebook reintroduced permanent comparison controls');
must_student_workflow(!str_contains($notebook,'student-record-progress'),'notebook reintroduced step-machine progression');
must_student_workflow(str_contains($experience,"['stage_key']??'')==='dry'"),'drying is not recognized as the terminal physical processing state');
must_student_workflow(str_contains($hardening,'O processamento terminou na secagem. Registre o resultado.'),'server-side processing still permits factual steps after drying');
must_student_workflow(str_contains($record,'id="exposicao"')&&str_contains($record,'id="processamento"')&&str_contains($record,'id="resultado"'),'record no longer exposes all documentary sections together');
must_student_workflow(!str_contains($record,"\$view='review'")&&!str_contains($record,'Salvar exposição e continuar'),'record navigation is again coupled to process progression');
must_student_workflow(str_contains($record,'student-process-history')&&str_contains($record,'Desfazer última etapa'),'processing history/correction hierarchy regressed');
must_student_workflow(str_contains($routePicker,'student_process_replan_template')&&str_contains($routePicker,'student_process_replan_standard'),'route changes no longer use the preserved-fact replanning service');
must_student_workflow(str_contains($record,'/aluno/processar.php?test=<?=$id?>&amp;intent=live'),'record lost the explicit contextual path into laboratory mode');
must_student_workflow(str_contains($bench,'data-exposure-tool')&&str_contains($bench,'data-quick-reciprocity'),'small exposure tools were sent back to separate pages');
must_student_workflow(!str_contains($bench,'data-lab-timer')&&str_contains($bench,'/aluno/processamentos.php'),'multi-stage processing regressed to the isolated inline timer');
must_student_workflow(str_contains($processManager,'student-process-step-list')&&str_contains($processRunner,'data-process-runner'),'multi-stage processing is missing its manager or laboratory execution surface');
must_student_workflow(str_contains($bench,'Modo de preparo')&&str_contains($bench,'student_experience_recipe_notes'),'recipe quantities and preparation instructions are separated again');
foreach(['exposicao.php'=>'#exposicao','reciprocidade.php'=>'#reciprocidade','preparo-solucoes.php'=>'#receitas'] as $file=>$anchor){$source=(string)file_get_contents($root.'/aluno/'.$file);must_student_workflow(str_contains($source,'/aluno/ferramentas.php'.$anchor),'legacy utility route '.$file.' no longer converges to integrated workbench');}
$legacyTimer=(string)file_get_contents($root.'/aluno/temporizador.php');
must_student_workflow(str_contains($legacyTimer,'/aluno/processamentos.php'),'legacy timer route must converge to the process manager');
echo "student-workflow-experience: ok\n";
