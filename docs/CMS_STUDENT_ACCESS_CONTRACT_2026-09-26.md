# CMS, material e matrícula — contrato de implementação — 26/09/2026

## Objetivo

Este documento consolida o comportamento acordado para hierarquia de páginas, acesso de páginas e seções, material do curso, aulas e matrícula de usuários já existentes. Ele também registra o estado factual do código em 26/09/2026 para impedir que implementação parcial seja tratada como concluída.

O trabalho deve continuar em blocos pequenos e independentes. Cada bloco recebe branch, testes, PR, merge e publicação antes do seguinte. Não misturar hierarquia, acesso, material, matrícula e limpeza de legado numa única alteração.

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

Uma seção de qualquer página pode ter uma das audiências:

- **Público** — qualquer visitante;
- **Usuários autenticados** — qualquer conta autenticada, mesmo sem matrícula naquela atividade;
- **Participantes deste curso** — exige matrícula ativa na atividade;
- **Turma específica** — exige matrícula ativa na turma escolhida.

Contrato HTML da seção:

- ausência de `data-cms-access` = público;
- `data-cms-access="authenticated"` = usuário autenticado;
- `data-cms-access="activity"` = matrícula ativa na atividade;
- `data-cms-access="cohort"` + `data-cms-cohort-id="ID"` = matrícula ativa na turma.

A autorização é feita no servidor. Uma seção sem acesso não deve ser entregue no DOM público.

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

O inspetor da própria seção deve expor audiência e disponibilidade. Vincular uma seção a uma aula não pertence a uma segunda tela editorial.

## 4. Página de material

A página de material é uma página normal do CMS com regras de acesso e seções editoriais reais. Ela não é um subsistema especial.

O material deve estar dividido em seções CMS selecionáveis. Cada seção precisa poder declarar a aula à qual sua disponibilidade está vinculada. A separação visual por “Aula 1”, “Aula 2” e “Aula 3” não substitui o vínculo editorial da seção com a aula.

O administrador vê o documento integral no editor, inclusive seções ainda bloqueadas e placeholders de infográficos pendentes. O aluno recebe apenas as seções para as quais possui audiência e disponibilidade válidas.

### Placeholders de infográficos

O placeholder é exclusivamente editorial:

- administrador: vê `Infográfico pendente` e a identificação técnica do slot;
- aluno: slot sem mídia vinculada não produz markup visível;
- asset existente: é entregue apenas conforme as regras de autorização da página/mídia.

Esse contrato foi corrigido no PR #101.

## 5. Hierarquia de páginas

A relação pai/filho é uma propriedade real de `cms_pages`, armazenada em `parent_page_id`.

Regras:

- pai e filho pertencem à mesma atividade e idioma;
- ciclos são inválidos;
- alterar o pai não altera slug nem URL;
- hierarquia editorial não é sinônimo da árvore de navegação pública;
- a interface precisa representar a hierarquia de fato, e não apenas armazenar um `parent_page_id` escondido em uma ação secundária;
- reordenação deve respeitar o conjunto de irmãos do mesmo pai, em vez de tratar todas as páginas do idioma como uma lista plana.

## 6. Estado factual do código em 26/09/2026

### Implementado e utilizável

- backend de seção reconhece audiências `public`, `authenticated`, `activity` e `cohort`;
- backend de seção reconhece disponibilidade `immediate`, `scheduled` e `lesson`;
- front controller filtra seções no servidor antes da renderização para visitantes/alunos;
- editor possui código de controles de audiência e disponibilidade da seção;
- `course_enrollments` permite várias matrículas para a mesma conta;
- reconciliação de inscrições confirmadas reutiliza uma conta localizada por CPF/e-mail;
- placeholder de infográfico pendente voltou a ser apenas administrativo no PR #101.

### Parcial / inconsistente

