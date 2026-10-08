# Auditoria de produto — área administrativa

Data: 2026-09-29

## Motivo

A área administrativa já recebeu duas correções estruturais importantes: reorganização da arquitetura de informação para coleções globais e saneamento da arquitetura CSS. Isso resolveu problemas de escalabilidade conceitual e de ownership da cascata, mas **não equivale a um produto visual e operacionalmente polido**.

A interface ainda apresenta sintomas sistêmicos de acabamento insuficiente: hierarquia duplicada, componentes visualmente concorrentes, inconsistência de densidade, páginas que misturam padrões, comportamento mobile frágil, feedback pouco previsível e microcopy que nem sempre acompanha o modelo mental atual.

A partir desta auditoria, correções pontuais por screenshot deixam de ser o método principal. O trabalho passa a seguir contratos de produto e componentes canônicos.

## Evidências já confirmadas

### 1. Cabeçalho e hierarquia duplicados

O shell já possui toolbar com título de página. Diversas telas ainda adicionam `overview-hero` ou cards de abertura que repetem título, contexto e explicação. Em mobile isso cria excesso de camadas verticais e aumenta o risco de conflitos entre header fixo, toolbar sticky e conteúdo.

Exemplo: `admin/index.php` usa a toolbar `Visão geral` e em seguida um `overview-hero` com `Painel`, nome do site/curso, texto explicativo e três ações. `admin/system.php` repete o padrão com `Sistema e atualizações` no shell e `Manutenção / Atualizações sem FTP` no conteúdo.

**Regra nova:** uma página possui um único cabeçalho primário. O shell fornece título e contexto global; a página pode ter descrição e ações, mas não deve criar um segundo hero por padrão.

### 2. Vocabulário visual com componentes redundantes

Hoje coexistem famílias com papéis parcialmente sobrepostos:

- `overview-card`
- `admin-editor-card`
- `admin-panel-plain`
- `admin-card`
- `admin-status-card`
- `admin-stat`

A distinção semântica entre elas não é suficientemente clara e isso produz páginas visualmente diferentes sem necessidade funcional.

**Regra nova:** reduzir o vocabulário para poucos componentes com responsabilidade inequívoca: `panel`, `metric`, `notice`, `collection`, `form-section`, `danger-zone` e, quando necessário, `master-detail`.

### 3. CSS local ainda aparece em página administrativa

`admin/system.php` ainda contém um bloco `<style>` local para `system-health-*`. Isso contradiz o objetivo de reaproveitamento/globalização e demonstra que o gate de arquitetura atual protege o shell, mas não todas as páginas administrativas.

**Regra nova:** nenhuma página administrativa pode conter CSS visual inline. Exceções só podem existir para valores estritamente dinâmicos impossíveis de expressar por classe/tokens, documentadas e testadas.

### 4. Responsividade validada tarde demais

A regressão em telefone mostrou que testes estruturais e browser tests existentes não garantiam a geometria real do shell em viewport móvel. O problema foi detectado visualmente depois do merge.

**Regra nova:** o contrato administrativo precisa de smoke visual/geométrico por breakpoint para o shell e para os principais padrões de página.

Breakpoints de validação mínima:

- 390 × 844 — telefone
- 768 × 1024 — tablet
- 1280 × 800 — desktop compacto
- 1600 × 900 — desktop amplo

### 5. Microcopy e modelo mental ainda carregam heranças antigas

Exemplo: na visão geral existe `Editar nome do curso` apontando para `activities.php`, embora a arquitetura atual tenha separado site/atividade de curso. Mesmo quando a ação funciona, o rótulo reforça uma relação conceitual incorreta.

**Regra nova:** rótulos, descrições e ações devem obedecer às entidades atuais: Site, Curso, Turma, Inscrição, Aluno, Pessoa, Aula, Material, Página e Formulário.

### 6. Padrão de coleção está funcional, mas ainda não está polido

Cursos e Inscrições já usam busca, filtros, paginação e tabelas. Ainda é necessário revisar:

- densidade vertical;
- prioridade e largura de colunas;
- alinhamento numérico;
- affordance da ação principal da linha;
- filtros ativos e remoção de filtros;
- persistência de contexto ao entrar/sair de registros;
- empty/loading/error states;
- comportamento em mobile sem transformar tabela em conteúdo ilegível.

### 7. Sistema e páginas técnicas precisam de hierarquia própria

`Sistema e atualizações`, `Integridade`, `Estrutura do site` e métricas não devem parecer páginas editoriais comuns. São ferramentas operacionais/técnicas e precisam apresentar:

