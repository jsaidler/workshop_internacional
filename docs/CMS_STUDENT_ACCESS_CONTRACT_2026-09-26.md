# CMS, material e matrícula — contrato de implementação — 26/09/2026

## Objetivo

Este documento consolida o comportamento acordado para hierarquia de páginas, acesso de páginas e seções, material do curso, aulas e matrícula de usuários já existentes. O trabalho foi executado em blocos pequenos e independentes, cada um com branch, testes, PR, merge e publicação antes do seguinte.

A regra continua válida para evoluções futuras: não misturar hierarquia, acesso, material, matrícula e limpeza de legado numa única alteração.

## 1. Identidade, conta e matrícula

`student_users` representa uma pessoa/conta global. Uma pessoa não recebe uma segunda conta ao comprar ou participar de outro curso.

`course_enrollments` representa o vínculo daquela conta com uma turma e, por consequência, com uma atividade/curso. Uma conta pode possuir várias matrículas independentes.

Fluxo obrigatório:

- usuário novo confirmado: criar a conta e a matrícula;
- usuário existente que se inscreve deslogado: reconciliar a identidade existente por CPF/e-mail e criar apenas a nova matrícula;
- usuário existente e autenticado: usar diretamente o `student_id` da sessão como identidade da inscrição; não redescobrir sua identidade pelo CPF/e-mail digitado;
- nunca solicitar nova ativação ou criação de nova senha apenas porque houve matrícula em outro curso;
- matrícula encerrada em um curso não interfere nas matrículas dos demais cursos;
- não criar uma segunda matrícula ativa equivalente para a mesma conta e turma;
- a Área do aluno mostra todas as matrículas ativas daquela conta.

A autenticação da conta e a matrícula são conceitos distintos. Estar autenticado não concede matrícula em todas as atividades.

## 2. Audiência de páginas e seções

O CMS é a autoridade editorial. Não existe uma árvore paralela de “páginas protegidas”.

Uma página ou seção pode usar as audiências:

- **Público** — qualquer visitante;
- **Usuários autenticados** — qualquer conta autenticada, mesmo sem matrícula naquela atividade;
- **Participantes deste curso** — exige matrícula ativa na atividade;
- **Turma específica** — exige matrícula ativa na turma escolhida.

Contrato HTML da seção:

- ausência de `data-cms-access` = público;
- `data-cms-access="authenticated"` = usuário autenticado;
- `data-cms-access="activity"` = matrícula ativa na atividade;
- `data-cms-access="cohort"` + `data-cms-cohort-id="ID"` = matrícula ativa na turma.

A autorização é feita no servidor. Uma seção sem acesso é removida antes da entrega do DOM público.

Para páginas, `cms_pages.access_level` é a autoridade e `cms_pages.access_cohort_id` guarda a turma quando a audiência for `cohort`. O valor histórico `enrolled` é apenas compatibilidade de leitura; novas gravações usam `activity`.

## 3. Disponibilidade de seções

Audiência e disponibilidade são independentes.

Uma seção pode estar:

- **Imediata**;
- **Agendada** por data/hora, com início e encerramento opcional;
- **Controlada por aula**.

Contrato HTML:

- ausência de `data-cms-availability` = imediata;
- `data-cms-availability="scheduled"` com `data-cms-visible-from` e/ou `data-cms-visible-until`;
- `data-cms-availability="lesson"` com `data-cms-lesson-id="ID"`.

Para disponibilidade controlada por aula, `cohort_lesson_releases.released_at` é a autoridade da liberação por turma: `NULL` = bloqueada; data futura = agendada; data passada/presente = liberada.

O inspetor da própria seção expõe audiência e disponibilidade. Turma e aula são validadas contra a atividade da página antes de salvar e antes de publicar. Regras inválidas falham fechadas na renderização pública.

## 4. Página de material

A página de material é uma página normal do CMS com regras de acesso e seções editoriais reais. Ela não é um subsistema especial.

O material está dividido em seções CMS selecionáveis. As seções da Aula 1, Aula 2 e Aula 3 usam `data-cms-availability="lesson"` com o ID real da respectiva aula, resolvido a partir de `lesson_key`. Capa e índice permanecem imediatos.

