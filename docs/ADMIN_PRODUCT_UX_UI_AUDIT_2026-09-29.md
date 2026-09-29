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