- status atual no topo;
- risco/severidade explícitos;
- ações primárias separadas de ações destrutivas;
- informação técnica secundária progressivamente revelada;
- logs/histórico com leitura compacta;
- cards somente quando representarem estados independentes.

## Objetivo de produto

A administração deve parecer uma única aplicação, não uma coleção de páginas PHP que compartilham um menu.

O usuário deve reconhecer imediatamente:

1. onde está;
2. o que está vendo;
3. qual é a ação principal;
4. quais filtros/contextos estão ativos;
5. o que mudou depois de uma ação;
6. como voltar sem perder o contexto.

## Sistema canônico de composição

### Shell

Responsável por:

- marca;
- navegação global;
- contexto do site atual;
- título da rota;
- menu mobile;
- ação global `Abrir site`;
- logout.

O shell **não** deve ser reconfigurado por página.

### Page header

Dentro do conteúdo, no máximo:

- descrição curta;
- ações primária/secundárias da página;
- escopo/filtro persistente quando aplicável.

Sem hero decorativo por padrão.

### Collection page

Ordem canônica:

1. toolbar de busca/filtros;
2. resumo de resultados/filtros ativos;
3. tabela/lista;
4. paginação.

Métricas só aparecem quando influenciam decisão operacional naquele fluxo.

### Detail page

Ordem canônica:

1. identidade do registro + estado;
2. ações principais;
3. conteúdo editável/operacional;
4. histórico/informação secundária;
5. danger zone separada.

### Form page

- grupos por intenção;
- labels persistentes;
- texto de ajuda apenas quando necessário;
- ações de salvar previsíveis;
- validação inline;
- confirmação explícita para ações irreversíveis.

## Auditoria por superfície

A revisão deve cobrir todas as rotas administrativas navegáveis, agrupadas por trabalho:

### Visão geral

- `/admin/`

Verificar: utilidade real dos cards, priorização, nomenclatura Site × Curso, ações rápidas, densidade e mobile.

### Operação

- `registrations.php`
- `cohorts.php`
- `students.php`
- `people.php`

Verificar: fluxo inscrição → pagamento → turma → aluno, filtros, seleção, edição, confirmação, feedback, estados e retorno ao contexto.

### Ensino

- `courses.php`
- `lessons.php`
- `material.php`

Verificar: catálogo, configuração, relações com página/formulário, liberação por turma, organização de conteúdo e navegação entre coleções filtradas.

### Site

- `pages.php`
- `forms.php`
- `submissions.php`
- `site.php`
- `design.php`
- `seo.php`

Verificar: separação conteúdo/configuração, status draft/publicado, descoberta do editor, ações destrutivas e coerência entre CMS e administração.

### Biblioteca

- `media.php`

Verificar: densidade, seleção, preview, edição, estados de processamento, visibilidade e comportamento mobile.

### Análise

- `analytics.php`

Verificar: legibilidade, hierarquia de métricas, filtros temporais, ausência de cards redundantes e leitura em telas estreitas.

### Sistema

- `system.php`
- `activities.php`
- `data-integrity.php`

Verificar: severidade, atualização, rollback, health checks, informações técnicas, ações críticas e redução de ruído.

## Dimensões da auditoria

Cada tela deve ser avaliada em oito eixos:

1. **Arquitetura de informação** — entidade correta, localização, contexto, redundâncias.
2. **Hierarquia visual** — título, subtítulo, ações, informação principal/secundária.
3. **Interação** — affordance, feedback, confirmação, retorno, persistência de contexto.
4. **Coleções e volume** — busca, filtro, ordenação, paginação, densidade e ações por linha.
5. **Formulários** — agrupamento, labels, validação, ajuda, foco e submissão.
6. **Responsividade** — telefone, tablet, desktop compacto e desktop amplo.
7. **Acessibilidade** — teclado, foco, contraste, labels, estados, áreas de toque.
8. **Consistência sistêmica** — uso de componentes/tokens compartilhados, sem soluções locais.

## Barra de qualidade

Uma tela só está pronta quando:

- não possui CSS inline visual;
- não cria componente local quando existe componente global equivalente;
- não repete o título primário do shell;
- possui uma ação principal inequívoca quando houver ação principal;
- filtros ativos são visíveis e reversíveis;
- estados vazio/erro/sucesso estão tratados;
- nenhuma informação essencial depende apenas de cor;
- nenhuma ação destrutiva está misturada com ação cotidiana;
- em 390 px não há conteúdo esmagado, clipping estrutural ou controles inacessíveis;
- em 1600 px a interface não fica artificialmente espalhada;
- listas suportam crescimento real sem virar grade de cards;
- abrir e voltar preserva o máximo possível de filtro/página/contexto;
- a mesma entidade recebe o mesmo nome em todas as rotas.

