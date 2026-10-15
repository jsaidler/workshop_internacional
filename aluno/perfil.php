<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
security_headers();student_private_headers();
$db=database();$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fperfil.php',true,303);exit;}
$error='';$notice='';
if(($_SERVER['REQUEST_METHOD']??'GET')==='POST'){
    if(!verify_csrf('student-profile',$_POST['_csrf']??null))$error='Solicitação inválida.';
    else try{student_account_update_profile($db,(int)$student['id'],$_POST);$student=student_account_current($db)??$student;$notice='Dados atualizados.';}catch(Throwable $e){$error=$e->getMessage();}
}
$profile=student_account_profile($db,(int)$student['id']);
if($error!==''){
    foreach(['name','email','cpf','phone','instagram','address','city_state','postal_code'] as $field){
        if(array_key_exists($field,$_POST)&&is_scalar($_POST[$field]))$profile[$field]=(string)$_POST[$field];
    }
}
require_once __DIR__.'/../app/student_profile_view.php';
student_shell_start('Conta · Área do aluno',null,$student);
student_profile_render($profile,$error,$notice);
student_shell_end();
