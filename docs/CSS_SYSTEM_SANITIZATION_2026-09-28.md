# Saneamento estrutural de CSS — 28/09/2026

## Motivo

O sistema acumulou folhas de estilo por cronologia de correção (`v2`, `v3`, `refinements`, `experience`, `task-centric`) em vez de por responsabilidade. Em várias superfícies, uma folha posterior passou a corrigir a anterior por ordem de carregamento, especificidade crescente e `!important`.

Esse padrão torna a aparência dependente de acidentes da cascata, dificulta reaproveitamento, faz uma correção local produzir regressão em outra tela e impede saber qual arquivo é autoridade de um componente.

Este trabalho é estrutural e cobre **administração, área do aluno, site público/CMS e editor visual**. Não altera modelagem de dados nem regras de negócio.

## Princípios canônicos

1. **Uma responsabilidade tem um proprietário CSS canônico.**
2. **Arquivos são organizados por superfície ou feature real, nunca por geração histórica de correções.**
3. **`!important` não é mecanismo de arquitetura.** O CSS autoral do projeto deve permanecer sem `!important`.
4. **Correções sistêmicas são feitas no proprietário do componente**, não em uma folha posterior, seletor de página, ID específico ou estilo inline.
5. **Feature CSS não é global.** Uma folha própria só existe quando a feature possui comportamento visual realmente específico e é carregada apenas onde a feature existe.
6. **Primitivas compartilhadas são consumidas, não recriadas.** Espaçamento, controles, botões, estados e campos comuns pertencem à camada compartilhada apropriada.
7. **A ordem da cascata é deliberada e pequena.** Não se adiciona uma nova folha para vencer regras anteriores.
8. **CSS inline visual não corrige defeitos sistêmicos.** Valores editoriais/dinâmicos continuam permitidos somente nos contratos já definidos do CMS e nunca como patch de layout.
9. **O CSS adicional do usuário continua soberano no site público.** CSS de sistema permanece na camada `cms-system`; `#cms-custom-css` permanece fora dela e por último.
10. **Comportamento visual é validado por regressão.** A limpeza estrutural não pode depender apenas de busca textual.
11. **Uma propriedade não pode se autocorrigir no mesmo seletor e contexto de cascata.** Para uma combinação `contexto + seletor + propriedade`, existe uma única declaração proprietária no CSS autoral. Variações reais devem aparecer em outro contexto explícito, como uma media query, ou em outro contrato semântico.
12. **Um seletor não é reaberto sem necessidade de ordem.** Declarações separadas de um mesmo seletor/contexto são consolidadas quando a movimentação é neutra para a cascata; divisões que dependem de source order não são movidas automaticamente e exigem refatoração deliberada do componente.

## Propriedade por superfície

### Administração

- `assets/admin-system.css` — autoridade global da aplicação administrativa: fundações, tokens administrativos, shell, navegação, controles, formulários, tabelas, cards, listas, diálogos e composição compartilhada.
- `assets/admin-media.css` — somente a interface específica da biblioteca/manutenção de mídia; é carregada apenas em `admin/media.php`.
- CSS específico adicional só é aceitável quando representa uma feature isolada e não redefine primitivas de `admin-system.css`.

Foram aposentadas como autoridades independentes as camadas históricas `admin.css`, `cms-admin.css`, `pro-admin.css`, `admin-ux-v2.css`, `admin-ux-v3.css`, `admin-data-ux.css`, `admin-form-ux.css`, `experience-ux.css`, `admin-media-maintenance.css` e `admin-media-task.css`.

O shell administrativo não contém mais patch visual inline para checkbox/radio ou estrutura de wordmark.

### Área do aluno

- `assets/ui-core.css` — primitivas reutilizáveis e escala de espaçamento compartilhada.
- `assets/student-area.css` — autoridade da superfície do aluno, inclusive contexto de curso e ritmo da aplicação.
- `template/page.css` e tokens de Design permanecem responsáveis pelo vocabulário visual público que a área do aluno deliberadamente reutiliza.

`experience-ux.css` deixa de existir como camada corretiva posterior.

### Site público / CMS

- `template/page.css` — fundações do template público.
- `assets/cms-core.css` — núcleo do renderer CMS.
- `assets/cms-pro.css` — componentes públicos avançados que constituem uma feature real.
- `assets/cms-responsive.css` — regras responsivas semânticas do CMS.
- `assets/cms-header.css` — cabeçalho/navegação pública.
- `assets/cms-editorial.css` — componentes editoriais públicos.
- `assets/cms-study.css` — superfície específica de material de estudo.

Essas folhas são importadas dentro de `@layer cms-system`. O CSS adicional do usuário permanece sem layer e é emitido por último.

`cms.css`, `cms-v3.css` e `cms-ui-refinements.css` deixam de ser camadas concorrentes.

### Editor visual

- `editor/editor-system.css` — autoridade do shell/editor: fundações, barras, painéis, canvas, inspector e composição principal.
- Folhas separadas permanecem apenas para features reais do editor, como edição rápida de formulário, componentes, estrutura/navegação e propriedades de layout.

`cms-editor.css`, `cms-pro-editor.css`, `cms-ux-v2.css`, `cms-ux-v3.css` e `task-centric.css` deixam de existir como sequência de substituição histórica.

## `!important`

O levantamento inicial encontrou `!important` em todas as grandes superfícies: template público, CMS, administração, mídia, área do aluno e editor. Havia casos em que a mesma propriedade era declarada com `!important` em uma camada e novamente com `!important` em outra posterior.

