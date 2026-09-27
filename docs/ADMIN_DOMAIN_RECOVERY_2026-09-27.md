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

## Diagnóstico de produção observado

O diagnóstico exportado em `2026-09-27T23:07:30+00:00` eliminou a ambiguidade necessária para a reconciliação:

- 9 pessoas;
- 11 matrículas no total, 9 ativas;
- 5 matrículas ativas órfãs de curso;
- 2 cursos ativos;
- 1 turma órfã;
- nenhuma aula órfã;
- nenhuma inscrição sem curso;
- 1 lote de importação.

A distribuição observada é estruturalmente inequívoca:

1. **Curso #1** — `Positivo Direto em Filme de Raios X`
   - página pública #1;
   - formulário de inscrição #1;
   - 3 turmas;
   - 4 matrículas ativas;
   - 4 inscrições;
   - nenhuma aula e nenhum material associados após a divisão indevida.
2. **Curso #2** — `Positivo direto em filme de raio-X`
   - página #6 (`caderno-positivo-direto`);
   - o mesmo formulário #1;
   - nenhuma turma;
   - nenhuma matrícula;
   - nenhuma inscrição;
   - 3 aulas;
   - 1 página de material.
3. **Turma #4** — `26-09-quintas`
   - sem curso;
   - 5 matrículas ativas;
   - exatamente 1 lote de importação histórica;
   - nenhuma inscrição de formulário como origem dessas matrículas.

Portanto, o estado correto não é dois cursos. O curso #2 é o fragmento de conteúdo criado quando a página CMS de material #6 foi promovida indevidamente a curso. A turma #4 é uma turma real do curso #1 que ficou fora do novo escopo por ter sido importada antes da associação canônica.

## Reconciliação determinística autorizada

A migração `074_reconcile_positive_course_snapshot.php` executa somente quando os IDs e relações acima ainda correspondem ao diagnóstico verificado. Ela não usa título, slug semelhante, ancestralidade editorial ou formulário compartilhado para inferir relações novas.

A operação é:

1. manter **curso #1** como curso canônico;
2. associar **turma #4** ao curso #1 e à página pública #1, sem transformá-la em turma padrão de novas inscrições;
3. mover as 3 aulas do curso #2 para o curso #1 preservando os IDs das aulas;
4. mover a relação da página CMS #6 e seus mapas seção → aula para o curso #1, sem copiar ou recriar conteúdo;
5. completar apenas as linhas ausentes de `cohort_lesson_releases` com `INSERT OR IGNORE`, preservando qualquer liberação já existente;
6. arquivar o curso #2 somente depois de ele ficar sem turmas, inscrições, aulas ou material;
7. limpar `public_page_id` e `registration_form_id` do registro arquivado para que a página #6 volte a ser apenas uma página CMS de material e o formulário #1 pertença somente ao curso canônico.

O registro #2 não é apagado fisicamente. A recuperação prioriza rastreabilidade e reversibilidade.

## Resultado esperado após a migração

- 1 curso ativo;
- 4 turmas vinculadas ao curso canônico;
- 9 matrículas ativas visíveis no contexto do curso;
- 0 matrículas ativas órfãs;
- 0 turmas órfãs;
- 3 aulas no curso canônico;
- página CMS #6 associada como material do curso #1;
- o lote de importação continua ligado à turma #4;
- as 9 pessoas continuam existindo como identidades globais em `student_users`.

## Regra permanente

Uma página CMS pode representar o curso ou ser material do curso, mas continua sendo uma página CMS e usa o mesmo editor. O domínio educacional apenas referencia páginas existentes.

Inscrição e matrícula continuam distintas:

- inscrição: intenção de participar do curso, com disponibilidade e estado de pagamento, ainda sem obrigação de turma;
- matrícula: vínculo efetivo pessoa → turma.

Importação histórica cria/reutiliza pessoa e matrícula na turma escolhida; não cria inscrição fictícia.

## Invariante de regressão

A tela global de Pessoas consulta `student_users` como autoridade. Ela não pode depender de `courses` para decidir se uma pessoa existe ou deve aparecer.

A reconciliação desta instalação é snapshot-específica. Qualquer futura inconsistência precisa ser diagnosticada novamente; a existência desta migração não autoriza novas inferências automáticas.