O administrador vê o documento integral no editor, inclusive seções ainda bloqueadas e placeholders de infográficos pendentes. O aluno recebe apenas as seções para as quais possui audiência e disponibilidade válidas.

### Placeholders e mídia privada

O placeholder é exclusivamente editorial:

- administrador: vê `Infográfico pendente` e a identificação técnica do slot;
- aluno: slot sem mídia vinculada não produz markup visível;
- asset existente: é entregue por URL assinada e revalidada no servidor conforme a autorização canônica da página e, quando houver, a matrícula/turma usada na assinatura.

Slots de mídia pertencem ao documento CMS; `course_page_media_slots` liga o slot ao asset da Biblioteca de mídia. O antigo armazenamento `student_private_media` permanece somente como compatibilidade de instalações anteriores.

## 5. Hierarquia de páginas

A relação pai/filho é propriedade real de `cms_pages`, armazenada em `parent_page_id`.

Regras:

- pai e filho pertencem à mesma atividade e idioma;
- ciclos são inválidos;
- alterar o pai não altera slug nem URL;
- hierarquia editorial não é sinônimo da árvore de navegação pública;
- a administração de Páginas apresenta a árvore real e a propriedade **Página superior**;
- reordenação ocorre somente entre irmãos do mesmo pai.

## 6. Autoridades canônicas

Depois dos blocos A–G, as autoridades são:

- **identidade:** `student_users`;
- **matrícula:** `course_enrollments`;
- **turma:** `course_cohorts`;
- **acesso da página:** `cms_pages.access_level` + `cms_pages.access_cohort_id`;
- **acesso/disponibilidade da seção:** atributos `data-cms-*` no próprio documento CMS;
- **liberação por aula/turma:** `cohort_lesson_releases`;
- **mídia editorial:** Biblioteca de mídia + `course_page_media_slots`;
- **hierarquia de páginas:** `cms_pages.parent_page_id`.

`course_page_sections` não é mais autoridade editorial. Ele permanece apenas como estrutura histórica para migrações/compatibilidade de instalações antigas; caminhos públicos e administrativos atuais não devem consultá-lo para decidir o que o aluno vê.

Da mesma forma, `access_level='enrolled'` não é um valor produzido pelos caminhos canônicos. A migração 067 normalizou páginas existentes para `activity`; leituras legadas podem reconhecer `enrolled` somente para compatibilidade.

## 7. Estado dos blocos

- **Bloco A — contrato e auditoria:** entregue no PR #102.
- **Bloco B — matrícula multi-curso e identidade da sessão:** entregue no PR #103.
- **Bloco C — hierarquia de páginas utilizável:** entregue no PR #104.
- **Bloco D — configurações completas da página:** entregue no PR #105.
- **Bloco E — configurações de seção e aulas:** entregue no PR #106.
- **Bloco F — material organizado por aulas:** entregue no PR #107.
- **Bloco G — autoridade canônica e regressão integrada:** este bloco remove dependências legadas dos caminhos ativos, consolida a entrega de mídia privada pela autorização do CMS e fixa por teste que `Páginas protegidas` não volte a ser uma interface editorial concorrente.

## 8. Compatibilidade e legado

Arquivos e funções antigas podem permanecer fisicamente no repositório quando ainda são necessários para migração, leitura de instalações anteriores ou rotas de compatibilidade. Isso não lhes devolve autoridade.

Em particular:

- `admin/student-area.php?view=pages` redireciona para `admin/pages.php`;
- ações editoriais históricas da Área do aluno respondem `410` em vez de modificar páginas, seções ou mídia;
- `course_page_sections` pode ser lido por migrações históricas, mas não por renderização pública atual;
- `student_private_media` pode ser lido para assets legados, mas novos vínculos usam a Biblioteca de mídia;
- o dashboard do aluno lista páginas canônicas `activity`, não produz nem depende de `enrolled`.

## 9. Regra operacional

Qualquer evolução que volte a criar uma segunda árvore de páginas/seções, uma segunda autoridade de liberação ou uma segunda identidade de aluno viola este contrato. Novas capacidades devem ser acrescentadas às autoridades canônicas acima e cobertas por regressão antes de publicação.
