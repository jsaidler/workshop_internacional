<?php
declare(strict_types=1);
function fail_student_visual_inventory(string $message): never {fwrite(STDERR,"student-visual-screen-inventory: $message\n");exit(1);}
function must_student_visual_inventory(bool $ok,string $message): void {if(!$ok)fail_student_visual_inventory($message);}
$root=dirname(__DIR__);
$rendered=[
'caderno.php','calibracao.php','comparar-processos.php','cursos.php','duvidas.php','excluir-teste.php','ferramentas.php','index.php','inventario-item.php','inventario.php','login.php','perfil.php','preparos.php','processamento-realizado.php','processamentos-trocar.php','processamentos.php','processar.php','senha.php','teste-compartilhado.php','teste-etapa.php','teste.php',
];
$nonScreens=[
'exposicao.php','logout.php','material-anotacao.php','material.php','media.php','preparo-solucoes.php','reciprocidade.php','temporizador.php','teste-media.php','testes.php','visibilidade-teste.php',
];
$actual=array_map('basename',glob($root.'/aluno/*.php')?:[]);sort($actual);$classified=array_merge($rendered,$nonScreens);sort($classified);
must_student_visual_inventory($actual===$classified,'every aluno/*.php file must be explicitly classified as rendered screen or non-screen endpoint');
$spec=(string)file_get_contents($root.'/tools/browser-tests/student-complete-area-audit.spec.cjs');
$requiredVisualNames=['login','home','course-list','course-detail','material','questions-list','question-new','question-thread','notebook','new-record','exposure','process-choice','process-plan','recording-start','recording-partial','recording-complete','result','step-editor','shared-record','delete-record','compare-records','process-library','process-editor','lab-runner','inventory','inventory-empty','inventory-movement','inventory-item-editor','preparations','preparation-editor','calibration-list','calibration-editor','tools','toolbox','profile','password-change'];
foreach($requiredVisualNames as $name)must_student_visual_inventory(str_contains($spec,"['$name'"),'complete visual audit is missing '.$name);
$replanSpec=(string)file_get_contents($root.'/tools/browser-tests/student-process-replanning-audit.spec.cjs');
must_student_visual_inventory(str_contains($replanSpec,"view==='choice'")&&str_contains($replanSpec,"view==='active'")&&str_contains($replanSpec,"view==='recorded'"),'route-change screen is not covered across factual and unconsolidated route states');
must_student_visual_inventory(!str_contains($replanSpec,"view==='interrupted'"),'visual audit must not preserve the obsolete interrupted-by-app state');
$workflow=(string)file_get_contents($root.'/.github/workflows/student-visual-audit.yml');
must_student_visual_inventory(str_contains($workflow,'student-complete-area-audit.spec.cjs'),'complete student visual audit is not executed by workflow');
must_student_visual_inventory(str_contains($workflow,'student-process-replanning-audit.spec.cjs'),'route-change visual audit is not executed by workflow');
$doc=(string)file_get_contents($root.'/docs/STUDENT_VISUAL_SCREEN_INVENTORY_2026-10-02.md');
foreach($rendered as $file)must_student_visual_inventory(str_contains($doc,'`aluno/'.$file.'`')||in_array($file,['teste.php'],true),'screen inventory doc is missing '.$file);
must_student_visual_inventory(str_contains($doc,'artefato inteiro deve ser aberto e observado'),'screen inventory must require human inspection');
echo "student-visual-screen-inventory: ok\n";
