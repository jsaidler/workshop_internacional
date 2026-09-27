# Handoff canônico — domínio de cursos, inscrições e material — 27/09/2026

Este documento registra a decisão final e a implementação correspondente. Em caso de conflito, ele prevalece sobre documentos anteriores que tratem `activity`, página raiz de workshop ou turma como equivalente semântico de curso.

## Autoridades

- `activity` = limite técnico do site/instalação, não curso.
- `courses` = identidade estável do curso.
- `cms_pages` = autoridade editorial para páginas públicas e material didático.
- `cms_forms` = autoridade para formulários; um formulário de inscrição pode ser associado a um curso.
- `cms_form_submissions` = inscrição original, preservando payload, snapshot, disponibilidade e pagamento.
- `course_cohorts` = turmas concretas do curso.
- `course_lessons` = aulas do curso.
- `course_enrollments` = matrícula de uma identidade global em uma turma concreta.
- `student_users` = identidade global da pessoa.

## Fluxo de inscrição

A sequência obrigatória é:

`pessoa → inscrição no curso → disponibilidade/pagamento → atribuição explícita de turma → matrícula`

Uma inscrição confirmada/paga pode permanecer sem turma. Nenhum fluxo de curso deve escolher automaticamente uma turma padrão apenas para produzir matrícula.

A mesma identidade global pode possuir inscrições e matrículas em vários cursos.

## Material didático

Material não é um tipo paralelo de página.

`course_material_pages` é apenas a relação entre `courses` e páginas CMS existentes. O conteúdo continua armazenado, editado, versionado, publicado e renderizado pelo CMS normal. O ponto de entrada **Curso → Material** apenas oferece uma visão contextual e o botão de edição abre o editor CMS normal.

Uma página associada ao material pode conter várias seções CMS. `course_material_sections` guarda exclusivamente a regra educacional:

`course_id + page_id + section_key → lesson_id`

- sem mapeamento → seção disponível a qualquer aluno matriculado no curso;
- com aula mapeada → seção disponível somente se essa aula estiver liberada para a turma da matrícula corrente.

A liberação é `turma + aula → released_at`. Uma aula pode liberar seções em várias páginas; uma página pode reunir seções de várias aulas.

O filtro é server-side: conteúdo bloqueado não deve ser enviado no HTML.

## Administração

A navegação de domínio é:

`Cursos → curso → Configuração / Turmas / Alunos / Aulas / Material`

`Inscrições` primeiro escolhe/recebe o contexto do curso e nunca mistura candidatos de cursos distintos na lista operacional.

O curso associa recursos globais existentes:

- página pública;
- formulário de inscrição;
- páginas CMS de material.

Nenhum desses vínculos cria cópia, renderer, editor ou sistema visual paralelo.

## Estado implementado

A migração `073_course_domain_material.php` cria o domínio de curso e os relacionamentos necessários sem duplicar conteúdo editorial, faz backfill apenas quando a associação é inequívoca e preserva compatibilidades legadas durante a transição.

As telas `admin/courses.php`, `admin/registrations.php` e `admin/student-area.php` passam a operar pelo curso. A Área do aluno lê curso → turma → aulas/material. O renderer reutiliza `cms_pages` e aplica o mapeamento seção→aula no filtro de acesso.

A suíte de regressão cobre domínio, administração, filtro progressivo de material e escopo de compartilhamento.

## Regra de evolução

Qualquer nova necessidade deve primeiro consumir estas autoridades. Não criar entidade editorial, editor, renderer, tema ou controle paralelo para material didático. Uma nova estrutura só é admissível se houver lacuna real no domínio e sem duplicar uma autoridade já existente.