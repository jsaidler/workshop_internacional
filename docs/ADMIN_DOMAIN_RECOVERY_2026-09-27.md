# Recuperação da administração de cursos e alunos — 27/09/2026

## Motivo

A migração que introduziu `courses` converteu vínculos transitórios (`workshop_page_id`) em cursos canônicos por inferência. A nova administração também passou a listar alunos somente por `course_id`. Como consequência, turmas e matrículas históricas ainda existentes no banco podem ficar invisíveis quando a turma não possui curso canônico, e mais de um curso pode ter sido criado sem decisão administrativa explícita.

Isso é um problema de autoridade de dados, não apenas de interface.

## Regra de recuperação

Nenhuma reconciliação automática pode:

- criar um curso por inferência histórica;
- apagar um curso por parecer duplicado;
- mover uma turma por semelhança de título;
- recriar ou duplicar `student_users`;
- converter importação histórica em inscrição fictícia.

Antes de qualquer alteração de vínculo, o estado instalado deve ser observado em modo somente leitura.

## Autoridades

- `student_users`: identidade global da pessoa;
- `cms_form_submissions`: inscrição original;
- `course_cohorts`: turma;
- `course_enrollments`: matrícula pessoa → turma;
- `courses`: curso canônico;
- `cms_pages`: conteúdo editorial;
- `student_import_batches`: registro histórico da importação para uma turma.

Uma pessoa deve permanecer administrativamente visível independentemente de sua turma ter ou não `course_id`.

## Administração

A área administrativa passa a separar:

- **Cursos**: contexto do curso e suas turmas, aulas, material e alunos matriculados;
- **Inscrições**: candidatos/inscrições no contexto do curso;
- **Pessoas**: identidades globais, com todas as inscrições e matrículas;
- **Integridade**: diagnóstico somente leitura dos vínculos atuais.

O nome **Área do aluno** deixa de representar uma área administrativa. Área do aluno é o produto acessado pelo estudante.

## Diagnóstico obrigatório

`/admin/data-integrity.php` deve mostrar, sem executar escrita:

- total de pessoas;
- total de matrículas;
- matrículas ativas cuja turma não possui curso;
- turmas sem curso;
- aulas sem curso;
- inscrições sem curso;
- todos os cursos e suas dependências;
- lotes de importação e a turma/curso a que pertencem.

Também fornece JSON do mesmo estado para análise técnica.

## Próxima etapa de reconciliação

Somente depois de observar o diagnóstico do banco instalado será criada uma migração de reconciliação. Ela deverá declarar explicitamente:

1. qual curso é canônico;
2. quais turmas devem ser vinculadas a ele;
3. quais aulas e materiais pertencem ao mesmo curso;
4. quais registros de curso são espúrios;
5. que um curso só pode ser removido depois de ficar sem dependências.

Nenhuma heurística por título, slug, página ancestral ou formulário é suficiente para executar essa etapa.

## Invariante de regressão

A tela global de Pessoas consulta `student_users` como autoridade. Ela não pode depender de `courses` para decidir se uma pessoa existe ou deve aparecer.
