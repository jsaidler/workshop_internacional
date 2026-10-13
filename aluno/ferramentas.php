<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';security_headers();student_private_headers();$db=database();$student=student_account_current($db);if(!$student){header('Location: /aluno/login.php?next=%2Faluno%2Fferramentas.php',true,303);exit;}
$tools=student_tools_for_student($db,(int)$student['id']);$byKey=[];foreach($tools as $tool)$byKey[(string)$tool['tool_key']]=$tool;$formulas=isset($byKey['solution_prep'])?student_solution_formulas():[];$recipeNotes=isset($byKey['solution_prep'])?student_experience_recipe_notes():[];student_shell_start('Laboratório',null,$student);
require __DIR__.'/../app/student_laboratory_view.php';
student_shell_end();
