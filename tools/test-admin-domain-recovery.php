<?php
declare(strict_types=1);

$root=dirname(__DIR__);
function recovery_expect(bool $ok,string $message): void {if(!$ok){fwrite(STDERR,"FAIL: $message\n");exit(1);}}

$people=(string)file_get_contents($root.'/admin/people.php');
$students=(string)file_get_contents($root.'/admin/students.php');
$integrity=(string)file_get_contents($root.'/admin/data-integrity.php');
$legacy=(string)file_get_contents($root.'/admin/student-area.php');
$shell=(string)file_get_contents($root.'/app/admin_shell.php');
$doc=(string)file_get_contents($root.'/docs/ADMIN_DOMAIN_RECOVERY_2026-09-27.md');

recovery_expect(str_contains($people,'FROM student_users u'),'Histórico completo deve continuar consumindo student_users diretamente.');
recovery_expect(str_contains($people,'c.course_id IS NULL'),'Histórico completo deve tornar matrículas sem curso visíveis.');
recovery_expect(str_contains($people,'Importação/manual'),'Histórico completo deve distinguir matrícula histórica de inscrição.');
recovery_expect(str_contains($students,'Busca global de pessoas com histórico de participação'),'Alunos deve ser a entrada administrativa para identidade e participação.');
recovery_expect(str_contains($students,'Ver histórico completo'),'A identidade global deve continuar acessível como detalhe secundário.');
recovery_expect(str_contains($integrity,'Somente leitura'),'Diagnóstico precisa declarar seu contrato somente leitura.');
recovery_expect(str_contains($integrity,'orphan_enrollments'),'Diagnóstico deve contar matrículas órfãs de curso.');
recovery_expect(str_contains($integrity,'student_import_batches'),'Diagnóstico deve preservar visibilidade das importações históricas.');
recovery_expect(!str_contains($integrity,"REQUEST_METHOD"),'Diagnóstico não deve possuir fluxo POST.');
recovery_expect(str_contains($legacy,"default=>'/admin/students.php'"),'Rota administrativa legada deve levar ao workspace atual de alunos.');
recovery_expect(str_contains($shell,"'students'=>['Alunos'"),'Navegação deve expor Alunos como entrada global.');
recovery_expect(!str_contains($shell,"'people'=>['Pessoas'"),'Navegação global não deve obrigar o operador a distinguir Pessoa de Aluno.');
recovery_expect(str_contains($shell,"'integrity'=>['Integridade'"),'Navegação deve expor Integridade.');
recovery_expect(!str_contains($shell,"'students'=>['Área do aluno'"),'Área do aluno não deve continuar como entidade administrativa.');
recovery_expect(str_contains($doc,'Nenhuma reconciliação automática'),'Documento deve proibir reconciliação inferencial.');

echo "Admin domain recovery regression passed.\n";
