# Identidade localizada de activity e hierarquia editorial de páginas — 26/09/2026

Este documento registra a terceira tranche da auditoria sistêmica iniciada em 25/09/2026. Ele complementa `PROJECT_STATE.md`, `SYSTEM_INTEGRATION_AUDIT_2026-09-25.md`, `SYSTEM_WIDE_AUDIT_2026-09-25.md` e `SYSTEM_WIDE_IMPLEMENTATION_2026-09-25.md`.

## Escopo

A tranche trata de duas lacunas estruturais que permaneceram depois da remoção dos efeitos colaterais de leitura e da criação explícita por templates:

1. `activities.public_title` era uma identidade pública única, insuficiente para uma instalação com PT-BR e EN;
2. `cms_pages` não possuía autoridade própria para hierarquia editorial nem para declarar equivalência entre traduções.

Não foi criada nenhuma interface administrativa paralela. Os títulos localizados continuam em `Cursos e workshops`; hierarquia e equivalência continuam em `Páginas`.

## Identidade localizada da activity

Foi introduzida a tabela aditiva `activity_locales`:

- chave: `(activity_id, locale)`;
- valor editorial atual: `public_title`;
- `activities.public_title` permanece preservado como compatibilidade legada e fallback durante a transição.

A migração faz backfill sem alterar o valor legado. Para activities que já possuem páginas, os locales existentes em `cms_pages` recebem inicialmente o mesmo valor que estava em `activities.public_title`. Isso preserva exatamente o comportamento visível anterior sem inventar traduções. Activities sem páginas recebem um registro inicial `pt-BR` apenas para não ficarem sem identidade localizada durante a migração.

A leitura passa a obedecer:

`activity_locales.public_title` do locale solicitado → fallback para `activities.public_title`.

Novas edições feitas pela interface canônica escrevem em `activity_locales`. O campo legado não é mais atualizado por essas edições. A criação de uma activity ainda preenche `activities.public_title` porque a coluna continua `NOT NULL`, mas cria simultaneamente a linha localizada correspondente ao idioma explicitamente escolhido. Portanto o valor legado permanece apenas como sombra de compatibilidade para instalações e consumidores ainda não migrados.

## Hierarquia editorial das páginas

A migração adiciona de forma não destrutiva a `cms_pages`:

- `parent_page_id INTEGER NULL`;
- `translation_group_uuid TEXT NULL`.

Todos os registros existentes permanecem com `parent_page_id = NULL` e `translation_group_uuid = NULL`. A migração não tenta inferir relações a partir de slug, título, ordem ou URL.

`parent_page_id` representa somente a árvore editorial. Não altera:

- `id`;
- `page_uuid`;
- `slug`;
- URL;
- `sort_order`;
- configuração da Navegação;
- documento, revisões ou publicação.

A relação pai/filha é aceita apenas entre páginas da mesma `activity` e do mesmo locale. A página inicial não pode se tornar subpágina. A camada de serviço impede autorreferência e ciclos.

O menu continua sendo uma apresentação curada. Ele não recebe nem mantém uma segunda árvore concorrente.

## Equivalência entre idiomas

`translation_group_uuid` passa a ser a identidade explícita de equivalência editorial. Duas páginas podem ser equivalentes mesmo com slugs diferentes, por exemplo:

- PT-BR: `inscricao`;
- EN: `registration`.

A camada de serviço valida que páginas equivalentes pertencem à mesma `activity` e a locales diferentes. Um índice parcial garante no banco no máximo uma página de cada locale por grupo dentro da mesma activity.

A resolução de equivalente obedece à migração gradual:

1. se existir `translation_group_uuid`, ele é a nova autoridade;
2. quando ainda não existir grupo explícito, permanece temporariamente o fallback legado: home com home e, para páginas não iniciais, mesmo slug.

Dessa forma não há ruptura imediata dos pares antigos enquanto as equivalências são cadastradas editorialmente.

O resolvedor único é usado pela troca de idioma pública e pelo sitemap/hreflang. A API do editor também expõe `parentPageId` e `translationGroupUuid` para que futuras evoluções do editor consumam a mesma autoridade, sem criar outro schema.

## Preservação de dados

A migração é exclusivamente aditiva. Ela não apaga nem reescreve:

- activities existentes;
- páginas;
- IDs e UUIDs;
- slugs e URLs;
- documentos e revisões;
- formulários e submissões;
- contas e matrículas;
- testes e mensagens;
- mídia.

Nenhuma página existente é automaticamente reparentada ou agrupada como tradução. Isso é deliberado: relações estruturais novas só passam a existir quando forem explicitamente definidas ou quando uma migração futura possuir evidência inequívoca.

## Interfaces canônicas

### Cursos e workshops

A tela existente `admin/activities.php` passa a editar separadamente:

- nome administrativo;
- nome público em Português (Brasil);
- nome público em English.

Na criação, o idioma do primeiro nome público é uma escolha explícita. O template continua sendo escolhido explicitamente e a opção `Em branco` continua neutra.

### Páginas

A tela existente `admin/pages.php` passa a permitir, nas ações da própria página:

- escolher a página superior dentro do mesmo curso e idioma;
- escolher a página equivalente em outro idioma.

Nenhum uploader, editor, árvore de navegação ou tela de “páginas protegidas” foi criado.

## Regressão

`tools/test-activity-locales-page-hierarchy.php` cobre:

- backfill do título legado;
- precedência da identidade localizada;
- ausência de novos writes no `activities.public_title` durante edição localizada;
- criação de activity em branco com identidade localizada;
- ausência de páginas automáticas no template em branco;
- persistência de `parent_page_id` sem alteração de slug;
- rejeição de pai em outro locale;
- rejeição de ciclos;
- agrupamento explícito de traduções com slugs diferentes;
- resolução da contraparte pelo grupo, e não pelo slug;
- presença dos controles nas interfaces canônicas;
- uso do resolvedor de tradução no renderer e no discovery.

O teste é executado tanto pelo CI de pull request quanto pela validação do workflow de produção.

## Estado de transição

Esta tranche introduz a nova autoridade, mas não remove o legado no mesmo release.

Próximos passos relacionados:

1. ampliar gradualmente o consumo de `activity_public_title()` em superfícies que ainda leiam diretamente `activities.public_title`;
2. cadastrar/confirmar editorialmente os pares de tradução reais antes de retirar o fallback por slug;
3. depois de observação em produção, transformar `activities.public_title` em compatibilidade read-only explícita em todos os consumidores restantes;
4. somente em release posterior avaliar a retirada do campo legado;
5. continuar a auditoria de formulários, finalidade explícita e lifecycle de matrículas.