## Automação e regressão

Adicionar progressivamente ao CI:

- proibição de `<style>` nas rotas `admin/*.php`;
- fixtures representativas de cada padrão de página;
- testes geométricos por breakpoint;
- screenshots de referência para shell e componentes críticos quando a estabilidade visual permitir;
- testes de navegação que garantam preservação de filtros/contexto;
- testes de estados de sucesso, erro e vazio.

## Ordem de execução

### Fase A — fundação de interface

1. shell/header/sticky/mobile;
2. page header canônico;
3. reduzir famílias de cards/panels;
4. notices/status/feedback;
5. toolbar/filtros/paginação;
6. forms/actions/danger zone.

### Fase B — fluxos operacionais

1. inscrições;
2. turmas;
3. alunos/pessoas;
4. cursos;
5. aulas/material.

### Fase C — CMS e superfícies técnicas

1. páginas/formulários;
2. mídia;
3. métricas;
4. sistema/integridade/estrutura.

### Fase D — polimento final

- microcopy;
- estados vazios;
- foco/teclado;
- responsividade fina;
- consistência tipográfica;
- densidade;
- alinhamentos;
- remoção de elementos sem função.

## Princípio de implementação

Não corrigir a interface acumulando exceções. Quando um defeito aparece em mais de uma tela, a correção pertence ao componente ou ao contrato global responsável por aquela relação.

A meta não é apenas “ficar bonito”. A meta é que a administração seja rápida, previsível e legível com 1 ou 100 cursos, 10 ou 10.000 inscrições, em desktop ou telefone, usando o mesmo sistema de interface.


## Auditoria integral de UI/UX — 08/10/2026

A inspeção motivada pelo uso real demonstrou que o gate anterior não justificava a expressão “auditoria visual completa”. Ele exercitava um conjunto representativo de views, mas não a administração inteira, não todos os breakpoints mínimos definidos neste próprio documento e não os estados interativos capazes de alterar composição.

### Falhas confirmadas

1. **Duplicação assíncrona no inspector do editor.** `cms-access-controls.js` pode iniciar duas injeções antes de `loadOptions()` terminar. A checagem de existência feita apenas antes do `await` não impede uma segunda cópia de Audiência/Acesso.
2. **Responsividade do editor com autoridades concorrentes.** A composição geral muda em 900 px enquanto a composição task-centric mantém o layout de três áreas até 820 px; 801–1100 px fica particularmente vulnerável a canvas comprimido.
3. **Declarações CSS inválidas no editor.** Propriedades fundidas sem ponto e vírgula em `editor-system.css` fazem o navegador descartar partes da regra, tornando o resultado dependente de fallback/cascata posterior.
4. **Hierarquia ruim nas configurações da página.** Ações globais de Design/Header podem aparecer antes das propriedades da página em edição.
5. **Shell mobile incompleto como drawer.** Abrir/fechar menu não inclui backdrop, Escape, foco inicial e devolução de foco.
6. **Faixa intermediária pouco auditada.** O breakpoint de colapso do sidebar ocorre em 800 px; tablet e desktop estreito precisam de validação explícita, não inferência.
7. **IA com nomes herdados.** “Navegação” administra também identidade, cabeçalho e rodapé; “Estrutura do site” é na prática identidade da instalação/manutenção legada.
8. **Rotas legadas completas ainda permanecem alcançáveis.** Compatibilidade deve redirecionar; não pode reabrir uma segunda administração antiga.
9. **Alvos de interação de 34–36 px permanecem em ações frequentes no telefone.**
10. **Cobertura visual insuficiente.** O audit anterior não cobria de forma equivalente Páginas, Design, SEO, builder de Formulários, Respostas master/detail, Mídia/detail dialog, Métricas, Integridade, Pessoas, Material, Aulas/acesso, editor de Processos e Catálogos, além de estados abertos/erro/sucesso/dialog/disclosure.

### Definição canônica de “inspeção completa”

A expressão só pode ser usada quando existir uma matriz declarada de superfícies e estados. O mínimo obrigatório passa a ser:

