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


## Agenda integrada — decisão de produto de 12/10/2026 (#234)

A identidade pedagógica continua em `course_lessons` (`course_id`, título, ordem, material e recursos). Cada turma configura a **ocorrência** da mesma aula em `cohort_lesson_releases`, estendida por `migrations/098_course_cohort_agenda.php` com data/hora UTC, fuso IANA, duração, URL, instruções e estado operacional. A Agenda é uma **projeção**, não um novo cadastro de aula. Encontros sem data continuam administráveis mas não aparecem como agendados; data e link nunca são inventados.

`released_at` continua sendo a **única autoridade de liberação** da combinação turma + aula. Marcar um encontro, atribuir um link ou concluir uma transmissão não libera material, seções CMS nem ferramentas protegidas. A ficha única `Cursos → Turma → Aulas` mantém encontro e liberação no mesmo contexto, com escolha explícita de manter/alterar a liberação.

A turma herda `courses.agenda_default_capacity` (10, salvo configuração) ou utiliza `course_cohorts.agenda_capacity`. Vagas são compartilhadas entre estudantes com `course_enrollments.status='active'` e ouvintes com `course_listener_reservations.status='confirmed'`, sem contar duas vezes a mesma pessoa. Os gatilhos SQLite são parte da fronteira transacional: nenhuma outra rota de criação de matrícula pode exceder o limite. Reduzir capacidade não expulsa ninguém; mostra excesso e impede novos ingressos.

O retorno como ouvinte exige identidade ativa e matrícula elegível de outro `cohort_id` do **mesmo curso** (a turma de origem pode ter terminado). A reserva pertence à turma inteira, tem fila cronológica e idempotência `UNIQUE(student_id,cohort_id)`; não cria inscrição financeira, segunda matrícula nem direito a material/testes. A confirmação só ocorre após **ambos** `agenda_enrollment_closes_at` e `agenda_listeners_open_at`; antes disso a fila não ocupa vagas. Cancelamento convoca o primeiro elegível, sob transação SQLite com `BEGIN IMMEDIATE`. O cronograma do encontro é distinto dos prazos comerciais. Links e instruções privados só entram na resposta autenticada de matrícula ativa ou ouvinte confirmado com elegibilidade atual. Ausência de turmas futuras não representa promessa de retorno ilimitado.

A atribuição ou transferência de inscrição paga em `course_registration_assign_cohort()` é transacional: modifica a turma e reconcilia a matrícula no mesmo commit. Se a capacidade impedir a matrícula, desfaz inclusive a troca de turma e apresenta uma mensagem administrativa de indisponibilidade; não é permitido informar sucesso com matrícula recusada. Desvinculação ou desativação de matrícula reavalia ouvintes e libera a capacidade da turma anterior, respeitando a fila.

### Auditoria de atribuição/transfêrencia — 13/10/2026 (#243)

A atribuição de uma inscrição reconcilia **somente a submissão operada**; reconciliações globais de pagamentos e inscrições continuam disponíveis nas operações próprias, mas não são efeitos colaterais de mudar turma. Apenas turma `active` do mesmo curso pode receber nova atribuição. Uma linha de matrícula pertencente a outra submissão ou criada manualmente não pode ter a sua origem substituída por `ON CONFLICT`: a operação deve apresentar conflito explícito. A alteração da submissão, a desativação histórica, a matrícula de destino e as reservas/filas da Agenda são uma seção crítica SQLite `BEGIN IMMEDIATE`; erro por capacidade ou integridade desfaz o conjunto. A desvinculação de turma utiliza a mesma transação e reavalia a fila liberada. Inscrições não confirmadas podem ter turma pré-definida sem criar matrícula. Não alterar autorização/material/liberação `released_at` durante remanejamento.

A reconciliação acionada pelo login/ativação delega à autoridade canônica quando existe o domínio `courses`, proibindo a escolha de turma padrão pelo fluxo legado. O hook de shutdown não pode executar reconciliação global depois das ações `assign_cohort`/`unassign_cohort`; nessas ações, a transação da operação é a única reconciliação de matrícula. O fallback legado permanece somente para schemas sem `courses`.
