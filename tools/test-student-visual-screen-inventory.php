<?php
declare(strict_types=1);
function fail_student_visual_inventory(string $message): never {fwrite(STDERR,"student-visual-screen-inventory: $message\n");exit(1);}
function must_student_visual_inventory(bool $ok,string $message): void {if(!$ok)fail_student_visual_inventory($message);}
$root=dirname(__DIR__);
$rendered=[
'agenda.php','caderno.php','calibracao.php','comparar-processos.php','cursos.php','duvidas.php','excluir-teste.php','ferramentas.php','index.php','inventario-item.php','inventario.php','login.php','perfil.php','preparos.php','processamentos.php','processar.php','registro-roteiro.php','senha.php','teste-compartilhado.php','teste-etapa.php','teste.php',
];
$nonScreens=[
'exposicao.php','logout.php','material-anotacao.php','material.php','media.php','preparo-solucoes.php','reciprocidade.php','temporizador.php','teste-media.php','testes.php','visibilidade-teste.php','processamento-realizado.php','processamentos-trocar.php',
];
$actual=array_map('basename',glob($root.'/aluno/*.php')?:[]);sort($actual);$classified=array_merge($rendered,$nonScreens);sort($classified);
must_student_visual_inventory($actual===$classified,'every aluno/*.php file must be explicitly classified as rendered screen or non-screen endpoint');
$spec=(string)file_get_contents($root.'/tools/browser-tests/student-complete-area-audit.spec.cjs');
$requiredVisualNames=['login','home','home-feedback','course-list','course-detail','course-feedback','material','questions-list','questions-feedback','question-new','question-thread','notebook','new-record','exposure','process-choice','process-plan','process-partial','result','result-waiting','result-revision','result-reviewed','step-editor','shared-record','delete-record','compare-select','compare-records','research-derived','process-library','process-editor','lab-runner','inventory','inventory-empty','inventory-movement','inventory-item-editor','preparations','preparation-editor','calibration-list','calibration-editor','tools','toolbox','profile','password-change'];
foreach($requiredVisualNames as $name)must_student_visual_inventory(str_contains($spec,"['$name'"),'complete visual audit is missing '.$name);
$operationalSources=[
 'course-feedback'=>'student-courses.php?state=attention',
 'questions-feedback'=>'student-questions-operational.php?state=evaluations',
 'process-library'=>'student-process-saved-cards.php?mode=library',
 'profile'=>'student-profile.php?mode=view',
 'login'=>'student-auth-operational.php?mode=login',
 'activation-password'=>'student-auth-operational.php?mode=activation',
 'password-change'=>'student-auth-operational.php?mode=change',
 'preparations'=>'student-long-editors.php?kind=preparation&mode=list',
 'calibration-list'=>'student-long-editors.php?kind=calibration&mode=list',
 'step-editor'=>'student-record-actions-operational.php?mode=step',
 'delete-record'=>'student-record-actions-operational.php?mode=delete',
];
foreach($operationalSources as $name=>$source){
 must_student_visual_inventory(str_contains($spec,"['".$name."','".$source."']"),$name.' must use its operational PHP renderer');
 must_student_visual_inventory(!str_contains($spec,"['".$name."','student-secondary-screens-audit.html"),$name.' cannot regress to legacy fixture');
}
must_student_visual_inventory(str_contains($spec,'operationalScreens.has(name)')&&str_contains($spec,'anonymousAuthScreens.has(name)')&&str_contains($spec,'contract.links')&&str_contains($spec,'scrollGutter'),'operational screen audit must verify v2 shell without injected styles');
foreach(['process-intent','recording-start','recording-associated','recording-partial','recording-complete'] as $obsolete)must_student_visual_inventory(!str_contains($spec,"\n  ['$obsolete','"),'complete visual audit still preserves obsolete temporal surface '.$obsolete);
must_student_visual_inventory(str_contains($spec,"['inventory','student-inventory-movement.php?mode=inventory']")&&str_contains($spec,"['inventory-empty','student-inventory-movement.php?mode=empty']"),'inventory list/empty visual audit must use PHP fixture that includes operational movement renderer');
must_student_visual_inventory(str_contains($spec,"toBe(46)"),'complete visual audit surface count must match the non-linear product');
$cadernoSpec=(string)file_get_contents($root.'/tools/browser-tests/student-caderno-product-audit.spec.cjs');
must_student_visual_inventory(str_contains($cadernoSpec,"'route-picker'")&&str_contains($cadernoSpec,"'record-empty'")&&str_contains($cadernoSpec,"'record-partial'"),'non-linear Caderno audit must cover route selection and incomplete records');
must_student_visual_inventory(str_contains($cadernoSpec,"'record-plan-edit'")&&str_contains($cadernoSpec,"name:'Marcar ✓'")&&str_contains($cadernoSpec,"name:'Salvar etapa'"),'non-linear Caderno audit does not protect check/edit/timer advantages');
foreach(["'record-options'","'record-free-step'","'record-result-context'"] as $state)must_student_visual_inventory(str_contains($cadernoSpec,$state),'Caderno visual audit is missing disclosure state '.$state);
$workflow=(string)file_get_contents($root.'/.github/workflows/student-visual-audit.yml');
must_student_visual_inventory(str_contains($workflow,'student-complete-area-audit.spec.cjs'),'complete student visual audit is not executed by workflow');
must_student_visual_inventory(str_contains($workflow,'student-caderno-product-audit.spec.cjs'),'non-linear Caderno visual audit is not executed by workflow');
$doc=(string)file_get_contents($root.'/docs/STUDENT_VISUAL_SCREEN_INVENTORY_2026-10-02.md');
foreach($rendered as $file)must_student_visual_inventory(str_contains($doc,'`aluno/'.$file.'`')||in_array($file,['teste.php'],true),'screen inventory doc is missing '.$file);
must_student_visual_inventory(str_contains($doc,'46 superfícies por viewport'),'screen inventory does not declare the current non-linear coverage');
must_student_visual_inventory(str_contains($doc,'artefato inteiro deve ser aberto e observado'),'screen inventory must require human inspection');
echo "student-visual-screen-inventory: ok\n";