- viewports **390×844**, **768×1024**, **1280×800** e **1600×900**;
- shell: menu fechado/aberto, foco e Escape;
- coleções: normal, filtros ativos, vazio, paginação e detalhe quando existir;
- formulários: edição, ajuda, validação/erro e ação destrutiva quando existir;
- dialogs/drawers/disclosures: pelo menos um estado aberto de cada padrão usado;
- editor visual: sem seleção, configurações da página, seção, conteúdo contextual, estrutura aberta e inspector longo;
- CMS estrutural: Páginas, Navegação/estrutura do site, Design, SEO, Formulários, Respostas e Mídia;
- Ensino/Operação: Curso, Turma, Inscrições, Alunos, Pessoas, Aulas/acesso, Material, Dúvidas e Testes;
- Laboratório: Processos e Catálogos;
- Análise/Sistema: Métricas, Atualizações, Integridade e identidade/manutenção da instalação.

O gate deve verificar automaticamente, quando aplicável:

- ausência de overflow global;
- ação primária e retornos dentro da viewport;
- controles únicos para uma mesma decisão;
- área de toque mínima administrativa de 40 px no telefone;
- drawers fecháveis por botão, backdrop e Escape, com foco devolvido;
- inspector/drawer com scroll próprio;
- ausência de CSS autoral inválido;
- ausência de rota legada renderizando uma administração paralela;
- nenhuma tabela operacional mobile dependente de scroll horizontal;
- nenhum painel lateral permanente reduzindo o canvas/tarefa a uma faixa residual.

Screenshots continuam obrigatórios, mas são evidência de uma matriz coberta, não substituto dessa matriz.

### Ordem de correção a partir desta auditoria

1. editor visual: idempotência, CSS válido, hierarquia do inspector e responsividade;
2. shell administrativo: drawer, foco, breakpoint intermediário e touch targets;
3. IA e compatibilidade: nomes coerentes e rotas legadas apenas como redirecionadores;
4. CMS operacional: Formulários, Respostas e Mídia;
5. superfícies já estruturalmente boas: densidade, microcopy e refinamento;
6. fechamento somente depois da inspeção humana dos quatro breakpoints e estados declarados.

Uma correção não reduz o escopo do audit. O inventário completo permanece aberto até a matriz inteira estar verde e visualmente revisada.


## Estado após o PR #213 — AUDITORIA AINDA ABERTA

O PR #213 foi integrado em 08/10/2026 pelo merge commit `3617fcc69579c2439869576ee518d6730df9c39f`. A distribuição de produção foi regenerada a partir desse mesmo commit.

O PR corrigiu problemas reais e importantes:

- idempotência da injeção assíncrona de controles de acesso no inspector;
- parte das declarações inválidas de `editor-system.css`;
- hierarquia do inspector de página, deslocando ações globais para um grupo secundário;
- breakpoint do editor e comportamento de drawer em largura intermediária;
- drawer administrativo com backdrop, Escape e devolução de foco;
- nomenclatura de partes da IA administrativa;
- rota `student-area-legacy.php` convertida em compatibilidade por redirecionamento;
- novos gates e fixtures para ampliar a cobertura visual e funcional da administração.

**Essas correções não encerram a auditoria.**

A revisão humana posterior ao merge mostrou que ainda existem incongruências visuais e de usabilidade na interface administrativa. Portanto:

- PR verde, screenshots gerados e testes geométricos aprovados **não equivalem a aprovação visual final**;
- a matriz criada no PR #213 deve ser tratada como infraestrutura de auditoria, não como prova de que todas as superfícies estão no padrão;
- fixtures sintéticas podem confirmar invariantes de layout, mas não substituem inspeção do runtime real e dos estados reais;
- qualquer superfície administrativa que ainda pareça fora do sistema visual ou produza fricção de uso continua dentro do escopo desta auditoria;
- a próxima revisão deve partir do inventário completo da administração e procurar ativamente por incongruências, em vez de validar apenas se as correções anteriores “não quebraram”;
- diferenças de espaçamento, hierarquia, densidade, linguagem, proporção, comportamento responsivo, foco, navegação, affordance e consistência entre telas devem ser tratadas como defeitos de produto quando prejudicarem a usabilidade;
- não declarar a administração “revisada”, “fechada” ou “visualmente aprovada” até uma nova inspeção humana transversal do runtime real.

### Regra específica para a próxima etapa

A próxima etapa deve começar por **inspeção e inventário**, não por novos hotfixes.

1. Ler o estado canônico e esta auditoria.
2. Abrir e percorrer as superfícies administrativas reais em ordem sistemática.
3. Registrar cada incongruência antes de alterar código.
4. Classificar cada problema por causa sistêmica, não apenas por página.
5. Corrigir primeiro os componentes/contratos compartilhados.
6. Reexecutar a matriz de QA e reinspecionar visualmente o runtime real.
7. Só então considerar a auditoria encerrada.
