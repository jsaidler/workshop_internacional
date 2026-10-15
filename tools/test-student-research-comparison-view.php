<?php
declare(strict_types=1);
function h(mixed $v): string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string{return 'comparison-synthetic-csrf';}
function check_comparison(bool $pass,string $description): void{if(!$pass){fwrite(STDERR,"student-research-comparison-view: $description\n");exit(1);}}
require __DIR__.'/../app/student_research.php';
require __DIR__.'/../app/student_research_comparison_view.php';
$a=['id'=>10,'title'=>'EI 200 — FeCl₃ <script>','test_date'=>'2026-10-01','film'=>'Fuji HR-U','lot'=>'24A','iso_reference'=>'200','aperture'=>'f/64','calculated_time'=>'04 s','reciprocity_time'=>'07 s','light_condition'=>'janela','tonal_range'=>'sombras','context_scope'=>'personal','notes'=>'Resultado <&>'];
$b=$a;$b['id']=11;$b['title']='EI 400';$b['iso_reference']='400';$b['notes']='Resultado B';
$records=[$a,$b];$map=[10=>$a,11=>$b];
ob_start();student_research_compare_view($records,$map,[10],'');$select=(string)ob_get_clean();
check_comparison(str_contains($select,'Escolha o segundo registro')&&str_contains($select,'name="id[]"')&&str_contains($select,'value="10"'),'contextual second selection lost');
check_comparison(str_contains($select,'EI 200 — FeCl₃ &lt;script&gt;')&&!str_contains($select,'<script>'),'picked record name must be escaped');
$stepsA=[['label'=>'Primeira revelação','chemical_name'=>'Parodinal','calculated_dilution'=>'10+550','temperature'=>'26 °C','duration'=>'07:00']];
$stepsB=[['label'=>'Primeira revelação','chemical_name'=>'Parodinal','calculated_dilution'=>'10+550','temperature'=>'26 °C','duration'=>'06:30']];
$comparison=['a'=>$a,'b'=>$b,'stepsA'=>$stepsA,'stepsB'=>$stepsB,'differences'=>student_research_difference_rows($a,$b,$stepsA,$stepsB),'exposureRows'=>student_research_exposure_rows($a,$b),'resultMediaA'=>[],'resultMediaB'=>[]];
ob_start();student_research_compare_view($records,$map,[10,11],'',$comparison);$analysis=(string)ob_get_clean();
check_comparison(str_contains($analysis,'Diferenças registradas')&&str_contains($analysis,'Resultado observado')&&str_contains($analysis,'Conferir exposição e processamento'),'descriptive layers missing');
check_comparison(str_contains($analysis,'Etapa 1 · Tempo')&&str_contains($analysis,'EI')&&str_contains($analysis,'06:30')&&str_contains($analysis,'student-compare-column-name">Registro A')&&str_contains($analysis,'student-compare-column-name">Registro B'),'domain differences not represented');
check_comparison(str_contains($analysis,'name="_csrf" value="comparison-synthetic-csrf"')&&str_contains($analysis,'name="action" value="fork"')&&str_contains($analysis,'name="compare_id[]" value="10"'),'continuation POST authority broken');
check_comparison(str_contains($analysis,'não atribui causa')&&str_contains($analysis,'Processamento e resultado não são copiados como fatos já realizados.'),'comparison falsely implies causal attribution/executed steps');
ob_start();student_research_compare_view($records,$map,[10,11],'Falha <teste>',$comparison,'Mudar EI <400> & manter tempo',10);$failed=(string)ob_get_clean();
check_comparison(str_contains($failed,'Falha &lt;teste&gt;')&&!str_contains($failed,'Falha <teste>'),'error must be escaped');
check_comparison(str_contains($failed,'Mudar EI &lt;400&gt; &amp; manter tempo')&&!str_contains($failed,'Mudar EI <400>'),'draft intent must remain escaped');
check_comparison(str_contains($failed,'name="source_test_id" value="10" required checked'),'selected basis lost on failed submission');
ob_start();student_research_compare_view($records,$map,[10,11],'',$comparison);$get=(string)ob_get_clean();
check_comparison(!str_contains($get,'required checked')&&!str_contains($get,'Mudar EI'),'GET must not invent draft or selection');
$same=$comparison;$same['b']=$a;$same['b']['id']=11;$same['stepsB']=$stepsA;$same['differences']=student_research_difference_rows($a,$same['b'],$stepsA,$stepsA);$same['exposureRows']=student_research_exposure_rows($a,$same['b']);
ob_start();student_research_compare_view($records,$map,[10,11],'',$same);$equal=(string)ob_get_clean();
check_comparison(str_contains($equal,'Nenhuma diferença técnica detectada.')&&!str_contains($equal,'class="student-research-difference-row"'),'zero differences must not fabricate evidence');
ob_start();student_research_compare_view([$a],[10=>$a],[],'');$one=(string)ob_get_clean();
check_comparison(str_contains($one,'São necessários dois registros.'),'insufficient data must be explicit');
$route=(string)file_get_contents(__DIR__.'/../aluno/comparar-processos.php');
check_comparison(str_contains($route,'student_research_compare_view(')&&str_contains($route,'student_research_difference_rows(')&&str_contains($route,'student_research_fork(')&&str_contains($route,"verify_csrf('student-research-compare'"),'real route must own authorization, domain derivation and POST');
check_comparison(str_contains($route,'in_array($candidate,$ids,true)')&&str_contains($route,'is_scalar('),'invalid POST must not reinstate unowned basis or arbitrary input');
echo "student-research-comparison-view: ok\n";
