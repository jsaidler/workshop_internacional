# Auditoria factual da Área do aluno — 27/09/2026

## Baseline confirmado

A revisão parte da branch de produção `wip/form-response-refinement-2026-07-16` no merge `67fe31d5f1898245ca5b8022f69fe593fa5556d0` (PR #125).

O painel `Admin → Sistema e atualizações` foi conferido em 27/09/2026 e mostrou:

- **Instalado:** `67fe31d5f189`
- **Produção disponível:** `67fe31d5f189`
- timestamp exibido em ambos: `2026-09-27T03:40:41+00:00`

Portanto, neste ponto da revisão, a hospedagem e o canal de produção estavam sincronizados no mesmo `sourceSha` abreviado. O editor permanece pausado nesse estado; a revisão seguinte é da Área do aluno.

## Princípio da revisão

Não criar nova autoridade para dados que já pertencem ao CMS, às matrículas, às turmas, às aulas ou aos testes. A correção deve fazer a experiência do aluno refletir corretamente as autoridades existentes.

A revisão é dividida em blocos pequenos, cada um com regressão no mesmo nível da alteração, PR, CI, merge e deploy antes do bloco seguinte.

## Problemas encontrados

### A1 — Verdade do dashboard

Dois defeitos funcionais aparecem na primeira tela após o login:

1. `released_at` futuro era tratado visualmente como aula liberada, embora a autorização canônica considere esse estado `scheduled`;
2. a descoberta de material no dashboard listava somente páginas `activity|enrolled`, omitindo páginas `cohort` destinadas exatamente à turma da matrícula.

Contrato:

- `NULL` → aguardando/bloqueada;
- data futura → agendada;
- data passada ou presente → liberada;
- somente `released` entra na contagem `x/y aulas liberadas`;
- o material de uma matrícula inclui páginas de atividade e páginas `cohort` cujo `access_cohort_id` seja o da própria matrícula;
- páginas de outra turma, públicas ou apenas autenticadas não são apresentadas como material do curso.

### A2 — Contexto curso/turma

A conta já pode possuir várias matrículas, mas a navegação ainda funciona como lista agregada. A próxima revisão deve transformar `Cursos` em ponto de escolha e tornar a matrícula selecionada um contexto de trabalho claro, sem criar uma nova entidade de autorização.

Também deve tornar visível o estado da turma (`active|closed`) e tratar explicitamente situações como curso sem aulas cadastradas.

### A3 — Conta completa

No celular, `Sair` desaparece e `Conta` leva apenas ao perfil. A rota de senha existe, mas não é descoberta pela interface.

Contrato futuro: `Conta` deve agrupar dados pessoais, segurança e encerramento de sessão, preservando uma única identidade global.

### A4 — Continuidade do material

O material deve continuar sendo página CMS normal, com a mesma autoridade editorial e de autorização. O problema é apenas a perda de contexto do workspace ao entrar no material.

A correção futura deve acrescentar contexto de retorno ao curso/Área do aluno sem criar um segundo renderer do material.

### A5 — Entrada e recuperação

Login e primeiro acesso existem e funcionam, mas ainda precisam de melhor hierarquia. Não existe fluxo visível de recuperação de senha; isso exige definição explícita de canal e token antes da implementação.

### A6 — Índice de testes

A tela agrega criação, registros próprios e compartilhados de todos os cursos. Deve ser reorganizada para continuar funcional em uma conta com múltiplas matrículas, sem virar uma interface administrativa.

### A7 — Workflow do teste

A etapa de Revelação possui hoje uma ação para salvar e outra para revisar; o segundo caminho pode abandonar dados ainda não enviados. A revisão deve manter um único avanço principal: salvar e continuar.

Também devem ser revistos estados de conclusão das etapas e o contexto de curso/turma nos testes compartilhados.

## Cobertura de regressão

A auditoria encontrou que a maior parte dos testes da Área do aluno era estática (`file_get_contents`/`str_contains`). Isso não substitui regressão de interação.

A partir do A1, cada bloco que alterar UX deve combinar:

- regressão PHP/SQLite para regra de domínio;
- Playwright/Chromium para a superfície realmente usada;
- ao menos um viewport de desktop e um de celular quando a tela for responsiva.

## Ordem de execução

1. A1 — verdade do dashboard;
2. A2 — contexto curso/turma;
3. A3 — Conta completa;
4. A4 — continuidade do material;
5. A5 — entrada e recuperação;
6. A6 — índice de testes;
7. A7 — workflow do teste;
8. revisão transversal final.

## A1 — branch de implementação

A implementação do primeiro bloco ocorre em `fix/student-dashboard-truth-a1-2026-09-27`, sem alterações no editor de páginas.
