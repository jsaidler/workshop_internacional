# CMS Editor — J4A: autoridade única da árvore de estrutura

Data: 2026-09-27

## Contexto

A auditoria do editor identificou sobreposição de responsabilidades entre a árvore de estrutura da barra lateral e uma segunda árvore renderizada dentro do inspetor por `cms-component-editor.js`.

As duas superfícies descreviam a mesma hierarquia estrutural. Além disso, a árvore interna do inspetor possuía seleção e DnD próprios, enquanto a árvore lateral já possui navegação, busca, seleção e DnD por meio de `cms-structure-sidebar.js`, `cms-structure-navigation.js` e `cms-direct-structure-dnd.js`.

Esse desenho cria duas autoridades ativas para a mesma responsabilidade editorial.

## Decisão do J4A

A árvore de estrutura da barra lateral (`#page-structure-tree`) passa a ser a única superfície ativa de navegação estrutural.

O inspetor continua responsável pelas propriedades e ações do nó selecionado, mas não deve oferecer uma segunda árvore da página.

Este sub-bloco não apaga ainda o código histórico de `treeMarkup()` / `bindTree()` em `cms-component-editor.js`. Primeiro retira a segunda árvore do runtime de forma pequena e reversível. A remoção do dead code fica para um sub-bloco posterior, depois de validação e implantação do J4A.

## Implementação

Foi adicionada a camada `cms-structure-authority.js`, carregada depois de `cms-structure-sidebar.js`.

Ela:

- mantém `#page-structure-tree` como autoridade estrutural visível;
- remove qualquer `.cms-structure-tree` criada dentro de `#inspector`;
- observa mutações do inspetor para impedir que a árvore duplicada reapareça quando a seleção muda;
- marca o inspetor com `data-cms-structure-authority="sidebar"` para tornar a decisão observável e testável.

Nenhum comportamento da árvore lateral é reimplementado.

## Autoridades após J4A

- navegação estrutural: `cms-structure-sidebar.js` + `cms-structure-navigation.js`;
- DnD estrutural: `cms-direct-structure-dnd.js`;
- propriedades do nó selecionado: `cms-component-editor.js`;
- segunda árvore dentro do inspetor: inativa no runtime.

## Próximo sub-bloco

J4B deve remover de `cms-component-editor.js` o dead code da árvore interna (`treeMarkup`, `bindTree` e chamadas correspondentes), sem alterar a árvore lateral nem seu DnD.

Depois disso, a auditoria continua com outras sobreposições de seleção, hover e promoção legada, sempre uma responsabilidade por PR.
