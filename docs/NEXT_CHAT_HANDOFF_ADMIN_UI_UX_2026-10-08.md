# NEXT CHAT HANDOFF — Auditoria de UI/UX Administrativa

Data: 08/10/2026  
Repositório: `jsaidler/workshop_internacional`  
Branch canônica: `wip/form-response-refinement-2026-07-16`  
Último merge funcional da auditoria administrativa: `3617fcc69579c2439869576ee518d6730df9c39f` (PR #213)  
`production-dist` gerada a partir do mesmo source SHA.

## Estado real

O PR #213 executou uma primeira tranche transversal da auditoria administrativa e foi validado por CI. Entre outras coisas, introduziu/corrigiu:

- auditoria visual automatizada em quatro viewports;
- fixture transversal de superfícies administrativas;
- regressões específicas do editor;
- idempotência da injeção assíncrona de Audiência/Acesso;
- correções de CSS inválido no editor;
- reorganização parcial do inspector;
- responsividade intermediária do editor;
- drawer administrativo com backdrop, Escape e devolução de foco;
- ajustes de IA/nomenclatura;
- redirecionamento da administração legada;
- novos gates estáticos e de navegador.

**O trabalho NÃO está concluído.**

Depois do merge, a revisão humana continuou encontrando incongruências e elementos fora do padrão. O problema não deve ser tratado como uma lista fechada de bugs já conhecidos. A análise visual anterior também se mostrou insuficiente: ela usou fixtures e invariantes úteis, mas ainda não substituiu uma inspeção sistemática da interface administrativa real.

## Regra principal para a próxima conversa

Não comece corrigindo o primeiro defeito que aparecer.

A primeira tarefa é uma **nova inspeção transversal do runtime administrativo real**, orientada por usabilidade, para produzir um inventário confiável das incongruências restantes. Só depois desse inventário devem começar as correções.

Não considerar workflow verde, screenshot automático ou fixture sintética como prova de qualidade visual final.

## Leitura obrigatória antes de qualquer alteração

Ler integralmente, nesta ordem:

1. `docs/PROJECT_STATE.md`
2. `docs/ADMIN_PRODUCT_UX_UI_AUDIT_2026-09-29.md`
3. `docs/ADMIN_ARCHITECTURE_2026-10-07.md`
4. `docs/CMS_EDITOR_CURRENT_STATE_AUDIT_2026-09-26.md`
5. `docs/ADMIN_COURSE_COHORT_WORKSPACE_2026-10-03.md`
6. `docs/CMS_PROFESSIONAL.md`
7. `docs/GLOBAL_DOCUMENTATION_CONTRACT_2026-10-07.md`
8. `docs/NEXT_CHAT_HANDOFF_ADMIN_UI_UX_2026-10-08.md`

Depois, inspecionar o código e os testes introduzidos pelo PR #213, especialmente:

- `.github/workflows/admin-visual-audit.yml`
- `tools/browser-tests/admin-complete-product-audit.spec.cjs`
- `tools/browser-fixture/admin-complete-product-audit.php`
- `tools/browser-tests/editor-admin-ux-audit.spec.cjs`
- `tools/browser-fixture/editor-admin-ux-audit.html`
- `tools/browser-tests/editor-inspector-coherence.spec.cjs`
- `tools/test-admin-complete-ux.php`
- `app/admin_shell.php`
- `assets/admin-system.css`
- `assets/admin-shell-responsive.css`
- `assets/admin.js`
- `editor/editor-system.css`
- `editor/task-centric.js`
- `editor/cms-access-controls.js`
- `editor/cms-inspector-coherence.js`

## Escopo da nova inspeção

A inspeção deve avaliar a administração como produto completo, não apenas como conjunto de páginas que “não têm overflow”.

### Shell e arquitetura global

- sidebar e mobile drawer;
- header e navegação;
- estados ativo, foco, hover e teclado;
- densidade, proporção, espaçamento e ritmo vertical;
- consistência das ações primárias/secundárias;
- vocabulário e arquitetura de informação;
- comportamento em telefone, tablet, desktop compacto e desktop amplo.

### Ensino e operação

- Visão geral;
- Cursos;
- Curso;
- Turmas;
- Turma;
- Inscrições;
- detalhe de inscrição;
- Alunos;
- detalhe de aluno;
- Pessoas;
- detalhe de pessoa;
- Dúvidas;
- detalhe de dúvida;
- Testes;
- detalhe de teste;
- Aulas e acesso;
- Material.

### CMS e site

- Páginas;
- editor visual;
- Cabeçalho e navegação;
- Design;
- SEO;
- Formulários;
- editor de formulário;
- Respostas master/detail;
- Mídia;
- detalhe/dialog de mídia.

### Laboratório, análise e sistema

- Processos globais;
- editor de processo;
- Catálogos;
- Métricas;
- Sistema e atualizações;
- Integridade;
- Identidade da instalação.

## Viewports obrigatórios

A revisão deve observar, no mínimo:

- 390×844
- 768×1024
- 1280×800
- 1600×900

Não inferir o comportamento de um breakpoint a partir de outro.

## O que avaliar em cada superfície

Não limitar a revisão a erros geométricos. Avaliar:

1. **Arquitetura de informação** — a tela está onde o usuário espera? O nome corresponde à tarefa?
2. **Hierarquia visual** — o que chama atenção primeiro é realmente o mais importante?
3. **Usabilidade** — fica claro o que fazer, salvar, voltar, publicar, cancelar ou confirmar?
4. **Densidade** — há excesso de informação, espaço desperdiçado ou controles comprimidos?
5. **Consistência** — ações, cards, tabelas, formulários e status seguem o mesmo sistema?
6. **Responsividade** — a tarefa continua confortável, não apenas tecnicamente cabendo?
7. **Navegação** — contexto, retorno e posição são preservados?
8. **Formulários** — agrupamento, labels, ajuda, validação e ações são previsíveis?
9. **Coleções** — filtros, busca, paginação, seleção e ações por item escalam?
10. **Acessibilidade operacional** — foco, teclado, touch targets, contraste e estados.
11. **Microcopy** — linguagem administrativa é clara e não expõe a modelagem interna.
12. **Estado** — vazio, carregando, erro, sucesso, bloqueado, publicado, rascunho, dialog aberto etc.

## Regra sobre a infraestrutura de audit do PR #213

Os testes novos devem ser preservados, mas sua cobertura precisa ser criticada.

Se a interface real apresentar algo errado que a fixture não apresenta:

1. considerar a interface real como evidência;
2. corrigir a fixture/teste para reproduzir o estado real;
3. só depois corrigir a implementação;
4. manter uma regressão que falharia antes da correção.

Não ajustar o teste para aceitar uma interface ruim.

## Fluxo de trabalho esperado

1. Confirmar o SHA canônico atual.
2. Criar uma branch nova a partir da canônica.
3. Fazer a inspeção e registrar um inventário priorizado **antes de modificar UI**.
4. Separar problemas sistêmicos de problemas realmente locais.
5. Atualizar a documentação canônica com o inventário.
6. Corrigir por tranche sistêmica, começando pelos defeitos de maior impacto de usabilidade.
7. Cada tranche recebe regressão no mesmo nível de escopo.
8. Executar CI e visual audit.
9. Inspecionar manualmente os artifacts/screenshots.
10. Não fazer merge se a inspeção humana ainda encontrar incongruências relevantes.
11. Depois do merge, atualizar o handoff/estado canônico.

## Restrições

- não criar CSS de hotfix cronológico;
- não usar `!important`;
- não criar uma segunda autoridade de UI;
- não corrigir problema sistêmico em uma única página;
- não apagar compatibilidade/dados sem provar que é seguro;
- não redesenhar comportamento de domínio sem documentação;
- não confundir “sem overflow” com “boa usabilidade”;
- não declarar auditoria completa sem inspeção humana transversal;
- não reabrir como implementação principal as rotas legadas aposentadas.

## Primeiro objetivo da próxima conversa

Produzir uma resposta do tipo:

> “Li o estado canônico e percorri a administração. Este é o inventário real das incongruências restantes, agrupado por causa sistêmica e prioridade. Ainda não corrigi nada.”

A partir desse inventário, escolher a primeira tranche de correção.

---

## Prompt pronto para iniciar o próximo chat

```text
Continue o projeto workshop_internacional exatamente do estado canônico atual no GitHub
`jsaidler/workshop_internacional`, branch `wip/form-response-refinement-2026-07-16`.

ANTES DE RESPONDER, PROPOR OU ALTERAR QUALQUER CÓDIGO:

1. confirme o SHA atual da branch canônica;
2. leia integralmente, nesta ordem:
   - docs/PROJECT_STATE.md
   - docs/ADMIN_PRODUCT_UX_UI_AUDIT_2026-09-29.md
   - docs/ADMIN_ARCHITECTURE_2026-10-07.md
   - docs/CMS_EDITOR_CURRENT_STATE_AUDIT_2026-09-26.md
   - docs/ADMIN_COURSE_COHORT_WORKSPACE_2026-10-03.md
   - docs/CMS_PROFESSIONAL.md
   - docs/GLOBAL_DOCUMENTATION_CONTRACT_2026-10-07.md
   - docs/NEXT_CHAT_HANDOFF_ADMIN_UI_UX_2026-10-08.md

O PR #213 foi integrado em `3617fcc69579c2439869576ee518d6730df9c39f`
e implementou a primeira tranche da auditoria administrativa, mas A AUDITORIA NÃO ESTÁ
ENCERRADA. Ainda há incongruências e correções necessárias na interface administrativa.
A análise visual anterior também foi insuficiente: workflows verdes, fixtures e screenshots
automatizados NÃO significam aprovação visual final.

A sua primeira tarefa NÃO é corrigir nada.

Faça uma inspeção transversal da UI/UX da administração real, voltada para USABILIDADE,
e produza primeiro um inventário priorizado das incongruências restantes.

Inspecione o produto inteiro:
- shell/navegação;
- Visão geral;
- Cursos/Curso;
- Turmas/Turma;
- Inscrições/detalhe;
- Alunos/detalhe;
- Pessoas/detalhe;
- Dúvidas/detalhe;
- Testes/detalhe;
- Aulas e acesso;
- Material;
- Páginas;
- editor visual;
- Cabeçalho e navegação;
- Design;
- SEO;
- Formulários/editor;
- Respostas;
- Mídia/dialog;
- Processos/editor;
- Catálogos;
- Métricas;
- Sistema;
- Integridade;
- Identidade da instalação.

Viewports obrigatórios:
- 390×844
- 768×1024
- 1280×800
- 1600×900

Não procure apenas overflow. Avalie arquitetura de informação, hierarquia visual,
densidade, proporções, espaçamento, consistência, affordance, fluxo, foco, teclado,
touch targets, formulários, tabelas/coleções, dialogs/drawers, linguagem, estados,
retorno de navegação e clareza das ações.

IMPORTANTE:
- considere a interface/runtime real como autoridade;
- se uma fixture divergir do real, a fixture está incompleta e deve ser corrigida;
- preserve os gates criados pelo PR #213, mas critique e amplie sua cobertura;
- não transforme a primeira incongruência encontrada em hotfix;
- antes de alterar UI, registre o inventário completo e agrupe os problemas por causa sistêmica;
- não use !important;
- não crie CSS de hotfix/override cronológico;
- não crie uma segunda autoridade visual;
- defeito sistêmico deve ser corrigido na camada compartilhada;
- atualize os documentos vivos a cada mudança de estado;
- não faça merge enquanto a inspeção humana dos artifacts ainda encontrar incongruências importantes.

Quando terminar a inspeção inicial, responda primeiro com o inventário priorizado e explique
quais problemas têm causa compartilhada. Não comece a correção até essa fotografia do
estado atual estar consolidada.
```
