# Auditoria ampla do sistema — 25/09/2026

Esta auditoria complementa `SYSTEM_INTEGRATION_AUDIT_2026-09-25.md`. O objetivo não é propor mais telas. É identificar onde o sistema ainda possui duas autoridades para o mesmo problema, acoplamentos ao primeiro workshop, responsabilidades no domínio errado e riscos operacionais que devem ser corrigidos antes de continuar expandindo a plataforma.

## Critério

Para cada capacidade, a auditoria pergunta:

- qual é o objeto real;
- qual camada deve ser autoridade;
- se existe outra camada escrevendo os mesmos dados;
- se o escopo é global, atividade, página, seção, turma, aula, conta ou registro;
- como corrigir sem alterar IDs, URLs ou conteúdo histórico desnecessariamente.

## P0 — corrigir antes de ampliar o sistema

### Criação de atividade ainda cria o primeiro workshop por baixo

`activity_create()` não cria hoje uma atividade neutra. Depois do INSERT ela chama `workshop_settings_seed()`, `workshop_cms_setup_activity()` e `workshop_refine_live_session_copy_for_activity()`. Quando não há origem explícita, também usa a atividade raiz como fonte quando ela existe.

Isso significa que a abstração administrativa promete “novo site/atividade”, mas a operação interna ainda nasce com DNA do workshop de positivo direto.

**Modelo correto:** criar curso/workshop deve oferecer explicitamente uma origem: vazio, duplicar atividade existente ou template deliberadamente escolhido. Nunca aplicar silenciosamente um seed específico de produto a toda nova atividade.

**Migração:** não alterar atividades existentes. Corrigir apenas novas criações e manter templates legados disponíveis somente como opção explícita.

### A antiga interface `Páginas protegidas` ainda existe e ainda escreve

Apesar da decisão de que acesso e disponibilidade pertencem à própria página/seção do CMS, `admin/student-area.php` ainda inclui `pages` nas views, exibe a aba “Páginas protegidas” e aceita ações como `set_page_access`, `save_section_map`, `bind_page_media` e `unbind_page_media`.

Isso é exatamente a duplicação de autoridade que a arquitetura nova proíbe. Além de confundir o administrador, permite que código legado continue escrevendo em estruturas antigas depois que o editor já é a autoridade de acesso e mídia.

**Correção:** retirar essa view e seus writes da Área do aluno. Manter apenas redirects/compatibilidade de leitura quando necessários à migração. Página, seção e mídia são administradas no CMS e na Biblioteca de mídia; Aulas administra somente aula/liberação.

### Vocabulário `activity` × `enrolled` ainda está inconsistente

A mesma tela ainda conta páginas protegidas usando `access_level='enrolled'`, enquanto o modelo atual de acesso usa `activity`. Esse problema deve ser corrigido em toda leitura antes de novas features de autorização.

**Correção de transição:** leitura aceita `activity` e `enrolled`; novos writes usam somente `activity`; backfill posterior; remoção de fallback apenas depois da observação em produção.

### Renderização pública ainda possui duas autoridades

`index.php` tenta primeiro `cms_pages`. Se não encontra home CMS publicada, cai para `template/public.php`. Esse template mantém formulário, preço, CTA de inscrição e lógica do primeiro workshop.

Enquanto esse fallback estiver ativo, o sistema tem duas maneiras de definir a página pública principal: CMS e stack legado.

**Correção:** CMS deve ser a autoridade única. O renderer legado deve primeiro virar fallback de migração instrumentado; depois, quando nenhuma instalação depender dele, ser removido. URLs existentes devem ser preservadas por páginas CMS ou redirects explícitos.

### Navegação mantém ordem própria concorrente com a futura árvore de páginas

`cms_site_settings.navigation.items` armazena uma lista ordenável independente das páginas. Isso é aceitável para escolher quais páginas aparecem no menu e permitir links externos, mas não pode se tornar uma segunda estrutura hierárquica quando `cms_pages.parent_page_id` existir.

**Correção:** a árvore editorial pertence a `cms_pages`. Navegação guarda apenas exposição, rótulo opcional, abertura em nova aba e eventuais links externos. Se houver submenu, ele deve derivar da árvore de páginas, não persistir outra árvore.

### Idiomas equivalentes são inferidos por slug

`cms_page_counterpart()` procura a página do outro idioma pelo mesmo slug (ou pela home). Isso falha assim que `inscricao` e `registration`, por exemplo, são slugs localizados diferentes.

**Correção:** criar identidade editorial compartilhada entre traduções (`page_group_uuid` ou equivalente). `hreflang`, troca de idioma e comparação estrutural usam essa relação. Não inferir equivalência por slug quando houver ambiguidade.

### Build valida apenas as migrações 001–019

`tools/build-dist.php` inclui todo o diretório `migrations`, mas a verificação estrutural obrigatória percorre apenas `range(1,19)`. O projeto já possui dezenas de migrações posteriores.

