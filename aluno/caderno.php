<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_record_create_dialog.php';
security_headers();
student_private_headers();

$db=database();
student_account_reconcile_confirmed_registrations($db);
$student=student_account_current($db);
if(!$student){
    header('Location: /aluno/login.php?next=%2Faluno%2Fcaderno.php',true,303);
    exit;
}

$studentId=(int)$student['id'];
$enrollments=student_enrollment_list($db,$studentId);
$error='';

if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-process-notebook',$_POST['_csrf']??null)){
        $error='Solicitação inválida.';
    }else{
        try{
            $action=(string)($_POST['action']??'create');
            if($action==='duplicate'){
                $record=student_process_duplicate($db,(int)($_POST['id']??0),$studentId);
                $_SESSION['student_process_notice']='Registro duplicado.';
                header('Location: /aluno/teste.php?id='.(int)$record['id'],true,303);
                exit;
            }
            if($action==='visibility'){
                student_test_set_visibility($db,(int)($_POST['id']??0),$studentId,(string)($_POST['visibility']??'private'));
                $_SESSION['student_process_notice']='Compartilhamento atualizado.';
                header('Location: /aluno/caderno.php',true,303);
                exit;
            }
            $record=student_process_create_record($db,$studentId,$_POST);
            header('Location: /aluno/teste.php?id='.(int)$record['id'],true,303);
            exit;
        }catch(Throwable $e){
            $error=$e->getMessage();
        }
    }
}

$notice=(string)($_SESSION['student_process_notice']??'');
unset($_SESSION['student_process_notice']);
$records=student_process_records_for_student($db,$studentId);
$shared=student_tests_shared_with_student($db,$studentId);

$failedCreate=$error!==''&&($_SERVER['REQUEST_METHOD']??'GET')==='POST'&&(string)($_POST['action']??'create')==='create';
student_shell_start('Caderno de Processos',null,$student);
?>
<?php require __DIR__.'/../app/student_notebook_view.php'; ?>

<?php
student_shell_end();

function student_process_date(string $value): string {
    $ts=strtotime($value);
    return $ts===false?$value:date('d/m/Y',$ts);
}

function student_notebook_has_exposure(array $record,array $sceneMedia): bool {
    if($sceneMedia)return true;
    foreach(['film','iso_reference','aperture','calculated_time','reciprocity_time','lot','light_condition','tonal_range'] as $key){
        if(trim((string)($record[$key]??''))!=='')return true;
    }
    return false;
}

function student_notebook_has_result(array $record,array $resultMedia): bool {
    if($resultMedia)return true;
    if(trim((string)($record['notes']??''))!=='')return true;
    return in_array((string)($record['status']??'draft'),['submitted','needs_revision','reviewed'],true);
}

function student_notebook_process_summary(array $steps,?array $plan,array $planSteps): string {
    $count=count($steps);
    if($count>0)return 'Processamento: '.student_experience_count($count,'etapa registrada','etapas registradas');
    if($plan){
        $completed=0;
        foreach($planSteps as $step)if((string)($step['status']??'')==='completed')$completed++;
        if($completed>0)return 'Processamento: '.$completed.' de '.count($planSteps).' etapas registradas';
        $name=trim((string)($plan['source_name']??''));
        return $name!==''?'Roteiro associado: '.$name:'Roteiro associado';
    }
    return 'Processamento ainda não registrado';
}
