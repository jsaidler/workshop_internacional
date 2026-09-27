# Auditoria factual da Área do aluno — 27/09/2026

## Baseline confirmado

A revisão partiu da branch de produção `wip/form-response-refinement-2026-07-16` no merge `67fe31d5f1898245ca5b8022f69fe593fa5556d0` (PR #125).

O painel `Admin → Sistema e atualizações` foi conferido em 27/09/2026 e mostrou:

- **Instalado:** `67fe31d5f189`
- **Produção disponível:** `67fe31d5f189`
- timestamp exibido em ambos: `2026-09-27T03:40:41+00:00`

Portanto, naquele ponto da revisão, a hospedagem e o canal de produção estavam sincronizados no mesmo `sourceSha` abreviado. Este é o último estado **instalado** confirmado visualmente durante esta auditoria. Os blocos posteriores podem estar publicados no atualizador sem que isso, sozinho, prove que a hospedagem já os instalou. O editor permanece pausado; a revisão em andamento é da Área do aluno.

## Princípio da revisão

Não criar nova autoridade para dados que já pertencem ao CMS, às matrículas, às turmas, às aulas ou aos testes. A correção deve fazer a experiência do aluno refletir corretamente as autoridades existentes.

A revisão é dividida em blocos pequenos, cada um com regressão no mesmo nível da alteração, PR, CI, merge e deploy antes do bloco seguinte.

## Problemas encontrados

### A1 — Verdade do dashboard

Dois defeitos funcionais apareciam na primeira tela após o login:

1. `released_at` futuro era tratado visualmente como aula liberada, embora a autorização canônica considere esse estado `scheduled`;
2. a descoberta de material no dashboard listava somente páginas `activity|enrolled`, omitindo páginas `cohort` destinadas exatamente à turma da matrícula.

Contrato:

- `NULL` → aguardando/bloqueada;
- data futura → agendada;
- data passada ou presente → liberada;
- somente `released` entra na contagem `x/y aulas liberadas`;
- o material de uma matrícula inclui páginas de atividade e páginas `cohort` cujo `access_cohort_id` seja o da própria matrícula;
- páginas de outra turma, públicas ou apenas autenticadas não são apresentadas como material do curso.

**Estado:** concluído no PR #126, merge `965169f0129542ea07a232e7707954e6f183f81d`. A suíte do PR passou completa, incluindo PHP, Playwright/Chromium, build e dry-run. O deploy de produção run `36294820849` concluiu com sucesso e publicou o novo artefato para o atualizador administrativo.

### A2 — Contexto curso/turma

A conta já pode possuir várias matrículas, mas a navegação funcionava como lista agregada. Este bloco transforma `Cursos` em ponto de escolha e torna a matrícula selecionada um contexto de trabalho claro, sem criar uma nova entidade de autorização.

Contrato:

- uma única matrícula ativa abre diretamente;
- múltiplas matrículas exigem escolha explícita;
- o contexto via URL usa o `cohort_uuid` já existente e não persiste um “curso atual” paralelo;
- UUID desconhecido não seleciona outra matrícula por fallback;
- o workspace selecionado usa a atividade correspondente também para os tokens de Design do shell;
- `active` aparece como **Turma ativa** e `closed` como **Turma encerrada**;
- `closed` permanece informativo: este bloco não inventa uma nova restrição operacional;
- curso sem aulas mostra **Sem aulas cadastradas**, não `0/0 aulas liberadas`;
- quando houver mais de uma matrícula, o workspace possui retorno explícito para **Todos os cursos**.

**Estado:** concluído no PR #127, merge `5fee4bd87a4cffe1393a80615f9f4968f748c221`. A suíte passou completa, incluindo Playwright/Chromium, build e dry-run. O deploy de produção run `36295230388` concluiu com sucesso e publicou o artefato para o atualizador administrativo.

### A3 — Conta completa

No celular, o shell ocultava `Sair` e `Conta` levava somente ao formulário de perfil. A rota de senha existia, mas não era descoberta pela interface. O formulário de perfil ainda usava um grid de duas colunas inline que não obedecia à regra responsiva da Área do aluno.

Contrato:

- `Conta` é o destino completo da identidade autenticada, reunindo **Dados pessoais**, **Segurança** e **Sessão**;
- o perfil usa a grade canônica `.student-form-grid`, que vira uma coluna no celular;
- `Alterar senha` é uma ação visível da Conta e usa a rota existente `/aluno/senha.php`;
- depois da alteração de senha iniciada pela Conta, o retorno padrão desse fluxo é a própria Conta;
- a tela de alteração de senha autenticada oferece retorno explícito `← Conta`;
- o primeiro acesso continua separado e não recebe navegação de conta antes da ativação;
- `Sair` fica disponível dentro de Conta também no celular e continua usando POST + CSRF no endpoint canônico `/aluno/logout.php`;
- o logout rápido do topo desktop pode continuar existindo; ele não é uma segunda autoridade, apenas outro acionador do mesmo endpoint.

**Estado:** concluído no PR #128, merge `9974cf0981d2c75b41735cccee5a11c94efc4df8`. A suíte passou completa, incluindo regressão PHP, Playwright/Chromium, build e dry-run. O deploy de produção run `36295605240` concluiu com sucesso e publicou o artefato para o atualizador administrativo.

### A4 — Continuidade do material

O material continua sendo página CMS normal, com a mesma autoridade editorial, visual e de autorização. O defeito observado era a perda do contexto da matrícula ao sair do workspace e entrar no material: o aluno voltava ao cabeçalho público sem indicação do curso/turma nem acesso direto ao workspace correspondente.

Contrato:

- não existe segundo renderer, template de caderno ou cópia do material dentro da Área do aluno;
- o contexto de curso é derivado da matrícula autorizada, não de estado paralelo;
- páginas `activity`/legado `enrolled` preservam o `cohort_uuid` solicitado quando ele pertence à conta;
- páginas `cohort` usam a turma autorizada pela própria página, mesmo que a URL sugira outra matrícula;
- páginas `public` e páginas genéricas `authenticated` não ganham artificialmente contexto de curso;
- a UI contextual nunca aparece no editor/preview;
- quando existe contexto, o link **Área do aluno** do cabeçalho volta diretamente ao workspace daquela matrícula;
- uma barra discreta identifica curso e turma e oferece **Voltar ao curso**, **Testes** e **Conta**;
- essa barra pertence ao shell/runtime, não ao documento CMS persistido;
- desktop e celular precisam permanecer sem overflow horizontal.

**Estado:** concluído no PR #129, merge `e15ec788e5d45bb198d9157c23e53f71a899761e`. A suíte passou completa após ajustar uma regressão estática antiga que exigia literalmente `href="/aluno/"`; o destino agora é contextual sem perder o acesso global. O deploy de produção run `36297361519` concluiu com sucesso e publicou o artefato para o atualizador administrativo.

### A5 — Entrada e recuperação

O login e o primeiro acesso funcionavam, mas eram apresentados lado a lado com peso visual semelhante. Isso confundia duas situações diferentes: a rotina normal de uma conta já ativada e a ativação única depois da matrícula confirmada.

O bloco foi dividido para não misturar hierarquia visual com um novo subsistema de segurança.

#### A5a — Hierarquia de entrada

Contrato:

- **Entrar** é o fluxo primário e aparece primeiro;
- **Primeiro acesso / Ativar conta** é explicitamente secundário e aparece depois, não como coluna concorrente;
- os dois fluxos continuam usando o mesmo backend e o mesmo CSRF já existentes;
- erros de login permanecem junto do login; erros de ativação permanecem junto da ativação;
- a tela não cria JavaScript, estado paralelo ou nova forma de autenticação;
- desktop e celular preservam a mesma ordem e não usam duas colunas concorrentes.

**Estado:** concluído no PR #130, merge `7401dd564ea597a11ac1beaf315b9c4bd7da34c0`. A suíte passou completa, incluindo regressão PHP, Playwright/Chromium, build e dry-run. O deploy de produção run `36297747362` concluiu com sucesso e publicou o artefato para o atualizador administrativo.

#### A5b — Recuperação de senha

A auditoria do código não encontrou transporte de e-mail de saída canônico na aplicação: não há serviço SMTP, PHPMailer nem chamada `mail()` que possa ser reutilizada com segurança. Portanto a recuperação não deve ser improvisada com CPF, perguntas pessoais, senha temporária fixa ou outro atalho que reduza a segurança da conta.

**Estado:** bloqueado por decisão/infraestrutura de entrega. A implementação futura exige primeiro uma autoridade de e-mail transacional e um fluxo de token de uso único com validade curta. A ausência desse subsistema não bloqueia os blocos seguintes.

### A6 — Índice de testes

A tela de Testes agregava criação, registros próprios e compartilhados de todas as matrículas da conta. Isso ainda funcionava para uma pessoa com um único curso, mas perdia a noção de workspace assim que a conta possuía mais de uma matrícula.

Contrato:

- Testes reutiliza o mesmo contexto de matrícula por `cohort_uuid` já usado em Cursos e Material;
- uma única matrícula abre diretamente; múltiplas matrículas exigem escolha explícita antes de mostrar criação e registros;
- `cohort_uuid` desconhecido não escolhe outra matrícula por fallback;
- o shell recebe a atividade da matrícula selecionada e reaplica os tokens de Design correspondentes;
- criar teste não oferece um seletor administrativo de todas as turmas: o `cohort_id` fica preso à matrícula selecionada e é validado também no POST;
- **Seus testes** mostra somente registros pertencentes à turma selecionada;
- em **Compartilhados com você**, visibilidade `cohort` aparece apenas para a turma selecionada e visibilidade `course` pode incluir outras turmas do mesmo curso;
- registros e compartilhamentos de outro curso não entram no workspace selecionado;
- a ação **Abrir meus testes** dentro do workspace de Cursos já leva o `cohort_uuid` correspondente;
- alteração de visibilidade retorna ao mesmo contexto quando a origem foi um workspace de turma;
- links de detalhe carregam o `cohort_uuid` como contexto de retorno, sem alterar a autoridade de acesso do teste;
- a interface não cria filtros administrativos, “curso atual” persistido nem nova entidade de autorização.

**Estado:** concluído no PR #131, merge `d97c3c0c47ae8945def6518defcaad1803954c53`. A suíte passou completa, incluindo regressão PHP, Playwright/Chromium, build e dry-run. O deploy de produção run `36299092654` concluiu com sucesso e publicou o artefato para o atualizador administrativo.

### A7 — Workflow do teste

A etapa de Revelação possuía duas rotas concorrentes: **Salvar revelação** submetia o formulário, enquanto **Revisar teste** era apenas um link e podia abandonar alterações ainda não enviadas. A faixa de etapas também funcionava como navegação livre e permitia sair de uma etapa de edição sem passar pela ação de salvamento.

Contrato:

- Exposição mantém um único avanço principal: **Salvar exposição e continuar**;
- Revelação passa a ter um único avanço principal: **Salvar revelação e continuar**;
- o botão de avanço da Revelação submete o próprio formulário de revelação antes de abrir a revisão;
- deixam de existir o botão secundário **Salvar revelação** e o link concorrente **Revisar teste**;
- a faixa 01/02/03 é indicador de progresso e etapa atual, não navegação livre enquanto há formulários editáveis; edição de etapas anteriores parte da revisão pelos links explícitos **Editar exposição** e **Editar revelação**;
- não se inventa uma nova coluna de “etapa concluída” nem se deduz conclusão porque algum campo está preenchido: o estado de ciclo do teste (`draft`, `submitted`, `needs_revision`, `reviewed`) continua sendo a autoridade persistida;
- a ficha própria valida o contexto de retorno contra a própria turma do teste; um `cohort_uuid` de outra matrícula não é aceito como contexto daquele registro;
- contexto válido é preservado nos POSTs, uploads, remoções de mídia, edição, revisão e exclusão;
- a ficha própria usa o Design da atividade real do teste;
- em teste compartilhado com visibilidade `cohort`, o contexto de retorno precisa ser a própria turma compartilhada;
- em teste compartilhado com visibilidade `course`, o contexto pode ser qualquer matrícula ativa do leitor na mesma atividade, inclusive outra turma desse curso, mas nunca outro curso;
- se mais de uma matrícula da mesma atividade puder servir de retorno e a URL não indicar uma delas, não se inventa uma seleção;
- a ficha compartilhada usa o Design da atividade do teste e retorna ao índice de Testes correspondente quando há contexto válido;
- nenhuma dessas regras altera a autoridade de acesso ao registro: propriedade e visibilidade continuam sendo verificadas pelos serviços canônicos existentes.

**Estado:** concluído no PR #132, merge `7e1f5b0f15f8e95183fae3780f8bb7ae71d4a405`. A suíte do PR passou completa, incluindo PHP, Playwright/Chromium, build e dry-run. O deploy de produção run `36299922808` concluiu com sucesso e publicou o artefato para o atualizador administrativo.

### Revisão transversal final

A leitura cruzada das rotas depois de A1–A7 encontrou dois defeitos concretos que os blocos isolados não cobriam:

1. `teste-compartilhado.php` usava `student_review_value()` e `student_test_message_date()`, mas essas funções estavam declaradas apenas ao final de `teste.php`. Como cada rota executa em uma requisição independente, a ficha compartilhada podia chegar a essas chamadas sem que as funções existissem naquele runtime.
2. upload e remoção de imagens ainda eram formulários independentes dos campos da etapa. Na Revelação, em especial, a ordem natural era preencher os parâmetros e depois fotografar o resultado; escolher a imagem submetia outro formulário e recarregava a página, podendo descartar valores digitados que ainda não haviam sido salvos.

Contrato do fechamento:

- funções de apresentação usadas por mais de uma rota pertencem a módulo carregado pelo `bootstrap`, não a uma página específica;
- `student_review_value()` e `student_test_message_date()` ficam no serviço compartilhado de workflow/teste e deixam de ser redeclaradas localmente;
- Exposição e Revelação usam, cada uma, um único formulário `multipart/form-data` que contém os campos da etapa e também as ações de mídia;
- ao adicionar ou remover uma imagem em uma etapa editável, o servidor salva primeiro os campos atuais daquela etapa e somente depois executa a mutação da mídia;
- upload/remoção continua na mesma etapa e informa que os dados também foram salvos;
- o botão **Salvar ... e continuar** continua sendo a única ação que avança de etapa;
- nenhuma ação de mídia cria nova autorização, novo registro paralelo ou semântica adicional de conclusão;
- regressão de navegador precisa provar que valores digitados na Revelação sobrevivem à inclusão de uma imagem antes do avanço para Revisão.

**Estado:** implementação em `fix/student-area-transversal-final-2026-09-27`.

## Cobertura de regressão

A auditoria encontrou que a maior parte dos testes da Área do aluno era estática (`file_get_contents`/`str_contains`). Isso não substitui regressão de interação.

A partir do A1, cada bloco que alterar UX combina:

- regressão PHP/SQLite ou teste de regra de domínio;
- Playwright/Chromium para a superfície realmente usada;
- ao menos um viewport de desktop e um de celular quando a tela for responsiva.

O fechamento transversal adiciona uma trava específica para as funções compartilhadas da ficha e para a ordem **salvar etapa → mutar mídia**, além de interação real que simula digitação seguida de inclusão do resultado.

## Ordem de execução

1. A1 — verdade do dashboard — **concluído**;
2. A2 — contexto curso/turma — **concluído**;
3. A3 — Conta completa — **concluído**;
4. A4 — continuidade do material — **concluído**;
5. A5a — hierarquia de entrada — **concluído**;
6. A5b — recuperação de senha — **aguarda infraestrutura de e-mail transacional**;
7. A6 — índice de testes — **concluído**;
8. A7 — workflow do teste — **concluído**;
9. revisão transversal final — **em implementação**.