Isso deixa uma lacuna: uma migração recente pode ser omitida/acidentalmente removida sem que essa invariável específica detecte o problema.

**Correção:** derivar a sequência esperada da fonte canônica de migrações ou validar todas as migrações presentes/registradas, incluindo continuidade e duplicidade. Não hardcodar o teto histórico 19.

## P1 — alta prioridade arquitetural

### Identidade administrativa continua dizendo “Site”

`admin_shell.php`, `admin/activities.php`, dashboard e editor ainda exibem “Site”, “Sites”, “Site atual”, “Gerenciar sites” e `Direct Positive Workshop` hardcoded. Isso não é apenas copy: mascara a hierarquia global → atividade → página e incentiva decisões erradas de escopo.

**Correção:** depois da identidade multi-curso estar no schema, renomear a IA para “Cursos e workshops”/“Atividade atual” e usar `JSaidler Fotografia` como marca global. Não fazer substituição textual isolada antes de definir a autoridade dos dados.

### `siteName`, `wordmark` e `public_title` continuam concorrendo

A tela `Site e navegação` permite editar ao mesmo tempo nome público do curso, nome do site e wordmark. `cms_site_defaults()` ainda nasce com identidade específica do positivo direto.

**Correção:** marca global em configuração global; nome localizado da atividade em `activity_locales`; página mantém título/nav/SEO. `siteName` e `wordmark` legados ficam read-only/fallback durante a transição e depois saem da autoridade.

### Área autenticada herda visual da primeira matrícula

`student_shell_activity()` percorre matrículas e pega a primeira atividade disponível quando a tela não tem contexto explícito. A mesma função também mantém `Direct Positive Workshop` hardcoded.

**Correção:** dashboard, conta e autenticação usam identidade/design global. Uma tela pertencente a curso/teste/material recebe atividade explicitamente do objeto. Nunca escolher contexto pela ordem de matrícula.

### Matrícula precisa de ciclo de vida próprio

Hoje excluir uma inscrição remove a matrícula que tem `source_submission_id`, o que é correto para a origem histórica. Entretanto matrícula é uma entidade própria: importações e operações administrativas podem existir sem submissão de origem.

**Correção:** a administração de Alunos deve permitir suspender, reativar, mover de turma e remover uma matrícula explicitamente, sem precisar apagar inscrição nem conta. Exclusão de inscrição permanece uma operação histórica separada.

### Automação de inscrição ainda depende de `form_key='registration'`

A exclusão administrativa e partes da reconciliação identificam inscrição pelo nome da chave. O editor de formulário possui “modelo inicial”, mas o schema não possui ainda finalidade operacional explícita.

**Correção:** formulário ganha purpose/automation (`generic`, `interest`, `enrollment` ou equivalente). Backfill de `registration`, fallback temporário por chave, depois single-write na finalidade nova.

### Seeds de formulários continuam sendo aplicados genericamente

`cms_forms_seed()` cria automaticamente `registration` PT e `interest` EN para qualquer atividade que não tenha formulários. Isso repete o mesmo problema encontrado na criação de atividade: uma infraestrutura multi-curso continua presumindo o primeiro produto.

**Correção:** seed automático somente em bootstrap/migração histórica do produto original. Nova atividade começa vazia ou a partir de template explicitamente escolhido.

### Editor acumulou várias gerações sobrepostas

`editor/index.html` carrega simultaneamente `cms-editor.css`, `cms-pro-editor.css`, `cms-ux-v2.css`, `cms-ux-v3.css`, `task-centric.css` e vários estilos especializados. Em JS, coexistem v3, pro, compat, legacy node promotion, consolidation, structure/navigation, layout e outros controladores.

Essa acumulação aumenta risco de ownership duplicado de eventos, cascata CSS imprevisível e correção por “mais uma camada”.

**Correção:** produzir um mapa de ownership do editor (texto, seção, mídia, vídeo, formulário, layout, histórico, estrutura, responsividade), classificar cada módulo como canônico/compatibilidade/legado e consolidar por responsabilidade. Remover apenas depois de regressão equivalente — não fazer limpeza cega.

### CSS administrativo ainda possui exceções locais

`admin/forms.php` mantém um bloco `<style>` próprio para o construtor de condições, enquanto o shell já carrega múltiplos stylesheets administrativos. Isso viola a regra de componente compartilhado e favorece drift visual.

**Correção:** mover estilos de componentes reutilizáveis para a camada administrativa canônica. Estilo inline deve permanecer apenas para invariantes funcionais realmente necessários e documentados.

### Dashboard mistura escopos

O dashboard é filtrado por `activity_id` para páginas, respostas e analytics, mas conta mídia global sem filtro e apresenta essa contagem no mesmo painel de atividade. Como a biblioteca é deliberadamente global, a métrica não está errada; o enquadramento está.

**Correção:** marcar mídia como “Biblioteca global” ou mover essa informação para uma área global. Não inventar `activity_id` em mídia apenas para combinar com o dashboard.

