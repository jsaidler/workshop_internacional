# CMS Editor — J4A: autoridade única da árvore de estrutura

Data: 2026-09-27

## Contexto

A auditoria do editor identificou sobreposição de responsabilidades entre a árvore de estrutura da barra lateral e uma segunda árvore renderizada dentro do inspetor por `cms-component-editor.js`.

As duas superfícies descreviam a mesma hierarquia estrutural. Além disso, a árvore interna do inspetor possuía seleção e DnD próprios, enquanto a árvore lateral já possui navegação, busca, seleção e DnD por meio de `cms-structure-sidebar.js`, `cms-structure-navigation.js` e `cms-direct-structure-dnd.js`.

Esse desenho cria duas autoridades ativas para a mesma responsabilidade editorial.

## Decisão do J4A

A árvore de estrutura da barra lateral (`#page-structure-tree`) passa a ser a única superfície ativa de navegação estrutural.

O inspetor continua responsável pelas propriedades e ações do nó selecionado, mas não deve oferecer uma segunda árvore da página.

Este sub-bloco não apaga ainda o código histórico de `treeMarkup()` / `bindTree()` em `cms-component-editor.js`. Primeiro retira a segunda árvore do runtime de forma pequena e reversível. A remoção do dead code fica para um sub-bloco posterior, depois de validação da nova autoridade.

## Implementação implantada

O PR #124 foi integrado no merge `49d218a2d801ff2093867ddaaf5fc87d225778ef`. A validação completa passou, incluindo regressão de navegador, build e dry run, e o deploy de produção `36291555705` concluiu com sucesso todas as etapas de validação, geração, upload e publicação do artefato.

Foi adicionada a camada `cms-structure-authority.js`, carregada depois de `cms-structure-sidebar.js`.

Ela:

- mantém `#page-structure-tree` como autoridade estrutural visível;
- remove qualquer `.cms-structure-tree` criada dentro de `#inspector`;
- observa mutações do inspetor para impedir que a árvore duplicada reapareça quando a seleção muda;
- marca o inspetor com `data-cms-structure-authority="sidebar"` para tornar a decisão observável e testável.

## Correção J4A.1 — ponte de seleção

Após o deploy do J4A, a revisão do fluxo completo encontrou uma dependência que a primeira regressão não cobria: `cms-structure-sidebar.js` ainda selecionava um nó estrutural procurando o botão correspondente na antiga `.cms-structure-tree` do inspetor. Como essa árvore passou corretamente a ser removida, a navegação lateral poderia localizar o item, mas não concluir a seleção do nó no canvas.

A correção J4A.1 elimina essa dependência em vez de restaurar a árvore duplicada. A própria camada de autoridade passa a traduzir o item clicado em `#page-structure-tree` para o nó estrutural correspondente do documento e dispara a seleção diretamente no canvas. Assim:

- a barra lateral continua sendo a única árvore estrutural;
- o inspetor não volta a participar da navegação da árvore;
- `cms-component-editor.js` continua recebendo a seleção estrutural e exibindo as propriedades do nó;
- a regressão de navegador passa a verificar explicitamente que clicar num item da árvore lateral seleciona o nó correspondente mesmo sem `.cms-structure-tree` no inspetor.

A descoberta também corrige o critério de regressão deste bloco: não basta provar que a árvore duplicada desapareceu; é obrigatório provar que a árvore canônica continua executando seleção de ponta a ponta.

## Autoridades após J4A.1

- navegação estrutural: `#page-structure-tree`, renderizada por `cms-structure-sidebar.js`;
- ponte árvore → canvas: `cms-structure-authority.js`;
- busca e breadcrumb: `cms-structure-navigation.js`;
- DnD estrutural: `cms-direct-structure-dnd.js`;
- propriedades do nó selecionado: `cms-component-editor.js`;
- segunda árvore dentro do inspetor: inativa no runtime.

## Próximo sub-bloco

Depois de J4A.1 validado, integrado e implantado, J4B deve remover de `cms-component-editor.js` o dead code da árvore interna (`treeMarkup`, `bindTree` e chamadas correspondentes), sem alterar a árvore lateral nem seu DnD.

Depois disso, a auditoria continua com outras sobreposições de seleção, hover e promoção legada, sempre uma responsabilidade por PR.
