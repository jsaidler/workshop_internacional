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
$bench=(string)file_get_contents($root.'/aluno/ferramentas.php');
$experience=(string)file_get_contents($root.'/app/student_experience.php');
$hardening=(string)file_get_contents($root.'/app/student_workbench_hardening.php');

must_student_workflow(str_contains($shell,'>Início</a>')&&str_contains($shell,'>Curso</a>')&&str_contains($shell,'>Caderno</a>'),'primary navigation lost task-centered destinations');
must_student_workflow(str_contains($shell,'data-student-toolbox')&&str_contains($shell,'data-toolbox-open'),'simple tools are not available as an integrated toolbox');
must_student_workflow(!str_contains($home,'Escolha o que você veio fazer'),'home still asks the student to classify their intent');
must_student_workflow(str_contains($home,'student_experience_process_state')&&str_contains($home,'Continuar'),'home no longer resumes the latest process');
must_student_workflow(str_contains($courses,'elseif(count($enrollments)===1)$selectedEnrollment=$enrollments[0]'),'single enrollment still requires an intermediate course click');
must_student_workflow(str_contains($notebook,'Mais ações')&&str_contains($notebook,'student_experience_process_state'),'notebook does not prioritize continuation over secondary actions');
must_student_workflow(!str_contains($notebook,'student-process-compare'),'notebook reintroduced permanent comparison controls');
must_student_workflow(str_contains($experience,"['stage_key']??'')==='dry'"),'drying is not recognized as the terminal processing state');
must_student_workflow(str_contains($hardening,'O processamento terminou na secagem. Registre o resultado.'),'server-side processing still permits steps after drying');
must_student_workflow(str_contains($record,"if(\$stageKey==='dry')")&&str_contains($record,"\$view='review'"),'drying does not send the workflow directly to result');
must_student_workflow(str_contains($record,'student-process-history')&&str_contains($record,'Desfazer última etapa'),'processing history/correction hierarchy regressed');
must_student_workflow(str_contains($record,'data-lab-timer'),'processing lost its contextual timer');
must_student_workflow(str_contains($bench,'data-exposure-tool')&&str_contains($bench,'data-quick-reciprocity')&&str_contains($bench,'data-lab-timer'),'workbench sends small tools back to separate pages');
must_student_workflow(str_contains($bench,'Modo de preparo')&&str_contains($bench,'student_experience_recipe_notes'),'recipe quantities and preparation instructions are separated again');
foreach(['exposicao.php'=>'#exposicao','reciprocidade.php'=>'#reciprocidade','temporizador.php'=>'#temporizador','preparo-solucoes.php'=>'#receitas'] as $file=>$anchor){$source=(string)file_get_contents($root.'/aluno/'.$file);must_student_workflow(str_contains($source,'/aluno/ferramentas.php'.$anchor),'legacy utility route '.$file.' no longer converges to integrated workbench');}
echo "student-workflow-experience: ok\n";