## P2 — melhorar depois da consolidação P0/P1

### SEO precisa conhecer a hierarquia de identidade

`cms_page_seo` guarda overrides por página, o que é correto, mas faltam fallbacks consistentes de marca global + atividade localizada + página. O sitemap e `hreflang` também dependem hoje da equivalência por slug.

**Modelo:** override explícito da página sempre vence; na ausência dele, compor `Página — Curso | JSaidler Fotografia`. Hreflang usa grupo de tradução, não slug.

### Design precisa de herança explícita

Design por atividade/locale é útil, mas não pode definir a aparência de superfícies globais. O modelo recomendado continua sendo `design global + overrides da atividade/locale`, preservando inicialmente os valores atuais como overrides completos.

### Locale da conta

A conta autenticada ainda é essencialmente PT-BR. Para usuário de curso em inglês, adicionar `preferred_locale` à conta é coerente. Fallback: preferência da conta → contexto da atividade → locale global.

### Timezone por atividade

Agendamentos de seção/aula para público internacional precisam de timezone inequívoco. Adicionar timezone da atividade herdando o global. Não reinterpretar timestamps históricos silenciosamente.

### Papéis administrativos

`require_admin()` hoje distingue apenas autenticado/não autenticado. Isso basta para uma instalação operada por uma pessoa, mas não escala para colaboradores futuros.

Não implementar RBAC prematuramente. Apenas não acoplar novas features à suposição de que todo admin precisa de permissão destrutiva. Quando houver necessidade real, os domínios naturais são owner/system, editor, gestor de curso/turma e avaliador.

### Ciclo de vida de atividade

`activities.status`, página/formulário/turma/matrícula têm estados independentes. Deve existir uma leitura operacional clara do curso (preparação, inscrições, andamento, encerrado, arquivado), mas sem transformar isso em cascata destrutiva.

## Coisas que NÃO precisam de outro sistema

### Biblioteca de mídia

A biblioteca já é global e `media_detail_admin()` já calcula “usado em” em documentos legados, páginas CMS e slots protegidos. A melhoria é expor/aperfeiçoar essa informação onde faltar, não criar tabela de uso paralela.

### Entrega de mídia privada

`admin/media-private.php` é endpoint de entrega autenticada, não uma segunda biblioteca. Deve permanecer como mecanismo técnico enquanto a Biblioteca global controla o asset e sua visibilidade.

### URL `/inscricao/`

`inscricao/index.php` apenas resolve a página CMS `inscricao` da atividade raiz. É um adaptador de rota/compatibilidade, não uma segunda página editorial. Pode permanecer enquanto preservar uma URL histórica útil; a autoridade do conteúdo continua sendo `cms_pages`.

### Testes dos alunos

A exclusão do teste já apaga mensagens, registros de mídia e arquivos físicos. Não recriar lifecycle paralelo. O trabalho restante é integração com matrícula/curso e aperfeiçoamento da experiência.

## Ordem de execução recomendada

### Bloco 1 — fechar autoridades concorrentes

1. remover writes e UI de `Páginas protegidas` da Área do aluno;
2. compatibilizar `activity/enrolled` e iniciar backfill;
3. tornar CMS a autoridade pública única e instrumentar o fallback legado;
4. corrigir validação de todas as migrações no build;
5. impedir seeds automáticos do primeiro workshop em novas atividades/formulários.

### Bloco 2 — consolidar modelo multi-curso

1. marca global `JSaidler Fotografia`;
2. identidade localizada da atividade;
3. hierarquia `activity → page → child page`;
4. grupos de tradução de páginas;
5. IA administrativa “Cursos e workshops”, não “Sites”;
6. design global + overrides por curso;
7. contexto autenticado explícito, nunca primeira matrícula.

### Bloco 3 — integrar operação acadêmica

1. finalidade operacional do formulário;
2. ciclo de vida independente da matrícula;
3. aula/liberação como condição consultada pelo CMS;
4. timezone por atividade;
5. preferred locale da conta;
6. métricas globais + recorte por curso.

### Bloco 4 — reduzir dívida técnica

1. mapa de ownership do editor;
2. remover compat layers comprovadamente não usadas;
3. consolidar estilos administrativos;
4. declarar stack legado de conteúdo/renderização read-only;
5. remover estruturas legadas somente após telemetria/testes de não uso.

## Definition of done arquitetural

Uma capacidade é considerada integrada somente quando:

- existe uma fonte de verdade inequívoca;
- a interface que edita o objeto é a interface natural daquele objeto;
- outros módulos apenas referenciam ou consultam essa autoridade;
- não existe segunda tela escrevendo os mesmos dados;
- o escopo global/atividade/página/seção/turma/aula/conta está explícito;
- os dados antigos foram preservados e há caminho determinístico de migração;
- regressões cobrem a autoridade e não apenas a aparência da tela.
