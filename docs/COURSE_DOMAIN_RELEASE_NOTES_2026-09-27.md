# Notas de implementação — Curso, inscrições e material didático — 27/09/2026

Este bloco consolida a mudança de domínio discutida em 27/09/2026.

## Decisões canônicas

- `courses` é a entidade de negócio do curso/workshop.
- `cms_pages` continua sendo a única entidade editorial de página.
- `cms_forms` continua sendo a única entidade de formulário.
- `student_users` continua sendo a identidade global da pessoa/aluno.
- inscrição e matrícula são estados distintos: inscrição pode existir sem turma; matrícula exige turma explícita.
- material didático é composto por páginas CMS existentes associadas ao curso, não por um segundo tipo de página.
- o ponto `Curso → Material` apenas contextualiza as páginas CMS e o mapeamento de seções para aulas; editar conteúdo sempre abre o editor CMS existente.
- a liberação progressiva pertence à combinação turma + aula.
- seções de material associadas a uma aula são removidas do HTML no servidor enquanto a aula não estiver liberada para a turma.
- seções sem aula associada são conteúdo geral do curso e permanecem disponíveis ao aluno matriculado.

## Administração

- `Cursos` organiza página pública, formulário de inscrição, turmas, alunos, aulas e material.
- `Inscrições` começa pelo curso e não mistura interessados de cursos diferentes.
- disponibilidade, pagamento e atribuição de turma são dimensões independentes.
- `Área do aluno` administrativa começa pelo curso e mostra matrículas daquele curso.
- `Outras respostas` continua cobrindo formulários que não sejam inscrições de curso.

## Migração

A migração `073_course_domain_material.php` é não destrutiva. Ela cria o domínio de curso e relações com páginas/formulários existentes, preserva compatibilidade com `activity_id`/`workshop_page_id` durante a transição e só faz backfill quando a associação é inequívoca.

O conteúdo das páginas não é copiado, movido ou convertido.