- `cms_pages.parent_page_id` existe e há funções de validação, mas a gestão visual continua essencialmente plana: a escolha do pai fica dentro do menu de ações, a lista só sinaliza filho com `↳` e a movimentação atual considera todas as páginas do idioma, não somente irmãos;
- acesso de página no editor expõe `public`, `authenticated` e `activity`, enquanto o vocabulário canônico do backend também contém `cohort`; a autorização de página ainda não implementa `cohort`;
- `student_accounts.php` ainda mantém helpers legados baseados em `access_level='enrolled'`, apesar da normalização para `activity`; o dashboard precisou de um hotfix local em vez de usar uma única autoridade;
- `student_accounts.php` ainda contém a autoridade editorial histórica `course_page_sections`, embora o contrato novo determine atributos da própria seção CMS;
- o material contém muitas seções CMS reais, mas o vínculo de todas elas com as aulas não está garantido no próprio documento; a migração 066 só transporta vínculos que já existiam em `course_page_sections`;
- inscrição de usuário autenticado ainda não registra no momento do envio que a identidade veio da sessão; a reconciliação posterior continua dependendo dos dados de CPF/e-mail do payload.

### Não considerar concluído

Não considerar “hierarquia de páginas”, “configuração completa do material” nem “matrícula multi-curso de usuário autenticado” concluídas enquanto os blocos abaixo não forem entregues e testados.

## 7. Ordem de implementação

### Bloco A — contrato e auditoria

Este documento. Nenhuma mudança funcional além das correções urgentes já isoladas.

### Bloco B — matrícula multi-curso e autoridade única de matrícula

- persistir `student_id` da sessão em submissões de formulário de inscrição quando o usuário estiver autenticado;
- reconciliar a submissão confirmada com esse `student_id` sem redescobrir identidade por CPF/e-mail;
- continuar usando CPF/e-mail para inscrições deslogadas;
- impedir conflitos de identidade e duplicação de matrícula ativa;
- remover dependência dos helpers `enrolled` nos caminhos ativos, preservando compatibilidade de leitura apenas onde necessário;
- testes cobrindo conta existente + novo curso, conta logada + novo curso e duas matrículas independentes.

### Bloco C — hierarquia de páginas utilizável

- apresentar páginas em árvore real;
- escolha de página superior em superfície primária e compreensível;
- ordenar/mover entre irmãos do mesmo pai;
- preservar slug/URL;
- manter navegação pública como configuração independente;
- testes de múltiplos níveis, ciclo, idioma, atividade e ordenação.

### Bloco D — configurações completas da página

- concentrar no editor as propriedades editoriais da página;
- completar os níveis de acesso previstos pelo modelo;
- eliminar divergência entre UI, endpoint e autorização do servidor;
- testes de acesso público, autenticado, atividade e turma quando aplicável.

### Bloco E — configurações de seção e aulas

- garantir que o inspetor da seção mostre audiência e disponibilidade de forma estável;
- garantir persistência no HTML do documento;
- validar turma/aula contra a atividade da página;
- manter audiência e disponibilidade independentes;
- testar `authenticated`, `activity`, `cohort`, janela agendada e aula bloqueada/agendada/liberada.

### Bloco F — material organizado por aulas

- garantir que todas as seções editoriais do material estejam claramente atribuídas à aula correta;
- manter capa/índice e outros conteúdos comuns com regra explícita adequada;
- preservar conteúdo existente e slots de mídia;
- admin vê tudo; aluno vê somente o que estiver autorizado/liberado;
- nenhuma seção liberada apenas por estar visualmente sob um título de aula.

### Bloco G — remoção dos caminhos concorrentes

- retirar os caminhos ativos que ainda tratem `course_page_sections` ou `enrolled` como autoridades editoriais;
- manter apenas compatibilidade/migração quando necessária;
- confirmar que “Páginas protegidas” não volta a ser uma interface concorrente;
- rodar regressão integrada de CMS, aluno, aulas, mídia e formulários.

## 8. Regra operacional

Nenhum bloco seguinte deve ser iniciado em uma branch que contenha trabalho ainda não validado do bloco anterior. Se um bloco crescer além de uma responsabilidade clara, ele deve ser subdividido novamente antes da implementação.
