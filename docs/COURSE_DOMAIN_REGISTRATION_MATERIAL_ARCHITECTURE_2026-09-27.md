# Arquitetura canônica — Curso, inscrição, turma e material didático — 27/09/2026

Este documento substitui, para regras de negócio, a interpretação transitória em que a página raiz do workshop era usada como entidade `curso`. Páginas continuam sendo a autoridade editorial; `activity` continua sendo apenas o limite técnico do site.

## Princípio estrutural

As responsabilidades são distintas:

- **Página CMS** é conteúdo editorial e continua sendo editada, publicada, versionada e renderizada pelo CMS normal.
- **Curso** é a entidade de domínio que organiza inscrição, turmas, aulas, material e matrículas.
- **Inscrição** representa o interesse/adesão de uma pessoa a um curso e pode existir sem turma definida.
- **Turma** é uma execução concreta do curso.
- **Matrícula** liga a identidade global do aluno a uma turma concreta.

A sequência é:

`pessoa → inscrição no curso → disponibilidade/pagamento → atribuição de turma → matrícula`

Não é:

`formulário → turma padrão → matrícula automática`.

Uma inscrição confirmada ou paga pode permanecer **sem turma** até que a organização das disponibilidades esteja concluída.

## Curso

`courses` é a identidade estável do curso. Ele pode apontar para recursos editoriais já existentes:

- `public_page_id` → página CMS pública do curso;
- `registration_form_id` → formulário CMS usado para inscrição.

Esses recursos não se tornam objetos especiais. A página pública continua sendo `cms_pages`; o formulário continua sendo `cms_forms`.

Criar um curso na administração significa **associar uma página CMS existente à entidade de domínio**. Não cria site, `activity`, renderer, editor ou árvore editorial paralelos.

## Formulário e inscrições

Cada curso pode ter um formulário de inscrição associado. A submissão de um formulário com `purpose='enrollment'` passa a registrar explicitamente `course_id` quando a associação é inequívoca.

`cms_form_submissions` continua sendo o registro original da resposta e preserva:

- formulário utilizado;
- página em que foi enviado;
- snapshot do formulário;
- payload completo, inclusive múltiplas opções de disponibilidade;
- pagamento e estado administrativo.

`course_id` define a qual curso aquela inscrição pertence.

A associação com turma (`cohort_id`) é independente. Nenhuma rotina pode escolher uma turma automaticamente apenas porque a inscrição foi confirmada ou paga.

A matrícula só é criada quando existem simultaneamente:

1. inscrição confirmada/paga;
2. curso identificado;
3. turma explicitamente atribuída;
4. turma pertencente ao mesmo curso.

## Identidade do aluno

`student_users` continua sendo identidade global. A mesma pessoa pode ter várias inscrições e várias matrículas:

`student_user → inscrições em cursos diferentes`

`student_user → course_enrollments → course_cohorts → course`

A administração de um curso mostra apenas as matrículas daquele curso; a identidade não é duplicada.

## Turmas e aulas

`course_cohorts.course_id` e `course_lessons.course_id` são as autoridades de escopo.

`activity_id` e `workshop_page_id` podem permanecer durante a transição para compatibilidade e backfill, mas não definem mais o curso.

A liberação progressiva continua pertencendo à combinação:

`turma + aula → released_at`

Uma aula pode estar bloqueada, agendada ou liberada para cada turma.

## Material didático

Material didático **continua sendo página CMS normal**.

Não existe editor de material, renderer de material ou tabela que replique o conteúdo da página.

`course_material_pages` é somente uma relação:

`course ↔ cms_page`

Assim, uma página pode ser criada/editada normalmente em **Páginas** e associada ao curso em **Curso → Material**. Remover a associação não remove a página.

O ponto de entrada **Curso → Material** é uma visão contextual das mesmas páginas do CMS. O botão **Editar página** abre o editor CMS normal.

## Seções e aulas

As seções continuam sendo as seções existentes no documento CMS, identificadas pelo `data-cms-section` estável.

`course_material_sections` armazena apenas a regra educacional:

`course_id + page_id + section_key → lesson_id`

Não armazena HTML.

Regra de disponibilidade:

- seção sem mapeamento de aula → disponível a qualquer aluno matriculado naquele curso;
- seção mapeada para uma aula → disponível somente quando essa aula estiver liberada para a turma do aluno.

Uma aula pode liberar seções em várias páginas e uma página pode conter seções de várias aulas.

O filtro é obrigatório no servidor: seção bloqueada não deve ser enviada no HTML.

## Administração

A organização principal passa a ser:

`Cursos → curso selecionado → Inscrições / Turmas / Alunos / Aulas / Material`

### Cursos

A tela não cria uma `activity`. Ela associa uma página CMS existente como página pública de um curso e permite ligar um formulário de inscrição existente.

### Inscrições

A tela de inscrições nunca mistura candidatos de cursos diferentes por padrão. Primeiro existe contexto de curso; dentro dele aparecem as inscrições daquele curso.

Disponibilidade, pagamento e turma são dimensões independentes.

### Material

Lista páginas CMS associadas ao curso e suas seções. Associação de seção com aula é feita nesta visão contextual, mas **Editar página** usa o editor normal.

## Migração e compatibilidade

A migração é não destrutiva.

Os vínculos transitórios `workshop_page_id` introduzidos anteriormente são usados apenas para inferir cursos existentes quando isso é inequívoco. O conteúdo editorial não é movido nem duplicado.

`course_page_sections` legado é copiado para o novo relacionamento `course_material_sections` quando a aula já possui curso inequívoco.

Inscrições antigas recebem `course_id` somente quando a associação por formulário/página é inequívoca. Estado ambíguo permanece explícito; não se adivinha por título ou texto.

## Invariantes obrigatórios

1. nenhum fluxo cria `activity` para representar curso;
2. material usa `cms_pages` e o editor CMS existente;
3. inscrição confirmada sem turma não cria matrícula;
4. uma turma só recebe inscrição do mesmo curso;
5. uma página de material só expõe conteúdo a matrícula do curso associado;
6. seção vinculada a aula bloqueada não chega ao HTML;
7. um aluno pode ter inscrições/matrículas em vários cursos sem duplicar identidade;
8. a administração não mistura inscrições de cursos diferentes como lista padrão.
