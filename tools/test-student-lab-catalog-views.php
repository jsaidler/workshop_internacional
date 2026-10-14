<?php
declare(strict_types=1);
function must_lab_catalog(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"student-lab-catalog-views: $message\n");exit(1);}}
function h(mixed $value): string {return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_token(string $scope): string {return 'catalog-fixture-csrf';}
function student_experience_count(int $n,string $one,string $many): string {return $n.' '.($n===1?$one:$many);}
function student_process_dilution(?float $developer,?float $water,string $mode): array {return ['label'=>'1+54'];}
function student_workbench_number(float $value): string {return (string)$value;}
require __DIR__.'/../app/student_lab_catalog_views.php';
$prepRow=['id'=>7,'label'=>'Predefinição <script>','developer_name'=>'Parodinal','developer_amount'=>10,'water_amount'=>540,
 'preparation_mode'=>'concentrate','temperature'=>'26 °C','duration'=>'07:00','agitation'=>'leve'];
ob_start();student_lab_preparations_view([$prepRow],null,[],[],'',false);$html=(string)ob_get_clean();
must_lab_catalog(str_contains($html,'Predefinição &lt;script&gt;')&&!str_contains($html,'Predefinição <script>'),'prepared data is not escaped');
must_lab_catalog(str_contains($html,'name="action" value="delete"')&&str_contains($html,'name="_csrf" value="catalog-fixture-csrf"'),'canonical preparation delete POST must retain CSRF');
must_lab_catalog(str_contains($html,'Ao usar esta predefinição, estes parâmetros são copiados')&&str_contains($html,'Abrir inventário'),'preparations must distinguish parameters from physical stock');
must_lab_catalog(str_contains($html,'href="/aluno/preparos.php?editar=7"'),'preparation editor deep link missing');
ob_start();student_lab_preparations_view([],null,[],[],'',false);$empty=(string)ob_get_clean();
must_lab_catalog(str_contains($empty,'Você ainda não salvou nenhuma condição de revelação.')&&str_contains($empty,'Criar primeira predefinição'),'empty preparation catalog lacks next action');
$calRow=['id'=>5,'label'=>'Referência <script>','film'=>'Fuji HR-U','developer'=>'Parodinal','preparation'=>'10/550','temperature'=>'26 °C','white_time'=>'03:00'];
ob_start();student_lab_calibrations_view([$calRow],null,'',false);$cal=(string)ob_get_clean();
must_lab_catalog(str_contains($cal,'Referência &lt;script&gt;')&&!str_contains($cal,'Referência <script>'),'calibration data not escaped');
must_lab_catalog(str_contains($cal,'03:00')&&str_contains($cal,'href="/aluno/calibracao.php?editar=5"'),'calibration timing or editor navigation missing');
ob_start();student_lab_calibrations_view([],null,'',false);$emptyCal=(string)ob_get_clean();
must_lab_catalog(str_contains($emptyCal,'Nenhuma calibração salva.')&&str_contains($emptyCal,'Nova referência'),'calibration empty state missing');
foreach(['preparos.php'=>'student_lab_preparations_view','calibracao.php'=>'student_lab_calibrations_view'] as $route=>$render){
 $source=(string)file_get_contents(__DIR__.'/../aluno/'.$route);
 must_lab_catalog(str_contains($source,$render.'(')&&str_contains($source,'verify_csrf('),'production route must use shared view and preserve CSRF');
}
echo "student-lab-catalog-views: ok\n";
