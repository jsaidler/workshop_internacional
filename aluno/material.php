<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/student_auth.php';
$student=current_student();$admin=current_admin();
if(!$student&&!$admin){$return=student_safe_return_url((string)($_SERVER['REQUEST_URI']??'/aluno/'));header('Location: /aluno/login.php?return='.rawurlencode($return),true,303);exit;}
if($student&&(int)$student['must_change_password']===1&&!$admin){header('Location: /aluno/senha.php',true,303);exit;}
$uuid=trim((string)($_GET['m']??''));$material=$uuid!==''?student_material_for_view(database(),$uuid,$student,$admin):null;
if(!$material){student_security_headers();http_response_code(404);?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="robots" content="noindex"><title>Material não encontrado</title></head><body><p>Material não encontrado ou não disponível para esta conta.</p><p><a href="/aluno/">Voltar à área do aluno</a></p></body></html><?php exit;}
student_material_headers();echo (string)$material['html_content'];
