# Auditoria factual da Área do aluno — 27/09/2026

## Baseline confirmado

A revisão partiu da branch de produção `wip/form-response-refinement-2026-07-16` no merge `67fe31d5f1898245ca5b8022f69fe593fa5556d0` (PR #125).

O painel `Admin → Sistema e atualizações` foi conferido em 27/09/2026 e mostrou:

- **Instalado:** `67fe31d5f189`
- **Produção disponível:** `67fe31d5f189`
- timestamp exibido em ambos: `2026-09-27T03:40:41+00:00`

Portanto, naquele ponto da revisão, a hospedagem e o canal de produção estavam sincronizados no mesmo `sourceSha` abreviado. O editor permanece pausado; a revisão seguinte é da Área do aluno.

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

**Estado:** concluído no PR #128, merge `9974cf0981d2c75b41735cccee5a11c94efc4df8`. A suíte do PR passou completa, incluindo Playwright/Chromium, build e dry-run. O deploy de produção run `36295605240` concluiu com sucesso e publicou o artefato para o atualizador administrativo.

### A4 — Continuidade do material

O material continua sendo página CMS normal, com a mesma autoridade editorial e de autorização. O defeito era a quebra de contexto ao sair do workspace para uma página do curso: o cabeçalho voltava a ser apenas o cabeçalho público e os links internos não preservavam a matrícula selecionada.

Contrato:

- não existe segundo renderer do material;
- o contexto só é ativado quando há uma conta autenticada e o `cohort_uuid` da URL corresponde a uma matrícula ativa daquela conta na atividade atual;
- contexto inválido ou ausente não altera a página pública;
- quando válido, o CMS mostra uma faixa discreta com curso, turma e ações **Curso**, **Testes** e **Conta**;
- `Área do aluno` no cabeçalho passa a funcionar como **Voltar ao curso** nesse contexto e aponta para `/aluno/?cohort=<uuid>`;
- links de páginas CMS e o wordmark preservam o `cohort_uuid`, evitando perder a matrícula ao navegar dentro do mesmo site;
- o seletor de idioma visível também pode preservar o contexto, mas `canonical` e `hreflang` continuam livres de parâmetros pessoais de matrícula;
- links customizados do CMS não recebem `cohort` automaticamente;
- a faixa contextual usa os tokens visuais existentes e permanece utilizável no celular.

**Estado:** implementação em `fix/student-material-continuity-a4-2026-09-27`.

### A5 — Entrada e recuperação

Login e primeiro acesso existem e funcionam, mas ainda precisam de melhor hierarquia. Não existe fluxo visível de recuperação de senha; isso exige definição explícita de canal e token antes da implementação.

### A6 — Índice de testes

A tela agrega criação, registros próprios e compartilhados de todos os cursos. Deve ser reorganizada para continuar funcional em uma conta com múltiplas matrículas, sem virar uma interface administrativa.

### A7 — Workflow do teste

A etapa de Revelação possui hoje uma ação para salvar e outra para revisar; o segundo caminho pode abandonar dados ainda não enviados. A revisão deve manter um único avanço principal: salvar e continuar.

Também devem ser revistos estados de conclusão das etapas e o contexto de curso/turma nos testes compartilhados.

## Cobertura de regressão

A auditoria encontrou que a maior parte dos testes da Área do aluno era estática (`file_get_contents`/`str_contains`). Isso não substitui regressão de interação.

A partir do A1, cada bloco que alterar UX combina:

- regressão PHP/SQLite ou teste de regra de domínio;
- Playwright/Chromium para a superfície realmente usada;
- ao menos um viewport de desktop e um de celular quando a tela for responsiva.

## Ordem de execução

1. A1 — verdade do dashboard — **concluído**;
2. A2 — contexto curso/turma — **concluído**;
3. A3 — Conta completa — **concluído**;
4. A4 — continuidade do material — **em implementação**;
5. A5 — entrada e recuperação;
6. A6 — índice de testes;
7. A7 — workflow do teste;
8. revisão transversal final.
