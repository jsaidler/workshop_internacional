<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fcaderno.php',true,303);exit;}$studentId=(int)$student['id'];
$ids=array_values(array_unique(array_filter(array_map('intval',(array)($_GET['id']??$_POST['compare_id']??[])),static fn(int $id): bool=>$id>0)));if(count($ids)>2)$ids=array_slice($ids,0,2);
$allRecords=student_process_records_for_student($db,$studentId);$recordMap=[];foreach($allRecords as $item)$recordMap[(int)$item['id']]=$item;
foreach($ids as $id)if(!isset($recordMap[$id])){http_response_code(404);exit('Registro não encontrado.');}
$error='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-research-compare',$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{
        $action=(string)($_POST['action']??'');
        if($action!=='fork')throw new RuntimeException('Ação inválida.');
        $baseId=(int)($_POST['source_test_id']??0);if(!in_array($baseId,$ids,true))throw new RuntimeException('Escolha um dos registros comparados como base.');
        $intent=(string)($_POST['research_intent']??'');$created=student_research_fork($db,$baseId,$studentId,$intent);$base=$recordMap[$baseId];
        $_SESSION['student_process_notice']='Próxima variação criada a partir de “'.(string)$base['title'].'”. Intenção: '.(string)$created['research_intent'].' Revise a exposição antes de continuar.';
        header('Location: /aluno/teste.php?id='.(int)$created['id'].'&view=exposure',true,303);exit;
    }catch(Throwable $e){$error=$e->getMessage();}
}

$comparison=null;
if(count($allRecords)>=2&&count($ids)===2){
  $a=$recordMap[$ids[0]];$b=$recordMap[$ids[1]];
  $stepsA=student_process_steps($db,(int)$a['id']);$stepsB=student_process_steps($db,(int)$b['id']);
  $comparison=[
    'a'=>$a,'b'=>$b,'stepsA'=>$stepsA,'stepsB'=>$stepsB,
    'differences'=>student_research_difference_rows($a,$b,$stepsA,$stepsB),
    'exposureRows'=>student_research_exposure_rows($a,$b),
    'resultMediaA'=>student_test_media_by_phase(student_test_media($db,(int)$a['id']),'result'),
    'resultMediaB'=>student_test_media_by_phase(student_test_media($db,(int)$b['id']),'result'),
  ];
}
require_once __DIR__.'/../app/student_research_comparison_view.php';
student_shell_start('Comparar experimentações',null,$student);
student_research_compare_view($allRecords,$recordMap,$ids,$error,$comparison);
student_shell_end();