A política passa a ser simples: **nenhum CSS autoral versionado pode conter `!important`**. Invariantes devem ser obtidos por escopo correto, seletor correto, DOM correto e propriedade canônica do componente. Regras como `[hidden]`, elementos de acessibilidade e honeypot também devem ser implementadas sem transformar `!important` em ferramenta de precedência.

A regressão `tools/test-css-architecture.php` faz essa regra falhar no CI.

## Ownership de declarações e seletores

Consolidar arquivos não é suficiente se uma folha canônica continuar contendo a história das correções anteriores. Por isso a auditoria opera também no nível da declaração e da distribuição interna dos seletores.

`tools/audit-css-ownership.php --strict` percorre todo o CSS autoral de `assets/`, `editor/`, `template/` e `admin/` e falha quando a mesma propriedade é declarada mais de uma vez para o mesmo seletor dentro do mesmo contexto de cascata. O objetivo não é proibir variações responsivas ou contratos semânticos diferentes: uma regra dentro de `@media`, `@supports`, `@container`, `@layer` ou `@scope` possui contexto próprio.

`tools/consolidate-css-ownership.php --check` verifica se ainda existe uma consolidação determinística pendente. A migração removeu declarações anteriores necessariamente anuladas por uma declaração posterior idêntica em seletor, contexto e propriedade e removeu regras que ficaram realmente vazias. A última declaração foi preservada, mantendo a precedência efetiva da cascata sem recorrer a `!important`.

`tools/consolidate-css-selectors.php --check` detecta declarações de um seletor reaberto que poderiam ser movidas para a ocorrência proprietária posterior sem atravessar nenhuma declaração concorrente da mesma família de propriedade. O algoritmo é propositalmente conservador: `margin` e seus longhands, `border`, `font`, `grid`, `flex`, `background` e outras famílias são tratados como potencialmente concorrentes; qualquer dúvida de precedência bloqueia a movimentação automática. A migração foi executada até atingir ponto fixo.

Assim, um seletor ainda pode aparecer mais de uma vez no relatório sem configurar autocorreção. Depois das passagens determinísticas, seletores repetidos remanescentes têm propriedades distintas e/ou uma dependência possível de source order. Eles são sinal de refatoração manual de componente, não justificativa para uma transformação mecânica capaz de alterar a cascata.

## Espaçamento e reaproveitamento

A escala `4 / 8 / 12 / 16 / 24 / 32 / 48 / 64 px` é compartilhada. Ela não autoriza uma camada global posterior a zerar margens arbitrariamente.

O princípio é:

- componentes controlam seu espaço interno;
- containers controlam a relação entre seus filhos com `gap` quando essa relação pertence ao container;
- páginas apenas compõem componentes;
- uma página não redefine uma primitiva para corrigir espaçamento;
- valores fora da escala só existem quando correspondem a geometria intrínseca deliberada, não como compensação de outra regra.

## Estratégia de migração

A primeira passagem consolidou as autoridades e preservou a ordem efetiva das regras existentes para reduzir risco de regressão. A segunda passagem consolidou a propriedade das declarações: uma propriedade deixa de existir em cadeia de correções dentro do mesmo seletor/contexto e passa a possuir uma única decisão efetiva. A terceira passagem reuniu automaticamente apenas declarações separadas cuja movimentação foi demonstrada como neutra em relação à ordem da cascata, repetindo essa análise até não restar nenhuma movimentação order-safe.

Os testes também fazem parte da arquitetura. Uma regressão não pode continuar lendo uma folha aposentada apenas porque o comportamento que verifica foi preservado. Os testes de formulário, páginas, material didático, página Pinhole e cascata de Design foram realinhados para verificar diretamente as autoridades canônicas. `test-css-architecture.php` falha se qualquer teste voltar a citar uma autoridade CSS aposentada e também verifica que os três gates de ownership continuam presentes no CI.

A limpeza só está concluída quando:

- não existem arquivos globais de correção cronológica;
- não existe `!important` no CSS autoral;
- não existem patches visuais inline nos shells;
- não existe redefinição da mesma propriedade no mesmo seletor/contexto;
- não existe consolidação determinística ou order-safe pendente;
- uma feature não redefine primitivas compartilhadas sem necessidade semântica;
- a suíte funcional e os testes de navegador passam;
- build e dry-run de distribuição passam;
- a documentação de estado registra a nova autoridade.

O `PROJECT_STATE.md` foi reconciliado com essas regras: a antiga exceção para `!important` funcional e a antiga autorização de patch estrutural inline no shell foram removidas, e as autoridades e gates atuais foram registradas no documento canônico de estado.

## Estado final verificado

Na validação de encerramento, a auditoria percorreu 27 folhas CSS autorais e encontrou **0 colisões de propriedade**. As duas consolidações de ownership retornaram **0 trabalho pendente**: nenhuma declaração obsoleta, nenhuma regra vazia e nenhuma movimentação order-safe adicional. Permanecem 137 ocorrências de seletor/contexto repetido, mas sem repetir a mesma propriedade; portanto não constituem a antiga cadeia de autocorreção e não são fundidas automaticamente quando sua posição relativa pode participar da cascata.

A regressão funcional em navegador executou 75 testes e todos passaram. Sintaxe PHP/JavaScript, arquitetura CSS, ownership, smoke tests do CMS, testes de build e dry-run de distribuição também concluíram com sucesso.

## Regra para trabalho futuro

Antes de criar ou alterar CSS: **localizar proprietário → reutilizar a primitiva existente → ampliar o proprietário se a lacuna for sistêmica → criar CSS de feature apenas quando a responsabilidade for realmente específica**.

É proibido resolver uma regressão criando `*-v2.css`, `*-v3.css`, `*-fix.css`, `*-hotfix.css`, `*-refinement.css`, `*-override.css` ou equivalente como nova camada de cascata.
